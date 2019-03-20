<?php declare(strict_types=1);
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Forms;

use Docalist\Lookup\LookupManager;

/**
 * Une version améliorée du contrôle Select qui simplifie la sélection d'une ou plusieurs valeurs dans des listes
 * contenant un grand nombre d'éléments.
 *
 * L'implémentation actuelle est basée sur selectize.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class EntryPicker extends Select
{
    /**
     * {@inheritdoc}
     */
    const CSS_CLASS = 'entrypicker';

    /**
     * {@inheritdoc}
     */
    public function setRepeatable($repeatable = true): self
    {
        if ($repeatable) {
            $this->invalidArgument("An EntryPicker can not be repeatable (cloning is not handled)");
        }
        $this->repeatable = $repeatable;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function loadOptions(array $selected = []): array
    {
        // Si le entrypicker ne porte pas sur un lookup, on laisse la classe Select gérer
        if (!is_string($this->options)) {
            return parent::loadOptions($selected);
        }

        // Détermine le type et la source des lookups
        list($type, $source) = explode(':', $this->options, 2);

        // Récupère le service qui gère les lookups de ce type
        $lookupManager = docalist('lookup'); /* @var LookupManager $lookupManager */
        $lookup = $lookupManager->getLookupService($type);

        // Convertit les données
        return $lookup->convertCodes($selected, $source);
    }
}
