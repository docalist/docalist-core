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

namespace Docalist\Forms\Theme;

use Docalist\Forms\Theme;

/**
 * Thème de base pour les formulaires.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class BaseTheme extends Theme
{
    protected $styles = [];
    protected $scripts = ['docalist-forms'];

    public function __construct()
    {
        parent::__construct(DOCALIST_CORE_DIR . '/views/forms/base');
    }
}
