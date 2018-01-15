<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 */
namespace Docalist;

use Docalist\Http\Response;
use Docalist\Http\ViewResponse;
use Docalist\Http\RedirectResponse;
use Docalist\Http\JsonResponse;
use ReflectionObject;
use ReflectionMethod;
use Exception;

/**
 * Un contrôleur permet de regrouper plusieurs actions ensemble et d'y accéder à partir d'une url.
 *
 * Chaque objet contrôleur a un identifiant unique (id) et les actions sont les méthodes (public ou protected)
 * de cet objet ayant le prefixe 'action'.
 *
 * Lorsque l'utilisateur appelle l'url du contrôleur, la méthode correspondante est appellée en passant en
 * paramètre les arguments fournis dans la requête.
 *
 * L'action doit retourner un objet Response qui est envoyé au navigateur.
 *
 * Par défaut, la classe Controller utilise le "generic post handler" mis en place dans WordPress 2.6 et
 * utilise le fichier wp-admin/admin.php comme point d'entrée mais les classes descendantes peuvent changer ça
 * (par exemple, la classe AdminPage utilise les entrées de menu comme point d'entrée).
 *
 * Exemple : Si un contrôleur avec l'ID 'test-ctrl' contient une méthode :
 *
 * <code>
 *     public function actionHello($name = 'world') {
 *         return $this->view('my-plugin:hello.php', ['name' => $name]);
 *     }
 * </code>
 *
 * on pourra accéder à cette action (si on les droits requis) avec l'url :
 *
 * <code>
 *     wordpress/wp-admin/admin.php?action=test-ctrl&m=Hello&name=guest
 *
 *     // ou bien, en utilisant la valeur par défaut des paramètres :
 *     wordpress/wp-admin/admin.php?action=test-ctrl&m=Hello
 * </code>
 *
 * Inversement, il est possible de générer un lien vers cette action avec :
 *
 * <code>
 *     $this->url('Hello', 'guess');
 *     // ou
 *     $this->url('Hello', ['name' => 'guess']);
 * </code>
 *
 * @see
 * - http://core.trac.wordpress.org/ticket/7283
 * - http://core.trac.wordpress.org/changeset/8315
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
abstract class Controller
{
    /**
     * Préfixe utilisé pour les noms de méthodes qui implémentent des actions.
     *
     * @var string
     */
    const ACTION_PREFIX = 'action';

    /**
     * Identifiant unique de ce contrôleur.
     *
     * @var string
     */
    protected $id;

    /**
     * Nom de la page qui sert de point d'entrée pour exécuter les actions de ce contrôleur.
     *
     * Par défaut, il s'agit de 'admin.php'.
     *
     * Pour une page d'admin ajoutée dans un sous menu, il s'agit de la page du menu ('options-general.php' pour
     * une page de réglages, 'edit.php?post_type=post' pour une page dans le menu Articles, 'admin-ajax.php' pour
     * un contrôleur ajax, etc.)
     *
     * @var string
     */
    protected $parentPage;

    /**
     * Nom du paramètre passé en query string qui contient l'ID du controlleur.
     *
     * Par défaut, c'est 'action' (c'est ce qu'attend le script wordpress admin.php) mais pour une page
     * ajoutée dans un menu, c'est 'page'.
     *
     * Les urls générées tiennent compte de ce paramètre. Exemples :
     *
     * - wordpress/wp-admin/admin.php?action=docalist-search-actions
     * - wordpress/wp-admin/options-general.php?page=docalist-biblio-settings
     *
     * @var string
     */
    protected $controllerParameter = 'action';

    /**
     * Nom du paramètre passé en query string qui indique l'action à exécuter.
     *
     * Paramétrable au cas où on ait un jour un conflit de nom avec des arguments utilisés par wordpress.
     *
     * @var string
     */
    protected $actionParameter = 'm'; // m comme "method"

    /**
     * Définit les droits requis pour exécuter les actions de ce contrôleur.
     *
     * Le tableau est de la forme "nom de l'action" => "capacité requise".
     *
     * Chacune des clés identifie l'une des actions du contrôleur (il faut respecter la casse exacte du nom
     * de la méthode correspondante).
     *
     * La clé "default" indique la capacité requise ('manage_options' par défaut) pour que le contrôleur soit
     * visible (par exemple, une page d'admin n'apparaîtra pas dans le menu si l'utilisateur n'a pas ce droit).
     *
     * Elle sert également de capacité par défaut pour les actions qui ne figurent pas dans le tableau.
     *
     * @var array
     */
    protected $capability = [];

    /**
     * Initialise le contrôleur.
     *
     * @param string $id            Identifiant unique du contrôleur.
     * @param string $parentPage    Url de la page parent.
     */
    public function __construct($id, $parentPage = 'admin.php')
    {
        $this->id = $id;
        $this->parentPage = $parentPage;
        $this->register();
    }

    /**
     * Enregistre le contrôleur dans Wordpress.
     */
    protected function register()
    {
        if ($this->canRun()) {
            add_action('admin_action_' . $this->getID(), function () {
                $this->run()->send();
                exit();
            });
        }
    }

    /**
     * Retourne l'identifiant du contrôleur.
     *
     * @return string
     */
    protected function getID()
    {
        return $this->id;
    }

    /**
     * Retourne le nom de la page parent.
     *
     * @return string
     */
    protected function getParentPage()
    {
        return $this->parentPage;
    }

    /**
     * Retourne le nom de l'action par défaut de ce contrôleur.
     *
     * Il s'agit de l'action qui sera exécutée si aucune action n'est indiquée dans les paramètres de la requête.
     *
     * @return string
     */
    abstract protected function getDefaultAction();

    /**
     * Retourne le nom de la méthode correspondant à l'action passée en paramètre ou à l'action en cours.
     *
     * La méthode se contente d'ajouter le préfixe 'action' au nom de l'action, elle ne vérifie pas que l'action
     * demandée existe ou peut être appellée.
     *
     * @param string $action Optionnel, nom de l'action, utilise l'action en cours si absent.
     *
     * @return string
     */
    protected function getMethod($action = '')
    {
        if (empty($action)) {
            $arg = $this->actionParameter;

            $action = empty($_REQUEST[$arg]) ? $this->getDefaultAction() : $_REQUEST[$arg];
        }

        return self::ACTION_PREFIX . ucfirst($action);
    }

    /**
     * Retourne le nom de l'action correspondant au nom de méthode passé en paramètre ou de la méthode en cours.
     *
     * La méthode se contente de supprimer le préfixe 'action' du nom de la méthode, elle ne vérifie pas que la
     * méthode indiquée existe ou peut être appellée.
     *
     * @param string $method Nom de la méthode
     *
     * @return string Nom de l'action correspondant à la méthode ou une chaine vide si le nom de méthode ne
     * commence pas par le préfixe 'action'.
     */
    protected function getAction($method = '')
    {
        empty($method) && $method = $this->getMethod();

        $prefixLength = strlen(self::ACTION_PREFIX);
        if (strncmp($method, self::ACTION_PREFIX, $prefixLength) !== 0) {
            return '';
        }

        return substr($method, $prefixLength);
    }

    /**
     * Retourne la capacité WordPress requise pour exécuter l'action passée en paramètre.
     *
     * Si aucune action n'est passée en paramètre, la méthode teste la capacité par défaut du contrôleur.
     *
     * @param string $action Nom de l'action à tester.
     *
     * @return string
     */
    protected function getCapability($action = '')
    {
        // Teste si une capacité spécifique a été définie pour l'action
        if (!empty($action) && !empty($this->capability[$action])) {
            return $this->capability[$action];
        }

        // Teste la capacité par défaut
        if (!empty($this->capability['default'])) {
            return $this->capability['default'];
        }

        // Admin only
        return 'manage_options';
    }


    /**
     * Teste si l'utilisateur en cours dispose de la capacité WordPress requise pour exécuter l'action indiquée.
     *
     * Si aucune action n'est passée en paramètre, la méthode teste la capacité par défaut du contrôleur.
     *
     * @param string $action Nom de l'action à tester.
     *
     * @return bool
     */
    protected function canRun($action = '')
    {
        return current_user_can($this->getCapability($action));
    }

    /**
     * Exécution l'action en cours.
     *
     * @return Response La réponse retournée par l'action.
     */
    protected function run()
    {
        // Détermine la méthode à appeller
        $name = $this->getMethod();

        // Vérifie que la méthode demandée existe
        $controller = new ReflectionObject($this);
        if (!$controller->hasMethod($name)) {
            return $this->view(
                'docalist-core:controller/action-not-found',
                ['action' => $this->getAction()],
                404
            );
        }

        // Vérifie qu'on peut l'appeller
        $method = $controller->getMethod($name);
        if ($method->isStatic() || !$method->isPublic()) {
            return $this->view(
                'docalist-core:controller/bad-request',
                ['message' => sprintf('<code>%s()</code> is static or not public.', $name)],
                400
            );
        }

        // Récupère la casse exacte de l'action
        // C'est important car sinon on pourrait court-circuiter les droits en changeant la casse de l'action
        // en query string : comme les méthodes php sont insensibles à la casse, cela marcherait.
        $action = $this->getAction($method->getName());

        // Vérifie que l'utilisateur a les droits requis pour exécuter l'action
        if (! $this->canRun($action)) {
            return $this->view(
                'docalist-core:controller/access-denied',
                ['action' => $action],
                403 // 401 ou 403 ? @see http://stackoverflow.com/a/8469124
            );
        }

        // Construit la liste des paramètres
        try {
            $parameters = $this->runParameters($method, $_REQUEST, false, true);
        } catch (Exception $e) {
            return $this->view(
                'docalist-core:controller/bad-request',
                ['message' => $e->getMessage()],
                400
            );
        }

        // Appelle la méthode avec la liste d'arguments obtenue
        return $method->invokeArgs($this, $parameters);
    }

    /**
     * Retourne un tableau contenant les paramètres nécessaires pour exécuter la méthode indiquée.
     *
     * @param ReflectionMethod $method Méthode à appeler.
     * @param array $args Paramètres fournis.
     *
     * @return array Un tableau contenant les paramètres fournis et les paramètres par défaut de la méthode.
     *
     * @throws Exception Si un paramètre obligatoire manque (paramètre non fourni pour lequel la méthode ne
     * propose pas de valeur par défaut).
     */
    private function runParameters(ReflectionMethod $method, array $args)
    {
        $result = [];
        foreach ($method->getParameters() as $parameter) {
            // Récupère le nom du paramètre
            $name = $parameter->getName();

            // Paramètre fourni
            if (isset($args[$name])) {
                // Récupère la valeur fournie
                $value = $args[$name];

                // Si la méthode attend un tableau, caste en array
                $parameter->isArray() && !is_array($value) && $value = [$value];

                // Tout est ok
                $result[$name] = $value;
                continue;
            }

            // Paramètre non fourni : génère une exception s'il n'a pas de valeur par défaut
            if (!$parameter->isDefaultValueAvailable()) {
                throw new Exception(sprintf('Required parameter "%s" is missing.', $name));
            }

            // Utilise la valeur par défaut du paramètre
            $result[$name] = $parameter->getDefaultValue();
        }

        return $result;
    }

    /**
     * Retourne l'url à utiliser pour appeller une action de ce contrôleur.
     *
     * Exemples :
     *
     * - getUrl() : retourne l'url à utiliser pour l'action en cours.
     * - getUrl('Action') : url d'une action sans paramètres ou dont tous les paramètres ont une valeur par défaut.
     * - getUrl('Action', ['arg1' => 1, 'arg2' => 2]) : les paramètres sont passés dans un tableau
     * - getUrl('Action', 'arg1', 'arg2') : les paramètres sont dans l'ordre attendu par l'action.
     *
     * @param string $action Nom de l'action. Les paramètres supplémentaires passés à la méthode sont ajoutés à l'url.
     *
     * @return string
     *
     * @throws Exception
     *
     * - si l'action indiquée n'existe pas
     * - si la méthode qui implémente l'action est 'private' ou 'static'
     * - si l'utilistateur en cours n'a pas les droits suffisants
     * - si un paramètre est obligatoire mais n'a pas été fourni
     * - s'il y a trop de paramètres fournis ou des paramètres qui ne sont pas
     *   dans la signature de la méthode de l'action.
     */
    protected function url($action = null)
    {
        // _deprecated_function(__METHOD__, '0.14', 'getUrl');
        return call_user_func_array([$this, 'getUrl'], func_get_args());
    }

    protected function getUrl($action = '')
    {
        // Récupère le nom de la méthode à utiliser
        $name = $this->getMethod($action);

        // Vérifie que la méthode existe
        $class = new ReflectionObject($this);
        if (!$class->hasMethod($name)) {
            $msg = __("L'action %s n'existe pas", 'docalist-biblio');
            throw new Exception(sprintf($msg, $action));
        }

        // Vérifie qu'on peut appeller cette méthode
        $method = $class->getMethod($name);
        if ($method->isPrivate() || $method->isStatic()) {
            $msg = __('La méthode %s est "%s".', 'docalist-core');
            $msg = sprintf($msg, $name, $method->isPrivate() ? 'private' : 'static');
            throw new Exception(sprintf($msg, $name));
        }

        // Récupère la casse exacte de l'action
        $action = $this->getAction($method->getName());

        // Vérifie que l'utilisateur a les droits requis pour l'action
        if (! $this->canRun($action)) {
            $msg = __("Vous n'avez pas les droits requis pour faire un lien vers l'action %s.", 'docalist-biblio');
            throw new Exception($msg, $action);
        }

        // Construit la liste des paramètres de la méthode
        $args = func_get_args();
        array_shift($args);
        count($args) === 1 && is_array($args[0]) && $args = $args[0]; // // Appel de la forme "action, array(args)"
        $args = $this->urlParameters($method, $args);

        // Ajoute les paramètres du contrôleur
        $t = [$this->controllerParameter => $this->getID()];
        $action !== $this->getDefaultAction() && $t[$this->actionParameter] = $action;
        $args = $t + $args;

        // Retourne l'url
        return add_query_arg($args, admin_url($this->getParentPage()));
    }

    protected function urlParameters(ReflectionMethod $method, array $args, $checkMax = TRUE, $checkMin = FALSE)
    {
        $result = [];
        $params = $method->getParameters();
        foreach ($params as $i => $param) {
            // Récupère le nom du paramètre
            $name = $param->getName();

            // Le paramètre peut être fourni soit par nom, soit par numéro
            $key = isset($args[$name]) ? $name : $i;

            // Le paramètre a été fourni
            if (isset($args[$key])) {
                // Récupère la valeur fournie
                $value = $args[$key];

                // Si la méthode attend un tableau, caste en array
                $param->isArray() && !is_array($value) && $value = [$value];

                // Tout est ok
                $result[$name] = $value;
                unset($args[$key]);
                continue;
            }

            // Paramètre non fourni : génère une exception s'il n'a pas de valeur par défaut
            if (!$param->isDefaultValueAvailable()) {
                throw new Exception(sprintf('Required parameter "%s" is missing.', $name));
            }
        }

        // Génère une exception si on a passé trop de paramètres
        if (count($args)) {
            throw new Exception(sprintf('Too many parameters (%s)', implode(', ', array_keys($args))));
        }

        return $result;
    }
    /**
     * Retourne l'url de base du contrôleur.
     *
     * @return string
     */
    protected function baseUrl()
    {
        return add_query_arg(
            [$this->controllerParameter => $this->getID()],
            admin_url($this->getParentPage())
        );
    }

    /**
     * Indique si la requête en cours est une requête POST.
     *
     * @return bool
     */
    protected function isPost()
    {
        return $_SERVER['REQUEST_METHOD'] == 'POST';
    }

    /**
     * Indique si la requête en cours est une requête GET.
     *
     * @return bool
     */
    protected function isGet()
    {
        return $_SERVER['REQUEST_METHOD'] == 'GET';
    }

    /**
     * Retourne une réponse de type ViewResponse.
     *
     * @param string    $view
     * @param array     $viewArgs
     * @param int       $status
     * @param array     $headers
     *
     * @return ViewResponse
     */
    protected function view($view, array $viewArgs = [], $status = 200, $headers = [])
    {
        !isset($viewArgs['this']) && $viewArgs['this'] = $this;

        return new ViewResponse($view, $viewArgs, $status, $headers);
    }

    /**
     * Retourne une réponse de type RedirectResponse.
     *
     * @param string    $url
     * @param int       $status
     * @param array     $headers
     *
     * @return RedirectResponse
     */
    protected function redirect($url, $status = 302, $headers = [])
    {
        return new RedirectResponse($url, $status, $headers);
    }

    /**
     * Retourne une réponse de type JsonResponse.
     *
     * @param mixed $content
     * @param int   $status
     * @param array $headers
     *
     * @return JsonResponse
     */
    protected function json($content = '', $status = 200, $headers = [])
    {
        return new JsonResponse($content, $status, $headers);
    }
}
