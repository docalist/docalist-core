<?php
/**
 * This file is part of the "Docalist Core" plugin.
 *
 * Copyright (C) 2012-2017 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 */
namespace Docalist\Cache;

use InvalidArgumentException;
use RuntimeException;

/**
 * Un cache permettant de stocker des fichiers générés sur disque (template compilé, version SQLite d'une
 * table d'autorité, etc.).
 */
class FileCache
{
    /**
     * @var int Permissions des fichiers ajoutés au cache.
     */
    const FILE_MASK = 0660;

    /**
     * @var int Permissions des répertoires qui sont créés.
     */
    const DIR_MASK = 0770;

    /**
     * @var string Path racine des fichiers ajoutés au cache.
     */
    protected $root;

    /**
     * @var string Path absolu du répertoire contenant les fichiers du cache.
     */
    protected $directory;

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
     * @param string $root      Path racine des fichiers qui pourront être stockés dans ce cache.
     * @param string $directory Path du répertoire qui contiendra les fichiers mis en cache.
     */
    public function __construct($root, $directory)
    {
        $this->setRoot($root)->setDirectory($directory);
    }

    /**
     * Retourne le path racine des fichiers qui peuvent être stockés dans le cache.
     *
     * @return string
     */
    public function getRoot()
    {
        return $this->root;
    }

    /**
     * Modifie le path racine des fichiers qui peuvent être stockés dans le cache.
     *
     * @param string $root
     *
     * @return self
     */
    public function setRoot($root)
    {
        $this->root = $this->normalizePath($root);

        return $this;
    }

    /** @deprecated */
    public function root()
    {
        trigger_error('deprecated');

        return $this->getRoot();
    }

    /**
     * Retourne le path du répertoire contenant les fichiers mis en cache.
     *
     * @return string
     */
    public function getDirectory()
    {
        return $this->directory;
    }

    /**
     * Modifie le path du répertoire contenant les fichiers mis en cache.
     *
     * @param string $directory
     *
     * @return self
     */
    public function setDirectory($directory)
    {
        $this->directory = $this->normalizePath($directory);

        return $this;
    }

    /** @deprecated */
    public function directory()
    {
        trigger_error('deprecated');

        return $this->getDirectory();
    }

    /**
     * Retourne le path dans le cache du fichier indiqué.
     *
     * On ne teste pas si le fichier existe : on se contente de déterminer le path qu'aurait le fichier
     * s'il était mis en cache.
     *
     * @param string $file Path absolu du fichier à tester.
     *
     * @return string Path dans le cache de ce fichier.
     *
     * @throws InvalidArgumentException Si le fichier indiqué ne peut pas figurer dans le cache.
     */
    public function getPath($file)
    {
        $file = strtr($file, '/\\', DIRECTORY_SEPARATOR);
        if (0 !== strncasecmp($this->root, $file, strlen($this->root))) {
            throw new InvalidArgumentException(sprintf('Unable to cache file "%s", does not match cache root', $file));
        }

        return $this->directory . substr($file, strlen($this->root));
    }

    /** @deprecated */
    public function path($file)
    {
        trigger_error('deprecated');

        return $this->getPath($file);
    }

    /**
     * Indique si un fichier figure dans le cache et s'il est à jour.
     *
     * @param string    $file le path du fichier à tester.
     * @param int       $time date/heure minimale du fichier en cache pour qu'il soit considéré comme à jour.
     *
     * @return bool true si le fichier est dans le cache et est à jour, false sinon.
     */
    public function has($file, $time = 0)
    {
        $path = $this->getPath($file);
        if (! file_exists($path)) {
            return false;
        }

        return ($time === 0) || (filemtime($path) >= $time);
    }

    /**
     * Stocke un fichier dans le cache.
     *
     * @param string $file Path du fichier à stocker.
     * @param string $data Contenu du fichier.
     *
     * @return self
     *
     * @throws InvalidArgumentException Si le fichier indiqué ne peut pas figurer dans le cache.
     * @throws RuntimeException         Si le répertoire du fichier ne peut pas être créé.
     */
    public function put($file, $data)
    {
        // Détermine le path du fichier en cache
        $path = $this->getPath($file);

        // Crée le répertoire destination si nécessaire
        $directory = dirname($path);
        if (! is_dir($directory) && ! @mkdir($directory, self::DIR_MASK, true)) {
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
     * @param string $file Le path du fichier à charger.
     *
     * @return string|null Le contenu du fichier ou null si le fichier ne figure pas dans le cache.
     *
     * @throws InvalidArgumentException Si le fichier indiqué ne peut pas figurer dans le cache.
     */
    public function get($file)
    {
        $path = $this->getPath($file);

        return file_exists($path) ? file_get_contents($path) : null;
    }

    /**
     * Supprime un fichier ou un répertoire du cache.
     *
     * Aucune erreur n'est générée si le fichier indiqué ne figure pas dans le cache.
     *
     * La fonction essaie également de supprimer les répertoires vides.
     *
     * @param string $file Le path du fichier ou du répertoire à supprimer (vide = tout le cache).
     *
     * @return bool True si le fichier ou le répertoire indiqué a été supprimé
     */
    public function clear($file = '')
    {
        // Détermine ce qu'il faut supprimer
        $path = $file ? $this->getPath($file) : $this->directory;

        // Suppression d'un répertoire complet
        if (is_dir($path)) {
            return $this->rmTree($path);
        }

        // Suppression d'un fichier
        if (! @unlink($path)) {
            return false;
        }

        // Le répertoire est peut-être vide, maintenant, essaie de le supprimer
        $path = dirname($path);
        while (strlen($path) > strlen($this->directory)) {
            if (! @rmdir($path)) {
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
     * - garantir qu'on a un séparateur à la fin.
     *
     * @param string $path
     *
     * @return string
     */
    protected function normalizePath($path)
    {
        $path = strtr($path, '/\\', DIRECTORY_SEPARATOR);
        $path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        return $path;
    }

    /**
     * Supprime un répertoire et son contenu.
     *
     * @param string $directory
     */
    protected function rmTree($directory)
    {
        $files = array_diff(scandir($directory), ['.', '..']);
        foreach ($files as $file) {
            $path = $directory . DIRECTORY_SEPARATOR . $file;
            if (is_dir($path)) {
                if (! $this->rmTree($path)) {
                    return false;
                }
            } else {
                if (! unlink($path)) {
                    return false;
                }
            }
        }

        return @rmdir($directory);
    }
}
