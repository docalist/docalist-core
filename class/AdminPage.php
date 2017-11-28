<?php
/**
 * This file is part of the "Docalist Core" plugin.
 *
 * Copyright (C) 2012-2017 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Core
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist;

use Docalist\Http\Response;
use Docalist\Http\TextResponse;
use Docalist\Http\CallbackResponse;
use ReflectionObject;
use ReflectionMethod;
use InvalidArgumentException;

/**
 * Une page d'administration dans le back-office.
 */
abstract class AdminPage extends Controller
{
    /**
     * {@inheritdoc}
     */
    protected $controllerParameter = 'page';

    /**
     * {@inheritdoc}
     */
    protected $menuTitle;

    /**
     * @param string $id Identifiant unique de la page.
     * @param string $parentPage Url de la page parent.
     * @param string $menuTitle Libellé de la page utilisé dans le menu.
     */
    public function __construct($id, $parentPage = '', $menuTitle = '')
    {
        $this->menuTitle = $menuTitle ?: $id;
        parent::__construct($id, $parentPage);
    }

    /**
     * Retourne le titre de la page affiché dans le menu.
     *
     * @return string
     */
    protected function menuTitle()
    {
        return $this->menuTitle;
    }

    /**
     * {@inheritdoc}
     */
    protected function register()
    {
        // On ne fait rien si l'utilisateur n'a pas les droits requis
        if (! $this->canRun()) {
            return;
        }

        /*
         * Remarque sur le titre de la page :
         * add_menu_page() et add_submenu_page() prennent en paramètre un
         * argument pageTitle qui est utilisé pour générer le titre qui figure
         * dans la balise <title> de la page.
         * Dans notre cas, ce titre n'est pas utilisé : comme la majorité des
         * pages sont dynamiques et incluent plusieurs actions, cela ne suffit
         * pas d'avoir un titre statique qui sera le même pour toutes les
         * actions.
         * A la place, nous générons dynamiquement le titre (quand la réponse
         * retournée est une adminPage) en récupérant le premier <h1>
         * généré par l'action (cf. plus bas add_filter 'admin_title').
         * Si jamais la page n'a pas généré de h1, il faut quand même avoir un
         * titre. Dans ce cas, c'est le libellé utilisé pour le menu qui sera
         * utilisé : c'est ce qu'on passe en paramètre à add_(sub)_menu_page().
         */

        // Crée la page dans le menu WordPress
        $parent = $this->getParentPage();
        $title = $this->menuTitle();
        $capability = $this->getCapability();
        if (empty($parent)) {
            $page = add_menu_page($title, $title, $capability, $this->getID(), function () {
            });
        } else {
            $page = add_submenu_page($parent, $title, $title, $capability, $this->getID(), function () {
            });
        }

        /*
            Exécute l'action et affiche le résultat.

            C'est wp-admin/admin.php:145 qui appelle le hook "load-$page"
            A ce stade, rien n'a été envoyé au navigateur, même pas les entêtes.
            On exécute l'action demandée (run) et on examine le type de
            la réponse générée par l'action.

            Cas 1. Si la réponse est une page d'admin (i.e. une réponse qui a
            adminPage === true), on se contente d'envoyer les entêtes http et
            on exécute la vue en bufferisant la sortie générée ($body).
            La vue peut ainsi faire des appels à wp_enqueue_*, génèrer des
            écrans d'aide, etc.

            Wordpress poursuit alors son exécution : il inclut admin-header.php
            qui génère les menus, l'admin bar, les screen metas, etc.

            Wordpress va ensuite appeller le hook de la page ("$page"). A ce
            stade, tout le début de la page html a été généré (on est dans
            html>body>wpwrap>wpcontent>wpbody-content) et les scripts et les
            css qui ont été ajoutés ont été générés (ceux de la partie head).

            On se contente alors d'afficher le body de la réponse qu'on avait
            bufferisé plus haut.
            Wordpress termine ensuite son exécution, génère les assets de footer
            et se termine.

            Cas 2. Si la réponse n'est pas une page d'admin (redirection,
            réponse JSON, etc.), on l'envoit directement au navigateur pendant
            le hook "load-$page" et on fait ensuite exit(), ce qui empêche
            Wordpress de continuer son exécution. Dans ce cas, seul le contenu
            de la réponse est envoyé au navigateur (pas de menu wp, etc.)
        */
        add_action("load-$page", function () use ($page) {
            // Indique à l'écran en cours qui est le parent de notre page
            // Normallement, c'est admin-header.php:119 qui fait ça, mais
            // dans notre cas il n'a pas encore été appellé. Ca pose
            // problème car dans ce cas, les vues qui appellent
            // screen_icon() ne récupèrent pas la bonne icone (wp 3.6).
            get_current_screen()->set_parentage($this->getParentPage());

            // Exécute l'action, récupère la réponse générée et le garbage éventuel
            ob_start();
            $response = $this->run();
            $garbage = ob_get_clean();

            // Erreur : l'action n'a pas retourné de réponse
            if (!($response instanceof Response)) {
                add_action($page, function () use ($response, $garbage) {
                    // En mode debug, on signale l'erreur
                    if (WP_DEBUG) {
                        $h3 = __("Erreur dans l'action %s", 'docalist-core');
                        $h3 = sprintf($h3, $this->getAction());

                        $msg = __('La méthode <code>%s()</code> a retourné :<pre>%s</pre>', 'docalist-core');
                        $msg = sprintf($msg, $this->getMethod(), var_export($response, true));
                        printf('<div class="error"><h3>%s</h3><p>%s</p></div>', $h3, $msg);
                    }

                    // Affiche ce qui a été généré lors de l'exécution
                    echo $garbage;
                });
            }

            // L'action a généré une réponse de type "page d'admin"
            elseif ($response instanceof TextResponse && $response->adminPage()) {
                // Envoie les entêtes de la réponse
                // wp ne pourra pas envoyer les siens (cf. admin-header.php)
                $response->sendHeaders();

                // Génère la réponse, mais sans l'envoyer
                // Permet à la vue de faire des "enqueue" et autres
                ob_start();
                $response->sendContent();
                $body = ob_get_clean();

                // Récupère le titre (h1) de la page et le fournit à wp pour
                // qu'on ait le bon titre dans la balise <title> de la page
                $matches = null; // évite warning "variable not initialized"
                if (preg_match('~<h1>(.*?)</h1>~', $body, $matches)) {
                    $title = strip_tags($matches[1]);
                    // @see admin-header.php:36
                    add_filter('admin_title', function () use ($title) {
                        return $title;
                    });
                }

                // Affiche la réponse après que wp a généré le header et les menus
                add_action($page, function () use ($body, $garbage) {
                    // Si on a du garbage, on le signale en mode WP_DEBUG
                    if ($garbage && WP_DEBUG) {
                        $h3 = __("Garbage dans l'action %s", 'docalist-core');
                        $h3 = sprintf($h3, $this->getAction());

                        $msg = __('La méthode <code>%s()</code> a généré :<pre>%s</pre>', 'docalist-core');
                        $msg = sprintf($msg, $this->getMethod(), $garbage);
                        printf('<div class="error"><h3>%s</h3><p>%s</p></div>', $h3, $msg);
                    }

                    // Affiche la réponse générée
                    echo $body;
                });

                // Laisse wp générer le footer
            } elseif ($response instanceof CallbackResponse && $response->adminPage()) {
                // contrairement au cas précédent, pas de bufferisation (exemple réindexation)
                // on ne peut pas faire des enqueue (il faut le faire avant de retourner l'objet Response)
                // et on ne peut pas mettre le titre exact dans la balise <title>
                $response->sendHeaders();
                add_action($page, function () use ($response) {
                    $response->sendContent();
                });
            }

            // L'action a généré un autre type de réponse (redirect, json...)
            else {
                // Génère et envoie la réponse
                $response->send();

                // Stoppe l'exécution de wp (ni header, ni menu, ni footer)
                exit();
            }
        });
    }

