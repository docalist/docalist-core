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

use Docalist\Type\Exception\InvalidTypeException;
use Docalist\Forms\Input;

/**
 * Classe de base pour les types scalaires.
 */
class Scalar extends Any
{

    public static function getClassDefault()
    {
        return ''; // null ? false ?
    }

    public function assign($value)
    {
        ($value instanceof Any) && $value = $value->value();
        if (! is_scalar($value)) {
            throw new InvalidTypeException('scalar');
        }

        $this->value = $value;

        return $this;
    }

    public function getFormattedValue(array $options = null)
    {
        return (string) $this->value;
    }

    public function getEditorForm(array $options = null)
    {
        $name = isset($this->schema->name) ? $this->schema->name() : $this->randomId();
        $form = new Input($name);
        isset($this->schema) && $form->name($this->schema->name());

        return $form;
    }
}
