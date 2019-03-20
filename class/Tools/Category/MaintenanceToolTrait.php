<?php declare(strict_types=1);
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Tools\Category;

/**
 * Catégorie "maintenance" pour un outil Docalist.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
trait MaintenanceToolTrait
{
    /**
     * Classe l'outil Docalist dans la catégorie "maintenance".
     *
     * @return string
     */
    public function getCategory(): string
    {
        return __('Maintenance et nettoyage', 'docalist-core');
    }
}
