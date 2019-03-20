<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Type;

use Docalist\Schema\Schema;
use Docalist\Repository\Repository;

/**
 * Classe de base pour les entités.
 *
 * Une entité est un composite qui dispose d'une identifiant unique (ID) et qui peut être enregistré dans un dépôt.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class Entity extends Composite
{
    /**
     * Identifiant de l'entité.
     *
     * @var scalar|null
     */
    protected $id;

    /**
     * Construit une nouvelle entité.
     *
     * @param array|null     $value  Un tableau contenant les données initiales de l'entité.
     * @param Schema|null    $schema Optionnel, le schéma de l'entité.
     * @param scalar|null    $id     Optionnel, l'ID de l'entité.
     */
    public function __construct(array $value = null, Schema $schema = null, $id = null)
    {
        parent::__construct($value, $schema);
        ! is_null($id) && $this->setID($id);
    }

    /**
     * Retourne l'identifiant unique de l'entité (ID).
     *
     * @return scalar|null Retourne l'ID de l'entité ou null si l'entité n'a pas encore d'ID.
     */
    public function getID()
    {
        return $this->id;
    }

    /**
     * Définit l'identifiant unique de l'entité (ID).
     *
     * @param scalar $id L'identifiant de l'entité
     */
    public function setID($id): void
    {
        $this->id = $id;
    }

    /**
     * Cette méthode est appellée juste avant que l'entité ne soit enregistrée dans un dépôt.
     *
     * @param Repository $repository Le dépôt dans lequel l'entité va être enregistrée.
     */
    public function beforeSave(Repository $repository): void
    {
    }

    /**
     * Cette méthode est appellée juste après que l'entité a été enregistrée dans un dépôt.
     *
     * @param Repository $repository Le dépôt dans lequel l'entité a été enregistrée.
     */
    public function afterSave(Repository $repository): void
    {
    }
}
