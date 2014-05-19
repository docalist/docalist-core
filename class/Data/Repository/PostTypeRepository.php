<?php
/**
 * This file is part of a "Docalist Core" plugin.
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package Docalist
 * @subpackage Core
 * @author Daniel Ménard <daniel.menard@laposte.net>
 * @version SVN: $Id$
 */
namespace Docalist\Data\Repository;

use Docalist\Data\Entity\EntityInterface;
use Docalist\Utils;
use WP_Post;
use InvalidArgumentException, RuntimeException;
use StdClass;

/**
 * Un dépôt dans lequel les entités sont stockées sous forme de Custom Post
 * Types WorPress.
 */
class PostTypeRepository extends AbstractRepository {
    /**
     * Le nom du custom post type, c'est-à-dire la valeur qui sera stockée dans
     * le champ post_type de la table wp_posts pour chacun des documents créés.
     *
     * @var string
     */
    protected $postType;

    /**
     * Liste des champs wordpress qui sont mappés vers un champ de l'entité.
     *
     * Les classes descendantes doivent surcharger la propriété en fonction
     * des champs définis dans l'entité.
     *
     * @var array Un tableau de la forme "champ wordpress" => "champ entité".
     */
    protected static $fieldMap = [];

    /**
     * Crée un nouveau dépôt.
     *
     * @param string $type le nom complet de la classe Entité utilisée pour
     * représenter les enregistrements de ce dépôt.
     *
     * @param string $postType Le nom du custom post type.
     *
     * @throws InvalidArgumentException Si $type ne désigne pas une classe d'entité.
     */
    public function __construct($type, $postType) {
        parent::__construct($type);
        $this->postType = $postType;
    }

    /**
     * Retourne le nom du custom post type, c'est-à-dire la valeur qui sera
     * stockée dans le champ post_type de la table wp_posts pour chacun des
     * documents créés.
     *
     * @return string
     */
    public function postType() {
        return $this->postType;
    }

    public function load($entity, $type = null) {
        // Vérifie qu'on a un ID
        $primaryKey = $this->checkPrimaryKey($entity, true);

        // Charge le post
        if (false === $post = WP_Post::get_instance($primaryKey)) {
            $msg = "La référence %s n'existe pas";
            $msg = sprintf($msg, $primaryKey);
            throw new RuntimeException($msg);
        }

        // Convertit le post en données d'entité
        $data = $this->postToEntity($post);

        // Type = false permet de récupérer les données brutes
        if ($type === false) {
            return $data;
        }

        // Par défaut, on retourne une entité du même type que le dépôt
        if (is_null($type)) {
            $type = $this->type;
        }

        // Sinon le type demandé doit être compatible avec le type du dépôt
        else {
            $this->checkType($type);
        }

        // Crée une entité du type demandé
        $entity = new $type($data);
        $entity->primaryKey($primaryKey);

        // Terminé
        return $entity;
    }

    public function store(EntityInterface $entity) {
        global $wpdb;

        // Récupère la clé de l'entité et vérifie son type
        $primaryKey = $this->checkPrimaryKey($entity, false);

        // Charge le post existant si on a une clé, créée un nouveau post sinon
        if ($primaryKey) {
            if (false === $post = WP_Post::get_instance($primaryKey)) {
                $msg = 'Post %s not found';
                throw new RuntimeException(sprintf($msg, $primaryKey));
            }
        } else {
            // wp nous oblige à passer un objet vide...
            $post = new WP_Post(new StdClass());
        }

        // Crée le post wp à partir des données de l'entité
        $post = (array) $post;
        $this->entityToPost($entity, $post);

        // Supprime les champ sen trop
        unset($post['filter']);
        unset($post['format_content']);

        // Met à jour le post si on a une clé
        if ($primaryKey) {
            if (false === $wpdb->update($wpdb->posts, $post, array('ID' => $primaryKey))) {
                throw new RuntimeException($wpdb->last_error);
            }

            // Vide le cache pour ce post (Important, cf WP_Post::get_instance)
            wp_cache_delete($primaryKey, 'posts');
        }

        // Crée un nouveau post sinon
        else {
            if (false === $wpdb->insert($wpdb->posts, $post)) {
                throw new RuntimeException($wpdb->last_error);
            }
            $primaryKey = (int) $wpdb->insert_id;
            $entity->primaryKey($primaryKey);
        }
    }

    public function delete($entity) {
        global $wpdb;

        $primaryKey = $this->checkPrimaryKey($entity, true);

        $result = $wpdb->delete($wpdb->posts, array('ID' => $primaryKey));
        if ($result === false) {
            $msg = 'Unable to delete post %s: %s';
            throw new RuntimeException($msg, $primaryKey, $wpdb->last_error);
        } elseif ($result === 0) {
            $msg = 'Post %s not found';
            throw new RuntimeException(sprintf($msg, $primaryKey));
        }
    }

    public function deleteAll() {
        global $wpdb;

        // Supprime tous les enregs
        $nb = $wpdb->delete($wpdb->posts, array('post_type' => $this->postType));

        // Réinitialise les séquences éventuelles utilisées par cette base
        docalist('sequences')->clear($this->postType);

        // Retourne le nombre de notices supprimées
        return $nb;
    }

    /**
     * Convertit les données d'un post wordpress dans le format de données
     * attendu par les entités de ce dépôt.
     *
     * Cette méthode est appellée par load() pour convertir un post wordpress
     * en entité.
     *
     * - Décode post_excerpt (on récupère les champs spécifiques)
     * - Copie les champs wordpress qui sont mappés vers des champs de l'entité
     *   en utilisant la propriété self::$fieldMap.
     *
     * @param array|WP_Post $post
     *
     * @return array Les données de l'entité
     */
    public function postToEntity($post) {
        is_object($post) && $post = (array) $post;

        // Si c'est un nouveau post, il se peut que post_excerpt soit vide
        if (empty($post['post_excerpt'])) {
            $data = [];
        }

        // Sinon, post_excerpt doit contenir du JSON valide
        else {
            $data = json_decode($post['post_excerpt'], true);

            // On doit obtenir un tableau (éventuellement vide), sinon erreur
            if (! is_array($data)) {
                $msg = 'JSON error %s while decoding field post_excerpt of post %s';
                $msg = sprintf($msg, json_last_error(), var_export($post, true));
                throw new RuntimeException($msg);
            }
        }

        // Initialise les champs virtuels de la notice à partir des champs wordpress
        foreach(self::$fieldMap as $src => $dst) {
            if (isset($post[$src])) {
                $data[$dst] = $post[$src];
            }
        }

        // Terminé
        return $data;
    }

    /**
     * Convertit les données d'une entité en post wordpress.
     *
     * @param array $entity
     * @param array Un post wordpress sous la forme d'un tableau
     */
    public function entityToPost(EntityInterface $entity, array & $post) {
        // Transfère les champs virtuels de la notice dans le post wordpress
        foreach(self::$fieldMap as $dst => $src) {
            if (isset($entity->$src)) {
                $post[$dst] = $entity->$src;
                unset($entity->$src);
            }
        }

        // Encode les données de l'entité en JSON
        $options = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
        WP_DEBUG && $options |= JSON_PRETTY_PRINT;
        $data = json_encode($entity, $options);

        // Stocke le JSON dans le champ post_excerpt
        $post['post_excerpt'] = $data;

        // Terminé
        return $post;
    }
}