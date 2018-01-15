<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 */
namespace Docalist\Forms;

use Docalist\Lookup\LookupManager;

/**
 * Un contrôle qui permet à l'utilisateur de choisir une ou plusieurs valeurs définies dans une table d'autorité.
 *
 * L'implémentation actuelle est basée sur selectize.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class EntryPicker extends Select
{
    public function setOptions($options)
    {
        // Dans Choice, on peut définir les options via un array, un callable ou une chaine de lookup("type:source")
        // Dans un EntryPicker, seul les chaines de lookup sont valides.
        if (is_string($options) && false !== strpos($options, ':')) {
            return parent::setOptions($options);
        }

        return $this->invalidArgument('%s: invalid lookup options, expected string ("type:source")');
    }

    /**
     * Convertit les codes passés en paramètre et détermine le libellé à afficher pour chacun des codes.
     *
     * @param array $data Un tableau de codes (par exemple ['FR', 'DE']).
     *
     * @return array Le tableau converti (par exemple ['FR' => 'France', 'DE' => 'Allemagne']).
     */
    protected function convertCodes($data)
    {
        // Sanity check
        if (empty($data)) {
            return $data;
        }

        // Détermine le type et la source des lookups
        list($type, $source) = explode(':', $this->options, 2);

        // Récupère le service de lookups qui gère les lookups de ce type
        $lookupManager = docalist('lookup'); /** @var LookupManager $lookupManager */
        $lookup = $lookupManager->getLookupService($type);

        // Convertit les données
        return $lookup->convertCodes($data, $source);
    }
}
