<?php
/**
 * This file is part of the "Docalist Core" plugin.
 *
 * Copyright (C) 2012-2015 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Core
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 * @version     SVN: $Id$
 */

namespace Docalist;

use InvalidArgumentException;

/**
 * Service "admin notices".
 *
 * Permet de générer facilement des messages qui seront affichés dans le back
 * office de WordPress :
 * - info : message d'information générique (en bleu)
 * - success : message de confirmation, opération réussie (en vert)
 * - warning : message d'avertissement, confirmation, etc. (en orange)
 * - error : message d'erreur, opération qui a échouée, etc. (en rouge).
 */
class AdminNotices {
    /**
     * Liste des notices enregistrées.
     *
     * @var array[] Un tableau de tableau : chaque notice du tableau contient
     * les éléments 'type', 'content' et 'title'.
     */
    protected $notices;

    // https://core.trac.wordpress.org/ticket/27418
    // https://core.trac.wordpress.org/ticket/31233

    /**
     * Retourne le nombre de notices qui ont été enregistrées.
     *
     * @return int
     */
    public function count() {
        return count($this->notices);
    }

    /**
     * Enregistre une notice de type "info".
     *
     * @param string|closure $content Le contenu de la notice.
     * @param string|closure|null $title Optionnel, le titre de la notice.
     *
     * @return self
     */
    public function info($content, $title = null) {
        return $this->add('info', $content, $title);
    }

    /**
     * Enregistre une notice de type "success".
     *
     * @param string|closure $content Le contenu de la notice.
     * @param string|closure|null $title Optionnel, le titre de la notice.
     *
     * @return self
     */
    public function success($content, $title = null) {
        return $this->add('success', $content, $title);
    }

    /**
     * Enregistre une notice de type "warning".
     *
     * @param string|closure $content Le contenu de la notice.
     * @param string|closure|null $title Optionnel, le titre de la notice.
     *
     * @return self
     */
    public function warning($content, $title = null) {
        return $this->add('warning', $content, $title);
    }

    /**
     * Enregistre une notice de type "error".
     *
     * @param string|closure $content Le contenu de la notice.
     * @param string|closure|null $title Optionnel, le titre de la notice.
     *
     * @return self
     */
    public function error($content, $title = null) {
        return $this->add('error', $content, $title);
    }

    /**
     * Enregistre une notice.
     *
     * @param string $type Type de la notice : 'info', 'succcess', 'warning' ou
     * 'error'.
     * @param string|closure $content Le contenu de la notice.
     * @param string|closure|null $title Optionnel, le titre de la notice.
     *
     * @return self
     */
    public function add($type, $content, $title = null) {
        // Initialisation au premier appel
        if (is_null($this->notices)) {
            $this->notices = [];
            add_action('admin_notices', [$this, 'render']);
        }

        // Stocke la notice
        $this->notices[] = [$type, $content, $title];

        // Ok
        return $this;
    }

    /**
     * Affiche les notices qui ont été enregistrées.
     *
     * @return self
     */
    public function render() {
        // Affiche les notices dans l'ordre où elles ont été ajoutées
        foreach($this->notices as $notice) {
            list($type, $content, $title) = $notice;

            printf('<div class="notice notice-%s">', $type);

            // Titre de la notice (<h3>)
            if ($title) {
                echo '<h3>';
                is_callable($title) ? $title() : print($title);
                echo '</h3>';
            }

            // Contenu de la notice (<p>)
            echo '<p>';
            is_callable($content) ? $content() : print($content);
            echo '</p>';

            echo '</div>';
        }

        // Réinitialise la liste des notices enregistrées
        $this->notices = [];

        // Ok
        return $this;
    }
}