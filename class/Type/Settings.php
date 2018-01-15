<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 */
namespace Docalist\Type;

use Docalist\Repository\Repository;

/**
 * Classe de base pour les settings.
 *
 * Un objet "settings" est une entité qui représente un ensemble de paramètres
 * stockés dans un dépôt déterminé.
 *
 * Contrairement à une entité qui en général obtient son ID depuis le dépôt où
 * elle est stockée, un objet Settings fixe lui-même son ID.
 *
 * L'ID peut être indiqué de plusieurs façon :
 * - déterminé par les classes descendantes (surcharge et initialisation de la
 *   propriété $id)
 * - passé au constructeur
 * - déterminé automatiquement à partir du nom de la classe (par exemple
 *   'docalist-core-settings' pour la classe Docalist\Core\Settings).
 *
 * Le dépôt dans lequel seront stockés les paramétres doit être fourni dès
 * la création de l'objet Settings. En général, il s'agit d'un dépôt de type
 * SettingsRepository ou ConfigRepository mais cela peut être n'importe quel
 * type de dépôt.
 *
 * Si les paramètres figurent déjà dans le dépôt, ceux-ci sont chargés, sinon,
 * l'objet Settings est initialisé avec sa valeur par défaut.
 *
 * Une fois créé, l'objet Settings mémorise le dépôt d'où il est issu, ce qui
 * permet d'invoquer les méthodes save(), reload() et reset() sans avoir à
 * indiquer à nouveau le dépôt.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class Settings extends Entity
{
    /**
     * Le dépôt dans lequel est stocké cet objet Settings.
     *
     * @var Repository
     */
    protected $repository;

    /**
     * Construit un nouvel objet Settings.
     *
     * @param Repository $repository Le dépôt dans lequel sont stockés les
     * paramètres.
     * @param scalar $id L'identifiant du settings
     */
    public function __construct(Repository $repository, $id = null)
    {
        // Stocke le dépôt associé
        $this->repository = $repository;

        // Détermine l'id des settings
        if (! is_null($id)) {           // ID transmis en paramètre
            $this->setID($id);          // Exception si la classe descendante a déjà fixé l'id
        } elseif (is_null($this->getID())) { // ID auto
            $this->id = strtolower(strtr(get_class($this), '\\', '-'));
        }                               // ID fixé en dur et non transmis

        // Si les settings ont été enregistrés dans le dépôt, on les charge
        if ($repository->has($this->getID())) {
            parent::__construct($repository->loadRaw($this->getID()));
        }

        // Sinon on initialise avec la valeur par défaut
        else {
            parent::__construct();
        }
    }

    /**
     * Retourne le dépôt associé aux settings.
     *
     * @return Repository
     */
    public function repository()
    {
        return $this->repository;
    }

    /**
     * Enregistre les settings.
     */
    public function save()
    {
        $this->repository->save($this);
    }

    /**
     * Recharge les settings et annule les éventuelles modification apportées.
     *
     * @return self $this
     */
    public function reload()
    {
        $this->__construct($this->repository);

        return $this;
    }

    /**
     * Supprime les settings du dépôt et réinitialise les paramètres avec leurs
     * valeurs par défaut.
     *
     * @return self $this
     */
    public function delete()
    {
        $id = $this->getID();
        if ($this->repository->has($id)) {
            $this->repository->delete($id);
        }

        return $this->assign($this->getDefaultValue());
    }
}
