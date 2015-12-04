<?php
/**
 * This file is part of the "Docalist Core" plugin.
 *
 * Copyright (C) 2012-2015 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Tests
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Tests\Forms;

use Docalist\Forms\Theme;
use Docalist\Forms\Item;

/**
 * Classe héritée de Element pour permettre de tester l'API interne.
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
