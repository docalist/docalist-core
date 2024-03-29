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

namespace Docalist\Repository;

use Docalist\Type\Entity;
use Docalist\Repository\Exception\BadIdException;
use Docalist\Repository\Exception\EntityNotFoundException;
use Exception;
use wpdb;

/**
 * Un dépôt permettant de stocker des entités dans la table wp_options de
 * WordPress.
 *
 * Remarques :
 * - Les entités sont enregistrées en json dans la table wp_options de wordpress.
 * - Pour enregistrer une entité, celle-ci doit obligatoirement avoir une
 *   clé (le nom de l'option dans la table wordpress)
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class SettingsRepository extends Repository
{
    public function __construct($type = Entity::class)
    {
        parent::__construct($type);
    }

    protected function checkId(mixed $id): int|string
    {
        // On n'accepte que des chaines de caractères
        if (!is_string($id)) {
            throw new BadIdException($id, 'string');
        }

        // Toutes les clés commencent par le préfixe 'docalist-'
        // - pour retrouver tous les settings dans la table wp_options
        // - pour qu'on ne puisse pas lire/modifier autre chose qu'un settings
        if (substr($id, 0, 9) !== 'docalist-') {
            $id = 'docalist-' . $id;
        }

        // Longueur maxi : 64 caractères
        if (strlen($id) > 64) {
            throw new BadIdException($id, 'max len 55'); // 64 - 'docalist-'
        }

        // Au format 'abcd-efgh' (en minuscules)
        if (! preg_match('~[a-z0-9]+(?:-[a-z0-9]+)*~', $id)) {
            throw new BadIdException($id, 'format "abcd-efgh" (lowercase)');
        }

        // Ok
        return $id;
    }

    /**
     * Détermine le nom de l'option dans la table wp_options à partir de l'id
     * indiqué.
     *
     * Pour permettre de retrouver tous les settings, on ajoute le préfixe
     * 'docalist-' à l'ID s'id ne le contient pas déjà.
     *
     * @param int|string $id
     * @return string
     */
    protected function key($id)
    {
        $id = (string) $id;
        return substr($id, 0, 9) === 'docalist-' ? $id : "docalist-$id";
    }

    public function has(int|string $id): bool
    {
        // Vérifie que l'ID est correct
        $id = $this->checkId($id);

        // Teste si l'option existe
        return false !== get_option($this->key($id));
    }

    protected function loadData(int|string $id): mixed
    {
        // L'entité est stockée comme une option worpdress
        if (false === $data = get_option($this->key($id))) {
            throw new EntityNotFoundException($id);
        }

        // Ok
        return $data;
    }

    protected function saveData(int|string|null $id, mixed $data): int|string
    {
        // Alloue un ID si nécessaire
        if (is_null($id)) {
            $id = uniqid();
        }

        // L'entité est stockée comme une option worpdress
        update_option($this->key($id), $data);

        return $id;
    }

    protected function deleteData(int|string $id): void
    {
        if (! delete_option($this->key($id))) {
            throw new EntityNotFoundException($id);
        }
    }

    public function count(): int
    {
        $wpdb = docalist(wpdb::class);

        $sql = "SELECT count(option_name) FROM $wpdb->options WHERE option_name like 'docalist-%'";

        return (int) $wpdb->get_var($sql);
    }

    public function deleteAll(): void
    {
        throw new Exception(__METHOD__ . " n'est pas encore implémenté.");
    }
}
