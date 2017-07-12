<?php
/**
 * This file is part of the "Docalist Forms" package.
 *
 * Copyright (C) 2012-2017 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Forms
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Forms\Theme;

use Docalist\Forms\Theme;

/**
 * Thème de base pour les formulaires.
 */
class Base extends Theme
{
    protected $styles = [];
    protected $scripts = ['docalist-forms'];

    public function __construct()
    {
        parent::__construct(DOCALIST_CORE_DIR . '/views/forms/base');
    }
}
