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

use Docalist\Forms\Container;

/**
 * Une URL.
 *
 * Exemple : adresse http, adresse e-mail, etc.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class Url extends Text
{
    public function getFormatSettingsForm(): Container
    {
        $form = parent::getFormatSettingsForm();
        $form->checkbox('add-protocol')
             ->setLabel(__('Protocole auto', 'docalist-core'))
             ->setDescription(
                 sprintf(
                     __("Ajoute le préfixe %s ou %s si aucun protocole n'est indiqué dans l'url.", 'docalist-core'),
                     '<code>http://</code>',
                     '<code>mailto:</code>'
                 )
             );

        return $form;
    }

    public function getFormattedValue($options = null): string
    {
        $url = $this->phpValue;
        if ($this->getOption('add-protocol', $options, true)) {
            $url = $this->addProtocol($url);
        }

        return $url;
    }

    /**
     * Ajoute le préfixe 'http://' ou 'mailto:' si aucun protocole n'est indiqué
     * dans l'url passée en paramètre.
     *
     * @param string $url Url à tester.
     *
     * @return string Url corrigée.
     */
    private function addProtocol(string $url): string
    {
        // Adresse e-mail
        if (strpos($url, '@') !== false) {
            substr($url, 0, 7) !== 'mailto:' && $url = 'mailto:' . $url;

            return $url;
        }

        // Url
        !preg_match('~^(?:f|ht)tps?://~i', $url) && $url = 'http://' . $url;

        return $url;
    }

    public function getDefaultEditor(): string
    {
        return 'input-large';
    }
}
