<?php declare(strict_types=1);
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Tools\Category;

/**
 * Catégorie "migration / mise à niveau" pour un outil Docalist.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
trait MigrationToolTrait
{
    /**
     * Classe l'outil Docalist dans la catégorie "nettoyage".
     *
     * @return string
     */
    public function getCategory(): string
    {
        return __('Mise à niveau et migration', 'docalist-core');
    }
}
