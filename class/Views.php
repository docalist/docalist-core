<?php

/**
 * This file is part of the 'Docalist Core' plugin.
 *
 * Copyright (C) 2012-2016 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Core
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist;

use Exception;

/**
 * Gestionnaire de vues Docalist.
 *
 * Les vues permettent de séparer (design pattern SOC) la préparation des données (contrôleur) de leur
 * représentation (vue).
 *
 * Les vues sont prédéfinies par les plugins mais peuvent être surchargées par le thème en cours.
 * Pour cela, un système de nom symbolique de la forme "{plugin}:{path}" est utilisé pour désigner les vues
 * (par exemple "docalist-core:table/edit" pour désigner la vue "table/edit" du plugin docalist-core).
 *
 * La vue sera alors recherchée aux emplacements suivants :
 * - wp-content/themes/{theme actuel}/views/{plugin}/{path}.php
 * - wp-content/plugins/{plugin}/views/{path}.path
 *
 * Par exemple, la vue "docalist-core:table/edit" sera recherchée dans :
 * - wp-content/themes/twentythirteen/views/docalist-core/table/edit.php
 * - wp-content/plugins/docalist-core/views/table/edit.php
 *
 * Remarque : l'extension '.php' est ajoutée automatiquement au path de la vue et ne doit pas être indiquée dans
 * le nom de la vue.
 */
class Views
{
    /**
     * Exécute une vue et affiche le résultat.
     *
     * @param string $view Le nom de la vue à exécuter.
     *
     * @param array $data Un tableau contenant les données à transmettre à la vue.
     *
     * Chacun des éléments sera disponible dans la vue comme une variable locale :
     *
     *     render('docalist-core:table/edit', ['table' => 'test'])
     *     >>> /table/edit.php : echo $table;
     *
     * Il est possible également de passer dans le tableau la variable 'this' avec une instance d'un objet existant.
     * Dans ce cas, la vue aura accès à $this et sera exécutée comme s'il s'agissait d'une méthode de l'objet
     * (elle a accès à toutes les propriétés et méthodes de l'objet, y compris celles qui sont protected ou private).
     *
     * La variable $view est une variable réservée. A l'intérieur d'une vue, c'est un tableau qui contient les
     * paramètres d'exécution de la vue :
     *
     * Par exemple :
     * array(
     *     'name' => 'docalist-core:table/edit',
     *     'path' => '/.../plugins/docalist-core/views/info.php',
     *     'data' => ['table' => 'test'], // les données initiales de la vue
     * )
     *
     * Dans une vue, $view est pratique pour le débogage mais aussi pour transmettre les données à une autre vue :
     *
     * docalist('views')->display('autre:vue', $view['data']);
     *
     * @return mixed La méthode retourne ce que retourne la vue (rien en général).
     *
     * @throws Exception si la vue n'existe pas.
     */
    public function display($view, array $data = [])
    {
        // Détermine le path de la vue
        if (false === $path = $this->getPath($view)) {
            $msg = __('Vue non trouvée "%s"', 'docalist-core');
            throw new Exception(sprintf($msg, $view));
        }

        // La closure qui exécute le template (sandbox)
        $render = function (array $view) {
            extract($view, EXTR_OVERWRITE | EXTR_REFS);

            return require $view['path'];
        };

        $data['view'] = ['name' => $view, 'path' => $path, 'data' => $data];

        // Binde la closure pour que $this soit dispo dans la vue
        $context = isset($data['this']) ? $data['this'] : null;
        $render = $render->bindTo($context, $context);

        // Exécute le template
        return $render($data);
    }

    /**
     * Exécute une vue et retourne le résultat.
     *
     * @param string $view Le nom de la vue à exécuter.
     *
     * @param array $data Un tableau contenant les données à transmettre à la vue (cf. display()).
     *
     * @return mixed La méthode retourne ce que retourne la vue (rien en général).
     *
     * @throws Exception si la vue n'existe pas.
     */
    public function render($view, array $data = [])
    {
        ob_start();
        $this->display($view, $data);

        return ob_get_clean();
    }

    /**
     * Retourne le path de la vue ont le nom symbolique est passé en paramètre.
     *
     * La vue est d'abord recherchée dans le répertoire "/views" du thème en cours, ce qui permet de surcharger les
     * vues par défaut fournies par les plugins en fournissant une version spécialisée, puis dans le répertoire
     * "views" du plugin.
     *
     * @param string $view Nom symbolique de la vue recherchée.
     *
     * @return string|false Le path de la vue ou false si la vue n'existe pas.
     */
    public function getPath($view)
    {
        static $themeDir = null;

        // Initialise themeDir au premier appel
        is_null($themeDir) && $themeDir = get_template_directory();

        // Vérifie que le nom de la vue a le format attendu
        if (false === $pt = strpos($view, ':')) {
            $msg = __('Nom de vue incorrect "%s" (plugin:view attendu)', 'docalist-core');
            throw new Exception(sprintf($msg, $view));
        }

        // Sépare le nom du plugin du nom de la vue
        $plugin = substr($view, 0, $pt);
        $view = substr($view, $pt + 1);

        // Teste si la vue existe dans le thème en cours
        $path = "$themeDir/views/$plugin/$view.php";
        if (file_exists($path)) {
            return $path;
        }

        // Teste si la vue existe dans le plugin
        $path = WP_PLUGIN_DIR . "/$plugin/views/$view.php";
        if (file_exists($path)) {
            return $path;
        }

        // Vue non trouvée
        return false;
    }

    /**
     * Détermine si une vue existe.
     *
     * @param string $view Le nom de la vue à tester
     *
     * @return bool
     */
    public function has($view)
    {
        return false !== $this->getPath($view);
    }
}
