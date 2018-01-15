<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 */
namespace Docalist\Lookup;

use Docalist\Table\TableInterface;

/**
 * Lookup sur un thésaurus.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class ThesaurusLookup extends TableLookup
{
    protected function getFields()
    {
        return
            'ROWID,code,label,MT,USE,' .
            "(SELECT group_concat(label,'¤') FROM data d WHERE USE=data.code) AS UF," .
            'BT,' .
            "(SELECT group_concat(code, '¤') FROM data d WHERE BT=data.code) AS NT," .
            'RT,description,sn';
    }

    protected function processResults($result, TableInterface $table)
    {
        /*
         * Fonctionnement : la requête sql (cf. getFields) nous a retourné, pour chaque champ relation,
         * une liste de codes séparés par le caractère "¤". Par exemple : { RT: "dvd¤vhs" }.
         * 1. On crée la liste de tous les codes distincts trouvés dans les résultats.
         * 2. On lance une requête sql unique pour obtenir le libellé de tous les codes trouvés.
         * 3. On convertit les relations présentes dans les résultats pour associer chaque code à son
         *    libellé. Par exemple : RT: { dvd: "Disque DVD", vhs: "Cassette VHS" }
         */

        // Laisse la classe parent faire le array_values()
        $result = parent::processResults($result, $table);

        // Si on n'a aucun résultat, terminé
        if (empty($result)) {
            return $result;
        }

        // Liste des codes relations à traiter (UF est géré différemment)
        $relations = ['USE', 'MT', 'BT', 'NT', 'RT'];

        // 1. Détermine la liste complète de tous les codes présents dans les résultats (en dédoublonnant)
        $codes = [];
        foreach ($result as $term) {
            foreach ($relations as $relation) {
                if ($term->$relation) {
                    foreach (explode('¤', $term->$relation) as $code) {
                        $codes[$code] = $table->quote($code);
                    }
                }
            }

            // Pour UF, la requête sql nous a retourné directement les libellés (car ce sont des non-descripteurs)
            $term->UF && $term->UF = explode('¤', $term->UF);
        }

        // Si les résultats ne contiennent aucun code, terminé
        if (empty($codes)) {
            return $result;
        }

        // 2. Détermine le libellé de chaque code et crée une table de conversion code => label
        $where = 'code IN (' . implode(',', $codes) . ')'; // quote() a déjà été appellée sur chaque code plus haut
        $codes = $table->search('code,label', $where);

        // 3. Transforme les champs relations en structures code => libellé
        foreach ($result as $term) {
            foreach ($relations as $relation) {
                if ($term->$relation) {
                    $t = [];
                    foreach (explode('¤', $term->$relation) as $code) {
                        $t[$code] = isset($codes[$code]) ? $codes[$code] : "CODE-NOT-FOUND:$code";
                    }
                    $term->$relation = $t;
                }
            }
        }

        // Terminé
        return $result;
    }
}
