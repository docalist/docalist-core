<?php
/**
 * This file is part of the 'Docalist Core' plugin.
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

use InvalidArgumentException;

/**
 * Gestionnaire de vues Docalist.
 *
 * Les vues permettent de séparer (design pattern SOC) la préparation des données (contrôleur) de leur
 * représentation (vue).
 *
 * Le service 'views' propose essentiellement deux méthodes : display() qui affiche (exécute) une vue en lui
 * passant des paramètres et render() qui retourne le résultat généré.
 *
 * Pour désigner les vues, le service 'views' utilise un système de noms symboliques de la forme 'group:path'.
 *
 * Le groupe est un identifiant permettant de regrouper différentes vues (par exemple nom d'un plugin) et le path
 * est le chemin relatif de la vue au sein de ce groupe (l'extension '.php' est ajoutée automatiquement au path
 * et ne doit pas être indiqué dans le nom symbolique indiqué). Lors de l'exécution, la vue indiquée sera recherchée
 * dans tous les répertoires qui sont associés au groupe.
 *
 * Remarque : si la vue n'est pas de la forme 'group:path', le service considère qu'il s'agit du groupe ''.
 *
 * Ce système permet à un plugin ou à un thème de surcharger les vues par défaut proposées par un autre plugin en
 * ajoutant un répertoire en tête de liste des répertoires associés à un groupe.
 *
 * Remarques :
 * - docalist-core initialise le service 'views' en associant le groupe '' à la racine du thème du site
 *   et en créant un groupe pour chaque plugin actif associé au répertoire '/views' du plugin.
 * - Ainsi, un appel à display('docalist-core:info') exécutera le fichier '/plugins/docalist-core/views/info.php' et
 *   un appel à display('contact') exécutera le fichier '/contact.php' du thème en cours.
 */
class Views
{
    /**
     * Liste des groupes, avec pour chaque groupe la liste des répertoires où seront recherchés les vues de ce groupe.
     *
     * @var array Un tableau de la forme 'group' => '/répertoire/' ou 'group' => ['/répertoire1/', '/répertoire2/']
     */
    protected $groups;

    /**
     * Initialise le gestionnaire de vues.
     *
     * @param array $groups un tableau de la forme 'group' => répertoire ou array(répertoires).
     * Important : chaque répertoire doit contenir un slash final.
     */
    public function __construct($groups = [])
    {
        $this->setGroups($groups);
    }

    /**
     * Modifie la liste des groupes.
     *
     * @param array $groups un tableau de la forme 'group' => répertoire ou array(répertoires).
     * Important : chaque répertoire doit contenir un slash final.
     *
     * @return self
     */
    public function setGroups(array $groups)
    {
        $this->groups = $groups;

        return $this;
    }

    /**
     * Retourne la liste des groupes définis.
     *
     * @return array un tableau de la forme 'group' => répertoire ou array(répertoires).
     */
    public function getGroups()
    {
        return $this->groups;
    }

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
     *     dans /table/edit.php, on peut avoir : echo $table;
     *
     * Il est possible également de passer dans le tableau la variable 'this' avec une instance d'un objet existant.
     * Dans ce cas, la vue aura accès à $this et sera exécutée comme s'il s'agissait d'une méthode de l'objet
     * (elle a accès à toutes les propriétés et méthodes de l'objet, y compris celles qui sont protected ou private).
     *
     * La variable $view est une variable réservée. A l'intérieur d'une vue, c'est un tableau qui contient les
     * paramètres d'exécution de la vue :
     *
     * Par exemple :
     * [
     *     'name' => 'docalist-core:table/edit',
     *     'path' => '/.../plugins/docalist-core/views/info.php',
     *     'data' => ['table' => 'test'], // les données initiales de la vue
     * ]
     *
     * Dans une vue, $view est pratique pour le débogage mais aussi pour transmettre les données à une autre vue :
     *
     * docalist('views')->display('autre:vue', $view['data']);
     *
     * @return mixed La méthode retourne ce que retourne la vue (rien en général).
     *
     * @throws InvalidArgumentException si la vue n'existe pas.
     */
    public function display($view, array $data = [])
    {
        // Détermine le path de la vue
        if (false === $path = $this->getPath($view)) {
            throw new InvalidArgumentException("View not found '$view'");
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

        // En php >= 7.1, il est interdit de reassigner $this (via extract)
        // Comme on a bindé la closure, on n'en a pas besoin
        unset($data['this']);

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
     * @throws InvalidArgumentException si la vue n'existe pas.
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
     * La vue est recherchée dans tous les répertoires qui sont associés au groupe indiqué dans la vue.
     *
     * @param string $view Nom symbolique de la vue recherchée.
     *
     * @return string|false Le path de la vue ou false si la vue n'existe pas.
     *
     * @throws InvalidArgumentException si le groupe indiqué dans la vue n'existe pas.
     *
     */
    public function getPath($view)
    {
        // Extrait le nom du groupe et le nom de la vue
        $group = '';
        $pt = strpos($view, ':');
        if ($pt !== false) {
            $group = substr($view, 0, $pt);
            $view = substr($view, $pt + 1);
        }

        // Vérifie que le groupe indiqué existe
        if (! isset($this->groups[$group])) {
            throw new InvalidArgumentException("Invalid group '$group' for view '$view'");
        }

        // Teste les différents répertoires du groupe
        $view .= '.php';
        foreach ((array)$this->groups[$group] as $dir) {
            $path = $dir . $view;
            if (file_exists($path)) {
                return $path;
            }
        }

        // Vue non trouvée
        return false;
    }
}
