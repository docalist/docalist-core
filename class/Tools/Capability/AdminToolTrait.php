<?php declare(strict_types=1);
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Tools\Capability;

/**
 * Ce trait implémente la méthode getCapability() d'un outil Docalist et requiert la capacité 'manage_options'.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
trait AdminToolTrait
{
    /**
     * Requiert la capacité 'manage_options' pour pouvoir exécuter l'outil.
     *
     * @return string
     */
    public function getCapability(): string
    {
        return 'manage_options';
    }
}
