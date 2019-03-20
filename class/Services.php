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

use Closure;
use InvalidArgumentException;

/**
 * Gestionnaire de services Docalist.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class Services
{
    /**
     * Liste des services déclarés.
     *
     * @var array
     */
    protected $services;

    /**
     * Initialise les services.
     *
     * @param array<string,mixed> $services Un tableau de services de la forme $id => $service.
     */
    public function __construct(array $services = [])
    {
        $this->services = $services;
    }

    /**
     * Retourne la liste des services actuellement déclarés.
     *
     * @return array<string,mixed> Un tableau de la forme $id => $service contenant tous les services dans l'ordre
     * dans lequel ils ont été déclarés.
     *
     * Remarque : les services qui ne sont pas encore instanciés sont représentés par une Closure.
     */
    public function getServices()
    {
        return $this->services;
    }

    /**
     * Ajoute un ou plusieurs services dans le gestionnaire de services.
     *
     * Vous pouvez ajouter un service unique en appellant la méthode avec deux paramètres ou un ensemble de service
     * en passant un tableau.
     *
     * @param string|array $id Identifiant unique du service à ajouter, ou tableau de la forme identifiant => service.
     *
     * @param mixed $service Le service à ajouter. Cela peut être un scalaire (un paramètre de configuration, par
     * exemple), un objet (par exemple un plugin) ou une closure qui sera invoquée lors du premier appel pour créer
     * l'instance du service.
     *
     * Ce paramètre n'est pas utilisé si vous passez un tableau de services pour $id.
     *
     * @throws InvalidArgumentException S'il existe déjà un service avec l'identifiant indiqué.
     *
     * @return self
     */
    public function add($id, $service = null)
    {
        foreach (is_array($id) ? $id : [$id => $service] as $id => $service) {
            if (array_key_exists($id, $this->services)) {
                throw new InvalidArgumentException("Service '$id' is already registered");
            }
            $this->services[$id] = $service;
        }

        return $this;
    }

    /**
     * Indique si le service indiqué existe.
     *
     * @param string $id l'identifiant du service recherché.
     *
     * @return bool
     */
    public function has($id)
    {
        return array_key_exists($id, $this->services);
    }

    /**
     * Indique si le service indiqué est chargé.
     *
     * Si le service n'existe pas, ou s'il a été défini via une closure et que cette closure n'a pas encore été
     * exécutée, le service est considéré comme "non chargé".
     *
     * Dans tous les autres cas, la méthode retourne true.
     *
     * @param string $id L'identifiant du service à tester.
     *
     * @return bool
     */
    public function isLoaded($id)
    {
        return array_key_exists($id, $this->services) && ! ($this->services[$id] instanceof Closure);
    }

    /**
     * Retourne un service précédement enregistré.
     *
     * Si le service n'a pas encore été instancié, il est créé en invoquant la Closure utilisée pour le définir,
     * sinon l'instance existante est retournée.
     *
     * @param string $id Le service à retourner.
     *
     * @throws InvalidArgumentException Si le service indiqué n'existe pas.
     *
     * @return mixed
     */
    public function get($id)
    {
        if (! array_key_exists($id, $this->services)) {
            throw new InvalidArgumentException("Service '$id' not found");
        }

        $service = $this->services[$id];
        if ($service instanceof Closure) {
            // Instancie le service
            $service = $service($this);

            // Permet aux plugins de paramétrer ou de modifier le service
            $service = apply_filters('docalist_service', $service, $id); // e.g. docalist_service
            $service = apply_filters('docalist_service_' . $id, $service); // e.g. docalist_service_views

            // Stocke le résultat
            $this->services[$id] = $service;

            // Ok
            return $service;
        }

        return $service;
    }
}
