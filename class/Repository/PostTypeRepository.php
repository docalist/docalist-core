<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 */
namespace Docalist\Repository;

use Docalist\Type\Entity;
use WP_Post;
use Docalist\Repository\Exception\BadIdException;
use Docalist\Repository\Exception\RepositoryException;
use Docalist\Repository\Exception\EntityNotFoundException;

/**
 * Un dépôt permettant de stocker des entités dans la table wp_posts de WordPress.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class PostTypeRepository extends Repository
{
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
     *
     * @param string $type Optionnel, le nom de classe complet des entités de
     * ce dépôt. C'est le type qui sera utilisé par load() si aucun type
     * n'est indiqué lors de l'appel.
     */
    public function __construct($postType, $type = Entity::class)
    {
        // Initialise le dépôt
        parent::__construct($type);

        // Stocke le post type wordpress de ce dépôt
        $this->postType = $postType;
    }

    /**
     * Retourne le nom du custom post type, c'est-à-dire la valeur qui sera
     * stockée dans le champ post_type de la table wp_posts pour chacune des
     * entités de ce dépôt.
     *
     * @return string
     */
    public function postType()
    {
        return $this->postType;
    }

    protected function checkId($id)
    {
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

    public function has($id)
    {
        // Vérifie que l'ID est correct
        $id = $this->checkId($id);

        // Teste si le post existe
        return false !== WP_Post::get_instance($id);
    }

    protected function loadData($id)
    {
        // Charge le post wordpress
        if (false === $post = WP_Post::get_instance($id)) {
            throw new EntityNotFoundException($id);
        }

        // Ok
        return (array) $post;
    }

    protected function saveData($id, $post)
    {
        $wpdb = docalist('wordpress-database');

        // Injecte les valeurs par défaut (uniquement celles qui sont indispensables)
        $this->postDefaults($post);

        // Insère ou met à jour le post si l'entité a déjà un ID
        if ($id) {
            $post['ID'] = $id;
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

        // Exécute l'action transition_post_status pour permettre l'indexation docalist search
        $post = WP_Post::get_instance($id);
        wp_transition_post_status($post->post_status, 'unknown', $post);

        // Ok
        return $id;
    }

    /**
     * Attribue une valeur par défaut aux différents champs du post passé en
     * paramètre.
     *
     * Cette méthode est appellée par saveData() juset avant que le post
     * wordpress ne soit enregistré.
     *
     * @param array $post
     */
    protected function postDefaults(array & $post)
    {
        /*
         * Remarques :
         * - get_default_post_to_edit() n'est pas adaptée : cela prend en compte
         *   ce que contient l'url en cours et cela applique des options qui
         *   sont spécifiques aux articles (comment_status, etc.)
         * - les champs sont listés dans l'ordre de la table wp_posts
         * - vérifier ce que fait wp_insert_post().
         */

        // Laisse ID vide pour permettre à mysql d'injecter un autonumber
        if (!isset($post['ID'])) {
            $post['ID'] = 0;
        }
        if (!isset($post['post_author'])) {
            $post['post_author'] = get_current_user_id();
        }
        if (!isset($post['post_date'  ])) {
            $post['post_date'  ] = current_time('mysql');
        }
        if (!isset($post['post_date_gmt'])) {
            $post['post_date_gmt'] = get_gmt_from_date($post['post_date']);
        }
        if (!isset($post['post_content'])) {
            $post['post_content'] = '';
        }
        if (!isset($post['post_title'])) {
            $post['post_title'] = '(no title)';
        }
        if (!isset($post['post_excerpt'])) {
            $post['post_excerpt'] = '{}';
        }
        if (!isset($post['post_status'])) {
            $post['post_title'] = 'publish'; // option ?
        }
        if (!isset($post['comment_status'])) {
            $post['comment_status'] = 'open'; // option ?
        }
        if (!isset($post['ping_status'])) {
            $post['ping_status'] = 'open'; // option ?
        }
        if (!isset($post['post_password'])) {
            $post['post_password'] = '';
        }
        if (!isset($post['post_name'])) {
            $post['post_name'] = '';
        }
        if (!isset($post['to_ping'])) {
            $post['to_ping'] = '';
        }
        if (!isset($post['pinged'])) {
            $post['pinged'] = '';
        }
        if (!isset($post['post_modified'])) {
            $post['post_modified'] = $post['post_date'];
        }
        if (!isset($post['post_modified_gmt'])) {
            $post['post_modified_gmt'] = $post['post_date_gmt'];
        }
        if (!isset($post['post_content_filtered'])) {
            $post['post_content_filtered'] = '';
        }
        if (!isset($post['post_parent'])) {
            $post['post_parent'] = 0;
        }
        if (!isset($post['guid'])) {
            $post['guid'] = ''; // ???
        }
        if (!isset($post['menu_order'])) {
            $post['menu_order'] = 0;
        }
        $post['post_type'] = $this->postType;
        if (!isset($post['post_mime_type'])) {
            $post['post_mime_type'] = '';
        }
        if (!isset($post['comment_count'])) {
            $post['comment_count'] = 0;
        }
    }

    protected function deleteData($id)
    {
        if (! wp_delete_post($id, true)) {
            throw new EntityNotFoundException($id);
        }
    }

    public function count()
    {
        $wpdb = docalist('wordpress-database');

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
    private function sql($sql, $msg)
    {
        $wpdb = docalist('wordpress-database');

        $wpdb->query($sql);
        if ($wpdb->last_error) {
            $msg .= ". Erreur dans la requête SQL <code>$sql</code>";
            do_action('docalist_biblio_deleteall_progress', $msg);
        } else {
            $msg .= ' (' . $wpdb->rows_affected . ')';
            do_action('docalist_biblio_deleteall_progress', $msg);
        }
    }

    public function deleteAll()
    {
        $wpdb = docalist('wordpress-database');

        $type = $this->postType();
        $count = $this->count();

        // Début de la suppression
        do_action('docalist_biblio_deleteall_start', $this, $count);

        // Toutes les notices
        $posts = "SELECT ID FROM $wpdb->posts WHERE post_type='$type'";

        // Révisions et autosaves de ces notices
        $revisions = "SELECT ID FROM $wpdb->posts WHERE post_type='revision' AND post_parent IN ($posts)";

        // Commentaires sur les notices et les révisions
        $comments = "SELECT comment_id FROM $wpdb->comments "
            . "WHERE comment_post_id IN ($posts) OR comment_post_id IN ($revisions)";

        // Supprime les termes de taxonomies et des révisions
        $sql = "DELETE FROM $wpdb->term_relationships WHERE object_id IN ($posts) OR object_id IN ($revisions)";
        $msg = __('Suppression des termes', 'docalist-biblio');
        $this->sql($sql, $msg);

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

        $sql = "DELETE FROM $wpdb->posts "
            . "WHERE post_type='revision' AND post_parent IN (SELECT ID FROM ($posts) AS tmp)";
        $msg = __('Suppression des révisions', 'docalist-biblio');
        $this->sql($sql, $msg);

        // Mise à jour dans les autres bases des notices filles dont le parent est l'une des notices supprimées
        // $sql = "UPDATE $wpdb->posts SET post_parent=0 WHERE post_parent IN ($posts)"; // ERROR 1093
        $sql = "UPDATE $wpdb->posts SET post_parent=0 "
            . "WHERE post_type!='$type' AND post_parent IN (SELECT ID FROM ($posts) AS tmp)";
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
     * Convertit les données d'une entité en post WordPress.
     *
     * @param array $data Les données de l'entité à convertir.
     *
     * @return array Un post wordpress sous la forme d'un tableau.
     */
    protected function encode(array $data)
    {
        $post = [];

        // Transfère les champs mappés de la notice dans le post wordpress
        foreach (static::$fieldMap as $dst => $src) {
            if (isset($data[$src])) {
                $post[$dst] = $data[$src];
                unset($data[$src]);
            }
        }

        // Encode le reste des données en json dans post_excerpt
        $post['post_excerpt'] = parent::encode($data);

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
    protected function decode($post, $id)
    {
        // Si c'est un nouveau post, il se peut que post_excerpt soit vide
        if (empty($post['post_excerpt'])) {
            //die('post_excerpt vide ' . __FILE__ . ':' . __LINE__);
            $data = [];
        }

        // Sinon, post_excerpt doit contenir du JSON valide
        else {
            $data = parent::decode($post['post_excerpt'], $id);
        }

        // Initialise les champs virtuels de la notice à partir des champs wordpress
        foreach (static::$fieldMap as $src => $dst) {
            if (isset($post[$src]) && $post[$src] !== '') {
                $data[$dst] = $post[$src];
            }
        }

        // Terminé
        return $data;
    }
}
