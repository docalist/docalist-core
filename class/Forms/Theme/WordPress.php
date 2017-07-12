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
 * Thème WordPress pour les formulaires.
 */
class WordPress extends Theme
{
    protected $styles = ['docalist-forms-wordpress'];
    protected $scripts = ['docalist-forms'];

    public function __construct()
    {
        parent::__construct(DOCALIST_CORE_DIR . '/views/forms/wordpress', self::get('base'));
    }
}
