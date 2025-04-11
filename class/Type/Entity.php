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

namespace Docalist\Type;

use Docalist\Repository\Repository;
use Docalist\Schema\Schema;

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
     */
    protected int|string|null $id = null;

    /**
     * Construit une nouvelle entité.
     *
     * @param array<mixed>|null $value  Un tableau contenant les données initiales de l'entité.
     * @param Schema|null       $schema Optionnel, le schéma de l'entité.
     * @param int|string|null   $id     Optionnel, l'ID de l'entité.
     */
    public function __construct(array|null $value = null, Schema|null $schema = null, int|string|null $id = null)
    {
        parent::__construct($value, $schema);
        !is_null($id) && $this->setID($id);
    }

    /**
     * Retourne l'identifiant unique de l'entité (ID).
     *
     * @return int|string|null Retourne l'ID de l'entité ou null si l'entité n'a pas encore d'ID.
     */
    public function getID(): int|string|null
    {
        return $this->id;
    }

    /**
     * Définit l'identifiant unique de l'entité (ID).
     *
     * @param int|string|null $id L'identifiant de l'entité
     */
    public function setID(int|string|null $id): void
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
