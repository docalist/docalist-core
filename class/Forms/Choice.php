<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Forms;

use Docalist\Table\TableInterface;

/**
 * Classe de base pour les champs qui permettent à l'utilisateur de faire un choix parmi une liste de
 * valeurs possibles (select, checklist, radiolist).
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
abstract class Choice extends Element
{
    /**
     * @var array|callable|string Les options disponibles.
     */
    protected $options = [];

    /**
     * Modifie la liste des options disponibles.
     *
     * @param array|callable|string $options Vous pouvez indiquer :
     *
     * - Un tableau de la forme 'value => label' contenant les options disponibles :
     *
     *   setOptions(['fr' => 'french', 'en' => english])
     *
     * - Un callable qui retourne un tableau contenant les options :
     *
     *   setOptions(function() { return ['fr' => 'french', 'en' => english]; });
     *
     * - Une chaine de caractères :
     *
     *   - le nom d'une table ou d'un thésaurus : setOptions('countries');
     *   - la chaine 'index' ??
     *   - une recherche : setOptions('search:type:person %s')
     *
     * @return self
     */
    public function setOptions($options)
    {
        if (is_array($options) || is_string($options) || is_callable($options)) {
            $this->options = $options;

            return $this;
        }

        return $this->invalidArgument('%s: invalid options (%s)', gettype($options));
    }

    /**
     * Retourne la liste des options disponibles.
     *
     * @return array|callable|string
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Charge la liste des options disponibles.
     *
     * - Si $options est un tableau, la méthode se contente de le retourner.
     * - Si $options est une closure, la méthode exécute la closure et retourne le résultat.
     * - Si $options est une chaine (nom de table ou de thésaurus), la méthode charge les options disponibles.
     *
     * @return array
     */
    protected function loadOptions()
    {
        if (is_array($this->options)) {
            return $this->options;
        }

        if (is_string($this->options)) {
            list(, $name) = explode(':', $this->options);

            $table = docalist('table-manager')->get($name); /** @var TableInterface $table */

            return $table->search('code,label');
        }

        if (is_callable($this->options)) {
            $callback = $this->options;

            return $callback($this);
        }

        return $this->invalidArgument('%s: invalid options (%s)', gettype($this->options));
    }

    /**
     * Affiche les options passées en paramètre.
     *
     * Cette méthode utilitaire permet de simplifier les vues (exemples views/form/base/select.php ou checklist.php).
     *
     * @param Theme $theme      Le thème à utiliser.
     * @param array $options    Les options à afficher.
     * @param array $data       Les options actuellement sélectionnées (données).
     * @param array $attributes Les attributs à ajouter à chacune des options.
     *
     * @return array Les valeurs invalides (celles qui figurent dans les données mais pas dans les options).
     */
    abstract protected function displayOptions(
        Theme $theme,
        array $options = [],
        array $data = [],
        array $attributes = []
    );
}
