<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Tests\Forms;

use Docalist\Forms\Theme;
use Docalist\Forms\Item;

/**
 * Classe héritée de Theme pour permettre de tester l'API interne.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class ThemeMock extends Theme
{
    public $lastDisplay;

    public function __construct()
    {
        parent::__construct(__DIR__);
    }

    public function display(Item $item, $view = null, array $args = [])
    {
        $this->lastDisplay = func_get_args();
        echo 'AbcXyz';
    }
}
