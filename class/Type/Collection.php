<?php

/**
 * This file is part of a "Docalist Core" plugin.
 *
 * Copyright (C) 2012-2015 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Core
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Type;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use InvalidArgumentException;
use Docalist\Type\Exception\InvalidTypeException;
use Docalist\Forms\Choice;

/**
 * Une collection de types.
 */
class Collection extends Any implements ArrayAccess, Countable, IteratorAggregate
{
    /**
     * Le type des éléments de cette collection.
     *
     * Destiné à être surchargé par les classes descendantes (Any par défaut).
     *
     * @var string
     */
    protected static $type = 'Docalist\Type\Any';

    public static function getClassDefault()
    {
        return [];
    }

    /**
     * Retourne le type (le nom de classe complet) des éléments de cette
     * collection.
     *
     * @return string
     */
    final public static function type()
    {
        return static::$type;
    }

    public function assign($value)
    {
        ($value instanceof Any) && $value = $value->value();
        if (! is_array($value)) {
            throw new InvalidTypeException('array');
        }

        $this->value = [];
        foreach ($value as $item) {
            $this->offsetSet(null, $item);
        }

        return $this;
    }

    public function value()
    {
        $result = [];
        foreach ($this->value as $item) {
            $result[] = $item->value();
        }

        return $result;
    }

    /**
     * Indique si un élément existe à la position indiquée.
     * (implémentation de l'interface ArrayAccess).
     *
     * @param int $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->value[$offset]);
    }

    /**
     * Retourne l'élément qui figure à la position indiquée.
     * (implémentation de l'interface ArrayAccess).
     *
     * @param int $offset
     *
     * @return Any
     *
     * @throws InvalidArgumentException Si la position indiquée n'est pas
     * valide.
     */
    public function offsetGet($offset)
    {
        if (isset($this->value[$offset])) {
            return $this->value[$offset];
        }

        $msg = __('Offset %s does not exist in the collection.', 'docalist-core');
        throw new InvalidArgumentException(sprintf($msg, $offset));
    }

    /**
     * Stocke un élément à la position indiquée.
     * (implémentation de l'interface ArrayAccess).
     *
     * @param int $offset Position à laquelle sera inséré l'élément, ou null
     * pour ajouter l'élément à la fin de la collection. Le paramètre offset est
     * ignoré si une clé a été définie dans le schéma de la collection. Dans ce
     * cas, c'est la clé de l'élément qui est utilisée comme position.
     *
     * @param mixed $value Les données de l'élément.
     */
    public function offsetSet($offset, $value)
    {
        // Cas d'une collection typée (avec un schéma)
        if ($this->schema) {
            // Détermine le type des éléments de cette collection
            $type = $this->schema->type();

            // Si value est un objet du bon type, ok
            if ($value instanceof $type) {
                $item = $value;
            }

            // Sinon instancie l'élément
            else {
                if (is_a($type, 'Docalist\Type\Composite', true)) {
                    $item = new $type($value); /* @var $item Composite */

                    // Un objet a déjà un schéma, donc on ne peut pas lui fournir le notre
                    // On se contente de recopier les propriétés qu'il n'a pas (format, etc.)
                    $item->schema->value += $this->schema->value;
                } else {
                    $item = new $type($value, $this->schema);
                }
            }
        }

        // Cas d'une collection libre (sans schéma associé)
        else {
            // Si value est déjà un Type, ok
            if ($value instanceof Any) {
                $item = $value;
            }

            // Sinon, essaie de créer un Type à partir de la valeur
            else {
                $item = self::fromPhpType($value);
            }
        }

        // Si c'est une collection à clé, ignore offset et utilise le sous-champ
        if ($this->schema && $key = $this->schema->key()) {
            $key = $item->$key();
            $this->value[$key] = $item;
        }

        // Collection sans clés
        else {
            if (is_null($offset)) {
                $this->value[] = $item;
            } else {
                $this->value[$offset] = $item;
            }
        }
    }

