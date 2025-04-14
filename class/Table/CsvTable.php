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
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

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
        if ($file === false) {
            throw new FileNotFoundException($this->path);
        }

        // Charge les entêtes de colonne
        // On peut avoir des lignes de commentaires (#xxx) avant les entêtes
        $this->fields = [];
        for (;;) {
            $fields = fgetcsv($file, 1024, ';', '"', '');
            if (!is_array($fields)) { // ($fields === false) {
                break;
            }
            if ($fields === [null]) { // ligne vide
                continue;
            }
            if (substr((string) $fields[0], 0, 1) === '#') {
                continue;
            }
            foreach($fields as $field) {
                $this->fields[] = trim((string)$field);
            }
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
        while (false !== $cols = fgetcsv($file, 1024, ';', '"', '')) {
            // Ignore les espaces
            //$values = array_map('trim', $values);  // phpstan n'aime pas 'trim'
            $values = [];
            foreach($cols as & $col) {
                $values[] = trim($col ?? '');
            }
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
        unset($this->db);

        // Retourne le path de la base sqlite à ouvrir
        return $path;
    }

    public function type()
    {
        return 'csv';
    }
}
