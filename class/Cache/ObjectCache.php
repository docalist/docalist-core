<?php
/**
 * This file is part of the "Docalist Core" plugin.
 *
 * Copyright (C) 2012-2015 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Cache
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Cache;

use stdClass;
use WP_Admin_Bar;

/**
 * Le cache d'objets de Docalist.
 *
 * Ce cache permet de stocker des objets en cache pour éviter de les construire
 * plusieurs fois (par exemple des schémas).
 *
 * Par défaut, le cache est géré en interne et il n'est pas pérenne (c'est un simple
 * tableau en mémoire qui sera "vidé" à la fin de la requête).
 *
 * Lors de l'appel au constructeur, il est possible d'indiquer qu'on veut utiliser
 * le cache d'objets de WordPress. Dans ce cas, le cache sera pérenne (l'idéal) ou
 * non selon la façon dont WordPress est paramétré : si on utilise un drop'in comme
 * APC cache ou MemCached, le cache sera pérenne, sinon il ne le sera pas.
 *
 * Lorsqu'on demande à utiliser le cache de WordPress, le cache Docalist se comporte
 * comme un wrapper très léger autour de l'object cache de WordPress.
 *
 * Par rapport à l'utilisation directe des fonctions wp_cache_xxx de WordPress, le
 * cache Docalist permet :
 * - d'activer et de désactiver facilement la persistance des données en cache
 *   (nécessaire en production mais génant lors du développement),
 * - de "mocker" le cache dans les tests unitaires,
 * - d'avoir des statistiques spécifiques à docalist.
 */
class ObjectCache
{
    /**
     * Indique si on utilise le cache wordpress ou non.
     *
     * @var bool
     */
    protected $wp;

    /**
     * Les entrées du cache lorsqu'on n'utilise pas le cache WordPress.
     *
     * @var array
     */
    protected $cache;

    /**
     * Stats sur le cache.
     *
     * @var integer[] Un tableau avec les entrées 'hits' et 'misses'.
     */
    protected $stats;

    /**
     * Initialise le cache.
     *
     * @param bool $useWordPressCache Indique si on utilise le cache d'objet de WordPress ou le cache interne.
     */
    public function __construct($useWordPressCache = false)
    {
        $this->wp = (bool) $useWordPressCache;
        !$useWordPressCache && $this->cache = [];
        $this->stats = ['hits' => 0, 'misses' => 0];
        $this->debugBar();
    }

    /**
     * Indique si on utilise le cache d'objet de WordPress ou le cache interne.
     *
     * @return bool
     */
    public function useWordPressCache()
    {
        return $this->wp;
    }

    /**
     * Récupère un objet en cache.
     *
     * @param string $key   La clé indiquée pour l'objet lorsqu'il a été mis en cache.
     * @param string $group Le group indiqué pour l'objet lorsqu'il a été mis en cache.
     *
     * @return stdClass L'objet en cache ou false si l'objet n'est pas/plus dans le cache.
     */
    public function get($key, $group = 'docalist')
    {
        if ($this->wp) {
            $object = wp_cache_get($key, $group);
        } else {
            $object = isset($this->cache[$group][$key]) ? $this->cache[$group][$key] : false;
        }

        ++$this->stats[$object ? 'hits' : 'misses'];

        return $object;
    }

    /**
     * Stocke un objet dans le cache.
     *
     * @param string $key   Une clé unique qui sera utilisée pour stocker l'objet dans le cache.
     * @param string $group Un identifiant permettant de regrouper ensemble les objets similaires.
     *
     * @param Object $object
     *
     * @return self
     */
    public function set($key, $object, $group = 'docalist')
    {
        if ($this->wp) {
            wp_cache_set($key, $object, $group);
        } else {
            $this->cache[$group][$key] = $object;
        }

        return $this;
    }

    /**
     * Retourne des statistiques sur l'utilisation du cache.
     *
     * @return integer[] Un tableau contenant les entrées 'hits' et 'misses'.
     */
    public function getStats()
    {
        return $this->stats;
    }

    private function debugBar()
    {
        add_action('admin_bar_menu', function (WP_Admin_Bar $adminBar) {
            $hits = $this->stats['hits'];
            $misses = $this->stats['misses'];
            $total = $hits + $misses;
            $elapsed = round((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000);
            $adminBar->add_node([
                'id' => 'schema-cache-info',
                'title' => "M:$misses H:$hits T:$total E:{$elapsed}ms",
            ]);
        }, 100);
    }
}
