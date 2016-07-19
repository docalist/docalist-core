<?php
/**
 * This file is part of the "Docalist Core" plugin.
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

use InvalidArgumentException;

/**
 * Autoloader de Docalist.
 */
class Autoloader
{
    /**
     * @var array Liste des espaces de noms enregistrés.
     *
     * Les clés du tableau contiennent l'espace de nom, les valeurs contiennent le path du répertoire qui contient
     * les classes php de cet espace de noms.
     */
    protected $path = [];

    /**
     * Crée un nouvel autoloader en enregistrant les espaces de noms passés en paramètre.
     *
     * @param array $namespaces un tableau de namespaces à enregistrer, de la forme :
     * namespace => path du répertoire contenant les classes de ce namespace.
     */
    public function __construct(array $namespaces = [])
    {
        $this->path = $namespaces;
        spl_autoload_register([$this, 'autoload'], true);
    }

    /**
     * Enregistre un espace de noms dans l'autoloader.
     *
     * @param string $namespace Namespace à enregistrer (important : pas d'antislash ni au début, ni à la fin).
     *
     * @param string $path Chemin absolu du dossier qui contient les classes pour le namespace indiqué.
     *
     * @return self
     *
     * @throws InvalidArgumentException Si le namespace est déjà enregistré avec un path différent.
     */
    public function add($namespace, $path)
    {
        // Vérifie que ce namespace n'a pas déjà été enregistré
        if (isset($this->path[$namespace]) && $this->path[$namespace] !== $path) {
            throw new InvalidArgumentException("Namespace '$namespace' is already registered with a different path");
        }

        // Enregistre le path
        $this->path[$namespace] = $path;

        return $this;
    }

    /**
     * Autoloader.
     *
     * Cette fonction est appellée automatiquement par spl_autoload_call lorsqu'une classe demandée n'existe pas.
     *
     * @param string $class Nom complet de la classe à charger.
     */
    protected function autoload($class)
    {
        $namespace = $class;
        while (false !== $pt = strrpos($namespace, '\\')) {
            $namespace = substr($class, 0, $pt);
            if (isset($this->path[$namespace])) {
                $file = substr($class, $pt);
                $file = strtr($file, '\\', DIRECTORY_SEPARATOR);
                $file .= '.php';
                $path = $this->path[$namespace] . $file;

                require_once $path;

                return;
            }
        }
    }
}
