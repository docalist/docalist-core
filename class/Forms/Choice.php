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

namespace Docalist\Forms;

use Docalist\Table\TableManager;
use InvalidArgumentException;

/**
 * Classe de base pour les champs qui permettent à l'utilisateur de faire un choix parmi une liste de
 * valeurs possibles (select, checklist, radiolist, entrypicker).
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
abstract class Choice extends Element
{
    /**
     * Le préfixe des classes CSS qui sont générées par displayOption() et startOptionGroup().
     *
     * @var string
     */
    const CSS_CLASS = 'choice';

    /**
     * La liste des options disponibles.
     *
     * @var array|callable|string
     */
    protected $options = [];

    /**
     * Modifie la liste des options disponibles.
     *
     * Les options disponibles peuvent être fournies au contrôle en indiquant :
     *
     * - un tableau de la forme code => label,
     * - un callable qui retourne la liste des options,
     * - une chaine de lookup indiquant où se trouvent les options disponibles.
     *
     * Exemples :
     * <code>
     *     setOptions(['fr' => 'french', 'en' => english]); // Appel avec un tableau d'options
     *     setOptions(function() {                          // Appel avec un callable qui retourne les options
     *         return ['fr' => 'french', 'en' => english];
     *     });
     *     setOptions('table:dates');                       // Appel avec un lookup de type table
     *     setOptions('thesaurus:dates');                   // Appel avec un lookup de type thésaurus
     * </code>
     *
     * Remarque : les contrôles de base (RadioList, CheckList, Select) ne supportent que les lookups de type 'table'
     * ou 'thesaurus'. Les lookups de type 'index' ou 'search' ne sont pas supportés car ça conduirait à injecter
     * dans le code html de la page des listes potentiellement gigantesques. Pour des lookups de ce type, il faut
     * utiliser le contrôle EntryPicker qui supporte les requêtes ajax et n'injecte pas la totalité des options
     * possibles dans le code de la page.
     *
     * @param array|callable|string $options La liste des options disponibles.
     *
     * @throws InvalidArgumentException Si les options indiquées ne sont pas valides.
     *
     * @return self
     */
    final public function setOptions($options): self
    {
        if (is_array($options) || is_string($options) || is_callable($options)) {
            $this->options = $options;

            return $this;
        }

        $this->invalidArgument('%s: invalid options (%s)', gettype($options));
    }

    /**
     * Retourne la liste des options disponibles.
     *
     * @return array|callable|string
     */
    final public function getOptions()// : mixed
    {
        return $this->options;
    }

    /**
     * Charge la liste des options qui seront affichées dans le contrôle.
     *
     * - Si $options est un tableau, la méthode se contente de le retourner.
     * - Si $options est une closure, la méthode exécute la closure et retourne le résultat (un tableau d'options).
     * - Si $options est une chaine (lookup de type table ou thésaurus), la méthode charge les options disponibles.
     *
     * @param string[] $selected Les données actuellement sélectionnées. Ce paramètre n'est utilisé que par la classe
     * EntryPicker qui n'affiche initialement que les options sélectionnées (les autres options disponibles sont
     * obtenues via des requêtes ajax). Pour tous les autres types de Choice (RadioList, CheckList et Select), le
     * paramètre $selected est ignoré.
     *
     * @return string[] Un tableau de la forme code => valeur.
     */
    protected function loadOptions(array $selected = []): array
    {
        // Si c'est un tableau, on le retourne tel quel
        if (is_array($this->options)) {
            return $this->options;
        }

        // Si c'est un callback, on l'exécute
        if (is_callable($this->options)) {
            return ($this->options)($this);
        }

        // Si c'est une chaine de lookup, vérifie que c'est une table ou un thesaurus et retourne le contenu
        if (is_string($this->options)) {
            // Détermine le type et la source des lookups
            list($type, $source) = explode(':', $this->options, 2);

            // Pour les Choice de base (radiolist, select...) on ne gère que les lookups de type table ou thesaurus
            // (avec des lookups de type index ou search on chargerait des listes potentiellement énormes dans
            // le code html de la page et en plus le format des entrées n'est plus le même dans ce cas là).
            if (!($type === 'table' || $type === 'thesaurus')) {
                $this->invalidArgument('Lookups of type "%s" are not supported, try with EntryPicker');
            }

            // Ouvre la table
            $tableManager = docalist('table-manager'); /** @var TableManager $tableManager */
            $table = $tableManager->get($source);

            // Charge la totalité des entrées de la table et retourne un tableau de la forme code => valeur
            return $table->search('code,label');
        }

        // ça ne peut pas être autre chose (cf. setOptions)
    }

    /**
     * Affiche la liste des options disponibles.
     *
     * La méthode affiche toutes les options qui sont retournées par loadOptions($selected). En général, il s'agit
     * de la totalité des options disponibles, telles qu'indiquées dans la source (setOptions). Pour le contrôle
     * EntryPicker, seules les options actuellement sélectionnées sont affichées (les autres options disponibles
     * sont chargées via des requêtes ajax).
     *
     * Les méthode gère la logique de la génération mais ce sont les méthodes displayOption(), startOptionGroup()
     * et endOptionGroup() qui génèrent les tags qui seront affichés. Ces trois méthodes doivent être implémentées
     * dans les classes descendantes.
     *
     * @param Theme     $theme      Thème à utiliser.
     * @param string[]  $selected   Les données actuelles (i.e. les options qui auront l'attribut "selected").
     */
    protected function displayOptions(Theme $theme, array $selected): void
    {
        // Charge les options disponibles (on passe $selected car EntryPicker ne charge que les options sélectionnées)
        $options = $this->loadOptions($selected);

        // Indexe les données par clé : au fur et à mesure de l'affichage, on les supprimera du tableau et si à la
        // fin il reste encore des données, elles seront affichées comme options invalides
        $selected = array_combine($selected, $selected);

        // Affiche toutes les options
        foreach ($options as $value => $label) {
            // Si label est une chaine, c'est une option simple
            if (is_string($label)) {
                // Affiche l'option
                $this->displayOption($theme, (string) $value, $label, isset($selected[$value]), false);

                // Supprime l'option de la liste des options sélectionnées
                unset($selected[$value]);

                // Passe à l'option suivante
                continue;
            }

            // Groupe d'options : value contient le label, et label doit contenur un tableau d'options
            if (! is_array($label)) {
                $this->invalidArgument('%s: invalid options for optgroup "%s", expected array.', $value);
            }

            // Génère le début du groupe
            $this->startOptionGroup($value, $theme);

            // Génère les options du groupe
            foreach ($label as $value => $label) {
                // Si label est une chaine, c'est une option simple
                if (is_string($label)) {
                    // Affiche l'option
                    $this->displayOption($theme, (string) $value, $label, isset($selected[$value]), false);

                    // Supprime l'option de la liste des options sélectionnées
                    unset($selected[$value]);

                    // Passe à l'option suivante
                    continue;
                }

                // Groupe d'options imbriqué, ce n'est pas autorisé, génère une exception
                if (is_array($label)) {
                    $this->invalidArgument('%s: invalid option "%s", options groups cannot be nested.', $value);
                }
            }

            // Génère la fin du groupe
            $this->endOptionGroup($theme);
        }

        // S'il reste encore des données dans selected, ce sont des options invalides
        foreach ($selected as $value) {
            $this->displayOption($theme, (string) $value, (string) $value, true, true);
        }
    }

    /**
     * Affiche une option.
     *
     * @param Theme     $theme      Thème à utiliser.
     * @param string    $value      Code de l'option.
     * @param string    $label      Libellé de l'option.
     * @param bool      $selected   True si l'option est sélectionnée (i.e. si elle figure dans le champ).
     * @param bool      $invalid    True si l'option figure dans le champ mais pas dans les options disponibles.
     */
    abstract protected function displayOption(
        Theme   $theme,
        string  $value,
        string  $label,
        bool    $selected,
        bool    $invalid
    ): void;

    /**
     * Affiche le tag de début d'un groupe d'options.
     *
     * @param string    $label      Libellé du groupe d'options.
     * @param Theme     $theme      Thème à utiliser.
     */
    abstract protected function startOptionGroup(string $label, Theme $theme): void;

    /**
     * Affiche le tag de fin d'un groupe d'options.
     *
     * @param Theme     $theme      Thème à utiliser.
     */
    abstract protected function endOptionGroup(Theme $theme): void;
}
