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
 * Un nombre.
 *
 * @template TPhpValue of int|float
 *
 * @extends Scalar<TPhpValue>
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class Number extends Scalar
{
    public static function getClassDefault(): int|float
    {
        return 0;
    }

    public function assign($value): void
    {
        ($value instanceof Any) && $value = $value->getPhpValue();
        if (!is_numeric($value)) {
            if (false === $value = filter_var($value, FILTER_VALIDATE_FLOAT)) {
                throw new InvalidTypeException('number');
            }
        }
        $int = (int) $value;
        $float = (float) $value;

        // @phpstan-ignore assign.propertyType
        $this->phpValue = ($int == $float) ? $int : $float;
    }

    public function getEditorForm($options = null): Element
    {
        return parent::getEditorForm($options)->setAttribute('type', 'number');
    }
}
