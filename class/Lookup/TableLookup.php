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
 * @subpackage  Core
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Lookup;

use Docalist\Table\TableInterface;
use Docalist\Tokenizer;

/**
 * Lookup sur une table d'autorité.
 */
class TableLookup implements LookupInterface
{
    public function hasMultipleSources()
    {
        return true;
    }

    public function getCacheMaxAge()
    {
        return 1 * WEEK_IN_SECONDS; // Une table n'est pas censée changer sans arrêt
    }

    public function getDefaultSuggestions($source = '')
    {
        // Récupère la table
        $table = docalist('table-manager')->get($source); /** @var TableInterface $table */

        // Lance la recherche
        $result = $table->search($this->getFields(), '', '_label', 100);

        // Traite et transforme les résultats obtenus
        return $this->processResults($result, $table);
    }

    public function getSuggestions($search, $source = '')
    {
        // Récupère la table
        $table = docalist('table-manager')->get($source); /** @var TableInterface $table */

        // Tokenize la chaine de recherche pour être insensible aux accents, etc.
        $arg = implode(' ', Tokenizer::tokenize($search));

        // Recherche par code
        if (strlen($search) >= 2 && $search[0] === '[' && substr($search, -1) === ']') {
            $where = $arg ? '_code=%s' : '_code IS NULL'; // arg=[] recherche les entrées qui n'ont pas de code
        }

        // Recherche par code/libellé
        else {
            $arg .= '%';
            $where = '_code LIKE %1$s OR _label LIKE %1$s';
        }

        // Lance la recherche
        $result = $table->search($this->getFields(), sprintf($where, $table->quote($arg)), '_label', 100);

        // Traite et transforme les résultats obtenus
        return $this->processResults($result, $table);
    }

    /**
     * Liste des champs qui seront retournés par la requête sql ("what").
     *
     * @return string
     */
    protected function getFields()
    {
        return 'ROWID,code,label,description';
    }

    /**
     * Traite les résultats obtenus via la requête sql et les transforme dans le format correct.
     *
     * @param array $result Les résultats obtenus
     * @param TableInterface $table Table d'où proviennent les résultats.
     *
     * @return array Les résultats transformés.
     */
    protected function processResults($result, TableInterface $table)
    {
//         foreach($result as & $term) {
//             $term = array_filter((array)$term);
//         }

        // Supprime la clé ROWID, pour que json_encode génère bien un tableau
        return array_values($result);
    }

    public function convertCodes(array $data, $source = '')
    {
        // Sanity check
        if (empty($data)) {
            return $data;
        }

        // Récupère la table
        $table = docalist('table-manager')->get($source); /** @var TableInterface $table */

        // Construit la clause WHERE ... IN (...)
        $codes = [];
        foreach ($data as $code) {
            $codes[] = $table->quote($code);
        }
        $where = 'code IN (' . implode(',', $codes) . ')';

        // Recherche toutes les entrées, on obtient un tableau de la forme 'code => label'
        $results = $table->search('code,label', $where);

        // Construit le tableau résultat, en respectant l'ordre initial des données
        $codes = [];
        foreach ($data as $code) {
            $codes[$code] = isset($results[$code]) ? $results[$code] : __('Invalid: ', 'docalist-core') . $code;
        }

        // Ok
        return $codes;
    }
}
