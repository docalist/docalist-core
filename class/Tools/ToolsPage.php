<?php declare(strict_types=1);
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Tools;

use Docalist\AdminPage;
use Docalist\Http\CallbackResponse;
use Docalist\Http\Response;
use Docalist\Tools\Tool;
use Docalist\Tools\Tools;
use InvalidArgumentException;
use Docalist\Tools\ToolsList;
use Docalist\Views;

/**
 * Page d'administration WordPress "Outils Docalist".
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class ToolsPage extends AdminPage
{
    /**
     * La liste des outils disponibles.
     *
     * @var Tools
     */
    private $tools;

    /**
     * Crée la page "Outils Docalist" dans le menu WordPress.
     */
    public static function setup(): void
    {
        add_action('admin_menu', function () {
            // Ajoute notre répertoire "views" au service "docalist-views"
            add_filter('docalist_service_views', function (Views $views) {
                return $views->addDirectory('docalist-tools', __DIR__ . '/views');
            });

            // C'est le filtre 'docalist-tools' qui fournit la liste des outils disponibles
            $filter = function (): array {
                return apply_filters('docalist-tools', []);
            };

            // L'objet ToolsList se charge exécutera notre fonction en cas de besoin
            $tools = new ToolsList($filter);

            // Crée la page "Outils Docalist"
            new ToolsPage($tools);
        });
    }

    /**
     * Initialise la page.
     *
     * @param Tools $tools La liste des outils disponibles.
     */
    private function __construct(Tools $tools)
    {
        $this->tools = $tools;
        parent::__construct('docalist-tools', 'tools.php', __('Outils Docalist', 'docalist-core'));
        $this->addCard();
    }

    /**
     * Ajoute une "carte" dans la page WordPress "Outils disponibles".
     */
    private function addCard(): void
    {
        add_action('tool_box', function () {
            docalist('views')->display('docalist-tools:available-tool', ['this' => $this]);
        });
    }

    /**
     * Charge les outils disponibles.
     *
     * La liste retournée ne contient que les outils pour lesquels l'utilisateur a les droit requis.
     *
     * @return Tool[] Un tableau de la forme Nom => Tool.
     */
    private function loadTools(): array
    {
        $tools = [];
        foreach ($this->tools->getList() as $toolId) {
            $tool = $this->tools->get($toolId);
            $capability = $tool->getCapability();
            if (empty($capability) || current_user_can($capability)) {
                $tools[$toolId] = $tool;
            }
        }

        return $tools;
    }

    /**
     * Trie la liste par libellé d'outil.
     *
     * @param Tool[] $tools Un tableau de la forme Nom => Tool tel que retourné par loadTools().
     *
     * @return Tool[] Un tableau de la forme Nom => Tool trié par label.
     */
    private function sortToolsByLabel(array $tools): array
    {
        uasort($tools, function (Tool $tool1, Tool $tool2) {
            return strnatcasecmp($tool1->getLabel(), $tool2->getLabel());
        });

        return $tools;
    }

    /**
     * Regroupe les outils par catégorie.
     *
     * @param Tool[] $tools Un tableau de la forme Nom => Tool tel que retourné par sortToolsByLabel().
     *
     * @return Tool[][] Un tableau de la forme Catégorie => [nom1 => Tool1, nom2 => Tool2]
     */
    private function groupToolsByCategory(array $tools): array
    {
        // Regroupe les outils par catégorie
        $categories = [];
        foreach ($tools as $id => $tool) {
            $categories[$tool->getCategory()][$id] = $tool;
        }

        // Trie les catégories par nom
        uksort($categories, 'strnatcasecmp');

        // Ok
        return $categories;
    }

    /**
     * {@inheritDoc}
     */
    protected function getDefaultAction()
    {
        return 'ListTools';
    }

    /**
     * Liste les outils disponibles.
     *
     * @return Response
     */
    public function actionListTools(): Response
    {
        $tools = $this->loadTools();
        $tools = $this->sortToolsByLabel($tools);
        $toolsByCategory = $this->groupToolsByCategory($tools);

        if (empty($toolsByCategory)) {
            return $this->view('docalist-tools:no-tools');
        }

        return $this->view('docalist-tools:list-tools', ['toolsByCategory' => $toolsByCategory]);
    }

    /**
     * Prépare l'exécution du script.
     */
    private function prepareRun(): void
    {
        // Permet au script de s'exécuter longtemps
        ignore_user_abort(true);
        set_time_limit(3600);

        ini_set('zlib.output_compression', 'off');
        ini_set('output_buffering', 'Off');

        // Supprime la bufferisation pour voir le suivi en temps réel
        while (ob_get_level()) {
            ob_end_flush();
        }
        flush();
        ob_implicit_flush();
    }

    /**
     * Exécute l'outil indiqué.
     *
     * @param string $tool
     */
    public function actionRun(string $tool): Response
    {
        // Charge l'outil à exécuter, génère une exception s'il n'existe pas
        $tool = $this->tools->get($tool);

        // Prépare l'exécution
        $this->prepareRun();

        // On retourne une réponse de type "callback" qui affiche la vue qui exécute l'outil
        $response = new CallbackResponse(function () use ($tool) {
            docalist('views')->display('docalist-tools:run-tool', [
                'this' => $this,
                'tool' => $tool,
                'args' => $_REQUEST
            ]);
        });

        // Indique que la réponse doit s'afficher dans le back-office wp
        $response->adminPage(true);

        // Ok
        return $response;
    }
}
