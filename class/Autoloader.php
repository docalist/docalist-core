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
     * @var array<string,string>
     */
    protected array $namespaces = [];

    /**
     * Crée un nouvel autoloader en enregistrant les espaces de noms passés en paramètre.
     *
     * @param array<string,string> $namespaces Un tableau de namespaces à enregistrer de la forme :
     *                                         namespace => path du répertoire contenant les classes de ce namespace.
     */
    public function __construct(array $namespaces = [])
    {
        $this->namespaces = $namespaces;
        spl_autoload_register($this->autoload(...), true, false); // PHP 8.1 first class callables
    }

    /**
     * Retourne la liste des espaces de noms enregistrés.
     *
     * @return array<string,string> Un tableau de la forme namespace => path
     */
    public function getNamespaces(): array
    {
        return $this->namespaces;
    }

    /**
     * Enregistre un espace de noms dans l'autoloader.
     *
     * @param string $namespace Namespace à enregistrer (important : pas d'antislash ni au début, ni à la fin).
     * @param string $path      Chemin absolu du dossier qui contient les classes pour le namespace indiqué.
     *
     * @throws InvalidArgumentException Si le namespace est déjà enregistré avec un path différent.
     */
    public function add(string $namespace, string $path): static
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
     * Essaie de déterminer le path du fichier qui contient la classe passée en paramètre.
     *
     * La méthode recherche le plus grand espace de noms enregistrés qui correspond au nom de la classe indiquée.
     * Si une correspondance est trouvée, elle utilise le path obtenu pour déterminer l'emplacement de la
     * classe correspondante.
     *
     * @param string $className Nom complet de la classe à tester.
     *
     * @return string|false Retourne le path de la classe si son espace de nom correspond à l'un des espaces de
     *                      nom enregistrés, false sinon.
     */
    public function resolve(string $className): string|false
    {
        $namespace = $className;
        while (false !== $backslash = strrpos($namespace, '\\')) {
            $namespace = substr($className, 0, $backslash);
            if (isset($this->namespaces[$namespace])) {
                $file = strtr(substr($className, $backslash), '\\', DIRECTORY_SEPARATOR);

                $path = $this->namespaces[$namespace].$file.'.php';
                if (file_exists($path)) {
                    return $path;
                }
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
     */
    public function autoload(string $class): void
    {
        $path = $this->resolve($class);
        if ($path !== false && file_exists($path)) {
            require_once $path;
        }
    }
}
