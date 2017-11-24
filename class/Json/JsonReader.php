<?php
/**
 * This file is part of the 'Docalist Biblio' plugin.
 *
 * Copyright (C) 2012-2017 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Json;

/**
 * Lecture de fichiers JSON par morceaux de 64 Ko sans avoir à le charger en entier en mémoire.
 *
 * Cette classe permet de lire des fichiers JSON de n'importe quelle taille. Elle traite le JSON comme une
 * suite de tokens (chaine de caractères, nombre, caractère, valeur...) et ignore les espaces non significatifs
 * qui figurent dans le fichier (espace, tabulation, retour à la ligne...)
 *
 * A chaque étape, les méthodes proposées permettent de savoir quel est le token en cours (is, isString,
 * isObject...) et de récupérer sa valeur (read, readString, readObject...)
 *
 * Toutes les méthodes read() commencent par vérifier que le token en cours correspond au token demandé (elles
 * génèrent une exception si ce n'est pas le cas) puis elles retournent la valeur en cours et passent au token
 * suivant.
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class JsonReader
{
    /**
     * Longueur des chunks.
     *
     * @var int
     */
    const CHUNK_SIZE = 64 * 1024; // 64 Ko cf. https://stackoverflow.com/a/15540773

    /**
     * Longueur maximale d'une chaine de caractères dans le fichier JSON.
     *
     * @var integer
     */
    const STRING_MAX_LEN = 1 * 1024 * 1024; // 1 Mo

    /**
     * Longueur maximale d'un nombre dans le fichier JSON.
     *
     * @var integer
     */
    const NUMBER_MAX_LEN = 100;

    /**
     * Expression régulière utilisée pour détecter les nombres.
     *
     * Adapté de https://stackoverflow.com/a/13340826
     *
     * @var string
     */
    const NUMBER_REGEXP = '~^-?(?:0|[1-9]\d*)(?:\.\d+)?(?:[eE][+-]?\d+)?~';

    /**
     * Expression régulière utilisée pour détecter les chaines de caractères.
     *
     * Adapté de https://regex101.com/r/tA9pM8/4
     *
     * @var string
     */
    const STRING_REGEXP = '~^(?>"(?>\\\\(?>["\\\\\/bfnrt]|u[a-fA-F0-9]{4})|[^"\\\\\0-\x1F\x7F]+)*")~';

    /**
     * Table de décision utilisée par isValue() et readValue() pour déterminer la méthode à appeller en
     * fonction du caracère en cours.
     *
     * @var string[] Un tableau de la forme "caractère en cours dans le buffer" => "méthode à appeller".
     */
    private static $mapMethods = [
        '"' => 'readString',
        '-' => 'readNumber',
        '0' => 'readNumber', '1' => 'readNumber', '2' => 'readNumber', '3' => 'readNumber', '4' => 'readNumber',
        '5' => 'readNumber', '6' => 'readNumber', '7' => 'readNumber', '8' => 'readNumber', '9' => 'readNumber',
        '{' => 'readObject',
        '[' => 'readArray',
        't' => 'readBool', 'f' => 'readBool',
        'n' => 'readNull',
    ];

    /**
     * Path du fichier en cours.
     *
     * @var string
     */
    protected $filename;

    /**
     * Handle du fichier en cours.
     *
     * @var resource
     */
    protected $file;

    /**
     * Chunk en cours.
     *
     * @var string
     */
    protected $buffer;

    /**
     * Nombre de caractères dispo dans le chunk en cours (taille du buffer).
     *
     * @var int
     */
    protected $size;

    /**
     * Position en cours dans le buffer (offset)
     *
     * @var int
     */
    protected $position;

    /**
     * Numéro de la ligne en cours dans le fichier.
     *
     * @var int
     */
    protected $line;

    /**
     * Position sur la ligne en cours.
     *
     * @var int
     */
    protected $col;

    /**
     * Initialise le reader avec le fichier passé en paramètre
     *
     * @param unknown $filename
     */
    public function __construct($filename)
    {
        $this->filename = $filename;
        $this->buffer = '';
        $this->size = 0;
        $this->position = 0;
        $this->line = $this->col = 1;

        $this->file = fopen($filename, 'rb');
        stream_set_chunk_size($this->file, self::CHUNK_SIZE);
        stream_set_read_buffer($this->file, self::CHUNK_SIZE);
        $this->readWhitespaces();
    }

    /**
     * Destructeur. Ferme le fichier en cours.
     */
    public function __destruct()
    {
        $this->file && fclose($this->file);
    }

    /**
     * Charge le chunk suivant dans le buffer.
     *
     * La méthode supprime du buffer tout ce qui a déjà été consumé (i.e. tout ce qui est avant position en cours)
     * puis essaie de lire un bloc de CHUNK_SIZE octets à partir du fichier.
     *
     * Si la lecture échoue ou si le bloc retourné est vide, on considère qu'on a atteint la fin du fichier.
     * Sinon, le buffer et ses propriétés (size, position) sont mises à jour.
     *
     * @return boolean Retourne true si on a réussi à lire au moins un octet, false sinon.
     */
    protected function readChunk()
    {
        // Supprime du buffer tout ce qu'on a déjà consommé
        if ($this->position !== 0) {
            $this->buffer = ($this->size === 0) ? '' : substr($this->buffer, $this->position);
            $this->position = 0;
        }

        // Essaie de lire un bout
        $chunk = fread($this->file, self::CHUNK_SIZE);

        // Si read échoue ou ne retourne rien, on considère que eof a été atteinte
        if ($chunk === false || 0 === $length = strlen($chunk)) {
            return false;
        }

        // Ajoute le chunk au buffer
        $this->buffer .= $chunk;
        $this->size += $length;

        // Ok
        return true;
    }

    /**
     * Ignore les espaces non significatifs qui figurent dans le buffer à la position en cours.
     *
     * Les espaces non significatifs sont les caractères " ", "\t", "\n" et "\r" (RFC4627).
     */
    protected function readWhitespaces()
    {
        for (;;) {
            // Pour tester les espaces, il faut qu'on ait au moins un caractère dans le buffer
            if ($this->size === 0 && !$this->readChunk()) {
                return;
            }

            // Si le buffer ne commence pas par des espaces, terminé
            if (0 === $length = strspn($this->buffer, " \t\n\r", $this->position)) {
                return;
            }

            // Met à jour line/col en fonction du nombre de sauts de lignes / blancs trouvés
            $whitespaces = substr($this->buffer, $this->position, $length);
            preg_replace('~\R~u', "\n", $whitespaces); // normalise les fins de ligne
            $this->line += substr_count($whitespaces, "\n"); // += nb de lignes
            $last = strrpos($whitespaces, "\n");
            $last ? ($this->col = strlen($whitespaces) - $last) : ($this->col += strlen($whitespaces));

            // Supprime les espaces du buffer
            $this->position += $length;
            $this->size -= $length;

            // S'il reste des caractères dans le buffer, ce ne sont pas des espaces, donc terminé
            if ($this->size === 0) {
                return;
            }

            // Plus rien dans le buffer mais peut-être que le prochain chunk contient des espaces, donc on boucle
        }
    }

    /**
     * Teste si le token en cours correspond au token passé en paramètre.
     *
     * @param string $token Le token à tester: '{', '}', '[', ']', ':', ',', '"', 'false', 'true' ou 'null'.
     *
     * @return boolean
     *
     * @SuppressWarnings(PHPMD.ShortMethodName)
     */
    public function is($token)
    {
        // Retourne false s'il reste moins de length caractères
        $length = strlen($token);
        if ($this->size < $length && !$this->readChunk()) { // len(text) doit être < max_chunk
            return false;
        }

        // Teste si le buffer contient le texte demandé
        return 0 === substr_compare($this->buffer, $token, $this->position, $length);
    }

    /**
     * Teste si le token en cours est différent du token pass en paramètre.
     *
     * Cette methode est juste un raccourci pour : !$this->is('token').
     *
     * @param string $token Le token à tester
     *
     * @return boolean
     */
    public function isNot($token)
    {
        return !$this->is($token);
    }

    /**
     * Vérifie que le token en cours correspond au token passé en paramètre et passe au token suivant.
     *
     * @param string $token le token à consommer.
     *
     * @return string Le valeur du token lu (et qui est donc identique au token passé en paramètre).
     *
     * @throws JsonParseError Si le token en cours ne correspond pas au token indiqué.
     */
    public function read($token)
    {
        $length = strlen($token);
        if ($this->size >= $length || $this->readChunk()) { // len(text) doit être < max_chunk
            if (0 === substr_compare($this->buffer, $token, $this->position, $length)) {
                $this->position += $length;
                $this->size -= $length;
                $this->col += $length;
                $this->readWhitespaces();

                return $token;
            }
        }

        // Erreur
        return $this->error('expected "' . $token . '"');
    }

    /**
     * Version optimisée de read() utilisée en interne pour les tokens de un seul caractère.
     *
     * @param string $char
     *
     * @return string
     *
     * @throws JsonParseError Si le carctère en cours ne correspond pas au caractère indiqué.
     */
    protected function readOneChar($char)
    {
        if ($this->size !== 0 || $this->readChunk()) {
            if ($this->buffer[$this->position] === $char) {
                ++$this->position;
                --$this->size;
                ++$this->col;
                $this->readWhitespaces();

                return $char;
            }
        }

        // Erreur
        return $this->error('expected "' . $char . '"');
    }

    /**
     * Teste si le token en cours est 'null'.
     *
     * @return boolean
     */
    public function isNull()
    {
        return $this->is('null');
    }

    /**
     * Vérifie que le token en cours est 'null' et passe au token suivant.
     *
     * @return null
     *
     * @throws JsonParseError Si le token en cours est autre chose que 'null'.
     */
    public function readNull()
    {
        $this->read('null');

        return null;
    }

    /**
     * Teste si le token en cours est 'true' ou 'false'.
     *
     * @return boolean
     */
    public function isBool()
    {
        return $this->is('true') || $this->is('false');
    }

    /**
     * Vérifie que le token en cours est 'true' ou 'false' et passe au token suivant.
     *
     * @return bool Le booléen lu.
     *
     * @throws JsonParseError Si le token en cours n'est ni 'true' ni 'false'.
     */
    public function readBool()
    {
        if ($this->is('true')) {
            $this->read('true');
            return true;
        }

        if ($this->is('false')) {
            $this->read('false');
            return false;
        }

        $this->error('expected "true" or "false"');
    }

    /**
     * Teste si le token en cours est un nombre (entier ou réel).
     *
     * @return boolean
     */
    public function isNumber()
    {
        $this->size < self::NUMBER_MAX_LEN && $this->readChunk();

        $match = null;
        return 0 !== preg_match(self::NUMBER_REGEXP, $this->buffer, $match, 0, $this->position);
    }

    /**
     * Vérifie que le token en cours est un nombre et passe au token suivant.
     *
     * @return int|float Le nombre lu.
     *
     * @throws JsonParseError Si le token en cours n'est pas un nombre.
     */
    public function readNumber()
    {
        $this->size < self::NUMBER_MAX_LEN && $this->readChunk();
        $match = null;
        if (preg_match(self::NUMBER_REGEXP, $this->buffer, $match, 0, $this->position)) {
            $length = strlen($match[0]);
            $this->position += $length;
            $this->size -= $length;
            $this->col += $length;
            $this->readWhitespaces();

            return 0 + $match[0]; // cast: "12" + 0 = int, "1.2" + 0 = float (https://stackoverflow.com/a/16606419)
        }

        $this->error('expected number');
    }

    /**
     * Teste si le token en cours est une chaine de caractères.
     *
     * @return boolean
     */
    public function isString()
    {
        // La chaine doit commencer par un guillemet double
        return $this->size !== 0 && $this->buffer[$this->position] === '"';
    }

    /**
     * Vérifie que le token en cours est une chaine de caractères et passe au token suivant.
     *
     * @return string La chaine lue.
     *
     * @throws JsonParseError Si le token en cours n'est pas une chaine de caractères.
     */
    public function readString()
    {
        // La chaine doit commencer par un guillemet double
        if ($this->size === 0 || $this->buffer[$this->position] !== '"') {
            return $this->error('expected string');
        }

        // On boucle car on peut avoir des chaines plus longues que la taille d'un chunk
        for (;;) {
            // Si le buffer contient une chaine complète, terminé
            $match = null;
            if (preg_match(self::STRING_REGEXP, substr($this->buffer, $this->position), $match)) {
//            if (preg_match(self::STRING_REGEXP, $this->buffer, $match, 0, $this->position)) {
                $length = strlen($match[0]);
                $this->position += $length;
                $this->size -= $length;
                $this->col += $length;
                $this->readWhitespaces();

                return json_decode($match[0]); // TODO traiter false
            }

            // Retourne false si le buffer a atteint la taille maxi d'une chaine
            if ($this->size >= self::STRING_MAX_LEN) {
                $this->error('string exceeds '. self::STRING_MAX_LEN . ' bytes');
            }

            // Lit un chunk supplémentaire dans le buffer et ré-essaye
            if (! $this->readChunk()) {
                break;
            }
        }
    }

    /**
     * Teste si le token en cours est une valeur.
     *
     * En JSON, une valeur est une chaine, un nombre, un objet, un tableau, un booléen ou la valeur null.
     *
     * @return boolean
     */
    public function isValue()
    {
        return $this->size !== 0 && isset(self::$mapMethods[ $this->buffer[$this->position] ]);
    }

    /**
     * Vérifie que le token en cours est une valeur et passe au token suivant.
     *
     * En JSON, une valeur est une chaine, un nombre, un objet, un tableau, un booléen ou la valeur null.
     *
     * @return string|int|float|object|array|bool|null La valeur lue.
     *
     * @throws JsonParseError Si le token en cours n'est pas une valeur.
     */
    public function readValue()
    {
        // Pour tester ce qu'on a, il faut qu'on ait moins un caractère
        if ($this->size === 0) {
            return $this->error('unexpected end of file');
        }

        // Récupère le caractère courant
        $char = $this->buffer[$this->position];

        // Si ce n'est pas un des caractères qui permettent de commencer une valeur, erreur
        if (! isset(self::$mapMethods[$char])) {
            return $this->error('unexpected char "' . $char . '"');
        }
        // Ok
        $method = self::$mapMethods[$char];

        return $this->$method();
    }

    /**
     * Teste si le token en cours est un objet.
     *
     * @return boolean
     */
    public function isObject()
    {
        return $this->size !== 0 && $this->buffer[$this->position] === '{';
    }

    /**
     * Vérifie que le token en cours est un objet et passe au token suivant.
     *
     * @param bool $assoc True pour retourner un tableau associatif plutôt qu'un objet (false par défaut).
     *
     * @return object|array L'objet lu ou un tableau associatif si vous passez true en paramètre.
     *
     * @throws JsonParseError Si le token en cours n'est pas un objet.
     */
    public function readObject($assoc = null)
    {
        // Début de l'objet
        $this->readOneChar('{');

        // Lit toutes les propriétés de l'objet
        $result = [];
        while ($this->size !== 0 && $this->buffer[$this->position] !== '}') {
            $key = $this->readString();
            $this->readOneChar(':');
            $result[$key] = $this->readValue();

            if ($this->size !== 0) {
                if ($this->buffer[$this->position] === ',') {
                    $this->readOneChar(',');
                    if ($this->buffer[$this->position] === '}') {
                        return $this->error('comma not allowed before "}"');
                    }
                    continue;
                }

                if ($this->buffer[$this->position] === '}') {
                    break;
                }
            }

            $this->error('expected "," or "}" (malformed object?)');
        }

        // Fin de l'objet
        $this->readOneChar('}');

        // Ok
        return $assoc ? $result : (object) $result;
    }

    /**
     * Teste si le token en cours est un tableau.
     *
     * @return boolean
     */
    public function isArray()
    {
        return $this->size !== 0 && $this->buffer[$this->position] === '[';
    }

    /**
     * Vérifie que le token en cours est un tableau et passe au token suivant.
     *
     * @return array Le tableau lu.
     *
     * @throws JsonParseError Si le token en cours n'est pas un tableau.
     */
    public function readArray()
    {
        // Début du tableau
        $this->readOneChar('[');

        // Lit tous les éléments du tableau
        $result = [];
        while ($this->size !== 0 && $this->buffer[$this->position] !== ']') {
            $result[] = $this->readValue();

            if ($this->size !== 0) {
                if ($this->buffer[$this->position] === ',') {
                    $this->readOneChar(',');
                    if ($this->buffer[$this->position] === ']') {
                        return $this->error('comma not allowed before "]"');
                    }
                    continue;
                }

                if ($this->buffer[$this->position] === ']') {
                    break;
                }
            }

            $this->error('expected "," or "]" (malformed array?)');
        }

        // Fin de l'objet
        $this->readOneChar(']');

        // Ok
        return $result;
    }

    /**
     * Teste si la fin du fichier est atteinte.
     *
     * @return boolean
     */
    public function isEof()
    {
        // Eof est atteint si on ne peut plus lire un seul caractère
        return $this->size === 0 && !$this->readChunk();
    }

    /**
     * Vérifie que la fin du fichier a été atteinte et génère une exception si ce n'est pas le cas.
     *
     * @return string Une chaine vide.
     *
     * @throws JsonParseError S'il reste des tokens non consommés.
     */
    public function readEof()
    {
        // Si on ne peut plus lire un seul caractère, ok, terminé
        if ($this->size === 0 && !$this->readChunk()) {
            return '';
        }

        // Erreur
        $this->error('expected EOF (garbage at end of file?)');
    }

    /**
     * Génère une exception contenant le message indiqué.
     *
     * @param string $message Message de l'exception.
     *
     * @throws JsonParseError
     */
    protected function error($message)
    {
        $meta = stream_get_meta_data($this->file);
        $regularFile = isset($meta['stream_type']) && $meta['stream_type'] === 'STDIO';
        $wrapperType = isset($meta['wrapper_type']) ? $meta['wrapper_type'] : 'stream';
        $name = $regularFile ? ('file "' . basename($this->filename) . '"') : ($wrapperType . ' data');

        ob_start();
        $this->debug();
        $debug = ob_get_clean();
        $message .= $debug;

        throw new JsonParseException(sprintf(
            'JSON error in %s line %d, column %d: %s.',
            $name,
            $this->line,
            $this->col,
            $message
        ));
    }

    /**
     * Dump l'état en cours du parser
     */
    public function debug()
    {
        $cli = PHP_SAPI ==='cli';
        $buffer = $this->buffer;

        $left = substr($buffer, 0, $this->position);
        $right = substr($buffer, $this->position);

        $max = 20;
        strlen($left) > $max && $left = '...' . substr($left, -$max);
        strlen($right) > $max && $right = substr($right, 0, $max) . '...';

        $left = strtr($left, ["\t" => '\t', "\r" => '\r', "\n" => '\n']);
        $right = strtr($right, ["\t" => '\t', "\r" => '\r', "\n" => '\n']);

        echo $cli ? "\n" : "<pre>";
        $left = sprintf(
            "JsonReader: pos=%d (%d:%d) avail=%d buffer=|%s",
            $this->position,
            $this->line,
            $this->col,
            $this->size,
            $left
        );
        echo $left, $right, "|\n";
        echo str_repeat(' ', strlen($left)), '^';
        echo $cli ? "\n" : "</pre>";
    }
}
