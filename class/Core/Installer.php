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

namespace Docalist\Core;

use Docalist\Tools\ToolsPage;

/**
 * Installation/désinstallation de docalist-core.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class Installer
{
    /**
     * Initialise l'installateur.
     */
    public function __construct()
    {
    }

    /**
     * Activation.
     */
    public function activate()
    {
        $adminRole = wp_roles()->get_role('administrator');
        if (! is_null($adminRole)) {
            $adminRole->add_cap(ToolsPage::CAPABILITY);
        }
    }

    /**
     * Désactivation.
     */
    public function deactivate()
    {
        $adminRole = wp_roles()->get_role('administrator');
        if (! is_null($adminRole)) {
            $adminRole->remove_cap(ToolsPage::CAPABILITY);
        }
    }
}
