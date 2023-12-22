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

namespace Docalist;

/**
 * Service "admin notices".
 *
 * Permet de générer facilement des messages qui seront affichés dans le back office de WordPress :
 * - info : message d'information générique (en bleu)
 * - success : message de confirmation, opération réussie (en vert)
 * - warning : message d'avertissement, confirmation, etc. (en orange)
 * - error : message d'erreur, opération qui a échouée, etc. (en rouge).
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class AdminNotices
{
    /*
     * Principes :
     * - Le service admin-notices n'est disponible que dans le back-office de
     *   Wordpress (on doit avoir un utilisateur connecté).
     * - Lorsqu'une notice est ajoutée, elle est stockée dans un meta de
     *   l'utilisateur en cours.
     * - Si l'action admin_notices est appelée, les notices sont affichées et
     *   le meta est supprimé.
     * - La méthode principale pour ajouter une notice est "add" mais il y a
     *   plusieurs helpers disponibles (info, success, warning, error).
     * - Chaque notice a un contenu et peut avoir un titre.
     */
    /**
     * Nom du meta utilisateur qui contient les notices.
     *
     * @var string
     */
    const META = 'docalist-admin-notice';

    // https://core.trac.wordpress.org/ticket/27418
    // https://core.trac.wordpress.org/ticket/31233

    /**
     * Crée le service admin-notices.
     */
    public function initialize()
    {
        add_action('admin_notices', function () {
            $this->render();
        });
    }

    /**
     * Enregistre une notice.
     *
     * @param string        $type       Type de la notice : 'info', 'succcess', 'warning' ou 'error'.
     * @param string        $content    Le contenu de la notice.
     * @param string|null   $title      Optionnel, le titre de la notice.
     */
    public function add(string $type, string $content, string $title = null): void
    {
        // Inutile de tester si on a un user, add_user_meta vérifie que l'id passé n'est pas vide
        add_user_meta(get_current_user_id(), self::META, [$type, $content, $title], false);
    }

    /**
     * Enregistre une notice de type "info".
     *
     * @param string        $content    Le contenu de la notice.
     * @param string|null   $title      Optionnel, le titre de la notice.
     */
    public function info(string $content, string $title = null): void
    {
        $this->add('info', $content, $title);
    }

    /**
     * Enregistre une notice de type "success".
     *
     * @param string        $content    Le contenu de la notice.
     * @param string|null   $title      Optionnel, le titre de la notice.
     */
    public function success(string $content, string $title = null): void
    {
        $this->add('success', $content, $title);
    }

    /**
     * Enregistre une notice de type "warning".
     *
     * @param string        $content    Le contenu de la notice.
     * @param string|null   $title      Optionnel, le titre de la notice.
     */
    public function warning(string $content, string $title = null): void
    {
        $this->add('warning', $content, $title);
    }

    /**
     * Enregistre une notice de type "error".
     *
     * @param string        $content    Le contenu de la notice.
     * @param string|null   $title      Optionnel, le titre de la notice.
     */
    public function error(string $content, string $title = null): void
    {
        $this->add('error', $content, $title);
    }

    /**
     * Affiche les notices qui ont été enregistrées.
     */
    protected function render(): void
    {
        // Si on n'a pas de user en cours, on ne peut rien faire
        if (0 === $user = get_current_user_id()) {
            return;
        }

        // Charge les notices enregistrées
        $notices = get_user_meta($user, self::META, false);
        if (empty($notices)) {
            return;
        }
        /** @var array<array<int,string>> $notices */

        // Affiche les notices dans l'ordre où elles ont été ajoutées
        foreach ($notices as $notice) {
            list($type, $content, $title) = $notice;

            printf(
                '<div class="notice notice-%s is-dismissible">%s<p>%s</p></div>',
                $type,
                empty($title) ? '' : '<h3>' . $title . '</h3>',
                $content
            );
        }

        // Réinitialise la liste des notices enregistrées
        delete_user_meta($user, self::META);
    }
}
