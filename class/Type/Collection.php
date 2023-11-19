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

namespace Docalist\Type;

use ArrayAccess;
use ArrayIterator;
use Closure;
use Countable;
use Docalist\Forms\Choice;
use Docalist\Forms\Container;
use Docalist\Forms\Element;
use Docalist\Schema\Schema;
use Docalist\Type\Exception\InvalidTypeException;
use Docalist\Type\Interfaces\Categorizable;
use Docalist\Type\Interfaces\Filterable;
use InvalidArgumentException;
use IteratorAggregate;
use Traversable;

/**
 * Une collection de types.
 *
 * @template Item of Any<mixed>
 *
 * @extends Any<array<Item>>
 *
 * @implements ArrayAccess<int|string,Item>
 * @implements IteratorAggregate<int|string,Item>
 *
 * @property array $phpValue
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class Collection extends Any implements ArrayAccess, Countable, IteratorAggregate, Filterable
{
    final public function __construct($value = null, Schema $schema = null)
    {
        // on rend le constructeur final pour que l'usage de "new static()" dans les méthodes
        // filter() et merge() soit "safe" (sinon phpstan signale une erreur)
        parent::__construct($value, $schema);
    }

    /**
     * @return array<Item>
     */
    public static function getClassDefault(): array
    {
        return [];
    }

    /**
     * On doit surcharger l'implémentation par défaut car ça retourne la valeur par défaut du schéma.
     * Comme le schéma d'une collection, c'est le schéma de ses éléments, ça crée systématiquement un
     * élément vide (par exemple on se retrouve avec une base vide dans biblio).
     *
     * @return array<Item>
     */
    public function getDefaultValue(): array
    {
        return [];
    }

    public function assign($value): void
    {
        ($value instanceof Any) && $value = $value->getPhpValue();
        if (!is_array($value)) {
            throw new InvalidTypeException('array');
        }

        $this->phpValue = [];
        foreach ($value as $item) {
            !empty($item) && $this->offsetSet(null, $item);
        }
    }

    /**
     * @return array<mixed>
     */
    public function getPhpValue(): array
    {
        $result = [];
        foreach ($this->phpValue as $item) { // faut-il conserver la clé ?
            $result[] = $item->getPhpValue();
        }

        return $result;
    }

    /**
     * Indique si un élément existe à la position indiquée (implémentation de l'interface ArrayAccess).
     *
     * @param int|string $offset
     */
    public function offsetExists($offset): bool
    {
        return isset($this->phpValue[$offset]);
    }

    /**
     * Retourne l'élément qui figure à la position indiquée (implémentation de l'interface ArrayAccess).
     *
     * @param int|string $offset
     *
     * @return Item
     *
     * @throws InvalidArgumentException Si la position indiquée n'est pas valide.
     */
    public function offsetGet(mixed $offset): mixed //: Any
    {
        // todo : le type de retour de offsetGet devrai être Any mais ça pose problème dans
        // docalist-database car TypeSettings->grids retourne des types Grid et non pas de types Any
        // Il faut revoir complètement le système de grilles et remettre le bon type de retour une
        // fois que ce sera fait.

        if (isset($this->phpValue[$offset])) {
            return $this->phpValue[$offset];
        }

        $msg = __('Offset %s does not exist in the collection.', 'docalist-core');
        throw new InvalidArgumentException(sprintf($msg, $offset));
    }

    /**
     * Stocke un élément à la position indiquée (implémentation de l'interface ArrayAccess).
     *
     * @param int|string|null $offset Position à laquelle sera inséré l'élément, ou null pour ajouter l'élément à la fin de
     *                                la collection. Le paramètre offset est ignoré si une clé a été définie dans le schéma de la collection. Dans
     *                                ce cas, c'est la clé de l'élément qui est utilisée comme position.
     * @param mixed           $value  Les données de l'élément.
     */
    public function offsetSet($offset, $value): void
    {
        // Détermine le type des éléments de cette collection
        /** @var class-string<Item> */
        $type = $this->getSchema()->type() ?: Any::class;

        // Si value n'est pas du bon type, on l'instancie
        if (!$value instanceof $type) {
            $value = new $type($value, $this->getSchema());
        }

        // Si c'est une collection indexée, ignore offset et indexe les éléments de la collection
        // $key contient soit le nom d'un sous-champ (composite) soit true pour indexer les valeurs
        if ($key = $this->getSchema()->key()) {
            $key = ($key === true) ? $value->getPhpValue() : $value->$key();
            $this->phpValue[$key] = $value;
        }

        // Collection sans clés
        else {
            if (is_null($offset)) {
                $this->phpValue[] = $value;
            } else {
                $this->phpValue[$offset] = $value;
            }
        }
    }

    /**
     * Supprime un élément à une position donnée (implémentation de l'interface ArrayAccess).
     *
     * @param int|string $offset
     */
    public function offsetUnset($offset): void
    {
        unset($this->phpValue[$offset]);
    }

    /**
     * Retourne le nombre d'éléments dans la collection (implémentation de l'interface Countable).
     */
    public function count(): int
    {
        return count($this->phpValue);
    }

    /**
     * Retourne un itérateur permettant de parcourir la collection (implémentation de l'interface IteratorAggregate).
     *
     * @return Traversable<int|string,Item>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->phpValue);
    }

    /**
     * Retourne le premier élément de la collection et positionne l'itérateur interne au début.
     *
     * @return ?Item Le premier élément ou null si la collection est vide.
     */
    public function first(): ?Any
    {
        return reset($this->phpValue) ?: null;
    }

    /**
     * Retourne le dernier élément de la collection et positionne l'itérateur interne à la fin.
     *
     * @return ?Item Le dernier élément ou null si la collection est vide.
     */
    public function last(): ?Any
    {
        return end($this->phpValue) ?: null;
    }

    /**
     * Retourne la clé ou l'index de l'élément en cours.
     *
     * @return int|string|null La clé de l'élément en cours ou null s'il n'y a pas d'élément courant.
     */
    public function key()
    {
        return key($this->phpValue);
    }

    /**
     * Retourne l'élément en cours.
     *
     * @return ?Item L'élément ou null si la collection est vide.
     */
    public function current(): ?Any
    {
        return current($this->phpValue) ?: null;
    }

    /**
     * Avance l'itérateur interne à l'élément suivant.
     *
     * @return ?Item L'élément suivant ou null s'il n'y a plus d'éléments.
     */
    public function next(): ?Any
    {
        return next($this->phpValue) ?: null;
    }

    /**
     * Retourne les clés de la collection.
     *
     * @return string[]
     */
    public function keys(): array
    {
        return array_keys($this->phpValue);
    }

    /**
     * Met à jour les clés de la collection.
     *
     * Pour une collection simple, les éléments de la collection sont simplement renumérotés de façon continue
     * à partir de zéro. Si une clé a été définie dans le schéma de la collection, la collection est réindexée en
     * utilisant la clé de chacun des éléments.
     */
    public function refreshKeys(): void
    {
        // Cas d'une collection à clé
        if ($key = $this->getSchema()->key()) {
            $result = [];
            foreach ($this->phpValue as $item) {
                $offset = ($key === true) ? $item->getPhpValue() : $item->$key->getPhpValue();
                $result[$offset] = $item;
            }
            $this->phpValue = $result;

            return;
        }

        // Collection sans clés
        $this->phpValue = array_values($this->phpValue);

        return;
    }

    public function filterEmpty(bool $strict = true): bool
    {
        foreach ($this->phpValue as $key => $item) { /* @var Any $item */
            if ($item->filterEmpty($strict)) {
                unset($this->phpValue[$key]);
            }
        }

        return empty($this->phpValue);
    }

    public function getSettingsForm(): Container
    {
        return $this->createTemporaryItem()->getSettingsForm();
    }

    public function getFormattedValue($options = null): string|array
    {
        // Paramètres d'affichage
        $prefix = $this->getOption('prefix', $options, '');
        $suffix = $this->getOption('suffix', $options, '');
        $sep = $this->getOption('sep', $options, ', ');
        $limit = (int) $this->getOption('limit', $options, 0);

        $explode = $this->getOption('explode', $options, false);
        $ellipsis = $this->getOption('ellipsis', $options, '');

        // Les items à formatter
        // $items = $this->phpValue;
        $items = $this->filter()->phpValue; // Supprime les entrées de type "internal" si on n'a pas les droits

        // Le résultat
        $result = [];

        // Sanity check / debug
        if ($explode && !is_a($this->getSchema()->type(), Categorizable::class, true)) {
            echo $this->getSchema()->name(), " : 'vue éclatée' activée mais le champ ne gère pas 'Categorizable'<br />";
            $explode = false;
        }

        // Cas 1. Option "vue éclatée" activée (explode)
        if ($explode) {
            // Formatte tous les items en les classant par catégorie (libellé)
            foreach ($items as $item) {
                /* @var Categorizable $item */
                $category = $item->getCategoryLabel();
                empty($category) && $category = $this->getOption('label', $options, '');

                /* @var Any $item */
                $result[$category][] = $prefix.$item->getFormattedValue($options).$suffix;
            }

            // Formatte les items dans chacune des catégories
            foreach ($result as $label => $items) {
                // Tronque la liste d'items si nécessaire
                $truncate = $this->truncate($items, $limit);

                // Insère le séparateur indiqué entre les items
                $result[$label] = implode($sep, $items);

                // Ajoute une ellipse si les items ont été tronqués
                $truncate && $result[$label] .= $ellipsis;
            }

            // Ok
            return $result;
        }

        // Cas 2. Affichage normal

        // Tronque la liste d'items si nécessaire (inutile de tout formatter)
        $truncate = $this->truncate($items, $limit);

        // Formatte chaque item
        foreach ($items as $item) { /* @var Any $item */
            $result[] = $prefix.$item->getFormattedValue($options).$suffix;
        }

        // Concatène les éléments avec le séparateur indiqué
        $result = implode($sep, $result);

        // Ajoute une ellipse si la liste d'items a été tronquée
        $truncate && $result .= $ellipsis;

        // Ok
        return $result;
    }

    /**
     * Tronque le tableau passé en paramètre.
     *
     * @param array<Item> $items Le tableau à tronquer.
     * @param int         $limit Le nombre d'éléments à conserver :
     *
     * - 0 : pas de limite,
     * - > 0 : ne conserve que les $limit premiers éléments,
     * - < 0 : ne conserve que les $limit derniers éléments.
     *
     * @return bool true si le tableau a été tronqué, false sinon.
     */
    private function truncate(array &$items, int $limit): bool
    {
        // Détermine s'il faut tronquer la liste
        $truncate = $limit && (abs($limit) < count($items));

        // Pas de limite (0) ou limite non atteinte, terminé
        if (!$truncate) {
            return false;
        }

        // Si $limit est positif, on ne garde que les x premiers
        if ($limit > 0) {
            $items = array_slice($items, 0, $limit);

            return true;
        }

        // Si $limit est négatif, on ne garde que les x derniers
        $items = array_slice($items, $limit);

        return true;
    }

    public function getFormatSettingsForm(): Container
    {
        // Crée un item pour récupérer son formulaire
        $item = $this->createTemporaryItem();
        $form = $item->getFormatSettingsForm();
        $name = $this->getSchema()->name();

        // Propose l'option "vue éclatée" si le champ est catégorisable
        if ($item instanceof Categorizable) { /* @var Categorizable $item */
            $form->checkbox('explode')
                ->setLabel(__('Vue éclatée', 'docalist-core'))
                ->setDescription(sprintf(
                    __('Affiche un champ distinct pour chaque %s.', 'docalist-core'),
                    $item->getCategoryName()
                ));
        }

        $form->input('prefix')
            ->setAttribute('id', $name.'-prefix')
            ->addClass('prefix regular-text')
            ->setLabel(__('Avant les items', 'docalist-core'))
            ->setDescription(__('Texte ou code html à insérer avant chaque item.', 'docalist-core'));

        $form->input('sep')
            ->setAttribute('id', $name.'-sep')
            ->addClass('sep small-text')
            ->setLabel(__('Entre les items', 'docalist-core'))
            ->setDescription(__('Séparateur ou code html à insérer entre les items.', 'docalist-core'));

        $form->input('suffix')
            ->setAttribute('id', $name.'-suffix')
            ->addClass('suffix regular-text')
            ->setLabel(__('Après les items', 'docalist-core'))
            ->setDescription(__('Texte ou code html à insérer après chaque item.', 'docalist-core'));

        $form->input('limit')
            ->setAttribute('type', 'number')
            ->setAttribute('id', $name.'-limit')
            ->addClass('limit small-text')
            ->setLabel(__('Limite', 'docalist-core'))
            ->setDescription(
                __("Permet de limiter le nombre d'items affichés.", 'docalist-core').
                ' '.
                __('Exemples : 3 = les trois premiers, -3 = les trois derniers, 0 (ou vide) = tout.', 'docalist-core')
            )
            ->setAttribute('placeholder', 'tout');

        $form->input('ellipsis')
            ->setAttribute('id', $name.'-limit')
            ->addClass('limit regular-text')
            ->setLabel(__('Ellipse', 'docalist-core'))
            ->setDescription(
                __("Texte à afficher si le nombre d'items dépasse la limite indiquée plus haut.", 'docalist-core')
            );

        return $form;
    }

    public function getAvailableEditors(): array
    {
        return $this->createTemporaryItem()->getAvailableEditors();
    }

    public function getEditorForm($options = null): Element
    {
        // Récupère le premier item ou crée un item temporaire si la collection est vide
        $item = reset($this->phpValue) ?: $this->createTemporaryItem();

        // Récupére le formulaire de l'item
        $form = $item->getEditorForm($options);

        // Modifie le champ pour qu'il soit répétable
        ($form instanceof Choice) ? $form->setAttribute('multiple') : $form->setRepeatable();

        // A voir : dans Choice, surcharger setRepeatable() et mettre 'multiple' à true

        // Ok
        return $form;
    }

    /**
     * Crée un item temporaire.
     *
     * Cette méthode est utilisée par getSettingsForm, getEditorForm, etc. pour récupérer le formulaire généré
     * par l'item.
     *
     * @return Item
     */
    public function createTemporaryItem(): Any // public car utilisée par IndexableCollectionTrait
    {
        $schema = $this->getSchema();

        // Récupère le type des items de la collection
        /** @var class-string<Item> */
        $type = $schema->type();

        // Pour une collection, default est un tableau de valeur
        // On est obligé de l'enlever du schéma car sinon item génère une exception 'bad type'
        $default = null;
        if (isset($schema->value['default'])) {
            $default = $schema->value['default'];
            unset($schema->value['default']);
        }

        // Crée l'item
        $item = new $type($type::getClassDefault(), $schema);

        // Restaure la valeur par défaut du schéma
        !is_null($default) && $schema->value['default'] = $default;

        // Ok
        return $item;
    }

    /**
     * Applique la fonction passée en paramètre à chacun des éléments présents dans la collection et retourne
     * un tableau contenant les valeurs retournées par la fonction.
     *
     * @return array<mixed>
     */
    public function map(Closure $transformer): array
    {
        return array_map($transformer, $this->phpValue);
    }

    /**
     * @return Collection<Item>
     */
    final public function filter(array $include = [], array $exclude = [], int $limit = 0): Collection
    {
        // Détermine la liste des éléments à retourner
        $items = [];
        foreach ($this->phpValue as $key => $item) {
            // Filtre l'elément
            if (is_null($item = $this->filterItem($item, $include, $exclude))) {
                continue;
            }

            // Ajoute l'élément à la liste
            $items[$key] = $item;

            // On s'arrête quand la limite indiquée est atteinte
            if ($limit && count($items) >= $limit) {
                break;
            }
        }

        // Crée une nouvelle collection contenant les éléments obtenus
        $result = new static([], $this->getSchema());
        $result->phpValue = $items;

        // Ok
        return $result;
    }

    /**
     * Détermine si l'élément passé en paramètre doit être retourné ou non par les méthodes filter() et similaires.
     *
     * @param Item          $item    L'item à filtrer.
     * @param array<string> $include Liste des valeurs à inclure.
     * @param array<string> $exclude Liste des valeurs à exclure.
     *
     * @return ?Item Retourne l'item à insérer dans la collection retournée par filter() ou null pour filtrer
     *               l'élément.
     *
     * Remarque : l'item retourné peut être différent de l'item passé en paramètre (par exemple la classe
     * MultiFieldCollection filtre sur le champ type et retourne le champ value).
     */
    protected function filterItem(Any $item, array $include = [], array $exclude = []): ?Any
    {
        // On filtre sur la valeur des items
        $value = $item->getPhpValue();

        // Si on a une liste blanche et que l'item n'y figure pas, on l'ignore
        if (!empty($include) && !in_array($value, $include, true)) {
            return null;
        }

        // Si on a une liste noire et que l'item y figure, on l'ignore
        if (!empty($exclude) && in_array($value, $exclude, true)) {
            return null;
        }

        // Ok
        return $item;
    }

    /**
     * Fusionne deux collections et retourne une nouvelle collection.
     *
     * Les items des deux collections sont fusionnés avec array_merge. Si les collections sont indexées par clés et
     * qu'elles ont des clés en commun, ce sont les items de la collection passée en paramètre qui sont retournés.
     *
     * @param Collection<Item> $collection
     *
     * @return Collection<Item> Retourne une nouvelle collection (les collections d'origine ne sont pas modifiées).
     */
    public function merge(Collection $collection): Collection
    {
        if (!is_a($collection, get_class($this))) {
            throw new InvalidArgumentException(sprintf(
                '"%s" is not mergeable with "%s"',
                get_class($collection),
                get_class($this)
            ));
        }
        $result = new static([], $this->getSchema());
        $result->phpValue = array_unique(array_merge($this->phpValue, $collection->phpValue));

        return $result;
    }
}
