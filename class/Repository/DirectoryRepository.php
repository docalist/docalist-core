<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Repository;

use Docalist\Type\Entity;
use Docalist\Repository\Exception\EntityNotFoundException;
use Docalist\Repository\Exception\RepositoryException;
use Docalist\Repository\Exception\BadIdException;
use InvalidArgumentException;

/**
 * Un dépôt permettant de stocker des entités dans un répertoire.
 *
 * Ce dépôt accepte des entités dont la clé est un entier ou une chaine composée de lettres, de chiffres et de tirets.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class DirectoryRepository extends Repository
{
    /**
     * Le répertoire du dépôt (path complet avec un slash à la fin).
     *
     * @var string
     */
    protected $directory;

    /**
     * Crée un nouveau dépôt.
     *
     * @param string $directory Le chemin complet du répertoire dans lequel
     * seront stockées les entités de ce dépôt.
     *
     * @param string $type Optionnel, le nom de classe complet des entités de
     * ce dépôt. C'est le type qui sera utilisé par load() si aucun type
     * n'est indiqué lors de l'appel.
     *
     * @throws InvalidArgumentException Si le répertoire indiqué nest pas
     * valide.
     */
    public function __construct($directory, $type = Entity::class)
    {
        // Initialise le dépôt
        parent::__construct($type);

        // Normalise les séparateurs
        $directory = strtr($directory, '/\\', DIRECTORY_SEPARATOR);
        $directory = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        // Crée le répertoire s'il n'existe pas déjà
        if (! is_dir($directory) && ! mkdir($directory, 0770, true)) {
            $msg = __('Unable to create the directory %s', 'docalist-core');
            throw new RepositoryException(sprintf($msg, $directory));
        }

        // on ne teste pas is_writable() : ce sera détecté par saveData()

        // Stocke le répertoire du dépôt
        $this->directory = $directory;
    }

    /**
     * Retourne le répertoire du dépôt.
     *
     * Remarque : le path retourné contient toujours un slash final
     * (un antislash sous Windows).
     *
     * @return string
     */
    public function directory()
    {
        return $this->directory;
    }

    protected function checkId($id)
    {
        // On n'accepte que des chaines de catactères
        if (!is_string($id)) {
            throw new BadIdException($id, 'string');
        }

        // Longueur maxi : 64 caractères
        if (strlen($id) > 64) {
            throw new BadIdException($id, 'max len 64');
        }

        // Au format 'abcd-efgh' (en minuscules)
        if (! preg_match('~^[a-z0-9]+(?:-[a-z0-9]+)*$~', $id)) {
            throw new BadIdException($id, 'format "abcd-efgh" (lowercase)');
        }

        // Ok
        return $id;
    }

    /**
     * Retourne le path d'une entité.
     *
     * La méthode ne teste pas si l'entité existe, elle se contente de
     * construire son path.
     *
     * @param string $id
     *
     * @return string
     */
    public function path($id)
    {
        return $this->directory . $id . '.json';
    }

    public function has($id)
    {
        // Vérifie que l'ID est correct
        $id = $this->checkId($id);

        // Teste si le fichier existe
        return file_exists($this->path($id));
    }

    protected function loadData($id)
    {
        // Vérifie que le fichier existe
        if (! file_exists($path = $this->path($id))) {
            throw new EntityNotFoundException($id);
        }

        // Charge les données de l'entité
        if (false === $data = file_get_contents($path)) {
            $error = error_get_last();
            throw new RepositoryException($error['message']);
        }

        // Ok
        return $data;
    }

    protected function saveData($id, $data)
    {
        // Alloue un ID si nécessaire
        is_null($id) && $id = uniqid();

        // Détermine le path du fichier
        $path = $this->path($id);

        // L'entité est stockée dans un fichier
        if (! file_put_contents($path, $data, LOCK_EX)) { // false:failure, 0:rien écrit
            $error = error_get_last();
            throw new RepositoryException($error['message']);
        }

        if (! chmod($path, 0660)) {
            $error = error_get_last();
            throw new RepositoryException($error['message']);
        }

        // Ok
        return $id;
    }

    protected function deleteData($id)
    {
        // Détermine le path du fichier
        $path = $this->path($id);

        // Vérifie que le fichier existe
        if (! file_exists($path)) {
            throw new EntityNotFoundException($id);
        }

        // Supprime le fichier
        if (! unlink($path)) {
            $error = error_get_last();
            throw new RepositoryException($error['message']);
        }
    }

    public function count()
    {
        throw new \Exception(__METHOD__ . " n'est pas encore implémenté.");
    }

    public function deleteAll()
    {
        throw new \Exception(__METHOD__ . " n'est pas encore implémenté.");
    }
}
