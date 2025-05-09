<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2023 Daniel Ménard
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
 * @author Daniel Ménard <daniel.menard.35@gmail.com>
 */
abstract class Choice extends Element
{
    /**
     * Le préfixe des classes CSS qui sont générées par displayOption() et startOptionGroup().
     *
     * @var string
     */
    public const CSS_CLASS = 'choice';

    /**
     * La liste des options disponibles.
     *
     * @var array<int|string,string>|array<int,array<int|string,string>>|callable|string
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
     * @param array<int|string,string>|array<int,array<int|string,string>>|callable|string $options la liste des options disponibles
     *
     * @throws InvalidArgumentException si les options indiquées ne sont pas valides
     */
    final public function setOptions(array|callable|string $options): static
    {
        switch (true) {
            case is_callable($options):
                break;

            case is_string($options):
                if (2 !== count(array_filter(explode(':', $options), 'is_string'))) {
                    throw $this->invalidArgument('%s: invalid lookup (%s)', gettype($options));
                }
                break;

            default: // case is_array($options):
                if ($options !== [] && !is_string(reset($options)) && !is_array(reset($options))) {
                    throw $this->invalidArgument('%s: invalid options (%s)', gettype($options));
                }
                break;
        }

        $this->options = $options;

        return $this;
    }

    /**
     * Retourne la liste des options disponibles.
     *
     * @return array<int|string,string>|array<int,array<int|string,string>>|callable|string
     */
    final public function getOptions(): array|callable|string
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
     *                           EntryPicker qui n'affiche initialement que les options sélectionnées (les autres options disponibles sont
     *                           obtenues via des requêtes ajax). Pour tous les autres types de Choice (RadioList, CheckList et Select), le
     *                           paramètre $selected est ignoré.
     *
     * @return array<int|string,string>|array<int,array<int|string,string>> un tableau de la forme code => valeur
     */
    protected function loadOptions(array $selected = []): array
    {
        // Si c'est un callback, on l'exécute
        if (is_callable($this->options)) {
            return ($this->options)($this);
        }

        // Si c'est un tableau, on le retourne tel quel
        if (is_array($this->options)) {
            return $this->options;
        }

        // Obligatoirement une chaine de lookup (cf. setOptions)
        // Vérifie que c'est une table ou un thesaurus et retourne le contenu

        // Détermine le type et la source des lookups
        [$type, $source] = explode(':', $this->options, 2);

        // Pour les Choice de base (radiolist, select...) on ne gère que les lookups de type table ou thesaurus
        // (avec des lookups de type index ou search on chargerait des listes potentiellement énormes dans
        // le code html de la page et en plus le format des entrées n'est plus le même dans ce cas là).
        if ('table' !== $type && 'thesaurus' !== $type) {
            throw $this->invalidArgument('Lookups of type "%s" are not supported, try with EntryPicker');
        }

        // Ouvre la table
        $table = $this->getTableManager()->get($source);

        // Charge la totalité des entrées de la table et retourne un tableau de la forme code => valeur
        $entries = $table->search('code,label');

        /** @var array<string,string> */
        return $entries;
    }

    private static TableManager|null $tableManager = null;
    public static function setTableManager(TableManager $tableManager): void
    {
        self::$tableManager = $tableManager;
    }
    public function getTableManager(): TableManager
    {
        if (is_null(self::$tableManager)) {
            throw new \LogicException(sprintf('Dependency %s of %s has not been initialized', TableManager::class, Choice::class));
        }
        return self::$tableManager;
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
     * @param Theme    $theme    thème à utiliser
     * @param string[] $selected Les données actuelles (i.e. les options qui auront l'attribut "selected").
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
            if (!is_array($label)) {
                throw $this->invalidArgument('%s: invalid options for optgroup "%s", expected array.', $value);
            }

            // Génère le début du groupe
            $this->startOptionGroup((string) $value, $theme);

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
                    throw $this->invalidArgument('%s: invalid option "%s", options groups cannot be nested.', $value);
                }
            }

            // Génère la fin du groupe
            $this->endOptionGroup($theme);
        }

        // S'il reste encore des données dans selected, ce sont des options invalides
        foreach ($selected as $value) {
            // Evite de signaler les valeurs vides comme invalides
            if (is_null($value) || '' === $value) {
                continue;
            }

            $this->displayOption($theme, (string) $value, (string) $value, true, true);
        }
    }

    /**
     * Affiche une option.
     *
     * @param Theme  $theme    thème à utiliser
     * @param string $value    code de l'option
     * @param string $label    libellé de l'option
     * @param bool   $selected True si l'option est sélectionnée (i.e. si elle figure dans le champ).
     * @param bool   $invalid  true si l'option figure dans le champ mais pas dans les options disponibles
     */
    abstract protected function displayOption(
        Theme $theme,
        string $value,
        string $label,
        bool $selected,
        bool $invalid
    ): void;

    /**
     * Affiche le tag de début d'un groupe d'options.
     *
     * @param string $label libellé du groupe d'options
     * @param Theme  $theme thème à utiliser
     */
    abstract protected function startOptionGroup(string $label, Theme $theme): void;

    /**
     * Affiche le tag de fin d'un groupe d'options.
     *
     * @param Theme $theme thème à utiliser
     */
    abstract protected function endOptionGroup(Theme $theme): void;
}
