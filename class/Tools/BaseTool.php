<?php declare(strict_types=1);
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Tools;

use Docalist\Tools\Tool;
use Docalist\Tools\Capability\AdminToolTrait;
use Docalist\Tools\Category\MaintenanceToolTrait;
use ReflectionObject;

/**
 * Classe de base d'un outil Docalist.
 *
 * Cette classe simplifie la création d'un outil Docalist en rendant optionnelle l'implémentation des méthodes de
 * l'interface Tool : elle génère le libellé et la description de l'outil à partir du DocBlock de l'outil et
 * fournit une catégorie ("maintenance") et une capacité ("admin") par défaut.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class BaseTool implements Tool
{
    use AdminToolTrait, MaintenanceToolTrait;

    /**
     * Le libellé et la description extraits du DocBlock de l'outil.
     *
     * @var null|array Null initialement, un tableau contenant les clés 'label' et 'description' une fois que
     * parseDocBlock() a été appellée.
     */
    private $doc;

    /**
     * Récupère le libellé et la description de l'outil dans le DocBlock de la classe et initialise la propriété doc.
     */
    private function parseDocBlock(): void
    {
        // Si on a déjà parsé, terminé
        if (! is_null($this->doc)) {
            return;
        }

        // Récupère la doc
        $doc = (new ReflectionObject($this))->getDocComment();

        // Normalise les fins de ligne
        $doc = str_replace("\r\n", "\n", $doc);

        // Supprime les marques de commentaires
        $doc = preg_replace('|^/\*\*[\r\n]+|', '', $doc);
        $doc = preg_replace('|\n[\t ]*\*/$|', '', $doc);
        $doc = preg_replace('|^[\t ]*\* ?|m', '', $doc);

        // Ignore les lignes vides (et les blancs) de début
        $doc = ltrim($doc);

        // Parcourt les lignes, extrait le label puis la description et stoppe au premier tag trouvé
        $lines = explode("\n", $doc);
        $doc = ['label' => '', 'description' => ''];
        $dest = 'label';
        foreach ($lines as $line) {
            if (empty($line)) {
                ($dest === 'label') ? ($dest = 'description') : ($doc[$dest] .= "<br \>");
                continue;
            }

            if ($line[0] === '@') {
                break;
            }

            !empty($doc[$dest]) && $doc[$dest] .= ' ';
            $doc[$dest] .= $line;
        }

        // Enlève le point final éventuel du libellé
        $doc['label'] = trim($doc['label'], '.');

        // Terminé
        $this->doc = $doc;
    }

    /**
     * {@inheritDoc}
     */
    public function getLabel(): string
    {
        $this->parseDocBlock();
        return $this->doc['label'];
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription(): string
    {
        $this->parseDocBlock();
        return $this->doc['description'];
    }

    /**
     * {@inheritDoc}
     */
    public function run(array $args = []): void
    {
        echo 'Implement ', get_class($this), '::run()';
    }
}
