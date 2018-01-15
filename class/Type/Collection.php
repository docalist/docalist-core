<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 */
namespace Docalist\Type;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use Closure;
use Docalist\Forms\Choice;
use Docalist\Type\Interfaces\Categorizable;
use Docalist\Type\Exception\InvalidTypeException;
use InvalidArgumentException;

/**
 * Une collection de types.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
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

    /*
     * On doit surcharger l'implémentation par défaut car ça retourne la valeur par défaut du schéma.
     * Comme le schéma d'une collection, c'est le schéma de ses éléments, ça crée systématiquement un
     * élément vide (par exemple on se retrouve avec une base vide dans biblio).
     */
    public function getDefaultValue()
    {
        return [];
    }

    /**
     * Retourne le type (le nom de classe complet) des éléments de cette collection.
     *
     * @deprecated Utiliser getType().
     *
     * @return string
     */
    final public static function type()
    {
        _deprecated_function(__METHOD__, '0.14', 'getType');

        return static::getType();
    }

    /**
     * Retourne le type (le nom de classe complet) des éléments de cette collection.
     *
     * @return string
     */
    final public static function getType()
    {
        return static::$type;
    }

    public function assign($value)
    {
        ($value instanceof Any) && $value = $value->getPhpValue();
        if (! is_array($value)) {
            throw new InvalidTypeException('array');
        }

        $this->phpValue = [];
        foreach ($value as $item) {
            $this->offsetSet(null, $item);
        }

        return $this;
    }

    public function getPhpValue()
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
     * @param int $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->phpValue[$offset]);
    }

    /**
     * Retourne l'élément qui figure à la position indiquée (implémentation de l'interface ArrayAccess).
     *
     * @param int $offset
     *
     * @return Any
     *
     * @throws InvalidArgumentException Si la position indiquée n'est pas valide.
     */
    public function offsetGet($offset)
    {
        if (isset($this->phpValue[$offset])) {
            return $this->phpValue[$offset];
        }

        $msg = __('Offset %s does not exist in the collection.', 'docalist-core');
        throw new InvalidArgumentException(sprintf($msg, $offset));
    }

    /**
     * Stocke un élément à la position indiquée (implémentation de l'interface ArrayAccess).
     *
     * @param int $offset Position à laquelle sera inséré l'élément, ou null pour ajouter l'élément à la fin de
     * la collection. Le paramètre offset est ignoré si une clé a été définie dans le schéma de la collection. Dans
     * ce cas, c'est la clé de l'élément qui est utilisée comme position.
     *
     * @param mixed $value Les données de l'élément.
     */
    public function offsetSet($offset, $value)
    {
        // Détermine le type des éléments de cette collection
        $type = $this->schema->type() ?: 'Docalist\Type\Any';

        // Si value n'est pas du bon type, on l'instancie
        if (! $value instanceof $type) {
            $value = new $type($value, $this->schema);
        }

        // Si c'est une collection indexée, ignore offset et utilise le champ indiqué comme clé
        if ($key = $this->schema->key()) {
            $this->phpValue[$value->$key()] = $value;
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
     * @param int $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->phpValue[$offset]);
    }

    /**
     * Retourne le nombre d'éléments dans la collection (implémentation de l'interface Countable).
     *
     * @return int
     */
    public function count()
    {
        return count($this->phpValue);
    }

    /**
     * Retourne un itérateur permettant de parcourir la collection (implémentation de l'interface IteratorAggregate).
     *
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->phpValue);
    }

    /**
     * Retourne le premier élément de la collection et positionne l'itérateur interne au début.
     *
     * @return mixed Le premier élément ou false si la collection est vide.
     */
    public function first()
    {
        return reset($this->phpValue);
    }

    /**
     * Retourne le dernier élément de la collection et positionne l'itérateur interne à la fin.
     *
     * @return mixed Le dernier élément ou false si la collection est vide.
     */
    public function last()
    {
        return end($this->phpValue);
    }

    /**
     * Retourne la clé ou l'index de l'élément en cours.
     *
     * @return int|string La clé de l'élément en cours ou null s'il n'y a pas d'élément courant.
     */
    public function key()
    {
        return key($this->phpValue);
    }

    /**
     * Retourne l'élément en cours.
     *
     * @return mixed L'élément ou false si la collection est vide.
     */
    public function current()
    {
        return current($this->phpValue);
    }

    /**
     * Avance l'itérateur interne à l'élément suivant.
     *
     * @return mixed L'élément suivant ou false s'il n'y a plus d'éléments.
     */
    public function next()
    {
        return next($this->phpValue);
    }

    /**
     * Retourne les clés de la collection.
     *
     * @return string[]
     */
    public function keys()
    {
        return array_keys($this->phpValue);
    }

    /**
     * Met à jour les clés de la collection.
     *
     * Pour une collection simple, les éléments de la collection sont simplement renumérotés de façon continue
     * à partir de zéro. Si une clé a été définie dans le schéma de la collection, la collection est réindexée en
     * utilisant la clé de chacun des éléments.
     *
     * @return self $this
     */
    public function refreshKeys()
    {
        // Cas d'une collection à clé
        if ($this->schema && $key = $this->schema->key()) {
            $result = [];
            foreach ($this->phpValue as $item) {
                $result[$item->$key->getPhpValue()] = $item;
            }
            $this->phpValue = $result;

            return $this;
        }

        // Collection sans clés
        $this->phpValue = array_values($this->phpValue);

        return $this;
    }

    public function filterEmpty($strict = true)
    {
        foreach ($this->phpValue as $key => $item) { /** @var Any $item */
            if ($item->filterEmpty($strict)) {
                unset($this->phpValue[$key]);
            }
        }

        return empty($this->phpValue);
    }

    public function getSettingsForm()
    {
        return $this->createTemporaryItem()->getSettingsForm();
    }

    public function getFormattedValue($options = null)
    {
        // Paramètres d'affichage
        $prefix = $this->getOption('prefix', $options, '');
        $suffix = $this->getOption('suffix', $options, '');
        $sep = $this->getOption('sep', $options, ', ');
        $limit = $this->getOption('limit', $options, 0);
        $explode = $this->getOption('explode', $options, false);
        $ellipsis = $this->getOption('ellipsis', $options, '');

        // Les items à formatter
        $items = $this->phpValue;

        // Le résultat
        $result = [];

        // Sanity check / debug
        if ($explode && ! is_a($this->schema->type(), 'Docalist\Type\Interfaces\Categorizable', true)) {
            echo $this->schema->name(), " : 'vue éclatée' activée mais le champ ne gère pas 'Categorizable'<br />";
            $explode = false;
        }

        // Cas 1. Option "vue éclatée" activée (explode)
        if ($explode) {
            // Formatte tous les items en les classant par catégorie (libellé)
            foreach ($items as $item) {
                /** @var Categorizable $item */
                $category = $item->getCategoryLabel();

                /** @var Any $item */
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
        foreach ($items as $item) { /** @var Any $item */
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
     *
     * @param int   $limit Le nombre d'éléments à conserver :
     *
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
        if ($item instanceof Categorizable) { /** @var Categorizable $item */
            $form->checkbox('explode')
                ->setLabel(__('Vue éclatée', 'docalist-core'))
                ->setDescription(sprintf(
                    __('Affiche un champ distinct pour chaque %s.', 'docalist-core'),
                    $item->getCategoryName()
                ));
        }

        $form->input('prefix')
            ->setAttribute('id', $name . '-prefix')
            ->addClass('prefix regular-text')
            ->setLabel(__('Avant les items', 'docalist-core'))
            ->setDescription(__('Texte ou code html à insérer avant chaque item.', 'docalist-core'));

        $form->input('sep')
            ->setAttribute('id', $name . '-sep')
            ->addClass('sep small-text')
            ->setLabel(__('Entre les items', 'docalist-core'))
            ->setDescription(__('Séparateur ou code html à insérer entre les items.', 'docalist-core'));

        $form->input('suffix')
            ->setAttribute('id', $name . '-suffix')
            ->addClass('suffix regular-text')
            ->setLabel(__('Après les items', 'docalist-core'))
            ->setDescription(__('Texte ou code html à insérer après chaque item.', 'docalist-core'));

        $form->input('limit')
            ->setAttribute('type', 'number')
            ->setAttribute('id', $name . '-limit')
            ->addClass('limit small-text')
            ->setLabel(__('Limite', 'docalist-core'))
            ->setDescription(
                __("Permet de limiter le nombre d'items affichés.", 'docalist-core') .
                ' ' .
                __('Exemples : 3 = les trois premiers, -3 = les trois derniers, 0 (ou vide) = tout.', 'docalist-core')
            )
            ->setAttribute('placeholder', 'tout');

        $form->input('ellipsis')
            ->setAttribute('id', $name . '-limit')
            ->addClass('limit regular-text')
            ->setLabel(__('Ellipse', 'docalist-core'))
            ->setDescription(
                __("Texte à afficher si le nombre d'items dépasse la limite indiquée plus haut.", 'docalist-core')
            );

        return $form;
    }

    public function getAvailableEditors()
    {
        return $this->createTemporaryItem()->getAvailableEditors();
    }

    public function getEditorForm($options = null)
    {
        // Crée un item et récupére son formulaire
        $form = $this->createTemporaryItem()->getEditorForm($options);

        // Modifie le champ pour qu'il soit répétable
        if ($form instanceof Choice) {
            $form->setAttribute('multiple');
        } else {
            $form->setRepeatable();
        }

        // Ok
        return $form;
    }

    /**
     * Crée un item temporaire.
     *
     * Cette méthode est utilisée par getSettingsForm, getEditorForm, etc. pour récupérer le formulaire généré
     * par l'item.
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
        $item = new $type($type::getClassDefault(), $this->schema);

        // Restaure la valeur par défaut du schéma
        ! is_null($default) && $this->schema->value['default'] = $default;

        // Ok
        return $item;
    }

    /**
     * Applique la fonction passée en paramètre à chacun des éléments présents dans la collection et retourne
     * un tableau contenant les valeurs retournées par la fonction.
     *
     * @param Closure $transformer
     *
     * @return array
     */
    public function map(Closure $transformer)
    {
        return array_map($transformer, $this->phpValue);
    }
}
