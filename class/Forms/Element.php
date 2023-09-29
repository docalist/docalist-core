<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2023 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Docalist\Forms;

use Docalist\Forms\Traits\AttributesTrait;
use Docalist\Schema\Schema;
use Docalist\Type\Any;
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
 * @author Daniel Ménard <daniel.menard.35@gmail.com>
 */
abstract class Element extends Item
{
    use AttributesTrait;

    /**
     * Nom de l'élément.
     */
    protected string $name = '';

    /**
     * Libellé associé à l'élément.
     */
    protected string $label = '';

    /**
     * Description de l'élément.
     */
    protected string $description = '';

    /**
     * Indique si le champ est répétable.
     *
     * @var bool|null
     */
    protected $repeatable;

    /**
     * Les données de l'élément, initialisées par bind().
     *
     * @var scalar|array<mixed>|null
     */
    protected $data;

    /**
     * Occurence en cours pour un champ répétable.
     *
     * @var int|string|null
     */
    protected $occurence;

    /**
     * Champ requis / mode d'affichage.
     */
    protected string $required = '';

    /**
     * Crée un élément de formulaire.
     *
     * @param string                        $name       optionnel, le nom de l'élément
     * @param array<string,string|int|bool> $attributes optionnel, les attributs de l'élément
     * @param Container|null                $parent     optionnel, le containeur parent de l'item
     */
    public function __construct(string $name = '', array $attributes = [], Container $parent = null)
    {
        parent::__construct($parent);
        if ('' !== $name) {
            $this->setName($name);
        }
        if ([] !== $attributes) {
            $this->addAttributes($attributes);
        }
    }

    /**
     * Par défaut, tous les éléments ont un layout.
     *
     * Certains éléments de la hiérarchie surchargent cette méthode pour indiquer qu'ils ne veulent pas
     * de layout (exemple : Hidden).
     *
     * Un élément qui n'a pas de layout n'a ni bloc label, ni bloc description (il est affiché "tel quel").
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
     */
    protected function hasDescriptionBlock(): bool
    {
        return true;
    }

    /**
     * Modifie le nom du champ.
     */
    final public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Retourne le nom du champ.
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
     */
    protected function getControlName(): string
    {
        // Si l'élément n'a pas de nom, retourne une chaine vide
        if ('' === $this->name) {
            return '';
        }

        // Récupère le nom de notre container parent
        $base = $this->parent instanceof Container ? $this->parent->getControlName() : '';

        // Construit le nom de l'élément
        $name = '' !== $base ? ($base.'['.$this->name.']') : $this->name;

        // Ajoute le numéro d'occurence
        if ($this->isRepeatable()) {
            $name .= '['.$this->occurence.']';
        }

        // Ok
        return $name;
    }

    final public function getPath(string $separator = '/'): string
    {
        $path = $this->parent instanceof Container ? $this->parent->getPath($separator) : '';
        if ('' !== $this->name) {
            if ('' !== $path) {
                $path .= $separator;
            }
            $path .= $this->name;
        }

        return $path;
    }

    /**
     * Modifie le libellé du champ.
     */
    final public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Retourne le libellé du champ.
     */
    final public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * Modifie la description du champ.
     */
    final public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Retourne la description du champ.
     */
    final public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Retourne la liste des modes d'affichage disponibles pour un champ requis.
     *
     * @return string[] un tableau de la forme code => libellé
     */
    final public function requiredModes(): array
    {
        return [
            'mark-before' => __('Insérer un astérique rouge avant le libellé', 'docalist-core'),
            'mark-after' => __('Ajouter un astérique rouge après le libellé', 'docalist-core'),
            'heavy-mark-before' => __('Insérer un gros astérique rouge avant le libellé', 'docalist-core'),
            'heavy-mark-after' => __('Ajouter un gros astérisque rouge après le libellé', 'docalist-core'),
            'color-label' => __('Afficher le libellé en rouge', 'docalist-core'),
            'color-container' => __('Ajouter un fond rouge au formulaire', 'docalist-core'),
        ];
    }

    /**
     * Définit si le champ est requis ou non.
     *
     * @param string $mode Un code (cf. requiredModes()) indiquant le mode d'affichage si le champ est requis
     *                     ou une chaine vide si le champ est optionnel.
     */
    final public function setRequired(string $mode = 'mark-after'): void
    {
        $modes = $this->requiredModes();
        if ('' !== $mode && !isset($modes[$mode])) {
            throw $this->invalidArgument('Invalid required mode');
        }
        $this->required = $mode;
    }

    /**
     * Indique si le champ est requis ou non.
     *
     * @return string Un code (cf. requiredModes()) indiquant le mode d'affichage si le champ est requis
     *                ou une chaine vide si le champ est optionnel.
     */
    final public function getRequired(): string
    {
        return $this->required;
    }

    /**
     * Modifie le flag repeatable du champ.
     */
    public function setRepeatable(?bool $repeatable = true): static
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
     */
    final public function isRepeatable(): bool
    {
        return true === $this->repeatable;
    }

