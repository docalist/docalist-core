<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Forms\Traits;

use InvalidArgumentException;

/**
 * Un trait pour les items de formulaires qui ont des attributs.
 *
 * Ce trait est partagé entre les classes :
 *
 * - Tag (dans l'arborescence Item » HtmlBlock » Tag) et
 * - Element (Item » Element)
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
trait AttributesTrait
{
    /**
     * @var array Attributs de l'élément.
     */
    protected $attributes = [];

    /**
     * Retourne la liste des attributs de l'élément.
     *
     * @return array
     */
    final public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Initialise les attributs de l'élément.
     *
     * @param array $attributes Un tableau de la forme nom  => valeur.
     *
     * @return self
     */
    final public function setAttributes(array $attributes): self
    {
        $this->attributes = [];

        return $this->addAttributes($attributes);
    }

    /**
     * Ajoute des attributs à l'élément.
     *
     * Si l'un des attributs passés en paramètre existe déjà dans les attributs de l'élément, la valeur
     * existante est écrasée.
     *
     * @param array $attributes Un tableau de la forme nom  => valeur.
     *
     * @return self
     */
    final public function addAttributes(array $attributes): self
    {
        foreach ($attributes as $name => $value) {
            $this->setAttribute($name, $value);
        }

        return $this;
    }

    /**
     * Indique si l'élément a l'attribut indiqué.
     *
     * @param string $name Le nom de l'attribut à tester.
     *
     * @return bool
     */
    final public function hasAttribute(string $name): bool
    {
        return isset($this->attributes[$name]);
    }

    /**
     * Retourne la valeur de l'attribut indiqué ou null si celui-ci n'existe pas.
     *
     * @param string $name le nom de l'attribut.
     *
     * @return string|null
     */
    final public function getAttribute(string $name)
    {
        return isset($this->attributes[$name]) ? $this->attributes[$name] : null;
    }

    /**
     * Modifie la valeur d'un attribut.
     *
     * @param string            $name   Le nom de l'attribut à modifier.
     * @param string|bool|null  $value  La valeur de l'attribut.
     *
     * @return self
     */
    final public function setAttribute(string $name, $value = true): self
    {
        // Supprime l'attribut si la valeur est vide
        if (is_null($value) || $value === false) {
            unset($this->attributes[$name]);

            return $this;
        }

        // La valeur doit être un scalaire
        if (! is_scalar($value)) {
            throw new InvalidArgumentException("Invalid value for attribute '$name' (" . gettype($value) . ')');
        }

        // Ok
        $this->attributes[$name] = $value;

        return $this;
    }

    /**
     * Supprime l'attribut indiqué.
     *
     * @param string $name Nom de l'attribut à supprimer.
     *
     * @return self
     */
    final public function removeAttribute(string $name): self
    {
        return $this->setAttribute($name, null);
    }

    /**
     * Ajoute une ou plusieurs classes à l'attribut 'class' de l'élément.
     *
     * Chacune des classes indiquées n'est ajoutée à l'attribut que si elle n'y figure pas déjà.
     * Les noms de classes sont sensibles à la casse.
     *
     * @param string $class La ou les classes à ajouter. Vous pouvez ajouter plusieurs classes en séparant
     * leurs noms par un espace.
     *
     * Exemple $input->addClass('text small');
     *
     * @return self
     */
    final public function addClass(string $class): self
    {
        // Si l'attribut class n'existe pas encore, on le crée et terminé
        if (!isset($this->attributes['class'])) {
            $this->attributes['class'] = trim($class);

            return $this;
        }

        // Ajoute chacune des classes si elle n'existe pas déjà
        foreach (explode(' ', $class) as $class) {
            if (empty($class)) {
                continue;
            }
            $found = strpos(' ' . $this->attributes['class'] . ' ', " $class ");
            $found === false && $this->attributes['class'] .= " $class";
        }

        return $this;
    }

    /**
     * Supprime une ou plusieurs classes de l'attribut 'class' de l'élément.
     *
     * @param string $class La ou les classes à supprimer. Vous pouvez enlever plusieurs classes en séparant
     * leurs noms par un espace.
     *
     * Exemple $input->removeClass('text small');
     *
     * Si removeClass() est appellée sans paramètre, l'attribut 'class' est supprimé.
     *
     * @return self
     */
    final public function removeClass($class = null): self
    {
        // Si l'attribut class n'existe pas, il n'y a rien à supprimer
        if (!isset($this->attributes['class'])) {
            return $this;
        }

        // Appel sans paramétre, supprime l'attribut class
        if (is_null($class)) {
            unset($this->attributes['class']);

            return $this;
        }

        // Supprime toutes les classes indiquées
        foreach (explode(' ', $class) as $class) {
            if (empty($class)) {
                continue;
            }
            $pos = strpos(' ' . $this->attributes['class'] . ' ', " $class ");
            if ($pos === false) {
                continue;
            }

            $len = strlen($class);
            if ($pos > 0 && ' ' === $this->attributes['class'][$pos - 1]) {
                --$pos;
                ++$len;
            }
            $this->attributes['class'] = trim(substr_replace($this->attributes['class'], '', $pos, $len));
            if ('' === $this->attributes['class']) {
                unset($this->attributes['class']);
                break;
            }
        }

        return $this;
    }

    /**
     * Indique si l'attribut 'class' de l'élément contient l'une des classes indiquées.
     *
     * @param string $class La ou les classes à tester. Vous pouvez également tester plusieurs classes en
     * séparant leurs noms par un espace.
     *
     * Exemple :
     *
     *     $input->hasClass('text small');
     *
     * Retournera true si l'attribut 'class' contient la classe 'text' OU la classe 'small'.
     *
     * @return bool
     */
    final public function hasClass(string $class): bool
    {
        // Si on n'a aucune classe, la réponse est simple
        if (!isset($this->attributes['class'])) {
            return false;
        }

        // Teste chacune des classes indiquées
        foreach (explode(' ', $class) as $class) {
            if (empty($class)) {
                continue;
            }
            if (false !== strpos(' ' . $this->attributes['class'] . ' ', " $class ")) {
                return true;
            }
        }

        return false;
    }

    /**
     * Inverse une ou plusieurs classes dans l'attribut 'class' de l'élément.
     *
     * Chaque classe est supprimée si elle existe déjà, ajoutée sinon.
     *
     * @param string $class Une ou plusieurs classes (séparées par un espace).
     *
     * @return self
     */
    final public function toggleClass(string $class): self
    {
        foreach (explode(' ', $class) as $class) {
            if (empty($class)) {
                continue;
            }
            $this->hasClass($class) ? $this->removeClass($class) : $this->addClass($class);
        }

        return $this;
    }
}
