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
        // Vérifie qu'on a une clé primaire
        $primaryKey = $this->checkPrimaryKey($entity, true);

        // Charge le post
        $post = WP_Post::get_instance($primaryKey);

        // Récupère les données de l'entité, stockées dans post_excerpt
        $data = $post->post_excerpt;

        // Si c'est un nouveau post, post_excerpt est vide
        if ($data === '') {
            $data = array();
        }

        // Sinon, post_excerpt doit contenir du JSON valide
        else {
            $data = json_decode($post->post_excerpt, true);

            // On doit obtenir un tableau (éventuellement vide), sinon c'est une erreur
            if (! is_array($data)) {
                $msg = 'JSON error %s while decoding field post_excerpt of post %s: %s';
                $msg = sprintf($msg, json_last_error(), $primaryKey, var_export($post->post_excerpt, true));
                throw new RuntimeException($msg);
            }
        }

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

        $this->synchronize($post, $entity, false);

        return $entity;
    }

    /**
     * Synchronise les données de l'entité et du post WordPress.
     *
     * Cette méthode permet d'avoir dans l'entité des "champs virtuels" qui
     * sont "mappés" vers des champs existants du post WordPress.
     *
     * @param WP_Post $post Le post
     * @param EntityInterface $entity l'entité
     * @param bool $save Sens de la synchronisation.
     * - Si save est à false, les données du post sont transférées vers
     *   l'entité (i.e. synchronisation lors du chargement d'une notice).
     * - Si save est à true, les données de l'entité sont transférées vers le
     *   post (i.e. synchronisation du post avant enregistrement dans la base).
     */
    protected function synchronize(WP_Post $post, EntityInterface $entity, $save = false) {
        // Recopier ref dans post
        if ($save) {
            $post->post_type = $this->postType();
        }

        // else recopier post dans ref
    }

    public function store(EntityInterface $entity) {
        global $wpdb;

        // Vérifie que l'entité est du bon type
        $this->checkType($entity);

        // Récupère la clé de l'entité
        $primaryKey = $entity->primaryKey();

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

        // Synchronise le post wp à partir des données de l'entité
        // Permet également de modifier l'entité avant enregistrement (par
        // exemple, affecter une valeur au champ Ref).
        $this->synchronize($post, $entity, true);

        // Encode les données de l'entité en JSON
        $options = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
        WP_DEBUG && $options |= JSON_PRETTY_PRINT;
        $data = json_encode($entity, $options);

        // Stocke le JSON dans le champ post_excerpt
        $post->post_excerpt = $data;

        // Pour wpdb, il faut maintenant un tableau et non plus un WP_Post
        $post = (array) $post;
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
}