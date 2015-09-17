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

use Docalist\Schema\Schema;
use Serializable, JsonSerializable;
use Docalist\Type\Exception\InvalidTypeException;

/*
 * Inspiration : https://github.com/nicolopignatelli/valueobjects
 */

/**
 * Classe de base pour les différents types de données.
 */
class Any implements Serializable, JsonSerializable {
    /**
     * La valeur du type.
     *
     * @var mixed
     */
    protected $value;

    /**
     * Le schéma du type.
     *
     * @var Schema
     */
    protected $schema;

    /**
     * La valeur par défaut du type.
     *
     * Cette valeur n'est utilisée que si le type n'a pas de schéma (si on a
     * un schéma, defaultValue() retourne la valeur par défaut indiquée dans
     * le schéma).
     *
     * @var mixed
     */
    static protected $default = null;

    /**
     * Indentation en cours, utilisé uniquement pour __toString() dans les
     * classes Object et Collection.
     *
     * @var string
     */
    static protected $indent = '';
    // TODO A réfléchir : introduire une classe Composite pour Object et Collection ?

    /**
     * Crée un nouveau type.
     *
     * @param mixed $value La valeur initiale. Pour les scalaires, vous devez
     * passer un type php natif correspondant au type de l'objet (int, bool,
     * float, ...) Pour les types structurés et les collections, vous devez
     * passer un tableau.
     * @param Schema $schema Optionnel, le schéma du type.
     */
    public function __construct($value = null, Schema $schema = null) {
        $this->schema = $schema;
        $this->assign(is_null($value) ? $this->defaultValue() : $value);
    }

    /**
     * Retourne la valeur par défaut de la classe.
     *
     * Cette méthode est statique, elle indique la valeur par défaut de cette
     * classe de type.
     *
     * @return mixed
     */
    static public final function classDefault() {
        return static::$default;
    }

    /**
     * Retourne la valeur par défaut de l'objet.
     *
     * La méthode retourne la valeur par défaut indiquée dans le schéma associé
     * à l'objet ou la valeur par défaut du type (classDefault) si aucun schéma
     * n'est associé ou s'il n'indique pas de valeur par défaut.
     *
     * @return mixed
     */
    public function defaultValue() {
        if ($this->schema) {
            $default = $this->schema->defaultValue();
            if (! is_null($default)) {
                return $default;
            }
        }

        return static::$default;
    }

    /**
     * Assigne une valeur au type.
     *
     * @param mixed $value La valeur à assigner.
     *
     * @return self $this
     *
     * @throws InvalidTypeException Si $value est invalide.
     */
    public function assign($value) {
        ($value instanceof Any) && $value = $value->value();
        $this->value = $value;

        return $this;
    }

    /**
     * Retourne la valeur sous la forme d'un type php natif (string, int, float
     * ou bool pour les types simples, un tableau pour les types structurés et
     * les collections).
     *
     * @return mixed
     */
    public function value() {
        return $this->value;
    }

    /**
     * Réinitialise le type à sa valeur par défaut.
     *
     * @return self $this
     */
    public final function reset() {
        return $this->assign($this->defaultValue());
    }

    /**
     * Retourne le schéma du type.
     *
     * @return Schema le schéma ou null si le type n'a pas de schéma associé.
     */
    public final function schema() {
        return $this->schema;
    }

    /**
     * Teste si deux types sont identiques.
     *
     * Par défaut, les types sont identiques si ils ont la même classe et
     * la même valeur.
     *
     * @param Any $other
     *
     * @return boolean
     */
    public function equals(Any $other) {
        return get_class($this) === get_class($other) && $this->value() === $other->value();
    }

