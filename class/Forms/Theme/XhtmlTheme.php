<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2023 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Docalist\Forms\Theme;

/**
 * Thème de base pour les formulaires.
 *
 * @author Daniel Ménard <daniel.menard.35@gmail.com>
 */
class XhtmlTheme extends BaseTheme
{
    public function __construct()
    {
        parent::__construct();
        $this->setDialect('xhtml');
    }
}
