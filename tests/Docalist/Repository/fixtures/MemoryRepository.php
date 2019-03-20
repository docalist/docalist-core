<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Tests\Repository\Fixtures;

use Docalist\Repository\Repository;
use Docalist\Repository\Exception\EntityNotFoundException;

/**
 * Un dépôt qui stocke ses données en mémoire
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class MemoryRepository extends Repository
{
    // Les données sont publiques pour permettrent aux tests de vérifier
    // les données stockées.
    public $data = [
        'notjson' => 1,
        'badjson' => '[',
        'notarray' => '1',
    ];

    public function has($id)
    {
        return isset($this->data[$this->checkId($id)]);
    }

    protected function loadData($id)
    {
        if (! isset($this->data[$id])) {
            throw new EntityNotFoundException($id);
        }
        return $this->data[$id];
    }

    protected function saveData($id, $data)
    {
        is_null($id) && $id = uniqid();
        $this->data[$id] = $data;
        return $id;
    }

    protected function deleteData($id)
    {
        if (! isset($this->data[$id])) {
            throw new EntityNotFoundException($id);
        }
        unset($this->data[$id]);
    }

    public function count()
    {
        return count($this->data);
    }

    public function deleteAll()
    {
        $this->data = [];
    }
}