    protected function view($view, array $viewArgs = [], $status = 200, $headers = [])
    {
        return parent::view($view, $viewArgs, $status, $headers)->adminPage(true);
    }

    /**
     * Retourne une ViewResponse demandant à l'utilisateur de confirmer son
     * action.
     *
     * Le lien généré par le bouton "OK" rappelle la même url en ajoutant
     * 'confirm=1" dans les paramètres de la query string.
     *
     * @param string $message Le message à afficher.
     * @param string $title Le titre (optionnel).
     *
     * @return ViewResponse
     */
    protected function confirm($message = null, $title = null)
    {
        return $this->view(
            'docalist-core:confirm',
            ['h2' => $title, 'message' => $message]
        );
    }

    /**
     * Retourne une ViewResponse affichant un message d'information.
     *
     * Le lien généré par le bouton "Annuler" fait un history.back().
     *
     * @param string $message Le message à afficher.
     * @param string $title Le titre (optionnel).
     *
     * @return ViewResponse
     */
    protected function info($message = null, $title = null)
    {
        return $this->view(
            'docalist-core:info',
            ['h2' => $title, 'message' => $message]
        );
    }

    /**
     * Retourne une ViewResponse affichant un message d'erreur.
     *
     * Le lien généré par le bouton "Annuler" fait un history.back().
     *
     * @param string $message Le message à afficher.
     * @param string $title Le titre (optionnel).
     *
     * @return ViewResponse
     */
    protected function error($message = null, $title = null)
    {
        return $this->view(
            'docalist-core:error',
            ['h2' => $title, 'message' => $message]
        );
    }

    protected function getDefaultAction()
    {
        return 'Index';
    }

    /**
     * Liste des outils disponibles.
     *
     * Liste toutes les actions publiques du module.
     *
     * i.e. les méthodes publiques, dont le nom commence par le préfixe
     * "action", et qui peuvent être appellées sans paramètres (aucun
     * paramètre ou paramètres ayant une valeur par défaut).
     */
    public function actionIndex()
    {
        return $this->view(
            'docalist-core:controller/actions-list',
            [
                'title' => $this->menuTitle(),
                'actions' => $this->getCallableActions(),
            ]
        );
    }

    /**
     * Retourne la liste des actions que l'utilisateur peut invoquer.
     *
     * La méthode retourne les actions qui peuvent être appellées sans paramètre obligatoire et pour lesquelles
     * l'utilisateur dispose droits requis. L'action par défaut n'est pas incluse dans la liste.
     *
     * @return string[]
     */
    protected function getCallableActions()
    {
        $actions = [];
        $default = $this->getDefaultAction();
        $controller = new ReflectionObject($this);
        foreach ($controller->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            // Ignore les méthodes qui ne sont pas des actions
            try {
                $action = $this->getAction($method->getName());
            } catch (InvalidArgumentException $e) {
                continue;
            }

            // Ignore l'action par défaut, celles qui demandent des droits et qui ont des paramètres obligatoires
            if ($action === $default || !$this->canRun($action) || $method->getNumberOfRequiredParameters() > 0) {
                continue;
            }

            // Stocke le nom de la méthode
            $actions[] = $action;
        }

        return $actions;
    }
}
