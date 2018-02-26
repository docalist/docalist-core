<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Xml;

use XMLReader as InternalXmlReader;
use DOMNode;
use DOMText;
use LogicException;
use InvalidArgumentException;
use Docalist\Xml\XmlParseException;

/**
 * Lecture de fichiers Xml.
 *
 * Cette classe permet de lire facilement des fichiers XML de n'importe quelle taille. Elle constitue un compromis
 * entre le module simple_xml de PHP qui est simple à utiliser mais qui charge la totalité du fichier en mémoire et
 * la classe XMLReader de PHP qui peut charger des fichiers de n'importe quelle taille mais qui est difficile à
 * utiliser (en interne, ces deux modules sont utilisés pour conserver de bonnes performances).
 *
 * La classe facile à utiliser, notamment pour des fichiers XML dont on connaît à l'avance la structure. Par exemple,
 * le fichier suivant :
 *
 * <code>
 *  <!-- file.xml -->
 *  <file>
 *      <records>
 *          <record id="1"><title>yeah</title></record>
 *          <record id="2">...</record>
 *      </records>
 *  </file>
 * </code>
 *
 * Peut être lu avec le code suivant :
 *
 * <code>
 *  $xml = new XmlReader('file.xml');
 *  $xml->enter('file')->enter('records');
 *  while ($xml->next('record')) {
 *      var_dump($xml->getNode());                      // [ '@attributes' => ['id'=> '1'], 'title' => 'yeah' ]
 *  }
 *  $xml->leave('records')->leave('file');              // Pas indispensable
 * </code>
 *
 * Principes :
 * - La librairie permet de n'avoir à gérer que des tags (i.e. des noeuds XML de type Element) : on va de tag en
 *   tag sans avoir à gérer les détails, en suivant l'arborescence du fichier XML, et on peut ignorer les tags qui
 *   ne nous intéressent pas.
 * - Une fois qu'on est sur un tag qu'on veut récupérer, il suffit d'appeller la méthode get() pour obtenir un
 *   objet SimpleXmlElement qui contient toutes les données du tag (attributs, contenu, noeuds fils, etc.)
 * - Les espaces non significatifs sont automatiquement ignorés (espaces, retour à la lignes, etc.)
 * - Les balises CDATA sont retournées dans le flux du texte standard, il n'y a pas à les gérer.
 * - Les entités sont automatiquement substituées, il n'y a pas à les gérer.
 * - Les commentaires (<!-- xx !> et les instructions de traitements (<? ... ?>) sont ignorés.
 * - Les éléments "auto fermants" (par exemple "<br />") sont toujours retournés comme s'ils étaient écrits avec
 *   un tag de fermeture (i.e. "<br></br>"). Dans le code, ça permet de n'avoir à gérer qu'un seul cas.
 * - Le même reader peut être réutilisé pour plusieurs fichiers (on peut appeller open() et close() plusieurs fois).
 * - Le fichier ouvert est fermé automatiquement par le destucteur (ou si on ouvre un autre fichier).
 *
 * La classe peut également être étendue pour gérer des fichiers plus complexes. Par exemple, une approche possible
 * consiste à créer une classe descendante avec des méthodes pour chaque niveau du fichier XML. Par exemple :
 *
 * <code>
 *  class MyReader extends XmlReader
 *  {
 *      protected $records = [];
 *
 *      public function parse()
 *      {
 *          $this->readFile();
 *
 *          return $this->records;
 *      }
 *
 *      public function readFile()
 *      {
 *          return $this->enter('file')->readRecords();
 *      }
 *
 *      public function readRecords()
 *      {
 *          while ($this->next('record')) {
 *              $this->readRecord();
 *          }
 *
 *          return $this;
 *      }
 *
 *      public function readRecord()
 *      {
 *          $records[] = $this->getNode();
 *
 *          return $this;
 *      }
 *  }
 * </code>
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class XmlReader
{
    /**
     * Un tag de début.
     *
     * @var int
     */
    const OPEN_TAG = InternalXmlReader::ELEMENT;

    /**
     * Un tag de fin.
     *
     * @var int
     */
    const CLOSE_TAG = InternalXmlReader::END_ELEMENT;

    /**
     * Un attribut.
     *
     * @var int
     */
    const ATTRIBUTE = InternalXmlReader::ATTRIBUTE;

    /**
     * Du texte.
     *
     * @var int
     */
    const TEXT = InternalXmlReader::TEXT;

    /**
     * Une instruction de traitement (PI).
     *
     * @var int
     */
    const PROCESSING_INSTRUCTION = InternalXmlReader::PI;

    /**
     * Un commentaire.
     *
     * @var int
     */
    const COMMENT = InternalXmlReader::COMMENT;

    /**
     * Indique que la fin du fichier a été atteinte.
     *
     * @var int
     */
    const EOF = InternalXmlReader::NONE;

    /**
     * L'objet XMLReader PHP utilisé en interne pour lire le fichier XML.
     *
     * @var InternalXmlReader
     */
    protected $xmlReader = null;

    /**
     * Indique si le prochain appelle à next() doit avancer ou non sur le noeud suivant.
     *
     * Quand on appelle enter() ou leave(), le flag passe à false (i.e. "noeud pas encore consommé").
     * Quand on appelle next(), il passe à true (i.e. "noeud déjà vu").
     *
     * @var string
     */
    protected $nextMustRead = true;

    /**
     * Indique si on vient juste d'entrée dans un élément vide.
     *
     * Ce tag est positionné à true quand on fait enter() sur un élement vide ('<a />' par exemple) et permet à
     * à leave() de traiter l'élément comme s'il avait un tag de début et un tag de fin (i.e. '<a></a>').
     *
     * @var bool
     */
    protected $inEmptyTag;

    /**
     * Constructeur nommé : crée un reader pour le fichier dont le path est passé en paramètre.
     *
     * @param string $uri       Path ou URI du fichier à ouvrir.
     * @param string $encoding  Optionnel, outrepasse l'encodage par défaut (celui indiqué dans le fichier ou UTF-8).
     *
     * @return self
     *
     * @throws InvalidArgumentException SI le fichier ne peut pas être ouvert.
     */
    public static function fromFile($uri, $encoding = '')
    {
        // Crée le reader interne
        $internalXmlReader = new InternalXmlReader();

        // Ouvre le fichier indiqué, génère une exception en cas de problème
        if (! @$internalXmlReader->open($uri, empty($encoding) ? null : $encoding, self::getParserOptions())) {
            throw new InvalidArgumentException('Unable to open XML file');
        }

        // Ok
        return new static($internalXmlReader);
    }

    /**
     * Constructeur nommé : crée un reader pour le source xml passé en paramètre.
     *
     * @param string $xml       Source XML à analyser.
     * @param string $encoding  Optionnel, outrepasse l'encodage par défaut (celui indiqué dans le source ou UTF-8).
     *
     * @return self
     *
     * @throws XmlParseException Si la chaine fournie est vide.
     */
    public static function fromString($xml, $encoding = '')
    {
        // Vérifie que $xml est une chaine non vide
        if (! is_string($xml) || empty(trim($xml))) {
            throw new XmlParseException('Invalid or empy XML string');
        }

        // Crée le reader interne
        $internalXmlReader = new InternalXmlReader();

        // Initialise le reader avec le code source fourni
        $internalXmlReader->XML($xml, empty($encoding) ? null : $encoding, self::getParserOptions());

        // Ok
        return new static($internalXmlReader);
    }

    /**
     * Retourne les options XML transmise au XmlReader interne pour ouvrir un fichier ou une chaine XML.
     *
     * @return int
     */
    protected static function getParserOptions()
    {
        return
            LIBXML_BIGLINES     |   // PHP > 7.0.0 - Allows line numbers greater than 65535 to be reported correctly.
            LIBXML_COMPACT      |   // Activate small nodes allocation optimization.
            LIBXML_NOBLANKS     |   // Remove blank nodes
            LIBXML_NOCDATA      |   // Merge CDATA as text nodes
            LIBXML_NOENT        |   // Substitute entities
            LIBXML_NONET        |   // Disable network access when loading documents
            LIBXML_PARSEHUGE    ;   // Relaxes any hardcoded limit from the parser.
    }

    /**
     * Le constructeur est protégé, il faut passer par les constructeurs nommés pour instancier la classe.
     *
     * @param InternalXmlReader $internalXmlReader Un objet XMLReader (de base) déjà ouvert et paramétré.
     *
     * @return self
     */
    protected function __construct(InternalXmlReader $internalXmlReader)
    {
        // Stocke le reader interne
        $this->xmlReader = $internalXmlReader;

        // Va sur le premier noeud
        $this->read();

        // Signale à next() que le noeud en cours n'a pas encore été consommé
        $this->nextMustRead = false;
    }

    /**
     * Avance jusqu'au noeud suivant.
     *
     * @return self
     *
     * @throws XmlParseException Si le fichier XML est mal formé.
     */
    protected function read()
    {
        // Efface les erreurs éventuelles en cours
        libxml_clear_errors();

        // Essaie d'avancer au noeud suivant
        if (@$this->xmlReader->read()) {
            return $this;
        }

        // Read peut retourner false soit parce qu'on a une erreur dans le xml, soit parce qu'on est à la fin
        if (false === $error = libxml_get_last_error()) {
            return $this;
        }

        // Transforme l'erreur en exception
        throw new XmlParseException(sprintf('XML error line %s: %s', $error->line, $error->message));
    }

    /**
     * Teste si le noeud en cours est un tag ayant le nom indiqué.
     *
     * @param string    $name   Nom à tester.
     * @param int       $type   Optionnel, type du noeud souhaité : OPEN_TAG (par défaut) ou CLOSE_TAG.
     *
     * @return boolean
     */
    public function is($name, $type = self::OPEN_TAG)
    {
        return ($this->xmlReader->nodeType === $type) && ($this->xmlReader->name === $name) ;
    }

    /**
     * Teste si la fin du fichier a été atteinte.
     *
     * @return boolean
     */
    public function isEof()
    {
        return ($this->xmlReader->nodeType === self::EOF);
    }

    /**
     * Génère une exception si le noeud en cours n'a pas le nom et le type indiqués.
     *
     * @param string    $name   Nom à tester.
     * @param int       $type   Optionnel, type du noeud souhaité : OPEN_TAG (par défaut) ou CLOSE_TAG.
     *
     * @return self Si le noeud en cours a le type et le nom indiqués.
     *
     * @throws XmlParseException Si le noeud en cours n'a pas le type et le nom indiqués.
     */
    public function mustBe($name, $type = self::OPEN_TAG)
    {
        // Si le type et le nom correspondent, ok
        if ($this->is($name, $type)) {
            return $this;
        }

        // Génère une exception
        switch ($type) {
            case self::OPEN_TAG:
                $message = sprintf('expected start tag <%s>', $name);
                break;

            case self::CLOSE_TAG:
                $message = sprintf('expected end tag </%s>', $name);
                break;

            default:
                $message = sprintf('expected node of type %s with name "%s"', $type, $name);
        }

        $line = $this->getCurrentLineNumber();
        $line = ($line === 0) ? 'at end of file' : sprintf('line %s', $line);

        throw new XmlParseException(sprintf('XML error %s: %s', $line, $message));
    }

    /**
     * Recherche dans le noeud en cours le prochain tag ouvrant ayant le nom indiqué.
     *
     * La méthode parcourt les noeuds enfants du noeud en cours jusqu'à ce qu'elle trouve un tag ouvrant ayant
     * le nom indiqué ou le tag de fermeture du noeud en cours.
     *
     * Si aucun nom de tag n'a été indiqué, elle s'arrête dès qu'elle trouve un tag ouvrant (c'est un moyen simple
     * d'ignorer un noeud (par exemple : $xml->is('head') && $xml->next()).
     *
     * @param string $name Nom du tag recherché.
     *
     * @return boolean Retourne true si on a trouvé un tag ouvrant ayant le nom indiqué, false sinon.
     */
    public function next($name = '')
    {
        $this->nextMustRead && $this->xmlReader->next();

        for (;;) {
            switch ($this->xmlReader->nodeType) {
                case self::EOF:         // Fin de fichier, on ne peut pas aller plus loin, retourne false
                case self::CLOSE_TAG:   // Tag de fermeture, on sort du tag en cours donc on n'a pas trouvé
                    $this->nextMustRead = false;
                    return false;

                case self::OPEN_TAG:    // Tag d'ouverture, retourne true si c'est le tag demandé, continue sinon
                    if (empty($name) || $this->xmlReader->name === $name) {
                        $this->nextMustRead = true;
                        return true;
                    }
            }
            $this->xmlReader->next();
        }
    }

    /**
     * Entre dans un tag.
     *
     * La méthode vérifie que le noeud en cours est un tag d'ouverture avec le nom indiqué puis se positionne
     * sur le premier noeud enfant de l'élément, ou sur le tag de fermeture si le tag est vide.
     *
     * @param string $name Nom du tag.
     *
     * @return self
     *
     * @throws XmlParseException Si le noeud en cours n'est pas un tag d'ouverture ou n'a pas le nom indiqué.
     */
    public function enter($name)
    {
        // Le noeud en cours doit être un tag ouvrant avec le nom demandé
        $this->mustBe($name);

        // Teste si c'est un élément vide ('<a/>') ou non (utilisé par leave)
        $this->inEmptyTag = $this->xmlReader->isEmptyElement;

        // Si c'est un élément vide on reste sur le tag ouvrant, sinon on appelle read() pour aller sur le 1er fils
        ! $this->inEmptyTag && $this->read();

        // On a un nouveau noeud en cours qui n'a pas été consommé
        $this->nextMustRead = false;

        return $this;
    }

    /**
     * Sort du tag en cours.
     *
     * La méthode recherche le tag de fermeture de l'élément en cours, vérifie que le nom du tag correspond au nom
     * passé en paramètre puis se positionne sur le noeud qui suit.
     *
     * @param string $name Nom du tag.
     *
     * @return self
     *
     * @throws LogicException Si les appels à enter et leave() ne sont pas correctement imbriqués : appel à leave()
     * sans avoir appellé enter(), nom de tags qui ne correspondent pas, etc.
     */
    public function leave($name)
    {
        // A la fin on sera sur un nouveau noeud pas encore consommé
        $this->nextMustRead = false;

        // Gére les éléments vides ('<a/>') comme s'ils ne l'étaient pas ('<a></a>')
        if ($this->inEmptyTag) {
            // On est encore positionné sur le tag ouvrant (cf. enter), vérifie que c'est le bon nom
            if ($this->xmlReader->name === $name) {
                $this->inEmptyTag = false;
                return $this->read();
            }
            throw new LogicException(sprintf("Call to leave('%s') in empty tag <%s/>", $name, $this->xmlReader->name));
        }

        // Recherche le prochain tag de fermeture
        for (;;) {
            // Si on en trouve un et qu'il a le bon nom, c'est ok, sinon exception "tag mismatch"
            if ($this->xmlReader->nodeType === self::CLOSE_TAG) {
                if ($this->xmlReader->name === $name) {
                    $this->inEmptyTag = false;
                    return $this->read();
                }
                throw new LogicException(sprintf("Call to leave('%s') in tag <%s>", $name, $this->xmlReader->name));
            }

            // Si on atteint la fin de fichier, génère une exception 'tag fermant non trouvé'
            if ($this->xmlReader->nodeType === self::EOF) {
                $message = sprintf("Call to leave('%s') without enter('%s') before", $name, $name);
                throw new LogicException($message);
            }

            // Avance au noeud suivant en ignorant les noeuds enfants
            $this->xmlReader->next();
        }
    }

    /**
     * Retourne le nom du tag en cours.
     *
     * @return string
     */
    public function getTagName()
    {
        return $this->xmlReader->name;
    }

    /**
     * Retourne le contenu textuel du noeud en cours (i.e. innerText).
     *
     * @param string $name Nom du tag attendu.
     *
     * @return string Une chaine contenant les contenu des noeuds de type texte présents dans le noeud en cours,
     * concaténés ensemble. Par exemple pour le noeud '<p>this<b>is <i>a</i> text</b></p>', la méthode retourne
     * 'this is a text'.
     */
    public function getText()
    {
        return $this->inEmptyTag ? '' : $this->xmlReader->readString(); // la totalité du contenu sans aucun tag
    }

    /**
     * Retourne le source XML du noeud en cours (i.e. outerXml).
     *
     * @return string Une chaine contenant la totalité du source XML du noeud en cours (tag ouvrant, contenu et
     * éléments enfants, tag de fermeture).
     */
    public function getOuterXml()
    {
        return $this->inEmptyTag ? '' : $this->xmlReader->readOuterXML();
    }

    /**
     * Retourne le source XML du contenu du noeud en cours (i.e. innerXml).
     *
     * @return string Une chaine XML contenant le contenu du noeud en cours.
     */
    public function getInnerXml()
    {
        return $this->inEmptyTag ? '' : $this->xmlReader->readInnerXML();
    }

    /**
     * Récupère le noeud en cours.
     *
     * @return DOMNode
     *
     * @throws XmlParseException S'il n'y a pas de noeud en cours (i.e. EOF).
     */
    public function getNode()
    {
        if ($this->inEmptyTag) {
            return new DOMText();
        }

        if ($this->isEof()) {
            throw new LogicException('Call to getNode() at EOF');
        }

        return $this->xmlReader->expand();
    }

    /**
     * Retourne le numéro de la ligne en cours dans le fichier XML.
     *
     * @return int Le numéro de la ligne en cours ou 0 si elle ne peut pas être déterminée.
     *
     * @throws XmlNoFileException   S'il n'y a pas de fichier en cours.
     */
    public function getCurrentLineNumber()
    {
        if ($this->xmlReader->nodeType === self::EOF) {
            return 0;
        }

        $node = $this->xmlReader->expand();

        return $node ? $node->getLineNo() : 'N/A';
    }
}
