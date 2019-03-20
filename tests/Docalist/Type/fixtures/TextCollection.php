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
use Docalist\Type\Text;

/**
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class TextCollection extends Collection
{
    protected static $type = Text::class;
}
