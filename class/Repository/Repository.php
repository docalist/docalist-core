<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Repository;

use Docalist\Type\Entity;
use Docalist\Repository\Exception\BadIdException;
use Docalist\Repository\Exception\RepositoryException;
use Docalist\Repository\Exception\EntityNotFoundException;

/**
 * Un repository est un dépôt dans lequel on peut stocker des entités.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
abstract class Repository
{
    /**
     * Le type par défaut des entités de ce dépôt.
     *
     * @var string
     */
    protected $type;

    /**
     * Construit un nouveau dépôt.
     *
     * @param string $type Optionnel, le nom de classe complet des entités de
     * ce dépôt. C'est le type qui sera utilisé par load() si aucun type
     * n'est indiqué lors de l'appel.
     */
    public function __construct($type = Entity::class)
    {
        $this->type = $type;
    }

    /**
     * Retourne le type par défaut des entités de ce dépôt.
     *
     * @return string Le nom de classe complet des entités.
     */
    final public function type()
    {
        return $this->type;
    }

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
     * @param int|string $id L'identifiant à tester.
     *
     * @throws BadIdException Si l'identifiant est invalide.
     */
    protected function checkId($id)
    {
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
     * @param int|string $id L'identifiant de l'entité recherchée
     *
     * @eturn bool
     */
    abstract public function has($id);

    /**
     * Charge une entité depuis le dépôt.
     *
     * @param int|string $id L'identifiant de l'entité à charger.
     *
     * @return Entity Retourne l'entité.
     *
     * @throws BadIdException Si l'identifiant indiqué est invalide (ID manquant
     * ou ayant un format invalide).
     *
     * @throws EntityNotFoundException Si l'entité n'existe pas dans le dépôt.
     *
     * @throws RepositoryException Si une erreur survient durant le chargement.
     */
    public function load($id)
    {
        $type = $this->type;
        return new $type($this->loadRaw($id), null, $id);
    }

    /**
     * Retourne les données brutes d'un entité stockée dans le dépôt.
     *
     * @param int|string $id L'identifiant de l'entité à charger.
     *
     * @return array
     *
     * @throws BadIdException Si l'identifiant indiqué est invalide (ID manquant
     * ou ayant un format invalide).
     *
     * @throws EntityNotFoundException Si l'entité n'existe pas dans le dépôt.
     *
     * @throws RepositoryException Si une erreur survient durant le chargement.
     */
    final public function loadRaw($id)
    {
        // Vérifie que l'ID est correct
        $id = $this->checkId($id);

        // Charge les données de l'entité
        return $this->decode($this->loadData($id), $id);
    }

    /**
     * Charge les données brutes d'une entité.
     *
     * @param int|string $id L'identifiant de l'identité à charger (déjà validé).
     *
     * @return array Les données brutes, non décodées, de l'entité.
     */
    abstract protected function loadData($id);

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
    final public function save(Entity $entity)
    {
        // Vérifie que l'ID de l'entité est correct
        ! is_null($id = $entity->getID()) && $id = $this->checkId($id);

        // Signale à l'entité qu'elle va être enregistrée
        $entity->beforeSave($this);

        // Ecrit les données de l'entité
        $newId = $this->saveData($id, $this->encode($entity->getPhpValue()));

        // Si un ID a été alloué, on l'indique à l'entité
        is_null($id) && $entity->setID($newId);

        // Signale à l'entité qu'elle a été enregistrée
        $entity->afterSave($this);

        // Ok
        return $this;
    }

    /**
     * Enregistre les données brutes d'une entité.
     *
     * @param int|string $id L'identifiant de l'identité (déjà validé).
     *
     * @param mixed $data Les données brutes, déjà encodées, de l'entité.
     *
     * @return int|string Retourne l'id de l'entité (soit l'id existant si l'entité
     * avait déjà un identifiant, soit l'id alloué si l'entité n'existait pas
     * déjà).
     */
    abstract protected function saveData($id, $data);

    /**
     * Supprime une entité du dépôt.
     *
     * @param int|string $id L'identifiant de l'entité à détruire.
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
    final public function delete($id)
    {
        // Vérifie que l'ID est correct
        $id = $this->checkId($id);

        // Charge les données de l'entité
        $this->deleteData($id);

        // Ok
        return $this;
    }

    /**
     * Supprime les données d'une entité.
     *
     * @param int|string $id
     */
    abstract protected function deleteData($id);

    /**
     * Encode les données d'une entité.
     *
     * L'implémentation par défaut encode les données en JSON.
     *
     * @param array $data
     *
     * @return mixed
     */
    protected function encode(array $data)
    {
        // Essaie d'encoder les données
        $result = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // Vérifie que json_encode() a réussi et retourne le résultat
        if ($result !== false) {
            return $result;
        }

        // Récupère le message d'erreur
        $err = json_last_error_msg();

        // Essaie de déterminer les champs responsables
        $fields = [];
        foreach (array_keys($data) as $field) {
            if (false === json_encode($data[$field], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) {
                $fields[] = $field;
            }
        }

        // Génère l'exception
        $msg = sprintf(__('Invalid data, json_encode() says "%s".', 'docalist-core'), $err);
        if (!empty($fields)) {
            $msg .= ' The error is probably in fields ' . implode(', ', $fields) . '.';
        }

        throw new RepositoryException($msg);
    }

    /**
     * Décode les données d'une entité.
     *
     * L'implémentation par défaut attend des données encodées en JSON.
     *
     * @param mixed $data
     * @param int|string $id
     *
     * @return array Les données décodées de l'entité.
     *
     * @throws RepositoryException Si les données ne peuvent pas être décodées.
     */
    protected function decode($data, $id)
    {
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

    /**
     * Retourne le nombre d'entités stockées dans le dépôt.
     *
     * @return int
     */
    abstract public function count();

    /**
     * Supprime toutes les entités stockées dans le dépôt.
     *
     * Le dépôt lui-même n'est pas supprimé.
     */
    abstract public function deleteAll();
}
