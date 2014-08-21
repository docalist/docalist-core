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
namespace Docalist\Repository;

use WP_Post;
use Docalist\Repository\Exception\BadIdException;
use Docalist\Repository\Exception\RepositoryException;
use Docalist\Repository\Exception\EntityNotFoundException;

/**
 * Un dépôt permettant de stocker des entités dans la table wp_posts de
 * WordPress.
 */
class PostTypeRepository extends Repository {
    /**
     * Le nom du custom post type, c'est-à-dire la valeur qui sera stockée dans
     * le champ post_type de la table wp_posts pour chacune des entités de ce
     * dépôt.
     *
     * @var string
     */
    protected $postType;

    /**
     * Mapping entre les champs wordpress et les champs de l'entité.
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
     * @param string $postType Le nom du custom post type.
     */
    public function __construct($postType) {
        $this->postType = $postType;
    }

    /**
     * Retourne le nom du custom post type, c'est-à-dire la valeur qui sera
     * stockée dans le champ post_type de la table wp_posts pour chacune des
     * entités de ce dépôt.
     *
     * @return string
     */
    public function postType() {
        return $this->postType;
    }

    protected function checkId($id) {
        // On n'accepte que des entiers positifs non nuls
        if (is_int($id) && $id > 0) {
            return $id;
        }

        if (is_string($id) && ctype_digit($id)) {
            $int = (int) $id;
            if ($id > 0) {
                return $int;
            }
        }

        // ID null ou incorrect
        throw new BadIdException($id, 'int > 0');
    }

    public function has($id) {
        // Vérifie que l'ID est correct
        $id = $this->checkId($id);

        // Teste si le post existe
        return false !== WP_Post::get_instance($id);
    }

    protected function loadData($id) {
        // Charge le post wordpress
        if (false === $post = WP_Post::get_instance($id)) {
            throw new EntityNotFoundException($id);
        }
// TODO : if post_type pas bon -> Exception
        // Ok
        return (array) $post;
    }

    protected function saveData($id, $post) {
        global $wpdb;

        // Insère ou met à jour le post si l'entité a déjà un ID
        if ($id) {
//            if (false === $wpdb->update($wpdb->posts, $post, ['ID' => $id, 'post_type' => $this->postType])) {
            $post['ID'] = $id;
            $post['post_type'] = $this->postType;
            if (false === $wpdb->replace($wpdb->posts, $post)) {
                throw new RepositoryException($wpdb->last_error);
            }

            // Vide le cache pour ce post
            wp_cache_delete($id, 'posts');
        }

        // Crée un nouveau post sinon
        else {
            if (false === $wpdb->insert($wpdb->posts, $post)) {
                throw new RepositoryException($wpdb->last_error);
            }
            $id = (int) $wpdb->insert_id; // insert retourne une chaine
        }

        // Ok
        return $id;
    }

    protected function deleteData($id) {
        if (! wp_delete_post($id, true)) {
            throw new EntityNotFoundException($id);
        }
    }

    /**
     * Indique si la base est vide.
     *
     * @return boolean
     */
/*
    public function isEmpty() {
        global $wpdb;

        $sql = "SELECT ID FROM $wpdb->posts WHERE post_type='$this->postType' LIMIT 1";
        $count = $wpdb->query($sql); // false=error, 0=vide, 1=non vide
        if ($count === false) {
            die("ERREUR SQL : <code>$sql</code>");
        }

        return $count ? false : true; // ! $count
    }
*/
    /**
     * Retourne le nombre de notices dans la base.
     *
     * @return int
     */
/*
    public function count() {
        global $wpdb;

        $type = $this->postType();
        $sql = "SELECT count(*) FROM $wpdb->posts WHERE post_type='$type'";

        return (int) $wpdb->get_var($sql);
        // à voir wp_count_posts() retourne un objet = count par post_status
        // faire la somme pour éviter la requête sql içi ?
    }
*/
    /**
     * Méthode utilitaire utilisée par deleteAll().
     *
     * @param string $sql Requête sql à exécuter.
     * @param string $msg Message à afficher.
     */
/*
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
*/
    /**
     * Vide la base
     */
/*
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
*/
    /**
     * Convertit les données d'une entité en post WordPress.
     *
     * @param array $data Les données de l'entité à convertir.
     *
     * @return array Un post wordpress sous la forme d'un tableau.
     */
    protected function encode(array $data) {
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
            if (isset($data[$src])) {
                $post[$dst] = $data[$src];
                unset($data[$src]);
            }
        }

        // Encode le reste des données en json dans post_excerpt
        $post['post_excerpt'] = parent::encode($data);

        // Valeur par défaut des champs dont le contenu dépend d'autres champs
        if (! isset($post['post_date_gmt'])) {
            $post['post_date_gmt'] = get_gmt_from_date($post['post_date']);
        }

        // Terminé
        return $post;
    }

    /**
     * Convertit un post WordPress en données d'entité.
     *
     * @param array Les données du post WordPress.
     * @param int $id L'ID du post
     *
     * @return array Les données de l'entité.
     */
    protected function decode($post, $id) {
        // Si c'est un nouveau post, il se peut que post_excerpt soit vide
        if (empty($post['post_excerpt'])) {
            die('post_excerpt vide ' . __FILE__ . ':' . __LINE__);
            $data = [];
        }

        // Sinon, post_excerpt doit contenir du JSON valide
        else {
            $data = parent::decode($post['post_excerpt'], $id);
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
}