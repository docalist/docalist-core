<?php declare(strict_types=1);
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Lookup;

use Docalist\Lookup\LookupInterface;
use Docalist\Table\TableInterface;
use Docalist\Tokenizer;

// Injecter le TableManager à utiliser

/**
 * Lookup sur une table d'autorité.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class TableLookup implements LookupInterface
{
    /**
     * {@inheritDoc}
     */
    public function hasMultipleSources(): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getCacheMaxAge(): int
    {
        return 1 * WEEK_IN_SECONDS; // Une table n'est pas censée changer sans arrêt
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultSuggestions(string $source = ''): array
    {
        // Récupère la table
        $table = docalist('table-manager')->get($source); /* @var TableInterface $table */

        // Lance la recherche
        $result = $table->search($this->getFields(), '', '_label', 100);

        // Traite et transforme les résultats obtenus
        return $this->processResults($result, $table);
    }

    /**
     * {@inheritDoc}
     */
    public function getSuggestions(string $search, string $source = ''): array
    {
        // Récupère la table
        $table = docalist('table-manager')->get($source); /* @var TableInterface $table */

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
        // Supprime la clé ROWID, pour que json_encode génère bien un tableau
        return array_values($result);
    }

    /**
     * {@inheritDoc}
     */
    public function convertCodes(array $data, string $source = ''): array
    {
        // Sanity check
        if (empty($data)) {
            return $data;
        }

        // Récupère la table
        $table = docalist('table-manager')->get($source); /* @var TableInterface $table */

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
            if (empty($code)) {
                continue;
            }
            $codes[$code] = isset($results[$code]) ? $results[$code] : __('Invalid: ', 'docalist-core') . $code;
        }

        // Ok
        return $codes;
    }
}
