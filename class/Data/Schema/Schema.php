<?php
/**
 * This file is part of a "Docalist Core" plugin.
 *
 * Copyright (C) 2012-2014 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package Docalist
 * @subpackage Core
 * @author Daniel Ménard <daniel.menard@laposte.net>
 * @version SVN: $Id$
 */
namespace Docalist\Data\Schema;

use InvalidArgumentException;

/**
 * Un schéma permet de décrire la liste des champs qui composent une structure
 * de données.
 */
class Schema {
    /**
     * La liste des champs du schéma.
     *
     * @var Field[]
     */
    protected $fields;

    /**
     * Construit un nouveau schéma.
     *
     * @param array $data La liste des champs du schéma.
     *
     * @throws InvalidArgumentException Si le schéma contient des erreurs.
     */
    public function __construct(array $fields = []) {
        if (empty($fields)) {
            $msg = 'No fields defined in schema';
            throw new InvalidArgumentException($msg);
        }
        $this->fields = [];
        foreach ($fields as $key => $field) {
            // Gère les raccourcis autorisés si $field est une chaine
            if (is_string($field)) {
                // Champ de la forme entier => nom
                if (is_int($key)) {
                    $field = ['name' => $field];
                }

                // Champ de la forme nom => type
                else {
                    $field = ['name' => $key, 'type' => $field];
                }
            }

            // Le nom peut être indiqué comme clé ou comme propriété mais pas les deux
            elseif (is_string($key)) {
                if (isset($field['name'])) {
                    $msg = 'Field name defined twice: %s,%s';
                    throw new InvalidArgumentException(sprintf($msg, $key, $field['name']));
                }
                $field['name'] = $key;
            }

            // Compile
            $field = new Field($field);

            // Vérifie que le nom du champ est unique
            $name = $field->name();
            if (isset($this->fields[$name])) {
                $msg = 'Field %s defined twice';
                throw new InvalidArgumentException(sprintf($msg, $name));
            }

            // Stocke le champ
            $this->fields[$name] = $field;
        }
    }

    /**
     * Retourne la liste des champs.
     *
     * @return Field[]
     */
    public function fields() {
        return $this->fields;
    }

    /**
     * Retourne le schéma du champ indiqué.
     *
     * @param string $field Le nom du champ.
     *
     * @throws Exception Une exception est générée si le champ indiqué n'existe
     * pas.
     *
     * @return Field
     */
    public function field($field) {
        if (!isset($this->fields[$field])) {
            $msg = 'Field %s does not exist';
            throw new InvalidArgumentException(sprintf($msg, $field));
        }

        return $this->fields[$field];
    }

    /**
     * Indique si le schéma contient le champ indiqué.
     *
     * @param string $field Le nom du champ à tester.
     *
     * @return bool
     */
    public function has($field) {
        return isset($this->fields[$field]);
    }

    /**
     * Convertit le schéma en tableau.
     *
     * @return array
     */
    public function toArray() {
        $result = [];
        foreach ($this->fields as $field) {
            $result['fields'][] = $field->toArray();
        }

        return $result;
    }
}