    /**
     * Retourne une représentation de la valeursous forme de chaine de
     * caractères.
     *
     * @return string
     */
    public function __toString() {
        return json_encode($this->value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * Sérialise la valeur (implémentation de l'interface Serializable).
     *
     * @return string
     */
    public final function serialize() {
        return serialize($this->value());
    }

    /**
     * Désérialise le type (implémentation de l'interface Serializable).
     *
     * @param string $serialized
     */
    public final function unserialize($serialized) {
        $this->assign(unserialize($serialized));
    }

    /**
     * Spécifie les données qui doivent être sérialisées en JSON
     * (implémentation de l'interface JsonSerializable).
     *
     * @return mixed
     */
    public final function jsonSerialize () {
        return $this->value();
    }
//+is ?

    /**
     * Retourne le nom de la classe.
     *
     * @return string Retourne le nom de classe complet du type (incluant le
     * namespace).
     */
    static public final function className() {
        return get_called_class();
    }

    /**
     * Retourne le namespace de la classe du type.
     *
     * @return string Le namespace de la classe ou une chaine vide s'il s'agit
     * d'une classe globale.
     */
    static public final function ns() {
        $class = get_called_class();
        $pt = strrpos($class, '\\');
        return $pt === false ? '' : substr($class, 0, $pt);
    }

    /**
     * Essaie de créer un Type à partir de la valeur php passée en paramètre.
     *
     * La méthode essaie de déterminer la classe Type la plus adaptée en
     * fonction du type php de la valeur indiquée.
     *
     * @param mixed $value
     * @return Any
     *
     * @throws InvalidTypeException Si le type de la valeur passée en paramètre
     * n'est pas reconnu.
     */
    static protected final function guessType($value) {
        if (is_array($value)) {
            // ça peut être une collection ou un tableau
            // pour tester si les clés sont des int (0..n) on pourrait utiliser
            // array_values($value) === $value
            // cf. https://gist.github.com/Thinkscape/1965669
            // mais dans notre cas, il suffit de tester la clé du 1er élément
            if (is_int(key($value))) { // tableau numérique
                return new Collection($value);
            } else {
                return new Object($value); // tableau associatif
            }
        }

        if (is_string($value)) {
            return new Text($value);
        }

        if (is_int($value)) {
            return new Integer($value);
        }

        if (is_bool($value)) {
            return new Boolean($value);
        }

        if (is_float($value)) {
            return new Decimal($value);
        }

        if (is_null($value)) {
            return new Any();
        }

        throw new InvalidTypeException('a php type that I can guess');
    }


    /**
     * Filtre les valeurs vides.
     *
     * La méthode filterEmpty() permet de supprimer les valeurs vides d'un
     * type : elle retourne true si la valeur est vide, false sinon.
     *
     * Pour un type scalaire, c'est équivalent à la fonction php empty().
     *
     * Pour un type composite (objet, collection, entité...), la méthode est
     * récursive : elle applique filterEmpty() à chacun des éléments qui
     * composent le type composite, supprime les éléments pour lesquels
     * filterEmpty() a retourné true et retourne true ou false selon que le
     * type composite est vide ou non après traitement.
     *
     * Par défaut ($strict = true), filterEmpty() effectue une comparaison
     * "stricte" pour déterminer si un objet est vide : elle retourne true si
     * toutes les propriétés de l'objet sont vides (autrement dit, un objet qui
     * contient au moins une propriété sera considéré comme non vide).
     *
     * En passant $strict = false, une comparaison spécifique est utilisée pour
     * déterminer si un objet est vide ou non. Pour cela, chaque type objet
     * peut surcharger la méthode filterEmpty() et définir dans quel cas il
     * est vide. Pour un auteur, par exemple, on considérera qu'il est vide si
     * on n'a pas de nom ; pour un résumé, on retournera true si l'objet Content
     * contient un type de contenu mais aucun texte, etc.
     *
     * @param bool $strict Définit le mode de comparaison utilisé pour
     * déterminer si la valeur est vide ou non (true par défaut).
     *
     * @return bool true si le champ est vide, false sinon.
     */
    public function filterEmpty($strict = true) {
        return empty($this->value);
    }
}