    /**
     * Supprime un élément à une position donnée.
     * (implémentation de l'interface ArrayAccess).
     *
     * @param int $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->value[$offset]);
    }

    /**
     * Retourne le nombre d'éléments dans la collection.
     * (implémentation de l'interface Countable).
     *
     * @return int
     */
    public function count()
    {
        return count($this->value);
    }

    /**
     * Retourne un itérateur permettant de parcourir la collection.
     * (implémentation de l'interface IteratorAggregate).
     *
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->value);
    }

    /**
     * Retourne le premier élément de la collection et positionne l'itérateur
     * interne au début.
     *
     * @return mixed Le premier élément ou false si la collection est vide.
     */
    public function first()
    {
        return reset($this->value);
    }

    /**
     * Retourne le dernier élément de la collection et positionne l'itérateur
     * interne à la fin.
     *
     * @return mixed Le dernier élément ou false si la collection est vide.
     */
    public function last()
    {
        return end($this->value);
    }

    /**
     * Retourne la clé ou l'index de l'élément en cours.
     *
     * @return int|string La clé de l'élément en cours ou null s'il n'y a pas
     * d'élément courant.
     */
    public function key()
    {
        return key($this->value);
    }

    /**
     * Retourne l'élément en cours.
     *
     * @return mixed L'élément ou false si la collection est vide.
     */
    public function current()
    {
        return current($this->value);
    }

    /**
     * Avance l'itérateur interne à l'élément suivant.
     *
     * @return mixed L'élément suivant ou false s'il n'y a plus d'éléments.
     */
    public function next()
    {
        return next($this->value);
    }

    /**
     * Retourne les clés de la collection.
     *
     * @return string[]
     */
    public function keys()
    {
        return array_keys($this->value);
    }

    /**
     * Met à jour les clés de la collection.
     *
     * Pour une collection simple, les éléments de la collection sont simplement
     * renumérotés de façon continue à partir de zéro. Si une clé a été définie
     * dans le schéma de la collection, la collection est recréée en utilisant
     * la clé de chacun des éléments.
     *
     * @return self $this
     */
    public function refreshKeys()
    {
        // Cas d'une collection à clé
        if ($this->schema && $key = $this->schema->key()) {
            $result = [];
            foreach ($this->value as $item) {
                $result[$item->$key->value()] = $item;
            }
            $this->value = $result;

            return $this;
        }

        // Collection sans clés
        $this->value = array_values($this->value);

        return $this;
    }

    public function filterEmpty($strict = true)
    {
        foreach ($this->value as $key => $item) { /* @var $item Any */
            if ($item->filterEmpty($strict)) {
                unset($this->value[$key]);
            }
        }

        return empty($this->value);
    }

    public function getSettingsForm()
    {
        return $this->createTemporaryItem()->getSettingsForm();
    }

