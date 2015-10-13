<?php

/**
 * This file is part of a "Docalist Core" plugin.
 *
 * Copyright (C) 2012-2015 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Core
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Type\Interfaces;

/**
 * API permettant d'obtenir une représentation textuelle d'un type de données
 * docalist.
 *
 * Tous les types de données docalist peuvent être "dumpés" sous forme de
 * chaine et implémentent la méthode magique __toString() de PHP.
 *
 * Cette interface permet de formaliser le contrat.
 */
interface Stringable
{
    /**
     * Retourne une représentation du type sous forme de chaine de caractères.
     *
     * Remarque : la chaine obtenue est plutôt destinée à du débogage et le
     * format est susceptible de changer.
     *
     * @return string Une représentation textuelle du type.
     */
    public function __toString();
}
