<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 */
namespace Docalist\Type;

use Docalist\Type\Text;

/**
 * Un numéro de téléphone.
 */
class PhoneNumber extends Text
{
    /*
     * Evolutions futures :
     *
     * - Pour le moment, un PhoneNumber est juste un champ texte.
     * - Normaliser le stockage des numéros de téléphones (format E164)
     * - Gérer le formattage (ajout ou pas du +33 selon que c'est un numéro à l'étranger ou pas)
     * - Générer un input tél pour la saisie.
     */

    public static function loadSchema()
    {
        return [
            'label' => __('Téléphone', 'docalist-core'),
            'description' => __('Numéro de téléphone.', 'docalist-core'),
        ];
    }
}
