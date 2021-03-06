<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Docalist\Forms;

use Docalist\Forms\Traits\AttributesTrait;
use Docalist\Type\Any;
use Docalist\Type\Collection;
use Docalist\Schema\Schema;
use InvalidArgumentException;

/**
 * Un élément de formulaire.
 *
 * Caractéristiques :
 * - c'est un item
 * - a des attributs
 * - a un nom
 * - a un libellé et une description
 * - peut être répétable
 * - contient des données
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
abstract class Element extends Item
{
    use AttributesTrait;

    /**
     * Nom de l'élément.
     *
     * @var string
     */
    protected $name = '';

    /**
     * Libellé associé à l'élément.
     *
     * @var string
     */
    protected $label = '';

    /**
     * Description de l'élément.
     *
     * @var string
     */
    protected $description = '';

    /**
     * Indique si le champ est répétable.
     *
     * @var bool|null
     */
    protected $repeatable = null;

    /**
     * Les données de l'élément, initialisées par bind().
     *
     * @var mixed
     */
    protected $data;

    /**
     * Occurence en cours pour un champ répétable.
     *
     * @var int|string
     */
    protected $occurence;

    /**
     * Champ requis / mode d'affichage
     *
     * @var string
     */
    protected $required = '';

    /**
     * Crée un élément de formulaire.
     *
     * @param string            $name       Optionnel, le nom de l'élément.
     * @param array             $attributes Optionnel, les attributs de l'élément.
     * @param Container|null    $parent     Optionnel, le containeur parent de l'item.
     */
    public function __construct(string $name = '', array $attributes = [], Container $parent = null)
    {
        parent::__construct($parent);
        !empty($name) && $this->setName($name);
        !empty($attributes) && $this->addAttributes($attributes);
    }

    /**
     * Par défaut, tous les éléments ont un layout.
     *
     * Certains éléments de la hiérarchie surchargent cette méthode pour indiquer qu'ils ne veulent pas
     * de layout (exemple : Hidden).
     *
     * Un élément qui n'a pas de layout n'a ni bloc label, ni bloc description (il est affiché "tel quel").
     *
     * @return bool
     */
    protected function hasLayout(): bool
    {
         return true;
    }

    /**
     * Par défaut, tous les éléments ont un bloc label.
     *
     * Certains éléments de la hiérarchie surchargent cette méthode pour indiquer qu'ils ne veulent pas
     * de bloc description (exemple : Button).
     *
     * @return bool
     */
    protected function hasLabelBlock(): bool
    {
        return true;
    }

    /**
     * Par défaut, tous les éléments ont un bloc description.
     *
     * Certains éléments de la hiérarchie surchargent cette méthode pour indiquer qu'ils ne veulent pas
     * de bloc description (exemple : Checkbox).
     *
     * @return bool
     */
    protected function hasDescriptionBlock(): bool
    {
        return true;
    }

    /**
     * Modifie le nom du champ.
     *
     * @param string $name
     *
     * @return self
     */
    final public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Retourne le nom du champ.
     *
     * @return string
     */
    final public function getName(): string
    {
        return $this->name;
    }

    /**
     * Retourne le nom du contrôle html de l'élément.
     *
     * La nom du contrôle est construit à partir du nom du nom de l'élément, de son numéro d'occurence
     * (s'il est répétable) et du nom de son container parent éventuel.
     *
     * Par exemple, si on a un élément "tel" répétable dans un container "contact" également répétable, la
     * méthode retournera une chaine de la forme : "contact[i][tel][j]".
     *
     * Si l'élément n'a pas de nom, une chaine vide est retournée.
     *
     * @return string
     */
    protected function getControlName(): string
    {
        // Si l'élément n'a pas de nom, retourne une chaine vide
        if (empty($this->name)) {
            return '';
        }

        // Récupère le nom de notre container parent
        $base = $this->parent ? $this->parent->getControlName() : '';

        // Construit le nom de l'élément
        $name = $base ? ($base . '[' . $this->name . ']') : $this->name;

        // Ajoute le numéro d'occurence
        $this->isRepeatable() && $name .= '[' . $this->occurence . ']';

        // Ok
        return $name;
    }

    /**
     * {@inheritDoc}
     */
    final public function getPath(string $separator = '/'): string
    {
        $path = $this->parent ? $this->parent->getPath($separator) : '';
        if (!empty($this->name)) {
            !empty($path) && $path .= $separator;
            $path .= $this->name;
        }

        return $path;
    }

    /**
     * Modifie le libellé du champ.
     *
     * @param string $label
     *
     * @return self
     */
    final public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Retourne le libellé du champ.
     *
     * @return string
     */
    final public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * Modifie la description du champ.
     *
     * @param string $description
     *
     * @return self
     */
    final public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Retourne la description du champ.
     *
     * @return string
     */
    final public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Retourne la liste des modes d'affichage disponibles pour un champ requis.
     *
     * @return string[] Un tableau de la forme code => libellé.
     */
    final public function requiredModes(): array
    {
        return [
            'mark-before'       => __('Insérer un astérique rouge avant le libellé', 'docalist-core'),
            'mark-after'        => __('Ajouter un astérique rouge après le libellé', 'docalist-core'),
            'heavy-mark-before' => __('Insérer un gros astérique rouge avant le libellé', 'docalist-core'),
            'heavy-mark-after'  => __('Ajouter un gros astérisque rouge après le libellé', 'docalist-core'),
            'color-label'       => __('Afficher le libellé en rouge', 'docalist-core'),
            'color-container'   => __('Ajouter un fond rouge au formulaire', 'docalist-core'),
        ];
    }

    /**
     * Définit si le champ est requis ou non
     *
     * @param string $mode Un code (cf. requiredModes()) indiquant le mode d'affichage si le champ est requis
     * ou une chaine vide si le champ est optionnel.
     */
    final public function setRequired(string $mode = 'mark-after'): void
    {
        $modes = $this->requiredModes();
        if ($mode !== '' && !isset($modes[$mode])) {
            $this->invalidArgument('Invalid required mode');
        }
        $this->required = $mode;
    }

    /**
     * Indique si le champ est requis ou non.
     *
     * @return string Un code (cf. requiredModes()) indiquant le mode d'affichage si le champ est requis
     * ou une chaine vide si le champ est optionnel.
     */
    final public function getRequired(): string
    {
        return $this->required;
    }

    /**
     * Modifie le flag repeatable du champ.
     *
     * @param bool|null $repeatable
     *
     * @return self
     */
    public function setRepeatable(?bool $repeatable = true)
    {
        // pas "final", surchargée dans EntryPicker
        $this->repeatable = $repeatable;

        return $this;
    }

    /**
     * Retourne le flag repeatable du champ.
     *
     * @return bool|null Retourne :
     *
     * - null si le flag n'a pas été explicitement définit (état initial).
     * - true si le flag a été activé.
     * - false si le flag a été désactivé.
     *
     * Remarque : en général, il est préférable d'utiliser la méthode {@link isRepeatable()} qui retourne toujours
     * soit true soit false.
     */
    final public function getRepeatable(): ?bool
    {
        return $this->repeatable;
    }

    /**
     * Indique si le champ est répétable.
     *
     * @return bool
     */
    final public function isRepeatable(): bool
    {
        return $this->repeatable === true;
    }

    /**
     * Retourne le "niveau de répétition" du noeud en cours.
     *
     * Exemples :
     * - pour un champ non répétable, retourne 0
     * - si le champ est répétable, retourne 1
     * - si le champ est répétable et que son parent est répétable, retoune 2
     * - et ainsi de suite
     *
     * @return int
     */
    final protected function getRepeatLevel(): int
    {
        $level = $this->parent ? $this->parent->getRepeatLevel() : 0;
        $this->isRepeatable() && ++$level;

        return $level;
    }

    /**
     * Indique si la valeur d'une instance unique de ce type de champ est un scalaire ou un tableau.
     *
     * Autrement dit, indique si le champ est multivalué ou non.
     *
     * La majorité des champs sont des champs simples dont la valeur est un scalaire (input text, textarea, etc.)
     *
     * Lorsqu'un champ simple est répétable, il devient multivalué et sa valeur est alors un tableau.
     *
     * Certains champs sont multivalués même lorsqu'ils ne sont pas répétables. C'est le cas par exemple pour
     * une checklist ou un select avec l'attribut multiple à true. Dans ce cas, le champ est obligatoirement
     * multivalué (et s'il est répétable, alors sa valeur sera un tableau de tableaux).
     *
     * Par défaut, isMultivalued() se contente d'appeller isRepeatable(). Les classes Select et Checklist
     * surchargent la méthode pour tenir compte de l'attribut multiple.
     *
     * Remarque : un container est toujours considéré comme multivalué : il contient les valeurs de tous les
     * éléments qu'il contient.
     *
     * @return bool
     */
    protected function isMultivalued(): bool
    {
        return $this->isRepeatable();
    }

    /**
     * Initialise les propriétés repeatable, label et description à partir du schéma passé en paramétre.
     *
     * Chaque propriété n'est initialisée que si elle est à null.
     *
     * @param Schema $schema
     */
    protected function bindSchema(Schema $schema): void
    {
        // Initialise la propriété "repeatable"
        if (is_null($this->repeatable)) {
            if (FALSE && $schema->repeatable()) {
                if ($this->isMultivalued()) {
                    $this->setRepeatable(is_a($schema->type(), Collection::class, true));
                } else {
                    $this->setRepeatable(true);
                }
            } else {
                $this->setRepeatable(false);
            }
        }

        // Initialise le libellé
        empty($this->label) && $this->setLabel($schema->label() ?? '');

        // Initialise la description
        empty($this->description) && $this->setDescription($schema->description() ?? '');
    }

    /**
     * Initialise l'élément à partir des données passées en paramètre.
     *
     * @param mixed $data
     */
    protected function bindData($data): void
    {
        // Cas d'un champ monovalué
        if (! $this->isMultivalued()) { // ni repeatable, ni multiple
            // Data doit être un scalaire
            if (!is_scalar($data) && ! is_null($data)) {
                $this->invalidArgument(
                    'Element %s is monovalued, expected scalar or null, got %s',
                    gettype($data)
                );
            }
        }

        // Cas d'un champ multivalué
        else {
            // On accepte un tableau ou null
            if (! is_array($data) && ! is_null($data)) {
                $data = (array) $data; // 'article' => ['article'], null => []
//                 return $this->invalidArgument(
//                     'Element %s is multivalued, expected array or null, got %s',
//                     gettype($data)
//                 );
            }

            // Si c'est un tableau vide, on stocke null plutôt que array()
            $data === [] && $data = null;
        }

        // Stocke les données
        $this->data = $data;
    }

    /**
     * Initialise les données de l'élément à partir des données passées en paramétre.
     *
     * @param mixed $data
     */
    final public function bind($data): void
    {
        // Si c'est un type docalist, initialise le schéma et récupère les données
        if ($data instanceof Any) {
            ($schema = $data->getSchema()) && $this->bindSchema($schema);
            $data = $data->getPhpValue();
        }

        // Initialise les données
        $this->bindData($data);

        // Initialise l'occurence en cours
        $this->isRepeatable() && $this->occurence = is_null($this->data) ? null : key($this->data);
    }

    /**
     * Retourne les données de l'élément.
     *
     * @return mixed La méthode retourne les données qui ont été stockées lors du dernier appel
     * à la méthode bind(). Si bind() n'a jamais été appellée, elle retourne null.
     */
    final public function getData()
    {
        return $this->data;
    }

    /**
     * Modifie le numéro d'occurence de l'élément.
     *
     * Cette méthode n'est utilisable que :
     *
     * - pour un champ répétable
     * - après que bind() a été appellé.
     *
     * @param int|string $occurence Une des clés des données du champ.
     */
    protected function setOccurence($occurence): void
    {
        // Si le champ n'est pas répétable, c'est une erreur d'appeller setOccurence
        // On accepte néanmoins setOccurence(0) pour un champ non répétable car cela
        // permet au vue de simplifier leur code en gérant de la même manière les deux
        // cas en faisant "foreach ( (array) $data )" (cf. base/input.php par exemple).
        if (! $this->isRepeatable()) {
            if ($occurence === 0) {
                return;
            }

            $this->invalidArgument('Element "%s" is not repeatable, occurence cannot be set.');
        }

        // Vérifie que la clé indiquée existe dans data
        $valid = empty($this->data) ? empty($occurence) : array_key_exists($occurence, $this->data);
        if (! $valid) {
            $this->invalidArgument('Element "%s" do not have data for occurence "%s".', $occurence);
        }

        // Ok
        $this->occurence = $occurence;
    }

    /**
     * Retourne le numéro d'occurence du champ.
     *
     * @return int|string
     */
    public function getOccurence()
    {
        return $this->occurence;
    }

    protected function getOccurences()
    {
        if ($this->isRepeatable()) {
            if (is_null($this->data)) {
                return [null];
            }

            return (array) $this->data;
        }

        return [$this->data];
    }

    /**
     * Génère un nouvel ID pour l'élément.
     *
     * @return string
     */
    final protected function generateID(): string
    {
        // Génère un ID à partir du nom ou du type de l'élément
        $id = $this->getControlName() ?: $this->getType();

        // Supprime les caractères spéciaux de jQuery
        // cf. https://learn.jquery.com/using-jquery-core/faq/how-do-i-select-an-element-by-an-id-that-has-characters-used-in-css-notation/
        $id = strtr($id, [
            ':' => '-',
            '.' => '-',
            ',' => '-',
            '=' => '-',
            '@' => '-',
            '[' => '-',
            ']' => '',
        ]);

        // Supprime les tirets superflus
        $id = rtrim($id, '-');
        $id = strtr($id, ['--' => '-']);

        // Stocke l'ID généré
        $this->attributes['id'] = $id;

        // Retourne l'ID
        return $id;
    }

    /**
     * Génère une exception InvalidArgumentException.
     *
     * @param string $message message style sprintf (%1 = nom/label/type de l'élément).
     * @param mixed ... paramètres à passer à sprintf.
     *
     * @throws InvalidArgumentException
     */
    protected function invalidArgument($message): void
    {
        $args = func_get_args();

        $name = $this->getName() ?: $this->getLabel();
        $name = $name ? $this->getType() . '[' . $name . ']' : $this->getType();
        $args[0] = $name;

        throw new InvalidArgumentException(vsprintf($message, $args));
    }
}
