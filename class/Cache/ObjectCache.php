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
 * Un wrapper très léger autour de l'object cache de WordPress.
 *
 * Par rapport au cache WordPress, ce service permet :
 * - d'activer et de désactiver facilement le cache (paramètre du constructeur)
 * - de "mocker" le cache dans les tests unitaires.
 * - d'avoir des statistiques spécifiques à docalist.
 */
class ObjectCache
{
    /**
     * Indique si le cache est activé.
     *
     * @var bool
     */
    protected $enabled;

    /**
     * Stats sur le cache.
     *
     * @var integer[] Un tableau avec les entrées 'hits' et 'misses'.
     */
    protected $stats;

    /**
     * Initialise le cache.
     */
    public function __construct($enabled = true)
    {
        $this->enabled = $enabled;
        $this->stats = ['hits' => 0, 'misses' => 0];
        $this->debugBar();
    }

    /**
     * Récupère un objet en cache.
     *
     * @param string $key La clé du schéma.
     *
     * @return stdClass L'objet en cache ou false si l'objet n'ets pas/plus dans le cache.
     */
    public function get($key, $group = 'docalist')
    {
        $object = $this->enabled ? wp_cache_get($key, $group) : false;
        ++$this->stats[$object ? 'hits' : 'misses'];

        return $object;
    }

    /**
     * Stocke un objet dans le cache.
     *
     * @param string $key La clé du schéma
     *
     * @param Object $object
     *
     * @return self
     */
    public function set($key, $object, $group = 'docalist')
    {
        $this->enabled && wp_cache_set($key, $object, $group);

        return $this;
    }

    /**
     * Retourne des stats sur le cache.
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