    /**
     * Retourne le "niveau de répétition" du noeud en cours.
     *
     * Exemples :
     * - pour un champ non répétable, retourne 0
     * - si le champ est répétable, retourne 1
     * - si le champ est répétable et que son parent est répétable, retoune 2
     * - et ainsi de suite
     */
    final protected function getRepeatLevel(): int
    {
        $level = $this->parent instanceof Container ? $this->parent->getRepeatLevel() : 0;
        if ($this->isRepeatable()) {
            ++$level;
        }

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
     */
    protected function isMultivalued(): bool
    {
        return $this->isRepeatable();
    }

    /**
     * Initialise les propriétés repeatable, label et description à partir du schéma passé en paramétre.
     *
     * Chaque propriété n'est initialisée que si elle est à null.
     */
    protected function bindSchema(Schema $schema): void
    {
        // Initialise la propriété "repeatable"
        if (is_null($this->repeatable)) {
            $this->setRepeatable(false);
        }

        // Initialise le libellé
        if ('' === $this->label) {
            $this->setLabel($schema->label() ?? '');
        }

        // Initialise la description
        if ('' === $this->description) {
            $this->setDescription($schema->description() ?? '');
        }
    }

    /**
     * Initialise l'élément à partir des données passées en paramètre.
     *
     * @param scalar|array<mixed>|null $data
     */
    protected function bindData($data): void
    {
        // Cas d'un champ monovalué
        if (!$this->isMultivalued()) { // ni repeatable, ni multiple
            // Data doit être un scalaire
            if (!is_scalar($data) && !is_null($data)) {
                throw $this->invalidArgument('Element %s is monovalued, expected scalar or null, got %s', gettype($data));
            }
        }

        // Cas d'un champ multivalué
        else {
            // On accepte un tableau ou null
            if (!is_array($data) && !is_null($data)) {
                $data = (array) $data; // 'article' => ['article'], null => []
                //                 throw $this->invalidArgument(
                //                     'Element %s is multivalued, expected array or null, got %s',
                //                     gettype($data)
                //                 );
            }

            // Si c'est un tableau vide, on stocke null plutôt que array()
            if ([] === $data) {
                $data = null;
            }
        }

        // Stocke les données
        $this->data = $data;
    }

    /**
     * Initialise les données de l'élément à partir des données passées en paramétre.
     *
     * @param scalar|array<mixed>|null $data
     */
    final public function bind(mixed $data): void
    {
        // Si c'est un type docalist, initialise le schéma et récupère les données
        if ($data instanceof Any) {
            $this->bindSchema($data->getSchema());
            $data = $data->getPhpValue();
        }

        // Initialise les données
        $this->bindData($data);

        // Initialise l'occurence en cours
        if ($this->isRepeatable()) {
            $this->occurence = is_array($this->data) ? key($this->data) : null;
        }
    }

    /**
     * Retourne les données de l'élément.
     *
     * @return scalar|array<mixed>|null La méthode retourne les données qui ont été stockées lors du dernier appel
     *               à la méthode bind(). Si bind() n'a jamais été appellée, elle retourne null.
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
     * @param int|string $occurence une des clés des données du champ
     */
    protected function setOccurence($occurence): void
    {
        // Si le champ n'est pas répétable, c'est une erreur d'appeller setOccurence
        // On accepte néanmoins setOccurence(0) pour un champ non répétable car cela
        // permet au vue de simplifier leur code en gérant de la même manière les deux
        // cas en faisant "foreach ( (array) $data )" (cf. base/input.php par exemple).
        if (!$this->isRepeatable()) {
            if (0 === $occurence) {
                return;
            }

            throw $this->invalidArgument('Element "%s" is not repeatable, occurence cannot be set.');
        }

        // Vérifie que la clé indiquée existe dans data
        $valid = is_array($this->data) ? array_key_exists($occurence, $this->data) : empty($occurence);
        if (!$valid) {
            throw $this->invalidArgument('Element "%s" do not have data for occurence "%s".', $occurence);
        }

        // Ok
        $this->occurence = $occurence;
    }

    /**
     * Retourne le numéro d'occurence du champ.
     *
     * @return int|string|null
     */
    public function getOccurence()
    {
        return $this->occurence;
    }

    /**
     * @return mixed[]
     */
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
     * Retounre une exception InvalidArgumentException.
     *
     * @param string $message message style sprintf (%1 = nom/label/type de l'élément)
     * @param scalar  ...$args paramètres à passer à sprintf.
     */
    protected function invalidArgument($message, mixed ...$args): InvalidArgumentException
    {
        $name = $this->getName() ?: $this->getLabel();
        $name = ('' !== $name) ? $this->getType().'['.$name.']' : $this->getType();

        array_unshift($args, $name);

        return new InvalidArgumentException(vsprintf($message, $args));
    }
}
