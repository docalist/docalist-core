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
 * @author Daniel Ménard <daniel.menard.35@gmail.com>
 */
trait AttributesTrait
{
    /**
     * @var array<string,string|int|bool> attributs de l'élément
     */
    protected array $attributes = [];

    /**
     * Retourne la liste des attributs de l'élément.
     *
     * @return array<string,string|int|bool>
     */
    final public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Initialise les attributs de l'élément.
     *
     * @param array<string,string|int|bool> $attributes un tableau de la forme nom  => valeur
     */
    final public function setAttributes(array $attributes): static
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
     * @param array<string,string|int|bool> $attributes un tableau de la forme nom  => valeur
     */
    final public function addAttributes(array $attributes): static
    {
        foreach ($attributes as $name => $value) {
            $this->setAttribute($name, $value);
        }

        return $this;
    }

    /**
     * Indique si l'élément a l'attribut indiqué.
     *
     * @param string $name le nom de l'attribut à tester
     */
    final public function hasAttribute(string $name): bool
    {
        return isset($this->attributes[$name]);
    }

    /**
     * Retourne la valeur de l'attribut indiqué ou null si celui-ci n'existe pas.
     *
     * @param string $name le nom de l'attribut
     *
     * @return string|int|bool|null
     */
    final public function getAttribute(string $name): mixed
    {
        return $this->attributes[$name] ?? null;
    }

    /**
     * Modifie la valeur d'un attribut.
     *
     * @param string               $name  le nom de l'attribut à modifier
     * @param string|int|bool|null $value la valeur de l'attribut
     */
    final public function setAttribute(string $name, $value = true): static
    {
        // Supprime l'attribut si la valeur est vide
        if (is_null($value) || false === $value) {
            unset($this->attributes[$name]);

            return $this;
        }

        // La valeur doit être un scalaire
        if (!is_scalar($value)) {
            throw new InvalidArgumentException("Invalid value for attribute '$name' (".gettype($value).')');
        }

        // Ok
        $this->attributes[$name] = $value;

        return $this;
    }

    /**
     * Supprime l'attribut indiqué.
     *
     * @param string $name nom de l'attribut à supprimer
     */
    final public function removeAttribute(string $name): static
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
     *                      leurs noms par un espace.
     *
     * Exemple $input->addClass('text small');
     */
    final public function addClass(string $class): static
    {
        // Si l'attribut class n'existe pas encore, on le crée et terminé
        if (!isset($this->attributes['class'])) {
            $this->attributes['class'] = trim($class);

            return $this;
        }

        // Ajoute chacune des classes si elle n'existe pas déjà
        foreach (explode(' ', $class) as $class) {
            if ('' === $class) {
                continue;
            }
            if (!str_contains(' '.$this->attributes['class'].' ', " $class ")) {
                $this->attributes['class'] .= " $class";
            }
        }

        return $this;
    }

    /**
     * Supprime une ou plusieurs classes de l'attribut 'class' de l'élément.
     *
     * @param ?string $class La ou les classes à supprimer. Vous pouvez enlever plusieurs classes en séparant
     *                       leurs noms par un espace.
     *
     * Exemple $input->removeClass('text small');
     *
     * Si removeClass() est appellée sans paramètre, l'attribut 'class' est supprimé.
     */
    final public function removeClass(string $class = null): static
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

        /** @var string */
        $classes = &$this->attributes['class'];

        // Supprime toutes les classes indiquées
        foreach (explode(' ', $class) as $class) {
            if ('' === $class) {
                continue;
            }
            $pos = strpos(' '.$classes.' ', " $class ");
            if (false === $pos) {
                continue;
            }

            $len = strlen($class);
            if ($pos > 0 && ' ' === $classes[$pos - 1]) {
                --$pos;
                ++$len;
            }
            $classes = trim(substr_replace((string) $classes, '', $pos, $len));
            if ('' === $classes) {
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
     *                      séparant leurs noms par un espace.
     *
     * Exemple :
     *
     *     $input->hasClass('text small');
     *
     * Retournera true si l'attribut 'class' contient la classe 'text' OU la classe 'small'.
     */
    final public function hasClass(string $class): bool
    {
        // Si on n'a aucune classe, la réponse est simple
        if (!isset($this->attributes['class'])) {
            return false;
        }

        // Teste chacune des classes indiquées
        foreach (explode(' ', $class) as $class) {
            if ('' === $class) {
                continue;
            }
            if (str_contains(' '.$this->attributes['class'].' ', " $class ")) {
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
     * @param string $class une ou plusieurs classes (séparées par un espace)
     */
    final public function toggleClass(string $class): static
    {
        foreach (explode(' ', $class) as $class) {
            if ('' === $class) {
                continue;
            }
            $this->hasClass($class) ? $this->removeClass($class) : $this->addClass($class);
        }

        return $this;
    }
}
