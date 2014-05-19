<?php
/**
 * This file is part of the "Docalist Core" plugin.
 *
 * Copyright (C) 2012-2014 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Core
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 * @version     SVN: $Id$
 */

namespace Docalist;

use Closure;
use Exception;

/**
 * Gestionnaire de services Docalist.
 */
class Services {
    /**
     * Liste des services déclarés.
     *
     * @var array
     */
    protected $services = [];

    /**
     * Ajoute un service dans le gestionnaire de services.
     *
     * @param string $id identifiant unique de l'objet.
     * @param mixed $service le service à ajouter. Cela peut être un scalaire
     * (un paramètre de configuration, par exemple), un objet (par exemple un
     * plugin) ou une closure qui sera invoquée lors du premier appel pour créer
     * l'instance du service.
     *
     * @throws Exception S'il existe déjà un service avec l'identifiant indiqué.
     *
     * @return self
     */
    public function add($id, $service) {
        if (isset($this->services[$id])) {
            $message = __('%s existe déjà.', 'docalist-core');
            throw new Exception(sprintf($message, $id));
        }

        $this->services[$id] = $service;

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
     * Retourne le service ayant l'identifiant indiqué.
     *
     * Si le service n'a pas encore été créé, il est instancié en invoquant
     * la Closure utilisée pour le définir, sinon, l'instance existante est
     * retournée.
     *
     * @param string $id l'identifiant de l'objet à retourner.
     *
     * @throws Exception Si l'identifiant indiqué n'existe pas.
     *
     * @return mixed
     */
    public function get($id) {
        if (! isset($this->services[$id])) {
            $message = __('Service "%s" non trouvé.', 'docalist-core');
            throw new Exception(sprintf($message, $id));
        }

        $service = $this->services[$id];
        if ($service instanceof Closure) {
            return $this->services[$id] = $service($this);
        }

        return $service;
    }
}