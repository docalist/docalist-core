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

namespace Docalist\Cache;

use InvalidArgumentException;
use RuntimeException;

use function Docalist\deprecated;

/**
 * Un cache permettant de stocker des fichiers générés sur disque (template compilé, version SQLite d'une
 * table d'autorité, etc.).
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class FileCache
{
    /**
     * Permissions des fichiers ajoutés au cache.
     */
    final public const FILE_MASK = 0660;

    /**
     * Permissions des répertoires qui sont créés.
     */
    final public const DIR_MASK = 0770;

    /**
     * Path racine des fichiers ajoutés au cache.
     */
    protected string $root;

    /**
     * Path absolu du répertoire contenant les fichiers du cache.
     */
    protected string $directory;

    /**
     * Crée un nouveau cache.
     *
     * Pour créer un nouveau cache, vous devez fournir deux noms de répertoire.
     *
     * - Le premier désigne votre "document root". Il doit s'agir d'un répertoire existant et vous devez fournir
     *   le path absolu. Ce path permet de déterminer le chemin relatif du fichier dans le cache.
     *   Seul les fichiers dont le path commence par ce chemin pourront être stockés en cache.
     *
     * - Le second désigne l'endroit où seront stockés les fichiers en cache. Là aussi il doit s'agir d'un path
     *   absolu, mais le répertoire sera créé s'il n'existe pas déjà (assurez-vous d'avoir les droits en écriture).
     *
     * Important : FileCache ne vérifie pas que vous passez des path corrects, c'est à vous de vérifier que vous
     * utilisez des paths corrects, non relatifs.
     *
     * @param string $root      path racine des fichiers qui pourront être stockés dans ce cache
     * @param string $directory path du répertoire qui contiendra les fichiers mis en cache
     */
    public function __construct(string $root, string $directory)
    {
        $this->setRoot($root)->setDirectory($directory);
    }

    /**
     * Retourne le path racine des fichiers qui peuvent être stockés dans le cache.
     */
    public function getRoot(): string
    {
        return $this->root;
    }

    /**
     * Modifie le path racine des fichiers qui peuvent être stockés dans le cache.
     */
    public function setRoot(string $root): self
    {
        $this->root = $this->normalizePath($root);

        return $this;
    }

    /**
     * @deprecated
     */
    public function root(): string
    {
        deprecated('FileCache::root()', 'getRoot()', '2017-03-14');

        return $this->getRoot();
    }

    /**
     * Retourne le path du répertoire contenant les fichiers mis en cache.
     */
    public function getDirectory(): string
    {
        return $this->directory;
    }

    /**
     * Modifie le path du répertoire contenant les fichiers mis en cache.
     */
    public function setDirectory(string $directory): self
    {
        $this->directory = $this->normalizePath($directory);

        return $this;
    }

    /**
     * @deprecated
     */
    public function directory(): string
    {
        deprecated('FileCache::directory()', 'getDirectory()', '2017-03-14');

        return $this->getDirectory();
    }

    /**
     * Retourne le path dans le cache du fichier indiqué.
     *
     * On ne teste pas si le fichier existe : on se contente de déterminer le path qu'aurait le fichier
     * s'il était mis en cache.
     *
     * @param string $filePath path absolu du fichier à tester
     *
     * @return string path dans le cache de ce fichier
     *
     * @throws InvalidArgumentException si le fichier indiqué ne peut pas figurer dans le cache
     */
    public function getPath(string $filePath): string
    {
        $path = strtr($filePath, '/\\', DIRECTORY_SEPARATOR);
        if (0 !== strncasecmp($this->root, $path, strlen($this->root))) {
            throw new InvalidArgumentException(sprintf('Unable to cache file "%s", path does not match cache root', $filePath));
        }

        return $this->directory.substr($path, strlen($this->root));
    }

    /**
     * @deprecated
     */
    public function path(string $file): string
    {
        deprecated('FileCache::path()', 'getPath()', '2017-03-14');

        return $this->getPath($file);
    }

    /**
     * Indique si un fichier figure dans le cache et s'il est à jour.
     *
     * @param string $file le path du fichier à tester
     * @param int    $time date/heure minimale du fichier en cache pour qu'il soit considéré comme à jour
     *
     * @return bool true si le fichier est dans le cache et est à jour, false sinon
     */
    public function has(string $file, int $time = 0): bool
    {
        $path = $this->getPath($file);
        if (!file_exists($path)) {
            return false;
        }

        return (0 === $time) || (filemtime($path) >= $time);
    }

    /**
     * Stocke un fichier dans le cache.
     *
     * @param string $file path du fichier à stocker
     * @param string $data contenu du fichier
     *
     * @throws InvalidArgumentException si le fichier indiqué ne peut pas figurer dans le cache
     * @throws RuntimeException         si le répertoire du fichier ne peut pas être créé
     */
    public function put(string $file, string $data): self
    {
        // Détermine le path du fichier en cache
        $path = $this->getPath($file);

        // Crée le répertoire destination si nécessaire
        $directory = dirname($path);
        if (!is_dir($directory) && !@mkdir($directory, self::DIR_MASK, true)) {
            throw new RuntimeException(sprintf('FileCache: unable to create directory "%s"', $directory));
        }

        // Stocke le fichier
        file_put_contents($path, $data, LOCK_EX);
        chmod($path, self::FILE_MASK);

        // Ok
        return $this;
    }

    /**
     * Retourne le contenu d'un fichier en cache.
     *
     * @param string $file le path du fichier à charger
     *
     * @return string|null le contenu du fichier ou null si le fichier ne figure pas dans le cache
     *
     * @throws InvalidArgumentException si le fichier indiqué ne peut pas figurer dans le cache
     */
    public function get(string $file): ?string
    {
        $path = $this->getPath($file);

        if (!file_exists($path)) {
            return null;
        }

        $content = file_get_contents($path);

        return (false === $content) ? null : $content;
    }

    /**
     * Supprime un fichier ou un répertoire du cache.
     *
     * Aucune erreur n'est générée si le fichier indiqué ne figure pas dans le cache.
     *
     * La fonction essaie également de supprimer les répertoires vides.
     *
     * @param string $file le path du fichier ou du répertoire à supprimer (vide = tout le cache)
     *
     * @return bool True si le fichier ou le répertoire indiqué a été supprimé
     */
    public function clear(string $file = ''): bool
    {
        // Détermine ce qu'il faut supprimer
        $path = $file ? $this->getPath($file) : $this->directory;

        // Suppression d'un répertoire complet
        if (is_dir($path)) {
            return $this->rmTree($path);
        }

        // Suppression d'un fichier
        if (!@unlink($path)) {
            return false;
        }

        // Le répertoire est peut-être vide, maintenant, essaie de le supprimer
        $path = dirname($path);
        while (strlen($path) > strlen($this->directory)) {
            if (!@rmdir($path)) {
                return true; // on ne peut pas supprimer le dir, mais le fichier l'a été lui, donc true
            }
            $path = dirname($path);
        }

        // Ok
        return true;
    }

    /**
     * Normalise le path passé en paramètre.
     *
     * - standardise le séparateur (slash ou antislash selon le système)
     * - garantit qu'on a un séparateur à la fin.
     */
    protected function normalizePath(string $filePath): string
    {
        return rtrim(
            strtr($filePath, '/\\', DIRECTORY_SEPARATOR),
            DIRECTORY_SEPARATOR
        ).DIRECTORY_SEPARATOR;
    }

    /**
     * Supprime un répertoire et son contenu.
     *
     * @return bool true si le répertoire a été supprimé, false en cas de problème
     */
    protected function rmTree(string $directory): bool
    {
        $files = scandir($directory);
        if (!is_array($files)) {
            return false;
        }
        $files = array_diff($files, ['.', '..']);
        foreach ($files as $file) {
            $path = $directory.DIRECTORY_SEPARATOR.$file;
            if (is_dir($path)) {
                if (!$this->rmTree($path)) {
                    return false;
                }
            } else {
                if (!unlink($path)) {
                    return false;
                }
            }
        }

        return @rmdir($directory);
    }
}
