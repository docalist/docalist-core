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
 * Une table au format PHP.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class PhpTable extends SQLite
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

        // Charge le fichier php
        $data = require_once $this->path;

        // Charge les entêtes de colonne
        $this->fields = array_shift($data);

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
        foreach ($data as $values) {
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

        // Retourne false pour indiquer que la base est déjà ouverte
        return false;
    }

    public function type()
    {
        return 'php';
    }
}
