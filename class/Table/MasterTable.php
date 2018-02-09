<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Table;

use Docalist\Tokenizer;
use Psr\Log\LoggerInterface;
use InvalidArgumentException;

/**
 * La master table.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class MasterTable extends CsvTable
{
    /**
     * Le logger utilisé.
     *
     * @var LoggerInterface
     */
    protected $log;

    public function __construct($path)
    {
        // Initialise notre log
        $this->log = docalist('logs')->get('tables');

        // Crée la master table si elle n'existe pas déjà
        $init = !file_exists($path);
        if ($init) {
            $this->createMasterTable($path);
        }

        // Initialise la table
        parent::__construct($path);

        // Référence la master table dans la master table
        if ($init) {
            $table = new TableInfo([
                'name' => 'master',
                'path' => $this->path,
                'label' => __('Table des tables (master table)', 'docalist-core'),
            ]);
            $this->register($table);
        }
    }

    protected function commit()
    {
        if ($this->commit) {
            // Commit les modifs
            $this->log && $this->log->debug('commit changes to master table');
            parent::commit();

            // Regénère le fichier texte
            $this->syncBack();
        }

        return $this;
    }

    /**
     * Regénère le fichier texte de la table à partir des données présentes
     * dans la base SQlite.
     *
     * @return self
     */
    protected function syncBack()
    {
        $fields = $this->fields();

        $header = $this->getFileHeader();
        $file = fopen($this->path, 'wt');
        fwrite($file, $header);
        fputcsv($file, $fields, ';', '"');

        $records = $this->search('rowid,' . implode(',', $fields), '', 'rowid');
        foreach ($records as $record) {
            fputcsv($file, (array) $record, ';', '"');
        }
        fclose($file);

        return $this;
    }

    /**
     * Retourne les lignes de commentaire qui figure dans l'entête du fichier.
     *
     * @return string
     */
    protected function getFileHeader()
    {
        $lines = file($this->path, FILE_IGNORE_NEW_LINES);
        $count = 0;
        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || $line[0] === '#') {
                ++$count;
                continue;
            }

            $header = array_slice($lines, 0, $count);
            $header = implode("\n", $header);
            $header && $header .= "\n";

            return $header;
        }
    }

    /**
     * Crée la master table à partir du template master-template.txt.
     *
     * Attention : si le fichier master.txt existe déjà, il est écrasé.
     *
     * @param string $path
     */
    protected function createMasterTable($path)
    {
        $this->log && $this->log->notice('creating master table');
        $template = file_get_contents(__DIR__ . '/master-template.txt');
        $schema = TableInfo::getDefaultSchema();
        $template = strtr($template, [
            '{year}' => date('Y'),
            '{path}' => $this->relativePath($path),
            '{fields}' => implode(';', $schema->getFieldNames()),
        ]);
        file_put_contents($path, $template);
    }

    /**
     * Convertit le chemin absolu d'une table en chemin relatif.
     *
     * Le path indiqué doit désigner un fichier situé dans l'arborescence du
     * site (i.e. le path doit commencer par root-dir).
     *
     * Le path relatif est destiné à être stocké dans la table master et il est
     * standardisé pour permettre de transférer la table master d'un site ou
     * d'un serveur à un autre. Les antislash sont convertis en slashs (pour ne
     * pas être sensible aux différences entre windows et linux).
     *
     * @param string $path Le path absolu de la table.
     *
     * @return string Le path relatif de la table.
     *
     * @throws InvalidArgumentException
     */
    protected function relativePath($path)
    {
        $root = docalist('root-dir');
        if (0 !== strncmp($path, $root, strlen($root))) {
            throw new InvalidArgumentException('Table path does not start with site path');
        }

        $path = substr($path, strlen($root));
        $path = strtr($path, DIRECTORY_SEPARATOR, '/');

        return $path;
    }

    /**
     * Prépare les données d'une structure TableInfo.
     *
     * @param TableInfo $table
     * @return array
     *
     * @throws InvalidArgumentException
     */
    protected function prepare(TableInfo $table)
    {
        $name = $table->name();

        if (! file_exists($table->path())) {
            throw new InvalidArgumentException("Invalid path for table $name: file {$table->path()} do not exists");
        }
        $table->path = $this->relativePath($table->path());
        !isset($table->label) && $table->label = $name;
        !isset($table->type) && $table->type = $name;
        !isset($table->format) && $table->format = $name;
        !isset($table->readonly) && $table->readonly = true;
        !isset($table->creation) && $table->creation = date_i18n('Y-m-d H:i:s');
        !isset($table->lastupdate) && $table->lastupdate = date_i18n('Y-m-d H:i:s');

        $fields = $table->getPhpValue();

        $fields['readonly'] = $fields['readonly'] ? '1' : '0';

        // Tokenize les champs
        foreach ($fields as $name => $value) {
            $fields["_$name"] = implode(' ', Tokenizer::tokenize($value));
        }

        return $fields;
    }

    /**
     * Vérifie que le nom de table passé en paramètre est correct et qu'il
     * n'existe pas déjà une table portant ce nom.
     *
     * @param string $name Nom de la table à vérifier.
     *
     * @throws InvalidArgumentException
     */
    public function checkName($name)
    {
        // Vérifie que le nom est correct
        if (! preg_match('~^[a-zA-Z0-9_-]+$~', $name)) {
            throw new InvalidArgumentException("Invalid table name '$name' (allowed chars: a-z, 0-9, '-' and '_')");
        }

        // Vérifie qu'il n'existe pas déjà une table avec ce nom
        if ($this->has($name)) {
            throw new InvalidArgumentException("Table $name already exists");
        }
    }

    /**
     * Déclare une table.
     *
     * Si la table est déjà enregistrée (même nom), les informations sur la
     * table sont mises à jour.
     *
     * @param TableInfo $table Propriétés de la table.
     *
     * @return self
     */
    public function register(TableInfo $table)
    {
        $this->log && $this->log->notice("Register table '{$table->name()}'", ['table' => $table]);

        // Si la table est déjà enregistrée (même nom), on met à jour
        if ($id = $this->rowid($table->name())) {
            return $this->update($id, $table);
        }

        // Prépare les données à insérer
        $fields = $this->prepare($table);

        // Prépare la requête sql
        $sql = sprintf(
            'INSERT INTO data(%s) VALUES (%s)',
            implode(',', array_keys($fields)),
            implode(',', array_map([$this->db, 'quote'], $fields))
        );

        // Démarre une transaction si ce n'est pas encore fait
        $this->beginTransaction();

        // Insère l'enregistrement
        $this->db->exec($sql);

        // Ok
        return $this;
    }

    /**
     * Met à jour les propriétés d'une table.
     *
     * @param int $rowid ID de la table à modifier.
     * @param TableInfo $table Nouvelles propriétés de la table
     *
     * @return self
     */
    public function update($rowid, TableInfo $table)
    {
        $this->log && $this->log->notice("Update properties of table #$rowid", ['table' => $table]);

        // Prépare les données à insérer
        $fields = $this->prepare($table);

        // Prépare la requête sql
        $set = [];
        foreach ($fields as $name => $value) {
            $set[] = $name . '=' . $this->db->quote($value);
        }
        $set = implode(',', $set);
        $sql = sprintf('UPDATE data SET %s WHERE rowid=%d', $set, $rowid);

        // Démarre une transaction si ce n'est pas encore fait
        $this->beginTransaction();

        // Met à jour la table
        $this->db->exec($sql);

        // Ok
        return $this;
    }

    /**
     * Supprime une table enregistrée dans la master table.
     *
     * Remarque : la table est juste supprimée de la master table, elle n'est
     * pas supprimée du disque.
     *
     * @param string $name
     *
     * @return self
     */
    public function unregister($name)
    {
        $this->log && $this->log->notice("Unregister table '$name'");

        // On ne sait jamais
        if ($name === 'master') {
            throw new InvalidArgumentException("Table $name can not be unregistered");
        }

        // Supprime la table si elle existe
        if ($id = $this->rowid($name)) {
            // Démarre une transaction si ce n'est pas encore fait
            $this->beginTransaction();

            // Exécute la requête
            $sql = "DELETE FROM data WHERE ROWID=$id";
            $this->db->exec($sql);
        }

        // Ok
        return $this;
    }

    /**
     * Retourne l'ID interne (rowid) de la table passée en paramètre.
     *
     * @param string $name
     *
     * @return int|false L'ID de la table ou false si elle n'existe pas.
     */
    public function rowid($name)
    {
        $id = $this->find('rowid', 'name=' . $this->db->quote($name));

        return ($id === false) ? false : (int) $id;
    }

    /**
     * Teste si la table indiquée est déclarée dans la master table.
     *
     * @param string $name
     *
     * @return bool
     */
    public function has($name)
    {
        return false !== $this->rowid($name);
    }

    /**
     * Retourne les propriétés de la table passée en paramètre.
     *
     * @param string $name Nom de la table.
     *
     * @return TableInfo Les propriétés de la table.
     *
     * @throws InvalidArgumentException Si la table indiquée n'existe pas dans
     * la master table.
     */
    public function table($name)
    {
        $fields = 'ROWID,' . implode(',', $this->fields());
        $table = $this->find($fields, 'name=' . $this->db->quote($name));

        if ($table === false) {
            throw new InvalidArgumentException("Table $name does not exist");
        }

        $table->path = docalist('root-dir') . $table->path;

        return new TableInfo((array) $table);
    }

    /**
     * Retourne la liste des tables déclarées.
     *
     * Par défaut la méthode retourne toute les tables mais vous pouvez filtrer
     * la liste par type et/ou par format en fournissant des paramètres (si vous
     * indiquez à la fois un type et un format, les deux critères sont combinés
     * en "ET").
     *
     * @param string $type Optionnel, ne retourne que les tables du type indiqué.
     * @param string $format Optionnel, ne retourne que les tables du format
     * indiqué.
     * @param bool $readonly Optionnel, ne retourne que les tables du type
     * indiqué.
     * @param string $sort Optionnel, ordre de tri (par défaut : _label).
     *
     * @return TableInfo[]
     */
    public function tables($type = null, $format = null, $readonly = null, $sort = '_label')
    {
        $where = '';
        if ($type) {
            $where = 'type=' . $this->db->quote($type);
        }
        if ($format) {
            $where && $where .= ' AND ';
            $where .= 'format=' . $this->db->quote($format);
        }
        if (!is_null($readonly)) {
            $where && $where .= ' AND ';
            $where .= 'readonly=' . $this->db->quote($readonly ? '1' : '0');
        }
        $fields = 'ROWID,' . implode(',', $this->fields());
        $tables = $this->search($fields, $where, $sort);

        foreach ($tables as & $table) {
            $table = new TableInfo((array) $table);
        }

        return $tables;
    }
}
