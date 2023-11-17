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

use Docalist\Type\Collection;
use Docalist\Type\Entity;
use Docalist\Type\Text;

/**
 * @property Text                $name     Client
 * @property Collection<Facture> $factures Factures
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class Client extends Entity
{
    public static function loadSchema(): array
    {
        return [
            'fields' => [
                'name'     => [
                    'type'    => Text::class,
                    'default' => 'noname',
                ],
                'factures' => [
                    'type'       => Facture::class,
                    'repeatable' => true,
                    'key'        => 'code',
                ],
            ],
        ];
    }
}
