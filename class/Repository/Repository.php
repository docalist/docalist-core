<?php
/**
 * This file is part of a "Docalist Core" plugin.
 *
 * Copyright (C) 2012-2014 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package Docalist
 * @subpackage Core
 * @author Daniel Ménard <daniel.menard@laposte.net>
 * @version SVN: $Id$
 */
namespace Docalist\Repository;

use Docalist\Type\Entity;
use Docalist\Repository\Exception\BadIdException;
use Docalist\Repository\Exception\RepositoryException;
use Docalist\Repository\Exception\EntityNotFoundException;

/**
 * Un repository est un dépôt dans lequel on peut stocker des entités.
 */
abstract class Repository {

    /**
     * Vérifie que l'identifiant passé en paramètre est valide pour ce type
     * de dépôt et génère une exception si ce n'est pas le cas.
     *
     * Chaque type de dépôt a des contraintes différentes sur les identifiants
     * qu'il accepte : un PostTypeRepository n'accepte que des entiers (postid),
     * un DirectoryRepository n'accepte que des noms de fichiers valides, un
     * SettingsRepository n'accepte que des options de moins de 64 caractères,
     * etc.
     *
     * @param scalar $id L'identifiant à tester.
     *
     * @throws BadIdException Si l'identifiant est invalide.
     */
    protected function checkId($id) {
        // L'implémentation par défaut accepte les entiers et les chaines
        if (is_int($id) || is_string($id)) {
            return $id;
        }

        // ID null ou incorrect
        throw new BadIdException($id, 'int/string');
    }

    /**
     * Teste si le dépôt contient l'entité indiquée.
     *
     * @param scalar $id L'identifiant de l'entité recherchée
     *
     * @eturn bool
     */
    abstract public function has($id);

    /**
     * Charge une entité depuis le dépôt.
     *
     * @param scalar $id L'identifiant de l'entité à charger.
     *
     * @param string $type Le type de l'entité à retourner (le nom complet de
     * la classe de l'entité) ou vide pour retourner les données brutes.
     *
     * @return array|Entity Retourne un tableau contenant les données chargées
     * (si $type est vide) ou une entité.
     *
     * @throws BadIdException Si l'identifiant indiqué est invalide (ID manquant
     * ou ayant un format invalide).
     *
     * @throws EntityNotFoundException Si l'entité n'existe pas dans le dépôt.
     *
     * @throws RepositoryException Si une erreur survient durant le chargement.
     */
    public final function load($id, $type = null) {
        // Vérifie que l'ID est correct
        $id = $this->checkId($id);

        // Charge les données de l'entité
        $data = $this->decode($this->readData($id), $id);

        // Retourne une entité si on a un type, les données brutes sinon
        return $type ? new $type($data, null, $id) : $data;
    }

    /**
     * Charge les données brutes d'une entité.
     *
     * @param scalar $id L'identifiant de l'identité à charger (déjà validé).
     *
     * @return array Les données brutes, non décodées, de l'entité.
     */
    abstract protected function readData($id);

    /**
     * Enregistre une entité dans le dépôt.
     *
     * Si l'entité existe déjà dans le dépôt (i.e. elle a déjà un ID), elle
     * est mise à jour. Dans le cas contraire, l'entité est ajoutée dans le
     * dépôt et un ID lui est alloué.
     *
     * @param Entity $entity L'entité à enregistrer.
     *
     * @return self $this
     *
     * @throws BadIdException Si l'identifiant de l'entité n'est pas valide (ID
     * ayant un format incorrect).
     *
     * @throws RepositoryException Si une erreur survient durant
     * l'enregistrement.
     */
    public final function save(Entity $entity) {
        // Vérifie que l'ID de l'entité est correct
        ! is_null($id = $entity->id()) && $id = $this->checkId($id);

        // Signale à l'entité qu'elle va être enregistrée
        $entity->beforeSave();

        // Ecrit les données de l'entité
        $newId = $this->writeData($id, $this->encode($entity->value()));

        // Si un ID a été alloué, on l'indique à l'entité
        is_null($id) && $entity->id($newId);

        // Signale à l'entité qu'elle a été enregistrée
        $entity->afterSave();

        // Ok
        return $this;
    }

    /**
     * Enregistre les données brutes d'une entité.
     *
     * @param scalar $id L'identifiant de l'identité (déjà validé).
     *
     * @param mixed $data Les données brutes, déjà encodées, de l'entité.
     *
     * @return scalar Retourne l'id de l'entité (soit l'id existant si l'entité
     * avait déjà un identifiant, soit l'id alloué si l'entité n'existait pas
     * déjà).
     */
    abstract protected function writeData($id, $data);

    /**
     * Supprime une entité du dépôt.
     *
     * @param scalar $id L'identifiant de l'entité à détruire.
     *
     * @return self $this
     *
     * @throws BadIdException Si l'identifiant indiqué est invalide (ID manquant
     * ou ayant un format invalide).
     *
     * @throws EntityNotFoundException Si l'entité n'existe pas dans le dépôt.
     *
     * @throws RepositoryException Si une erreur survient durant la suppression.
     */
    public final function delete($id) {
        // Vérifie que l'ID est correct
        $id = $this->checkId($id);

        // Charge les données de l'entité
        $this->removeData($id);

        // Ok
        return $this;
    }

    /**
     * Supprime les données d'une entité.
     *
     * @param scalar $id
     */
    abstract protected function removeData($id);

    /**
     * Encode les données d'une entité.
     *
     * L'implémentation par défaut encode les données en JSON.
     *
     * @param array $data
     *
     * @return mixed
     */
    protected function encode(array $data) {
        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Décode les données d'une entité.
     *
     * L'implémentation par défaut attend des données encodées en JSON.
     *
     * @param mixed $data
     * @param scalar $id
     *
     * @return array Les données décodées de l'entité.
     *
     * @throws RepositoryException Si les données ne peuvent pas être décodées.
     */
    protected function decode($data, $id) {
        if (! is_string($data)) {
            $msg = __('Invalid JSON data in entity %s (%s)', 'docalist-core');
            throw new RepositoryException(sprintf($msg, $id, gettype($data)));
        }
        $data = json_decode($data, true);

        // On doit obtenir un tableau (éventuellement vide), sinon erreur
        if (! is_array($data)) {
            $msg = __('JSON error while decoding entity %s: error %s', 'docalist-core');
            throw new RepositoryException(sprintf($msg, $id, json_last_error()));
        }

        return $data;
    }
}