    public function getFormattedValue(array $options = null)
    {
        // Paramètres d'affichage
        $prefix = $this->getOption('prefix', $options, '');
        $suffix = $this->getOption('suffix', $options, '');
        $sep = $this->getOption('sep', $options, ', ');
        $limit = $this->getOption('limit', $options, 0);
        $explode = $this->getOption('explode', $options, false);
        $ellipsis = $this->getOption('ellipsis', $options, '');

        // Les items à formatter
        $items = $this->value;

        // Le résultat
        $result = [];

        // Sanity check / debug
        if ($explode && ! is_a($this->schema->type(), 'Docalist\Type\Categorizable', true)) {
            echo $this->schema->name(), " : 'vue éclatée' activée mais le champ ne gère pas 'Categorizable'<br />";
            $explode = false;
        }

        // Cas 1. Option "vue éclatée" activée (explode)
        if ($explode) {
            // Formatte tous les items en les classant par catégorie (libellé)
            foreach ($items as $item) {
                /* @var $item Categorizable */
                $category = $item->getCategoryLabel();

                /* @var $item Any */
                $result[$category][] = $prefix . $item->getFormattedValue($options) . $suffix;
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
        foreach ($items as $item) { /* @var $item Any */
            $result[] = $prefix . $item->getFormattedValue($options) . $suffix;
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
     * @param array $items Le tableau à tronquer.
     * @param int $limit Le nombre d'éléments à conserver :
     * - 0 : pas de limite,
     * - > 0 : ne conserve que les $limit premiers éléments,
     * - < 0 : ne conserve que les $limit derniers éléments.
     *
     * @return bool true si le tableau a été tronqué, false sinon.
     */
    protected function truncate(array & $items, $limit)
    {
        // Détermine s'il faut tronquer la liste
        $truncate = $limit && (abs($limit) < count($items));

        // Pas de limite (0) ou limite non atteinte, terminé
        if (! $truncate) {
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

    public function getFormatSettingsForm()
    {
        // Crée un item pour récupérer son formulaire
        $item = $this->createTemporaryItem();
        $form = $item->getFormatSettingsForm();
        $name = $this->schema->name();

        // Propose l'option "vue éclatée" si le champ est catégorisable
        if ($item instanceof Categorizable) { /* @var $item Categorizable */
            $form->checkbox('explode')
                ->label(__('Vue éclatée', 'docalist-core'))
                ->description(sprintf(
                    __('Affiche un champ distinct pour chaque %s.', 'docalist-core'),
                    $item->getCategoryName()
                ));
        }

        $form->input('prefix')
            ->attribute('id', $name . '-prefix')
            ->attribute('class', 'prefix regular-text')
            ->label(__('Avant les items', 'docalist-core'))
            ->description(__('Texte ou code html à insérer avant chaque item.', 'docalist-core'));

        $form->input('sep')
            ->attribute('id', $name . '-sep')
            ->attribute('class', 'sep small-text')
            ->label(__('Entre les items', 'docalist-core'))
            ->description(__('Séparateur ou code html à insérer entre les items.', 'docalist-core'));

        $form->input('suffix')
            ->attribute('id', $name . '-suffix')
            ->attribute('class', 'suffix regular-text')
            ->label(__('Après les items', 'docalist-core'))
            ->description(__('Texte ou code html à insérer après chaque item.', 'docalist-core'));

        $form->input('limit')
            ->attribute('type', 'number')
            ->attribute('id', $name . '-limit')
            ->attribute('class', 'limit small-text')
            ->label(__('Limite', 'docalist-core'))
            ->description(
                __("Permet de limiter le nombre d'items affichés.", 'docalist-core') .
                ' ' .
                __('Exemples : 3 = les trois premiers, -3 = les trois derniers, 0 (ou vide) = tout.', 'docalist-core')
            )
            ->attribute('placeholder', 'tout');

        $form->input('ellipsis')
            ->attribute('id', $name . '-limit')
            ->attribute('class', 'limit regular-text')
            ->label(__('Ellipse', 'docalist-core'))
            ->description(
                __("Texte à afficher si le nombre d'items dépasse la limite indiquée plus haut.", 'docalist-core')
            );

        return $form;
    }

    public function getEditorForm(array $options = null)
    {
        // Crée un item et récupére son formulaire
        $form = $this->createTemporaryItem()->getEditorForm($options);

        // Modifie le champ pour qu'il soit répétable
        if ($form instanceof Choice) {
            $form->multiple(true);
        } else {
            $form->repeatable(true);
        }

        // Ok
        return $form;
    }

    /**
     * Crée un item temporaire.
     *
     * Cette méthode est utilisée par getSettingsForm, getEditorForm, etc. pour récupérer
     * le formulaire généré par l'item.
     *
     * @return Any
     */
    private function createTemporaryItem()
    {
        // Récupère le type des items de la collection
        $type = $this->schema->type();

        // Pour une collection, default est un tableau de valeur
        // On est obligé de l'enlever du schéma car sinon item génère une exception 'bad type'
        $default = null;
        if (isset($this->schema->value['default'])) {
            $default = $this->schema->value['default'];
            unset($this->schema->value['default']);
        }

        // Crée l'item
        $item = new $type(null, $this->schema);

        // Restaure la valeur par défaut du schéma
        ! is_null($default) && $this->schema->value['default'] = $default;

        // Ok
        return $item;
    }
}
