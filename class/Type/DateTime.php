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

namespace Docalist\Type;

use DateTime as PhpDateTime;
use Docalist\Forms\Element;
use Docalist\Forms\Input;
use Exception;

/**
 * Une date/heure stockée sous forme de chaine au format 'yyyy-MM-dd HH:mm:ss'.
 *
 * Exemple : "2014-09-02 11:19:24"
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class DateTime extends Text
{
    public function getAvailableEditors(): array
    {
        return parent::getAvailableEditors() + [
            'datetime-local' => __('Champ date/heure', 'docalist-core'),
            'datetime'       => __('Champ date/heure + fuseau horaire', 'docalist-core'),
        ];
    }

    public function getEditorForm($options = null): Element
    {
        $editor = $this->getOption('editor', $options, $this->getDefaultEditor());
        switch ($editor) {
            case 'datetime':
            case 'datetime-local':
                $type = $editor;
                break;

            default:
                return parent::getEditorForm($options);
        }

        $form = new Input();
        $form->setAttribute('type', $type);

        return $this->configureEditorForm($form, $options);
    }

    public function getAvailableFormats(): array
    {
        // Formats dispos
        $formats = [
            'd/m/Y',    // 04/09/2017
            'd-m-Y',    // 04-09-2017

            'd/m/y',    // 04/09/17
            'd-m-y',    // 04-09-17

            'Y-m-d',    // 2017-09-04

            'j M Y',    // 4 sep 2017
            'j F Y',    // 4 septembre 2017
        ];

        // Au lieu d'afficher un libellé, on affiche des exemples (année en cours avec un jour et un mois < 10)
        $date = PhpDateTime::createFromFormat('d/m', '4/7'); // 4 juillet de l'année en cours
        assert(false !== $date, 'createFromFormat() ne peut pas retourner false comme on lui passe une date valide');

        $examples = [];
        foreach ($formats as $format) {
            $examples[$format] = date_i18n($format, $date->getTimestamp());
        }

        // Ok
        return $examples;
    }

    public function getFormattedValue($options = null): string
    {
        $format = $this->getOption('format', $options, $this->getDefaultFormat());
        // à gérer : si format nommé (raw, text...)
        // utiliser date_i18n() ?

        // Essaie de convertir la date au format texte en objet DateTime
        try {
            $dateTime = new PhpDateTime($this->phpValue);
        }

        // En cas d'erreur (syntaxe incorrecte, etc.), on retourne la valeur "brute"
        catch (Exception $e) {
            return $this->phpValue;
        }

        // Récupère le timestamp correspondant et demande à WordPress de formatter
        return date_i18n($format, $dateTime->getTimestamp());
    }
}
