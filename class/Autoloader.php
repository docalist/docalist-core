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

use InvalidArgumentException;

/**
 * Autoloader de Docalist.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class Autoloader
{
    /**
     * Liste des espaces de noms enregistrés.
     *
     * Les clés du tableau contiennent l'espace de nom, les valeurs contiennent le path du répertoire qui contient
     * les classes php de cet espace de noms.
     *
     * @var array
     */
    protected $namespaces = [];

    /**
     * Crée un nouvel autoloader en enregistrant les espaces de noms passés en paramètre.
     *
     * @param array $namespaces Un tableau de namespaces à enregistrer de la forme :
     *                          namespace => path du répertoire contenant les classes de ce namespace.
     */
    public function __construct(array $namespaces = [])
    {
        $this->namespaces = $namespaces;
        spl_autoload_register([$this, 'autoload'], true, false);
    }

    /**
     * Retourne la liste des espaces de noms enregistrés.
     *
     * @eturn array Un tableau de la forme namespace => path
     */
    public function getNamespaces()
    {
        return $this->namespaces;
    }

    /**
     * Enregistre un espace de noms dans l'autoloader.
     *
     * @param string $namespace Namespace à enregistrer (important : pas d'antislash ni au début, ni à la fin).
     * @param string $path      Chemin absolu du dossier qui contient les classes pour le namespace indiqué.
     *
     * @return self
     *
     * @throws InvalidArgumentException Si le namespace est déjà enregistré avec un path différent.
     */
    public function add($namespace, $path)
    {
        // Vérifie que ce namespace n'a pas déjà été enregistré
        $path = strtr($path, '/', DIRECTORY_SEPARATOR);
        if (isset($this->namespaces[$namespace]) && $this->namespaces[$namespace] !== $path) {
            throw new InvalidArgumentException(sprintf(
                'Namespace "%s" is already registered with a different path',
                $namespace
            ));
        }

        // Enregistre le path
        $this->namespaces[$namespace] = $path;

        return $this;
    }

    /**
     * Essaie de déterminer le path de la classe passée en paramètre.
     *
     * La méthode recherche le plus grand espace de noms enregistrés qui correspond au nom de la classe indiquée. Si
     * une correspondance est trouvée, elle utilise le path obtenu pour déterminer l'emplacement (théorique) de la
     * classe correspondante.
     *
     * Remarque : aucun test n'est fait pour tester si le path obtenu existe ou non.
     *
     * @param string $className Nom complet de la classe à tester.
     *
     * @return string|false Retourne le path de la classe si son espace de nom correspond à l'un des espaces de
     *                      nom enregistrés, false sinon.
     */
    public function resolve($className)
    {
        $namespace = $className;
        while (false !== $backslash = strrpos($namespace, '\\')) {
            $namespace = substr($className, 0, $backslash);
            if (isset($this->namespaces[$namespace])) {
                $file = strtr(substr($className, $backslash), '\\', DIRECTORY_SEPARATOR);

                return $this->namespaces[$namespace] . $file . '.php';
            }
        }

        return false;
    }

    /**
     * Essaie de charger la classe passée en paramètre.
     *
     * Cette fonction est appellée automatiquement par spl_autoload_call() lorsqu'une classe demandée n'existe pas.
     *
     * @param string $class Nom complet de la classe à charger.
     *
     * @return bool Vrai si la classe indiquée a été chargée, false sinon.
     */
    public function autoload($class)
    {
        if (false !== $path = $this->resolve($class)) {
            require_once $path;

            if (! wp_doing_ajax()) {
                add_action('wp_footer', function () use ($class, $path) {
                    echo'<script>console.log("docalist autoload:", ', json_encode($class), ', " -> ", ', json_encode($path), ');</script>';
                }, 9999);
            }

            return true;
        }

        return false;
    }
}
