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
    protected static $fieldMap = [
     // 'post_author'           => '',
     // 'post_date'             => '',
     // 'post_date_gmt'         => '',
     // 'post_content'          => '',
     // 'post_title'            => '',
     // 'post_excerpt'          => '',
     // 'post_status'           => '',
     // 'comment_status'        => '',
     // 'ping_status'           => '',
     // 'post_password'         => '',
     // 'post_name'             => '',
     // 'to_ping'               => '',
     // 'pinged'                => '',
     // 'post_modified'         => '',
     // 'post_modified_gmt'     => '',
     // 'post_content_filtered' => '',
     // 'post_parent'           => '',
     // 'guid'                  => '',
     // 'menu_order'            => '',
     // 'post_type'             => '',
     // 'post_mime_type'        => '',
     // 'comment_count'         => '',
    ];

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
        $data = $this->postToEntity((array) $post);

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
        $entity->repository($this);
        $entity->primaryKey($primaryKey);

        // Terminé
        return $entity;
    }

    public function store(EntityInterface $entity) {
        global $wpdb;

        // Récupère la clé de l'entité et vérifie son type
        $primaryKey = $this->checkPrimaryKey($entity, false);

        // Crée le post wp à partir des données de l'entité
        $post = $this->entityToPost($entity);

        // Met à jour le post si on a une clé
        if ($primaryKey) {
            if (false === $wpdb->update($wpdb->posts, $post, array('ID' => $primaryKey))) {
                throw new RuntimeException($wpdb->last_error);
            }

            // Vide le cache pour ce post
            wp_cache_delete($primaryKey, 'posts');
        }

        // Crée un nouveau post sinon
        else {
            if (false === $wpdb->insert($wpdb->posts, $post)) {
                throw new RuntimeException($wpdb->last_error);
            }
            $entity->primaryKey((int) $wpdb->insert_id);
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

    /**
     * Indique si la base est vide.
     *
     * @return boolean
     */
    public function isEmpty() {
        global $wpdb;

        $type = $this->postType();
        $sql = "SELECT ID FROM $wpdb->posts WHERE post_type='$type' LIMIT 1";
        $count = $wpdb->query($sql); // false=error, 0=vide, 1=non vide
        if ($count === false) {
            die("ERREUR SQL : <code>$sql</code>");
        }

        return $count ? false : true; // ! $count
    }

    /**
     * Retourne le nombre de notices dans la base.
     *
     * @return int
     */
    public function count() {
        global $wpdb;

        $type = $this->postType();
        $sql = "SELECT count(*) FROM $wpdb->posts WHERE post_type='$type'";

        return (int) $wpdb->get_var($sql);
    }

    /**
     * Méthode utilitaire utilisée par deleteAll().
     *
     * @param string $sql Requête sql à exécuter.
     * @param string $msg Message à afficher.
     */
    private function sql($sql, $msg) {
        global $wpdb;

        $wpdb->query($sql);
        if ($wpdb->last_error) {
            $msg .= ". Erreur dans la requête SQL <code>$sql</code>";
            do_action('docalist_biblio_deleteall_progress', $msg);
        }
        else {
            $msg .= ' (' . $wpdb->rows_affected . ')';
            do_action('docalist_biblio_deleteall_progress', $msg);
        }
    }

    /**
     * Vide la base
     */
    public function deleteAll() {
        global $wpdb;

        $type = $this->postType();
        $count = $this->count();

        // Début de la suppression
        do_action('docalist_biblio_deleteall_start', $this, $count);

        // Toutes les notices
        $posts = "SELECT ID FROM $wpdb->posts WHERE post_type='$type'";

        // Révisions et autosaves de ces notices
        $revisions = "SELECT ID FROM $wpdb->posts WHERE post_type='revision' AND post_parent IN ($posts)";

        // Commentaires sur les notices et les révisions
        $comments = "SELECT comment_id FROM $wpdb->comments WHERE comment_post_id IN ($posts) OR comment_post_id IN ($revisions)";

        // Supprime les termes de taxonomies et des révisions
        $sql = "DELETE FROM $wpdb->term_relationships WHERE object_id IN ($posts) OR object_id IN ($revisions)";
        $msg = __('Suppression des termes', 'docalist-biblio');
        $this->sql($sql, $msg);

        // TODO : mettre à jour wp_term_taxonomy.count pour chacun des termes supprimés

        // Supprime les métas des notices et des révisions
        $sql = "DELETE FROM $wpdb->postmeta WHERE post_id IN ($posts) OR post_id IN ($revisions)";
        $msg = __('Suppression des méta-données des notices', 'docalist-biblio');
        $this->sql($sql, $msg);

        // Supprime les metas des commentaires (statut akismet, trash status, etc.)
        $sql = "DELETE FROM $wpdb->commentmeta WHERE comment_id IN ($comments)";
        $msg = __('Suppression des méta-données des commentaires', 'docalist-biblio');
        $this->sql($sql, $msg);

        // Supprime les commentaires des notices et des révisions
        $sql = "DELETE FROM $wpdb->comments WHERE comment_post_id IN ($posts) OR comment_post_id IN ($revisions)";
        $msg = __('Suppression des commentaires', 'docalist-biblio');
        $this->sql($sql, $msg);

        // Supprime les révisions et les autosaves

        // $sql = "DELETE FROM $wpdb->posts WHERE post_type='revision' AND post_parent IN ($posts)";

        // La requête ci-dessus ne fonctionne pas, on obtient une ERROR 1093
        // "You can't specify target table 'wp_posts' for update in FROM clause"
        // On ne peut pas utiliser une subquery qui porte sur la table dans
        // laquelle on supprime.
        // Pour que cela fonctionne, il faut passer par une table intermédiaire.
        // source : http://stackoverflow.com/a/12508381

        $sql = "DELETE FROM $wpdb->posts WHERE post_type='revision' AND post_parent IN (SELECT ID FROM ($posts) AS tmp)";
        $msg = __('Suppression des révisions', 'docalist-biblio');
        $this->sql($sql, $msg);

        // Mise à jour dans les autres bases des notices filles dont le parent est l'une des notices supprimées
        // $sql = "UPDATE $wpdb->posts SET post_parent=0 WHERE post_parent IN ($posts)"; // ERROR 1093
        $sql = "UPDATE $wpdb->posts SET post_parent=0 WHERE post_type!='$type' AND post_parent IN (SELECT ID FROM ($posts) AS tmp)";
        $msg = __('Mise à jour des notices filles', 'docalist-biblio');
        $this->sql($sql, $msg);

        // Supprime les notices
        $sql = "DELETE FROM $wpdb->posts WHERE post_type='$type'";
        $msg = __('Suppression des notices', 'docalist-biblio');
        $this->sql($sql, $msg);

        // Réinitialise la totalité du cache wordpress
        wp_cache_init();
        $msg = __('Réinitialisation du cache WordPress', 'docalist-biblio');
        do_action('docalist_biblio_deleteall_progress', $msg);

        // Fin de la suppression
        do_action('docalist_biblio_deleteall_done', $this, $count);
    }

    /**
     * Convertit les données d'un post wordpress dans le format de données
     * attendu par les entités de ce dépôt.
     *
     * Cette méthode est appellée par load() pour convertir un post wordpress
     * en entité.
     *
     * Par défaut, elle décode post_excerpt (récupère les champs spécifiques) et
     * copie les champs wordpress qui sont mappés vers des champs de l'entité
     * en utilisant la propriété self::$fieldMap.
     *
     * @param array $post
     *
     * @return array Les données de l'entité
     */
    public function postToEntity(array $post) {
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
        foreach(static::$fieldMap as $src => $dst) {
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
     * @param EntityInterface $entity
     *
     * @return array Un post wordpress sous la forme d'un tableau
     */
    public function entityToPost(EntityInterface $entity) {
        // Valeurs par défaut du post (champs dans l'ordre de la table wp_posts)
        $now = current_time('mysql');
        $nowGmt = current_time('mysql', true);
        $post = [
            'post_author'           => get_current_user_id(),
            'post_date'             => $now,
         // 'post_date_gmt'         => fait plus bas
            'post_content'          => '',
            'post_title'            => 'ref sans title',
            'post_excerpt'          => '',
            'post_status'           => 'publish',
            'comment_status'        => 'open',
            'ping_status'           => 'open',
            'post_password'         => '',
            'post_name'             => '',
            'to_ping'               => '',
            'pinged'                => '',
            'post_modified'         => $now,
            'post_modified_gmt'     => $nowGmt,
            'post_content_filtered' => '',
            'post_parent'           => 0,
            'guid'                  => '',
            'menu_order'            => 0,
            'post_type'             => $this->postType,
            'post_mime_type'        => '',
            'comment_count'         => 0,
        ];

        // Transfère les champs virtuels de la notice dans le post wordpress
        foreach(static::$fieldMap as $dst => $src) {
            if (isset($entity->$src)) {
                $post[$dst] = $entity->$src;
                unset($entity->$src);
            }
        }

        // Encode le reste des données en json dans post_excerpt
        $options = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
        WP_DEBUG && $options |= JSON_PRETTY_PRINT;
        $post['post_excerpt'] = json_encode($entity, $options);

        // Valeur par défaut des champs dont le contenu dépend d'autres champs
        if (! isset($post['post_date_gmt'])) {
            $post['post_date_gmt'] = get_gmt_from_date($post['post_date']);
        }

        // Terminé
        return $post;
    }
}