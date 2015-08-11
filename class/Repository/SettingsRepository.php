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
 */
namespace Docalist\Repository;

use Docalist\Repository\Exception\BadIdException;
use Docalist\Repository\Exception\EntityNotFoundException;

/**
 * Un dépôt permettant de stocker des entités dans la table wp_options de
 * WordPress.
 *
 * Remarques :
 * - Les entités sont enregistrées en json dans la table wp_options de wordpress.
 * - Pour enregistrer une entité, celle-ci doit obligatoirement avoir une
 *   clé (le nom de l'option dans la table wordpress)
 */
class SettingsRepository extends Repository {
    public function __construct($type = 'Docalist\Type\Settings') {
        parent::__construct($type);
    }

    protected function checkId($id) {
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
     * @param scalar $id
     * @return string
     */
    protected function key($id) {
        return substr($id, 0, 9) === 'docalist-' ? $id : "docalist-$id";
    }

    public function has($id) {
        // Vérifie que l'ID est correct
        $id = $this->checkId($id);

        // Teste si l'option existe
        return false !== get_option($this->key($id));
    }

    protected function loadData($id) {
        // L'entité est stockée comme une option worpdress
        if (false === $data = get_option($this->key($id))) {
            throw new EntityNotFoundException($id);
        }

        // Ok
        return $data;
    }

    protected function saveData($id, $data) {
        // Alloue un ID si nécessaire
        is_null($id) && $id = uniqid();

        // L'entité est stockée comme une option worpdress
        update_option($this->key($id), $data);

        return $id;
    }

    protected function deleteData($id) {
        if (! delete_option($this->key($id))) {
            throw new EntityNotFoundException($id);
        }
    }

    public function count() {
        global $wpdb;

        $sql = "SELECT count(option_name) FROM $wpdb->options WHERE option_name like 'docalist-%'";
        return (int) $wpdb->get_var($sql);
    }

    public function deleteAll() {
        throw new \Exception(__METHOD__ . " n'est pas encore implémenté.");
    }
}