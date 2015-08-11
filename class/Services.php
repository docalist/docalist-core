<?php
/**
 * This file is part of the "Docalist Core" plugin.
 *
 * Copyright (C) 2012-2015 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Core
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */

namespace Docalist;

use Closure;
use LogicException;
use Psr\Log\LoggerInterface;

/**
 * Gestionnaire de services Docalist.
 */
class Services {
    /**
     * Liste des services déclarés.
     *
     * @var array
     */
    protected $services;

    /**
     *
     * @var LoggerInterface
     */
    protected $log;

    /**
     * Initialise les services.
     *
     * @param array $services
     */
    public function __construct(array $services = []) {
       $this->services = $services;
    }

    /**
     * Ajoute un ou plusieurs services dans le gestionnaire de services.
     *
     * Vous pouvez ajouter un service unique en appellant la méthode avec deux
     * paramètres ou un ensemble de service en passant directement un tableau
     * en paramètre.
     *
     * @param string|array $id identifiant unique du service à ajouter, ou
     * tableau de services de la forme identifiant => service.
     * @param mixed $service le service à ajouter. Cela peut être un scalaire
     * (un paramètre de configuration, par exemple), un objet (par exemple un
     * plugin) ou une closure qui sera invoquée lors du premier appel pour créer
     * l'instance du service. Ce paramètre n'est pas utilisé si vous passez un
     * tableau de services pour $id.
     *
     * @throws LogicException S'il existe déjà un service avec l'identifiant indiqué.
     *
     * @return self
     */
    public function add($id, $service = null) {
        if (is_array($id)) {
            foreach($id as $id => $service) {
                $this->add($id, $service);
            }
            return $this;
        }

        if (isset($this->services[$id])) {
            $message = __('%s existe déjà.', 'docalist-core');
            throw new LogicException(sprintf($message, $id));
        }

        $this->services[$id] = $service;

        if ($id === 'logs') {
            $this->log = $this->get('logs')->get('services');
        }

        $this->log && $this->log->debug('create service {id}', ['id' => $id]);

        return $this;
    }

    /**
     * Indique si le service indiqué existe.
     *
     * @param string $id l'identifiant du service recherché.
     *
     * @return bool
     */
    public function has($id) {
        return isset($this->services[$id]);
    }

    /**
     * Indique si le service indiqué est chargé.
     *
     * Si le service n'existe pas, ou s'il a été défini via une closure et que
     * cette closure n'a pas encore été exécutée, le service est considéré comme
     * "non chargé".
     *
     * Dans tous les autres cas, la méthode retourne true.
     *
     * @param string $id l'identifiant du service recherché.
     *
     * @return bool
     */
    public function isLoaded($id) {
        return isset($this->services[$id])
            && ! ($this->services[$id] instanceof Closure);
    }

    /**
     * Retourne le service ayant l'identifiant indiqué.
     *
     * Si le service n'a pas encore été créé, il est instancié en invoquant
     * la Closure utilisée pour le définir, sinon, l'instance existante est
     * retournée.
     *
     * @param string $id l'identifiant de l'objet à retourner.
     *
     * @throws LogicException Si l'identifiant indiqué n'existe pas.
     *
     * @return mixed
     */
    public function get($id) {
        if (! isset($this->services[$id])) {
            $message = __('Service "%s" non trouvé.', 'docalist-core');
            throw new LogicException(sprintf($message, $id));
        }

        $service = $this->services[$id];
        if ($service instanceof Closure) {
            $this->log && $this->log->debug('instantiate service {id}', ['id' => $id]);

            return $this->services[$id] = $service($this);
        }

        return $service;
    }

    /**
     * Retourne la liste des services déclarés.
     *
     * @return array un tableau contenant les noms de tous les services, dans
     * l'ordre dans lequel ils ont été déclarés.
     */
    public function names() {
        return array_keys($this->services);
    }

    /**
     * Retourne l'état des services (chargés ou non).
     *
     * @eturn array un tableau contenant les noms de tous les services.
     */
    public function state() {
        $t = $this->services;
        foreach($t as $id => & $state) {
            $state = $this->isLoaded($id);
        }
        unset($state);

        return $t;
    }
}