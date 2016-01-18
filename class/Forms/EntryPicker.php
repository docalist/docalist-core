<?php
/**
 * This file is part of the "Docalist Forms" package.
 *
 * Copyright (C) 2012-2015 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Forms
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Forms;

use Docalist\Table\TableInterface;

/**
 * Un contrôle qui permet à l'utilisateur de choisir une ou plusieurs valeurs
 * définies dans une table d'autorité.
 *
 * L'implémentation actuelle est basée sur selectize.
 */
class EntryPicker extends Select
{
    /**
     * Si les lookups portent sur une table, convertit les données passées en
     * paramètre en tableau d'options.
     *
     * @param array $data
     *
     * @return array
     */
    protected function prepareData($data)
    {
        // Rien à faire si les lookups ne portent pas sur un table
        if (! is_string($this->options) || substr($this->options, 0, 5) === 'index') {
            return array_combine($data, $data);
        }

        // Ouvre la table
        list(, $name) = explode(':', $this->options); // TODO à virer
        $table = docalist('table-manager')->get($name); /* @var TableInterface $table */

        // Construit la clause WHERE ... IN (...)
        $options = [];
        foreach ($data as $option) {
            $options[] = $table->quote($option);
        }
        $where = 'code IN (' . implode(',', $options) . ')';

        // Recherche toutes les entrées, on obtient un tableau de la forme 'code => label'
        $results = $table->search('code,label', $where);

        // Construit le tableau d'options, en respectant l'ordre initial des articles
        $options = [];
        foreach ($data as $key) {
            $options[$key] = isset($results[$key]) ? $results[$key] : __('Invalid: ', 'docalist-core') . $key;
        }

        // Ok
        return $options;
    }
}
