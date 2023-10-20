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

namespace Docalist\Tests\Forms;

use Docalist\Forms\Item;
use Docalist\Forms\Theme;

/**
 * Classe héritée de Theme pour permettre de tester l'API interne.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class ThemeMock extends Theme
{
    /**
     * @var mixed[]
     */
    public array $lastDisplay;

    public function __construct()
    {
        parent::__construct(__DIR__);
    }

    public function display(Item $item, $view = null, array $args = []): static
    {
        $this->lastDisplay = func_get_args();
        echo 'AbcXyz';

        return $this;
    }
}
