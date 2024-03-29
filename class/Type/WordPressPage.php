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

namespace Docalist\Type;

use Docalist\Type\Exception\InvalidTypeException;
use WP_Post;

/**
 * Un champ de type ListEntry permettant de sélectionner une page WordPress existante.
 *
 * Le champ stocke l'ID (int) de la page sélectionnée.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class WordPressPage extends ListEntry
{
    public static function loadSchema(): array
    {
        return [
            'label' => __('Page WordPress', 'docalist-core'),
        ];
    }

    /*
     * Comme on hérite de ListEntry, on hérite indirectement de Text et donc l'ID de la page
     * est stocké sous forme de chaine. Pour le stocker comme entier (comme si on héritait de Integer),
     * on surcharge assign (copier/coller de ce qu'on a dans Integer).
     * Idée : que ListEntry soit un trait et qu'on ait des classes comme TextEntry, IntegerEntry, etc.
     */
    public function assign($value): void
    {
        ($value instanceof Any) && $value = $value->getPhpValue();
        if (!is_int($value)) {
            if ($value === '') {
                $value = 0;
            } elseif (false === $value = filter_var($value, FILTER_VALIDATE_INT)) {
                throw new InvalidTypeException('int');
            }
        }

        $this->phpValue = (string) $value; // était stocké comme int avant, cast to string car on hérite de text
    }

    /**
     * Retourne la liste hiérarchique des pages sous la forme d'un tableau
     * utilisable dans un select.
     *
     * @return array<int,string> Un tableau de la forme PageID => PageTitle
     */
    protected function getEntries(): array
    {
        $entries = ['…'];
        $pages = get_pages();
        if (is_array($pages)) {
            foreach ($pages as $page) { /** @var WP_Post $page */
                $entries[$page->ID] = str_repeat('   ', count($page->ancestors)).$page->post_title;
            }
        }

        return $entries;
    }
}
