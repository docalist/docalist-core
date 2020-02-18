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
final class Installer
{
    /**
     * Retourne la liste des capacités qui seront créées/supprimées lors de
     * l'activation / la désactivation du plugin.
     *
     * @return string[]
     */
    private function getCapabilities(): array
    {
        return [
            // Voir la page "Outils docalist"
            ToolsPage::CAPABILITY,

            // Voir les entrées de type "internalxxx" des MultiFieldCollection
            'docalist_collection_view_internal',
        ];
    }

    /**
     * Activation.
     */
    final public function activate(): void
    {
        $adminRole = wp_roles()->get_role('administrator');
        if (is_null($adminRole)) {
            return;
        }

        foreach ($this->getCapabilities() as $capability) {
            $adminRole->add_cap($capability);
        }
    }

    /**
     * Désactivation.
     */
    final public function deactivate(): void
    {
        $adminRole = wp_roles()->get_role('administrator');
        if (is_null($adminRole)) {
            return;
        }

        foreach ($this->getCapabilities() as $capability) {
            $adminRole->remove_cap($capability);
        }
    }
}
