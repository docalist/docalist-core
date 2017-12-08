<?php
/**
 * This file is part of the 'Docalist Biblio' plugin.
 *
 * Copyright (C) 2012-2017 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
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
 * SuppressWarnings(PHPMD.TooManyPublicMethods)
 * SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class JsonReader
{
    /*
     * Notes sur l'implémentation.
     *
     * Gestion du buffer :
     * - Le buffer est géré via trois propriétés qui travaillent ensemble :
     * - $buffer contient les données en cours,
     * - $size indique le nombre d'octets encore disponibles dans le buffer (i.e. caractères non consommés),
     * - $position indique la position en cours dans le buffer (les caractères avant sont ceux qu'on a déjà lus).
     * - C'est redondant d'avoir à la fois $position et $size mais ça simplifie le code.
     *
     * Lecture du fichier :
     * - La méthode readChunk() charge le fichier à décoder par morceaux de 64 Ko (chunks), ce qui correspond à la
     *   taille standard d'un bloc IO sur la majorité des systèmes et elle retourne false si elle n'a pas réussit
     *   à lire au moins un caractère (i.e. EOF).
     * - A chaque fois qu'on lit un nouveau bloc, on commence par supprimer du buffer tous les caractères déjà
     *   consommés puis on ajoute au bout du buffer le nouveau bloc chargé.
     * - Pour la majorité des tokens, on a au maximum un seul chunk en mémoire (plus éventuellement quelques octets
     *   non consommés du chunk précédent).
     * - Par contre, les chaines de caractères peuvent faire plus de 64 Ko. Dans ce cas, on va lire autant de chunks
     *   que nécessaire jusqu'à ce qu'on ait une chaine complète. Mais si le buffer atteint une taille limite
     *   (STRING_MAX_LEN, 1 Mo par défaut) et qu'on n'a toujours pas une chaine complète, on génère une exception.
     * - Pour tous les autres types de tokens (nombres, true, false...) on suppose que CHUNK_SIZE est suffisamment
     *   grand pour contenir un token entier (autrement dit, on ne peut pas reconnaître correctement un nombre qui
     *   ferait plus de CHUNK_SIZE caractères).
     *
     * Gestion des tokens :
     * - Pour simplifier la lecture des tokens, on s'assure qu'on est toujours positionné au début d'un token.
     * - Initialement (à l'ouverture du fichier), on charge un premier chunk et on appelle la méthode
     *   skipWhitespaces() pour passer tous les blancs et aller au début du premier token non blanc du fichier.
     * - A chaque fois qu'on lit un token, on passe également tous les blancs qui suivent et on se positionne au
     *   début du token suivant.
     * - Quand on passe les blancs, on s'assure qu'il reste au moins un caractère (non blanc dans le buffer).
     *
     * Du coup, à tout moment, on a donc seulement deux cas possibles :
     * - soit size est à zéro et dans ce cas, c'est qu'on a atteint la fin du fichier,
     * - soit size est non nul et dans ce cas, buffer[position] contient le premier caractère du token suivant.
     */

    /**
     * Longueur des chunks (en octets).
     *
     * @var int
     */
    const CHUNK_SIZE = 64 * 1024; // 64 Ko cf. https://stackoverflow.com/a/15540773

    /**
     * Expression régulière utilisée pour détecter les chaines de caractères.
     *
     * @var string
     */
    const STRING_REGEXP = '~"(?:[^\0-\x1f\\\\"]++|\\\\["bfnrt/\\\\]|\\\\u[a-fA-F0-9]{4})*+"~A';

    /*
     * Explication :
     * ~                            #
     *    "                         # La chaine doit commencer par un guillemet double
     *    (?:                       # On a ensuite zéro, un ou plusieurs caractères qui sont :
     *        [^\0-\x1f\\\\"]++     # - Soit un caractère normal (en premier car le plus courant)
     *        |                     #
     *        \\\\["bfnrt/\\\\]     # - Soit un antislash suivi par un caractère échappement
     *        |                     #
     *        \\\\u[a-fA-F0-9]{4}   # - Soit un antislash suivi par "u" et 4 caractères hexadécimaux
     *    )*+                       #
     *    "                         # La chaine doit se terminer par un guillemet double
     * ~A                           # La regexp est ancrée de force sur l'offset fourni à preg_match()
     *
     * Remarques :
     * - Comme readString() appelle preg_match() en passant un offset on utilise le flag 'A' pour ancrer la regexp.
     *   (on aurait pu aussi utiliser la séquance \G au début de la regexp. cf https://externals.io/message/99442)
     * - PHP nous oblige à doubler tous les antislashs et PCRE aussi donc on a 4 "\" à chaque fois qu'on en veut un.
     * - Toutes les séquences sont possessives ("++" ligne 4 et "*+" ligne 9) pour empêcher le backtracking.
     * - On aurait pû écrire la même chose avec des groupes atomiques de la forme "(?>xxx)" mais c'est plus concis.
     * - Pour le "ou", les caractères normaux sont en premier car c'est le cas le plus courant.
     * - Il n'y a aucun groupe capturé.
     */

    /**
     * Longueur maximale d'une chaine de caractères dans le fichier JSON (en octets).
     *
     * @var integer
     */
    const STRING_MAX_LEN = 1 * 1024 * 1024; // 1 Mo

    /**
     * Expression régulière utilisée pour détecter les nombres.
     *
     * Adapté de https://stackoverflow.com/a/13340826
     *
     * @var string
     */
    const NUMBER_REGEXP = '~-?\d++(?:\.\d++)?+(?:[eE][+-]?\d++)?+~A';

    /*
     * Explication  (cf. diagramme disponible sur http://www.json.org/number.gif) :
     * ~
     *    -?                        # Le nombre peut commencer par un signe "-" optionnel
     *                              #
     *    \d++                      # On a ensuite un entier (simplification, cf. remarque ci-dessous)
     *                              #
     *    (?:                       # On peut ensuite avoir une partie décimale :
     *       \.\d++                 # - Un signe "." suivi de un ou plusieurs chiffres
     *    )?+                       #
     *    (?:                       # Enfin on peut avoir un exposant qui contient :
     *       [eE]                   # - La lettre "e" en minu ou en maju
     *       [+-]?                  # - Le signe "+" ou le signe "-" (optionnels)
     *       \d++                   # - Un entier
     *    )?+
     * ~A                           # La regexp est ancrée de force sur l'offset fourni à preg_match()
     *
     * Remarques :
     * - Normallement, pour la partie entière du nombre, on devrait avoir la regexp suivante : 0 | [1-9]\d*
     *   c'est-à-dire : soit le chiffre zéro, soit un nombre entier sans zéros devant.
     * - Mais ce "OU" est assez coûteux et comme le but de la classe n'est pas de valider le json, mais juste
     *   d'essayer de le lire, on a simplifié en : \d+. Ca veut dire que sur le texte "01" on détecte un entier
     *   unique alors que normallement, on aurait lû deux nombres distincts ("0" puis "1"). Mais comme de toute
     *   façon, ce n'est pas du JSON valide...
     * - Sur les tests faits avec regex101.com, ça fait moitié moins d'étapes (458 au lieu de 828)
     */

    /**
     * Longueur maximale d'un nombre dans le fichier JSON (en octets).
     *
     * @var integer
     */
    const NUMBER_MAX_LEN = 100;

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
     * Nombre de caractères disponibles (non consommés) dans le chunk en cours.
     *
     * @var int
     */
    protected $size;

    /**
     * Position en cours dans le buffer (offset du premier caractère non consommé).
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
     * Numéro de colonne sur la ligne en cours.
     *
     * @var int
     */
    protected $col;

    /**
     * Table de décision utilisée par isValue() et readValue() pour déterminer la méthode à appeller en
     * fonction du caractère en cours.
     *
     * @var string[] Un tableau de la forme "caractère en cours dans le buffer" => "méthode à appeller".
     */
    private static $mapMethods = [
        // Une chaine commence obligatoirement par un guillemet double
        '"' => 'getString',

        // Un nombre peut commencer par le signe "-" ou un chiffre
        '-' => 'getNumber',
        '0' => 'getNumber', '1' => 'getNumber', '2' => 'getNumber', '3' => 'getNumber', '4' => 'getNumber',
        '5' => 'getNumber', '6' => 'getNumber', '7' => 'getNumber', '8' => 'getNumber', '9' => 'getNumber',

        // Un objet commence obligatoirement par une accolade ouvrante
        '{' => 'getObject',

        // Un tableau commence obligatoirement par un crochet ouvrant
        '[' => 'getArray',

        // Les littéraux null, true et false commencent par "n", "t" ou "f"
        'n' => 'getNull', 't' => 'getBool', 'f' => 'getBool',
    ];

    /**
     * Initialise le reader avec le fichier passé en paramètre
     *
     * @param string $filename
     */
    public function __construct($filename)
    {
        $this->filename = $filename;
        $this->buffer = '';
        $this->size = 0;
        $this->position = 0;
        $this->line = $this->col = 1;

        $this->file = fopen($filename, 'rb');
        $this->readChunk();
        $this->skipWhitespaces();
    }

    /**
     * Ferme le fichier en cours quand le Reader est détruit.
     */
    public function __destruct()
    {
        $this->file && fclose($this->file) && $this->file = null;
    }

    /**
     * Charge le chunk suivant dans le buffer.
     *
     * La méthode supprime du buffer tout ce qui a déjà été consumé (i.e. tout ce qui est avant la position en cours)
     * puis essaie de lire un bloc de CHUNK_SIZE octets à partir du fichier.
     *
     * Si la lecture échoue ou si le bloc retourné est vide, on considère qu'on a atteint la fin du fichier.
     *
     * Sinon, on concatène le bloc dans le buffer et on incrémente $size de la taille du bloc.
     *
     * @return boolean Retourne true si on a réussi à lire au moins un octet, false sinon.
     */
    protected function readChunk()
    {
        // Supprime du buffer tout ce qu'on a déjà consommé
        if (0 !== $this->position) {
            $this->buffer = (0 === $this->size) ? '' : substr($this->buffer, $this->position);
            $this->position = 0;
        }

        // Essaie de lire un bout
        $chunk = $this->file ? fread($this->file, self::CHUNK_SIZE) : false;

        // Si read échoue (false) ou ne retourne rien (chaine vide), on considère que EOF a été atteinte
        if (false === $chunk || 0 === $length = strlen($chunk)) {
            $this->file && fclose($this->file) && $this->file = null; // ferme le fichier dès que possible

            return false;
        }

        // Ajoute le chunk au buffer
        $this->buffer .= $chunk;
        $this->size += $length;

        // Indique qu'on a réussi à lire au moins un caractère
        return true;
    }

    /**
     * Ignore les espaces non significatifs et avance la position courant au début du token suivant.
     *
     * En JSON, les espaces non significatifs sont les caractères : " ", "\t", "\n" et "\r" (RFC4627).
     */
    protected function skipWhitespaces()
    {
        // Liste des caractères blancs en JSON.
        $whitespaces = [
            ' '  => 1,
            "\t" => 8, // taille par défaut des tabulations
            "\n" => 0,
            "\r" => 0,
        ];

        do {
            while ($this->size && isset($whitespaces[$char = $this->buffer[$this->position]])) {
                ++$this->position;
                --$this->size;
                ($char === "\n") ? (++$this->line && $this->col = 0) : ($this->col += $whitespaces[$char]);
            }
        } while (!$this->size && $this->readChunk());

        // On ignore tous les espaces qu'on a dans le chunk en cours.
        // A la fin, s'il reste des caractères dans le buffer, ce ne sont pas des espaces, donc on a terminé.
        // Mais si le buffer est vide, il faut lire le chunk suivant car il a peut-être des espaces au début et
        // de toute façon, on doit garantir qu'on a au moins un caractère dans le buffer si on n'a pas atteint EOF.
        // Donc on essaie de lire le chunk suivant et on sort si readChunk() retourn false (EOF).
        // Au final, on boucle tant qu'on a un buffer vide et qu'on peut lire un autre chunk.
    }

    /**
     * Teste si le buffer commence par le texte passé en paramètre.
     *
     * Remarques :
     * - La méthode est prévue pour tester les symboles standards de JSON mais on peut lui passer n'importe quel
     *   texte en paramètre : is('12'), is('fal'), etc.
     * - On ne regarde pas du tout ce qui suit le texte indiqué. Par exemple, la méthode retourne true pour
     *   is('12') si le buffer contient '123' et pour is('true') s'il contient 'truelle'.
     * - La méthode est sensible à la casse : is('A') retourne false si le buffer contient 'a'.
     * - On ne peut pas tester un token plus grand que CHUNK_SIZE.
     *
     * @param string $text Le texte à tester.
     *
     * @return boolean Retourne true si le buffer (à la position en cours) commence par le texte indiqué.
     *
     * @SuppressWarnings(PHPMD.ShortMethodName) Par défaut, PHPMD génère un warning pour les méthodes dont le nom fait
     * moins de trois caractères. Comme on veut vraiment que la méthode s'appelle "is", on masque ce warning.
     */
    public function is($text)
    {
        // Retourne false s'il reste moins de length caractères disponibles (text doit être < CHUNK_SIZE)
        $length = strlen($text);
        if ($this->size < $length) {
            $this->readChunk();
            if ($this->size < $length) {
                return false;
            }
        }

        // Teste si le buffer commence par le texte indiqué
        return 0 === substr_compare($this->buffer, $text, $this->position, $length);
    }

    /**
     * Vérifie que le buffer commence par le texte indiqué et le consomme.
     *
     * @param string $text Le texte à consommer.
     *
     * @throws JsonParseError Si le buffer ne commence pas par le texte indiqué.
     */
    public function get($text)
    {
        // Génère une erreur si le buffer ne commence pas par le texte demandé
        if (!$this->is($text)) {
            throw $this->parseError('expected "' . $text . '"');
        }

        // Consomme le texte
        $length = strlen($text);
        $this->position += $length;
        $this->size -= $length;
        $this->col += $length;

        // Passe les espaces qui suivent
        $this->skipWhitespaces();
    }

    /**
     * Version optimisée de read() utilisée en interne pour consommer un caractère unique.
     *
     * @param string $char Caractère à consommer.
     *
     * @throws JsonParseError Si le caractère en cours ne correspond pas au caractère indiqué.
     */
    protected function getChar($char)
    {
        // Génère une erreur si le buffer est vide ou ne commence pas par le caractère indiqué
        if (0 === $this->size || $this->buffer[$this->position] !== $char) {
            throw $this->parseError('expected "' . $char . '"');
        }

        // Consomme le caractère
        ++$this->position;
        --$this->size;
        ++$this->col; // et si le char c'est \r ou \n ?..

        // Passe les espaces qui suivent
        $this->skipWhitespaces();
    }

    /**
     * Teste si le buffer contient 'null'.
     *
     * @return boolean
     */
    public function isNull()
    {
        return $this->is('null');
    }

    /**
     * Vérifie que le buffer contient 'null' et passe au token suivant.
     *
     * @return null
     *
     * @throws JsonParseError Si le buffer contient autre chose que 'null'.
     */
    public function getNull()
    {
        $this->get('null');

        return null;
    }

    /**
     * Teste si le buffer contient 'true' ou 'false'.
     *
     * @return boolean
     */
    public function isBool()
    {
        return $this->is('true') || $this->is('false');
    }

    /**
     * Vérifie que le buffer contient 'true' ou 'false' et passe au token suivant.
     *
     * @return bool La valeur lue (true ou false).
     *
     * @throws JsonParseError Si le buffer contient autre chose que 'true' ou 'false'.
     *
     * @SuppressWarnings(PHPMD.BooleanGetMethodName) Par défaut, PHPMD considère que les méthodes qui retournent
     * un booléen doivent être nommées isXXX() ou hasXXX(). Dans notre cas, on veut que le nom soit cohérent avec
     * les autres méthodes (getString, getNumber...) donc on masque ce warning.
     */
    public function getBool()
    {
        if ($this->is('true')) {
            $this->get('true');

            return true;
        }

        if ($this->is('false')) {
            $this->get('false');

            return false;
        }

        throw $this->parseError('expected "true" or "false"');
    }

    /**
     * Teste si le buffer contient un nombre.
     *
     * @return boolean
     */
    public function isNumber()
    {
        // Charge le chunk suivant s'il reste moins de NUMBER_MAX_LEN caractères dans le buffer. Cela permet de
        // gérer les nombres qui sont à cheval sur deux chunks (ceux qui commencent à la fin du chunk en cours
        // et qui continuent au début du chunk suivant).
        if ($this->size < self::NUMBER_MAX_LEN) {
            $this->readChunk();
        }

        // Teste si le buffer match la regexp utilisée pour reconnaître les nombres
        $match = null;
        return 0 !== preg_match(self::NUMBER_REGEXP, $this->buffer, $match, 0, $this->position);
    }

    /**
     * Vérifie que le buffer contient un nombre et passe au token suivant.
     *
     * @return int|float Le nombre lu.
     *
     * @throws JsonParseError Si le buffer contient autre chose qu'un nombre.
     */
    public function getNumber()
    {
        // Charge le chunk suivant s'il reste moins de NUMBER_MAX_LEN caractères dans le buffer (cf. isNumber)
        ($this->size < self::NUMBER_MAX_LEN) && $this->readChunk();

        // Génère une erreur si le buffer ne matche pas la regexp utilisée pour reconnaître les nombres
        $match = null;
        if (0 === preg_match(self::NUMBER_REGEXP, $this->buffer, $match, 0, $this->position)) { // \G ou /A requis
            throw $this->parseError('expected number');
        }

        // Consomme le nombre reconnu
        $length = strlen($match[0]);
        $this->position += $length;
        $this->size -= $length;
        $this->col += $length;

        // Passe les espaces qui suivent
        $this->skipWhitespaces();

        // Laisse PHP convertir le texte lu en entier ou en float
        return 0 + $match[0]; // cast: 0+"12"= int, 0+"1.2"= float (cf. https://stackoverflow.com/a/16606419)
    }

    /**
     * Teste si le buffer contient le début d'une chaine de caractères.
     *
     * Remarque : la méthode teste seulement si le buffer contient un guillemet double à la position en cours, elle
     * ne vérifie pas qu'on a une chaine entière correctement formée.
     *
     * @return boolean
     */
    public function isString()
    {
        return $this->size && $this->buffer[$this->position] === '"';
    }

    /**
     * Vérifie que le buffer contient une chaine de caractères et passe au token suivant.
     *
     * @return string La chaine lue.
     *
     * @throws JsonParseError Si le buffer contient autre chose qu'une chaine de caractères.
     */
    public function getString()
    {
        // La chaine doit commencer par un guillemet double
        if (! $this->size || $this->buffer[$this->position] !== '"') { // i.e. isString() inlined
            throw $this->parseError('expected string');
        }

        // Charge des chunks jusqu'à ce qu'on ait une chaine complète ou qu'on atteigne la limite STRING_MAX_LEN
        $match = null;
        for (;;) {
            // Si le buffer contient une chaine complète, terminé
            if (1 === preg_match(self::STRING_REGEXP, $this->buffer, $match, 0, $this->position)) { // \G ou /A requis
                break;
            }

            // Génère une erreur si le buffer a atteint la taille maximale autorisée pour les chaines
            if ($this->size >= self::STRING_MAX_LEN) {
                throw $this->parseError('string exceeds '. self::STRING_MAX_LEN . ' bytes');
            }

            // Lit un chunk supplémentaire dans le buffer et ré-essaye
            if (! $this->readChunk()) {
                throw $this->parseError('invalid string: bad escape sequence or missing closing quote');
            }
        }

        // Consomme la chaine trouvée
        $length = strlen($match[0]);
        $this->position += $length;
        $this->size -= $length;
        $this->col += $length;  // On ne peut pas avoir de saut de lignes dans une chaine, ils sont encodés

        // Passe les espaces qui suivent
        $this->skipWhitespaces();

        // On a une chaine JSON complète (avec guillemets, séquences d'échappement et séquences unicode)
        // On laisse PHP la décoder pour nous
        if (null === $result = json_decode($match[0])) {
            throw $this->parseError('Invalid JSON string: ' . json_last_error_msg());
        }

        // Ok
        return $result;
    }

    /**
     * Teste si le buffer contient le début d'une valeur.
     *
     * En JSON, une valeur est une chaine, un nombre, un objet, un tableau, un booléen ou la valeur null.
     *
     * @return boolean
     */
    public function isValue()
    {
        return 0 !== $this->size && isset(self::$mapMethods[ $this->buffer[$this->position] ]);
        // Remarque : on n'appelle pas readChunk : soit on a au moins un caractère, soit EOF a été atteint
    }

    /**
     * Vérifie que le buffer contient une valeur et passe au token suivant.
     *
     * En JSON, une valeur est une chaine, un nombre, un objet, un tableau, un booléen ou la valeur null.
     *
     * @return string|int|float|object|array|bool|null La valeur lue.
     *
     * @throws JsonParseError Si le buffer contient autre chose qu'une valeur.
     */
    public function getValue()
    {
        // Pour tester ce qu'on a, il faut qu'on ait moins un caractère
        if (!$this->size) {
            throw $this->parseError('unexpected end of file');
        }

        // Récupère le caractère courant
        $char = $this->buffer[$this->position];

        // Si ce n'est pas un des caractères qui permettent de commencer une valeur, erreur
        if (! isset(self::$mapMethods[$char])) {
            throw $this->parseError('unexpected char "' . $char . '"');
        }

        // Ok
        $method = self::$mapMethods[$char];

        return $this->$method();
    }

    /**
     * Teste si le buffer contient le début d'un objet.
     *
     * @return boolean
     */
    public function isObject()
    {
        return $this->size && $this->buffer[$this->position] === '{';
    }

    /**
     * Vérifie que le buffer contient un objet et passe au token suivant.
     *
     * @param bool|null $assoc True pour retourner un tableau associatif plutôt qu'un objet (false par défaut).
     *
     * @return object|array L'objet lu ou un tableau associatif si vous passez true en paramètre.
     *
     * @throws JsonParseError Si le buffer contient autre chose qu'un objet.
     */
    public function getObject($assoc = null)
    {
        // Début de l'objet
        $this->getChar('{');

        // Lit toutes les propriétés de l'objet
        $result = [];
        while (!$this->size || $this->buffer[$this->position] !== '}') {
            $key = $this->getString();
            $this->getChar(':');
            $result[$key] = $this->getValue();
            $this->size && $this->buffer[$this->position] === ',' && $this->getChar(',');
        }

        // remarque : on est moins strict que JSON car notre code autorise une virgule avant l'accolade de
        // fermeture, ce qui est interdit en JSON. Mais le but de cette classe n'est pas de valider le JSON,
        // juste d'arriver à le charger le plus rapidement possible.

        // Fin de l'objet
        $this->getChar('}');

        // Ok
        return $assoc ? $result : (object) $result;
    }

    /**
     * Teste si le buffer contient le début d'un tableau.
     *
     * @return boolean
     */
    public function isArray()
    {
        return $this->size && $this->buffer[$this->position] === '[';
    }

    /**
     * Vérifie que le buffer contient un tableau et passe au token suivant.
     *
     * @return array Le tableau lu.
     *
     * @throws JsonParseError Si le buffer contient autre chose qu'un tableau.
     */
    public function getArray()
    {
        // Début du tableau
        $this->getChar('[');

        // Lit tous les éléments du tableau
        $result = [];
        while (!$this->size || $this->buffer[$this->position] !== ']') {
            $result[] = $this->getValue();
            $this->size && $this->buffer[$this->position] === ',' && $this->getChar(',');
        }

        // remarque : on est moins strict que JSON car notre code autorise une virgule avant l'accolade de
        // fermeture, ce qui est interdit en JSON. Mais le but de cette classe n'est pas de valider le JSON,
        // juste d'arriver à le charger le plus rapidement possible.

        // Fin du tableau
        $this->getChar(']');

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
        // Eof est atteint si on n'a plus rien dans le buffer
        return 0 === $this->size;
    }

    /**
     * Vérifie que la fin du fichier a été atteinte et génère une exception si ce n'est pas le cas.
     *
     * @return string Une chaine vide.
     *
     * @throws JsonParseError S'il reste des caractères non consommés dans le buffer.
     */
    public function getEof()
    {
        // S'il reste des caractères dans le buffer, erreur
        if ($this->size) {
            throw $this->parseError('expected EOF (garbage at end of file?)');
        }

        // Ok
        return '';
    }

    /**
     * Ferme le fichier et retourne une exception contenant le message indiqué.
     *
     * @param string $message Message de l'exception.
     *
     * @return JsonParseException
     */
    protected function parseError($message)
    {
        // Ferme le fichier
        $this->file && fclose($this->file) && $this->file = null;

        // Crée l'exception
        return new JsonParseException($message, $this->line, $this->col);
    }
}
