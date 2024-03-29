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

namespace Docalist\Table;

use Docalist\Cache\FileCache;
use Docalist\Tokenizer;

/**
 * Une table au format CSV.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class CsvTable extends SQLite
{
    protected $readonly = true;

    protected function compile()
    {
        // Si la table est en cache et qu'elle est à jour, rien à compiler
        /** @var FileCache $cache */
        $cache = docalist(FileCache::class);
        if ($cache->has($this->path, (int) filemtime($this->path))) {
            return $cache->getPath($this->path);
        }

        $path = $cache->getPath($this->path);

        // Ouvre le fichier texte
        $file = fopen($this->path, 'rb');

        // Charge les entêtes de colonne
        // On peut avoir des lignes de commentaires (#xxx) avant les entêtes
        for (;;) {
            $this->fields = fgetcsv($file, 1024, ';');
            if (substr($this->fields[0], 0, 1) === '#') {
                continue;
            }
            $this->fields = array_map('trim', $this->fields);
            break;
        }

        $sql = $this->parseFields();

        $this->createSQLiteDatabase($path, $sql);

        // Prépare le statement utilisé pour charger les données
        $sql = sprintf(
            'INSERT INTO "data"("%s") VALUES (%s);',
            implode('","', $this->fields),
            rtrim(str_repeat('?,', count($this->fields)), ',)')
        );
        $statement = $this->db->prepare($sql);

        // Charge les données
        $index = array_flip($this->fields);
        while (false !== $values = fgetcsv($file, 1024, ';')) {
            // Ignore les espaces
            $values = array_map('trim', $values);

            // Les lignes qui commencent par "#" sont des commentaires
            if (substr($values[0], 0, 1) === '#') {
                continue;
            }
            $allvalues = $values;
            foreach ($values as $i => $value) {
                if (trim($value) === '') {
                    $allvalues[$i] = null;
                    if (isset($index['_' . $this->fields[$i]])) {
                        $allvalues[] = null;
                    }
                } else {
                    if (isset($index['_' . $this->fields[$i]])) {
                        $allvalues[] = implode(' ', Tokenizer::tokenize($value));
                    }
                }
            }
            $statement->execute($allvalues);
        }
        // Ferme le curseur
        $statement->closeCursor();

        // Ferme le fichier texte
        fclose($file);

        // Enregistre la base sqlite
        $this->commit();
        $this->db = null;

        // Retourne le path de la base sqlite à ouvrir
        return $path;
    }

    public function type()
    {
        return 'csv';
    }
}
