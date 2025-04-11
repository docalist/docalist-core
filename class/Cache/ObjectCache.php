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

namespace Docalist\Cache;

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
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class ObjectCache
{
    /**
     * Indique si on utilise le cache wordpress ou non.
     */
    protected bool $wp;

    /**
     * Les entrées du cache lorsqu'on n'utilise pas le cache WordPress.
     *
     * @var array<string,array<string,object>>
     */
    protected $cache;

    /**
     * Stats sur le cache.
     *
     * @var int[] un tableau avec les entrées 'hits' et 'misses'
     */
    protected array $stats;

    /**
     * Initialise le cache.
     *
     * @param bool $useWordPressCache indique si on utilise le cache d'objet de WordPress ou le cache interne
     */
    public function __construct(bool $useWordPressCache = false)
    {
        $this->wp = (bool) $useWordPressCache;
        !$useWordPressCache && $this->cache = [];
        $this->stats = ['hits' => 0, 'misses' => 0];
        $this->debugBar();
    }

    /**
     * Indique si on utilise le cache d'objet de WordPress ou le cache interne.
     */
    public function useWordPressCache(): bool
    {
        return $this->wp;
    }

    /**
     * Récupère un objet en cache.
     *
     * @param string $key   la clé indiquée pour l'objet lorsqu'il a été mis en cache
     * @param string $group le group indiqué pour l'objet lorsqu'il a été mis en cache
     *
     * @return object|false L'objet en cache ou false si l'objet n'est pas/plus dans le cache
     */
    public function get(string $key, string $group = 'docalist'): object|false
    {
        if ($this->wp) {
            $object = wp_cache_get($key, $group);
            if (!is_object($object)) {
                $object = false;
            }
        } else {
            $object = $this->cache[$group][$key] ?? false;
        }

        ++$this->stats[$object ? 'hits' : 'misses'];

        return $object;
    }

    /**
     * Stocke un objet dans le cache.
     *
     * @param string $key    une clé unique qui sera utilisée pour stocker l'objet dans le cache
     * @param object $object L'onjet à stocker
     * @param string $group  un identifiant permettant de regrouper ensemble les objets similaires
     */
    public function set(string $key, object $object, string $group = 'docalist'): static
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
     * @return int[] un tableau contenant les entrées 'hits' et 'misses'
     */
    public function getStats(): array
    {
        return $this->stats;
    }

    private function debugBar(): void
    {
        add_action('admin_bar_menu', function (WP_Admin_Bar $adminBar): void {
            $hits = $this->stats['hits'];
            $misses = $this->stats['misses'];
            $total = $hits + $misses;
            $requestTimeFloat = $_SERVER['REQUEST_TIME_FLOAT'];
            assert(is_float($requestTimeFloat));
            $elapsed = round((microtime(true) - $requestTimeFloat) * 1000);
            $adminBar->add_node([
                'id' => 'schema-cache-info',
                'title' => "M:$misses H:$hits T:$total E:{$elapsed}ms",
            ]);
        }, 100);
    }
}
