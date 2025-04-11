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
     * @var array<Tool|callable|string>|callable
     */
    private $tools;

    /**
     * Initialise la liste des outils disponibles.
     *
     * @param array<Tool|callable|string>|callable $tools Un tableau d'outils ou un callback qui retourne un tableau d'outils.
     *
     * Chaque élément du tableau (fourni ou retourné par le callback) doit être de la forme id => factory. La clé est
     * un identifiant unique qui indique le nom de l'outil et la valeur associée indique comment instancier l'outil.
     *
     * Il peut s'agir :
     * - object : d'un outil déjà instancié (i.e. un objet qui implémente l'interface Tool),
     * - callable : un callback qui se charge d'instancier l'outil (doit retourner une objet Tool),
     * - string : le nom d'une classe PHP qui implemente l'interface Tool et dont le constructeur peut être appellé
     *   sans paramètres.
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
     * Exécute le callback si on a passé une fonction au constructeur au lieu d'un tableau.
     *
     * @throws InvalidArgumentException
     */
    private function loadList(): void
    {
        // Terminé si on a déjà fait l'initialisation
        if (is_array($this->tools)) {
            return;
        }

        // Exécute le callable et vérifie qu'il retourne un tableau
        $this->tools = call_user_func($this->tools);
        if (! is_array($this->tools)) {
            throw new InvalidArgumentException('Tools callback must return an array, got ' . gettype($this->tools));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getList(): array
    {
        $this->loadList();

        return array_keys($this->tools);
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $name): bool
    {
        $this->loadList();

        return array_key_exists($name, $this->tools);
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $name): Tool
    {
        // Vérifie que l'outil existe
        if (! $this->has($name)) {
            throw new InvalidArgumentException(sprintf('Invalid tool "%s"', $name));
        }

        // Regarde ce qu'on a dans le tableau pour le moment
        $tool = $this->tools[$name];

        // Si l'outil est déjà instancié, terminé
        if ($tool instanceof Tool) {
            return $tool;
        }

        // Si l'outil est défini via un callback, on l'appelle
        if (is_callable($tool)) {
            $tool = call_user_func($tool);

            if (! $tool instanceof Tool) {
                throw new InvalidArgumentException(sprintf('Callback for tool "%s" must return a Tool', $name));
            }

            $this->tools[$name] = $tool;

            return $tool;
        }

        // Nom de classe PHP
        if (is_string($tool) && is_a($tool, Tool::class, true)) {

            $tool = new $tool();
            $this->tools[$name] = $tool;

            return $tool;
        }

        // Impossible de créer l'outil
        throw new InvalidArgumentException(sprintf('Invalid factory for tool "%s"', $name));
    }
}
