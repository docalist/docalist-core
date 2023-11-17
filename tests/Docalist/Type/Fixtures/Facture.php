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

namespace Docalist\Tests\Type\Fixtures;

use Docalist\Type\Composite;
use Docalist\Type\Integer as DocalistInteger;
use Docalist\Type\Text;

/**
 * @property Text            $code
 * @property Text            $label
 * @property DocalistInteger $total
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class Facture extends Composite
{
    public static function loadSchema(): array
    {
        return [
            'fields' => [
                'code'  => Text::class,
                'label' => Text::class,
                'total' => Integer::class,
            ],
        ];
    }
}
