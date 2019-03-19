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

use Docalist\Type\Scalar;
use Docalist\Type\Exception\InvalidTypeException;
use Docalist\Forms\Element;

/**
 * Type booléen.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class Boolean extends Scalar
{
    public static function getClassDefault()
    {
        return true;
    }

    public function assign($value): void
    {
        ($value instanceof Any) && $value = $value->getPhpValue();
        if (! is_bool($value)) {
            $value = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if (is_null($value)) {
                throw new InvalidTypeException('boolean');
            }
        }

        $this->phpValue = $value;
    }

    public function getEditorForm($options = null): Element
    {
        return parent::getEditorForm($options)->setAttribute('type', 'checkbox');
    }
}
