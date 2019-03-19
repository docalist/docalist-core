<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Type;

use Docalist\Type\Exception\InvalidTypeException;
use Docalist\Forms\Element;

/**
 * Type entier.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class Integer extends Number
{
    public function assign($value): void
    {
        ($value instanceof Any) && $value = $value->getPhpValue();
        if (! is_int($value)) {
            if ($value === '') {
                $value = 0;
            } elseif (false === $value = filter_var($value, FILTER_VALIDATE_INT)) {
                throw new InvalidTypeException('int');
            }
        }

        $this->phpValue = $value;
    }

    public function getEditorForm($options = null): Element
    {
        return parent::getEditorForm($options)->setAttribute('step', '1');
    }
}
