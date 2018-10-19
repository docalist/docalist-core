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

use Docalist\Tools\Tool;
use Docalist\Tools\Tools;
use InvalidArgumentException;

/**
 * Implémentation standard de l'interface Tools.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class ToolsList implements Tools
{
    /**
     * La liste des outils disponibles.
     *
     * Initialement, la propriété contient soit un callable, soit un tableau avec le nom de classe PHP des outils.
     *
     * Une fois que load() a été appellée, elle contient un tableau de la forme : Nom => Classe PHP.
     *
     * @var array|callable
     */
    private $tools;

    /**
     * Indique si load() a déjà été appellée.
     *
     * @var bool
     */
    private $loaded = false;

    /**
     * Initialise la liste des outils disponibles.
     *
     * @param array|callable $tools Un tableau ou une fonction qui fournit le nom de classe PHP des outils disponibles.
     */
    public function __construct($tools)
    {
        if (!is_array($tools) && !is_callable($tools)) {
            throw new InvalidArgumentException('Invalid tools, expected array or callable');
        }
        $this->tools = $tools;
    }

    /**
     * Initialise la liste des outils.
     *
     * - Exécute le callback si on a passé une fonction au constructeur au lieu d'un tableau.
     * - Convertit le nom de classe des outils en ID.
     *
     * @throws InvalidArgumentException
     */
    private function load(): void
    {
        // Terminé si on a déjà fait l'initialisation
        if ($this->loaded) {
            return;
        }

        // Si on nous a fourni un callback, on l'exécute et on vérifie qu'il retourne un tableau
        if (is_callable($this->tools)) {
            $this->tools = call_user_func($this->tools);
            if (! is_array($this->tools)) {
                throw new InvalidArgumentException('Tools must be an array, got ' . gettype($this->tools));
            }
        }

        // Crée un tableau de la forme Nom => Classe
        $tools = [];
        foreach ($this->tools as $class) {
            $tools[$this->getToolName($class)] = $class;
        }

        // Ok
        $this->tools = $tools;
        $this->loaded = true;
    }

    /**
     * Convertit un nom de classe en nom d'outil.
     *
     * @param string $class Nom de classe PHP.
     *
     * @return string Nom de l'outil.
     */
    private function getToolName(string $class): string
    {
        // On ne garde que le nom court, il ne faut pas que deux outils aient le même nom de classe.
        // L'implémentation peut être changée si besoin sans que ça n'ait d'impact.
        $class = substr(strrchr($class, '\\'), 1);

        // Transforme le nom de classe en snake case (SearchReplace -> search-replace)
        return strtolower(preg_replace('/[A-Z]/', '-\\0', lcfirst($class)));
    }

    /**
     * {@inheritDoc}
     */
    public function getList(): array
    {
        $this->load();

        return array_keys($this->tools);
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $tool): bool
    {
        $this->load();

        return isset($this->tools[$tool]);
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $tool): Tool
    {
        if (! $this->has($tool)) {
            throw new InvalidArgumentException(sprintf('Invalid tool "%s"', $tool));
        }

        $class = $this->tools[$tool];

        return new $class();
    }
}
