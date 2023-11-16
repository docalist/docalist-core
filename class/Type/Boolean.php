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

use Docalist\Forms\Element;
use Docalist\Type\Exception\InvalidTypeException;

/**
 * Type booléen.
 *
 * @extends Scalar<bool>
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class Boolean extends Scalar
{
    public static function getClassDefault(): bool
    {
        return true;
    }

    public function assign($value): void
    {
        ($value instanceof Any) && $value = $value->getPhpValue();
        if (!is_bool($value)) {
            $value = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if (is_null($value)) {
                throw new InvalidTypeException('boolean');
            }
        }

        $this->phpValue = $value;
    }

    public function getFormattedValue($options = null): string
    {
        return $this->phpValue ? 'TRUE' : 'FALSE';
        // old code de scalar : return (string) $this->phpValue;
    }

    public function getEditorForm($options = null): Element
    {
        $form = parent::getEditorForm($options);
        $form->setAttribute('type', 'checkbox');

        return $form;
    }
}
