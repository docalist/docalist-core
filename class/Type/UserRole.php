<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Type;

use Docalist\Type\ListEntry;

/**
 * Un champ texte permettant de sélectionner un rôle WordPress : administrator, editor, subscriber...
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class UserRole extends ListEntry
{
    public static function loadSchema(): array
    {
        return [
            'label' => __('Rôle WordPress', 'docalist-core'),
        ];
    }

    /**
     * Retourne la liste des rôles WordPress disponibles.
     *
     * @return array Un tableau de la forme [Nom du rôle => Libellé du rôle]
     */
    protected function getEntries(): array
    {
        static $roles = null;

        is_null($roles) && $roles = array_map('translate_user_role', wp_roles()->get_names());

        return $roles;
    }

    public function getDefaultEditor(): string
    {
        return 'list-inline';
    }
}
