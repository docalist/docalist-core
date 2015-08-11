<?php
/**
 * Markdown-viewer : liseuse de documentation au format markdown.
 *
 * Copyright (C) 2013 Daniel Ménard
 *
 * Ce petit script php permet d'afficher sous forme de pages html joliment
 * stylées (bootstrap) les fichiers markdown présents dans un répertoire.
 *
 * Il utilise la librairie javascript http://strapdownjs.com, c'est elle
 * qui fait tout le boulot.
 *
 * Les pages sont indexables par les moteurs. Elles sont valides html5 à
 * l'exception du tag xmp utilisé par strapdownjs et qui est deprecated.
 *
 * Utilisation :
 * - créez un répertoire dans votre web root (par exemple /doc)
 * - collez-y ce fichier en gardant son nom (index.php)
 *   (ou utilisez un svn:externals à partir de svn 1.6 si même dépôt)
 * - ajoutez quelques fichiers markdown
 * - ouvrez l'url /doc/ dans votre navigateur (ou /doc/index.php si votre
 *   serveur web ne le retourne pas automatiquement).
 *
 * Les premières lignes du script contiennent quelques options de config
 * que vous pouvez modifier à votre convenance.
 *
 * Normallement, il n'y a pas lieu de toucher à ce qui suit "stop editing".
 *
 * @licence   GPL v3
 * @version   1.0 du 08/02/13
 *
 * La version la plus récente de ce script se trouve à l'adresse suivante :
 * http://docalist.googlecode.com/svn/markdown-viewer/index.php
 */

/**
 * Nom du "package" documenté tel qu'il sera affiché dans le titre juste
 * avant le titre du fichier markdown (la première ligne "# xxx" trouvée).
 *
 * Par défaut, le nom du package est déterminé automatiquement (c'est le nom
 * du répertoire parent) mais vous pouvez indiquer à la place une chaine.
 */
$package = ucfirst(basename(dirname(__DIR__))) . ' — ';

/**
 * Nom du fichier par défaut qui sera affiché si aucun nom de fichier n'est
 * indiqué en paramètre.
 *
 * Les noms des fichiers sont testés dans l'ordre. Le premier fichier qui
 * sera trouvé dans le répertoire en cours sera affiché.
 */
$default = array('TOC.md', 'toc.md', 'index.md', 'README.md', 'default.md');

/**
 * Liste des extensions de fichier autorisées.
 *
 * Une erreur 404 sera générée si le fichier n'a pas l'une des ces extensions.
 */
$allowedExtensions = array('md', 'markdown', 'txt');

/**
 * Message d'erreur "404 - File not found", au format markdown
 *
 * Ce message sera retourné si le fichier demandé n'existe pas ou s'il n'est
 * pas autorisé.
 */
$error404 = "# 404 - Not Found\n\nFichier non trouvé.";

/*
 * Nom du thème bootstrap à utiliser pour le rendu.
 *
 * Valeurs possibles :
 * amelia, bootstrap, cerulean, cyborg, journal, readable, simplex, slate,
 * spacelab, spruce, superhero, united
 */
$theme = 'cerulean';

// Stop editing !

// Url de base de notre script (exemple : xxx/doc/index.php)
$baseUrl = $_SERVER['SCRIPT_NAME'];

// Url demandée (exemple : xxx/doc/index.php/about.md)
$url = $_SERVER['REQUEST_URI'];

// Détermine le fichier à afficher (exemple : about.md)
$file = substr($url, strlen($baseUrl) + 1);
if (false === $file) {
    foreach($default as $file) {
        if (file_exists(__DIR__ . '/' . $file)) break;
    }
}

// Charge le fichier s'il est autorisé
$path = __DIR__ . '/' . $file;
if (   in_array(pathinfo($file, PATHINFO_EXTENSION), $allowedExtensions)
    && false === strpos($file, '..') // pas utile mais bon...
    && file_exists($path) ) {

    $content = file_get_contents($path);
} else {
    $content = $error404;
}

// Détermine son titre
$title = preg_match('~#\s*(.*)~', $content, $match) ? $match[1] : $file;

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8" />
<title><?php echo $package, $title ?></title>
<base href="<?php echo $baseUrl ?>/">
<xmp theme="<?php echo $theme ?>" style="display:none;"><?php echo $content ?></xmp>
<script src="http://strapdownjs.com/v/0.2/strapdown.js"></script>