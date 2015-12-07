<?php

/**
 * This file is part of a "Docalist Core" plugin.
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
namespace Docalist\Type;

use Docalist\Schema\Schema;
use Docalist\Repository\Repository;
use Docalist\MappingBuilder;
use LogicException;

/**
 * Classe de base pour les entités.
 *
 * Une entité est un objet qui dispose d'une identité (un ID unique).
 */
class Entity extends Composite
{
    /**
     * L'identifiant de l'entité.
     *
     * @var scalar
     */
    protected $id;

    /**
     * Construit une nouvelle entité.
     *
     * @param array $value
     * @param Schema $schema
     * @param scalar $id
     */
    public function __construct(array $value = null, Schema $schema = null, $id = null)
    {
        parent::__construct($value, $schema ?: $this::defaultSchema());
        ! is_null($id) && $this->id($id);
    }

    /**
     * Retourne ou modifie l'identifiant de l'entité.
     *
     * @param scalar $id Appellée sans argument, la méthode retourne
     * l'identifiant de l'entité ou null si l'entité n'a pas encore d'identité.
     * Appellée avec un argument, elle initialise l'identifiant de l'entité
     *
     * Attention : on ne peut définir l'identifiant de l'entité qu'une seule
     * fois, celui-ci est en lecture seule une fois défini.
     *
     * @return scalar|self
     *
     * @throws LogicException Si vous essayer de modifier l'identifiant d'une
     * entité qui a déjà un id.
     */
    public function id($id = null)
    {
        // Getter
        if (is_null($id)) {
            return $this->id;
        }

        // Setter. Vérifie que l'id n'a pas déjà été défini
        if (! is_null($this->id)) {
            $msg = 'ID already set (%s) for entity "%s"';
            throw new LogicException(sprintf($msg, $this->id, get_class($this)));
        }

        // Stocke l'id
        $this->id = $id;

        return $this;
    }

    /**
     * Cette méthode est appellée juste avant que l'entité ne soit enregistrée
     * dans un dépôt.
     */
    public function beforeSave(Repository $repository)
    {
    }

    /**
     * Cette méthode est appellée juste après que l'entité a été enregistrée
     * dans un dépôt.
     */
    public function afterSave(Repository $repository)
    {
    }

    // -------------------------------------------------------------------------
    // Interface Indexable
    // -------------------------------------------------------------------------

    public function setupMapping(MappingBuilder $mapping)
    {
        foreach($this->schema()->getFieldNames() as $field) {
            $this->__get($field)->setupMapping($mapping);
        }
    }

    public function mapData(array & $document)
    {
        foreach($this->getFields() as $field) {
            $field->mapData($document);
        }
    }
}
