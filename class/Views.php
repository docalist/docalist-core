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
 * Gestionnaire de vues de Docalist.
 *
 * Les vues permettent de séparer (design pattern SOC) la préparation des données (contrôleur) de leur
 * représentation (vue).
 *
 * Le service 'views' propose essentiellement deux méthodes :
 *
 * - display() qui affiche (exécute) une vue en lui passant des paramètres ;
 * - render() qui fait la même chose maus retourne le résultat généré au lieu de l'afficher.
 *
 * Pour désigner les vues, le service 'views' utilise un système de noms symboliques de la forme "group:path".
 *
 * Par exemple : docalist('views')->display('docalist-core:info') affiche la vue "info" du groupe "docalist-core".
 *
 * Le groupe est un identifiant quelconque (éventuellement vide) qui permet de regrouper différentes vues
 * ensembles : en général il s'agit du nom de code d'un plugin (par exemple "docalist-core" dans l'exemple précédent).
 *
 * Le path est un chemin relatif de la vue au sein de ce groupe (l'extension '.php' est ajoutée automatiquement au
 * path et ne doit pas être indiqué dans le nom symbolique indiqué).
 *
 * Lors de l'exécution, la vue indiquée sera recherchée dans tous les répertoires (dans l'ordre) qui sont associés
 * au groupe indiqué.
 *
 * Remarque : si la vue n'est pas de la forme 'group:path', le service considère qu'il s'agit du groupe ''.
 *
 * Ce système permet à un plugin ou à un thème de surcharger les vues par défaut proposées par un autre plugin en
 * ajoutant un répertoire en tête de liste des répertoires associés à un groupe. Par exemple :
 *
 * - Par défaut, un appel de la forme display('docalist-core:info') affichera le fichier qui se trouve dans le
 *   répertoire "docalist-core/views/info.php"
 * - Mais le thème ou un autre plugin peut ajouter un répertoire supplémentaire au groupe "docalist-core", par
 *   exemple docalist('views')->addDirectory('docalist-core', '/myplugin/views/override').
 * - Dans ce cas, c'est le fichier "/myplugin/views/override/info.php" qui sera exécuté s'il existe ou le fichier
 *   par défaut (dans docalist-core) sinon.
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
     * Les path des répertoires sont normalisés (cf. normalizePath) et contient toujours un (anti)slash à la fin.
     *
     * @var string[]|array[] Un tableau de la forme 'group' => répertoire(s).
     */
    protected $groups;

    /**
     * Initialise le gestionnaire de vues.
     *
     * @param string[]|array[] $groups La liste initiale des groupes (cf. setGroups).
     * Des groupes et des répertoires supplémentaires peuvent être ajoutés en appellant addDirectory().
     */
    public function __construct($groups = [])
    {
        $this->setGroups($groups);
    }

    /**
     * Modifie la liste des groupes.
     *
     * @param string[]|array[] $groups Un tableau dont les entrées sont de la forme :
     *
     * - 'group1' => 'répertoire' ou
     * - 'group2' => ['répertoire1', 'répertoire2', etc.]
     *
     * Remarques :
     *
     * - Le path de chaque répertoire indiqué est normalisé (ajout d'un slash/antislash de fin et uniformisation
     *   du séparateur) mais aucun test n'est fait pour vérifier que le répertoire indiqué existe.
     * - Lorsqu'un groupe contient plusieurs répertoire, ceux-ci sont testés dans l'ordre indiqué.
     *
     * @return self
     */
    public function setGroups(array $groups)
    {
        $this->groups = [];
        foreach($groups as $group => $directories) {
            foreach((array) $directories as $directory) {
                $this->addDirectory($group, $directory);
            }
        }

        return $this;
    }

    /**
     * Retourne la liste des groupes définis.
     *
     * @return string[]|array[] Un tableau de la forme 'group' => répertoire(s).
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * Ajoute un répertoire au groupe indiqué.
     *
     * Si le groupe indiqué n'existe pas encore, il est créé. S'il existe déjà, le répertoire indiqué est
     * ajouté avant les répertoires existants (pour qu'il soit prioritaire).
     *
     * @param string $group     Nom du groupe à créer ou modifier.
     * @param string $directory Path absolu du répertoire à ajouter au groupe.
     *
     * @return self
     */
    public function addDirectory($group, $directory)
    {
        $path = $this->normalizePath($directory);
        if (!isset($this->groups[$group])) {
            $this->groups[$group] = $path;

            return $this;
        }

        is_string($this->groups[$group]) && $this->groups[$group] = array($this->groups[$group]);
        array_unshift($this->groups[$group], $path);

        return $this;
    }

    /**
     * Normalise le path passé en paramètre.
     *
     * - standardise le séparateur (slash ou antislash selon le système)
     * - garantit qu'on a un séparateur à la fin.
     *
     * @param string $path
     *
     * @return string
     */
    protected function normalizePath($path)
    {
        return rtrim(strtr($path, '/\\', DIRECTORY_SEPARATOR), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    /**
     * Exécute une vue et affiche le résultat.
     *
     * @param string $view Le nom de la vue à exécuter.
     *
     * @param mixed[] $data Un tableau contenant les données à transmettre à la vue.
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
            throw new InvalidArgumentException(sprintf('View not found "%s"', $view));
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
     * @param mixed[] $data Un tableau contenant les données à transmettre à la vue (cf. display()).
     *
     * @return string Retourne le résultat généré par la vue.
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
     * Retourne le path de la vue dont le nom symbolique est passé en paramètre.
     *
     * La vue est recherchée dans tous les répertoires qui sont associés au groupe indiqué dans la vue.
     *
     * @param string $view Nom symbolique de la vue recherchée.
     *
     * @return string|false Le path de la vue ou false si la vue n'existe pas.
     *
     * @throws InvalidArgumentException si le groupe indiqué dans la vue n'existe pas.
     */
    public function getPath($view)
    {
        // Extrait le nom du groupe et le nom de la vue
        $group = '';
        $file = $view . '.php';
        $colon = strpos($file, ':');
        if ($colon !== false) {
            $group = substr($file, 0, $colon);
            $file = substr($file, $colon + 1);
        }

        // Vérifie que le groupe indiqué existe
        if (! isset($this->groups[$group])) {
            throw new InvalidArgumentException(sprintf('Unknown group "%s" in view "%s"', $group, $view));
        }

        // Teste les différents répertoires du groupe
        foreach ((array)$this->groups[$group] as $dir) {
            $path = $dir . $file;
            if (file_exists($path)) {
                return $path;
            }
        }

        // Vue non trouvée
        return false;
    }
}
