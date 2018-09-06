<?php declare(strict_types=1);
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Pipeline;

use InvalidArgumentException;

/**
 * Interface d'un pipeline de données.
 *
 * Un {@link https://martinfowler.com/articles/collection-pipeline/ pipeline de données} constitue une chaine de
 * traitements qui permet d'appliquer une suite d'opérations à une collection d'items.
 *
 * <code>
 * collection initiale -> [operation 1] -> nouvelle collection -> [operation 2] -> ... -> collection finale.
 * </code>
 *
 * Un item peut être de n'importe quel type : un scalaire (entier, chaine...), un tableau, un objet, un fichier...
 *
 * Une collection est une liste d'items : c'est un {@link http://php.net/language.types.iterable Iterable},
 * c'est-à dire un tableau ou un objet qui implémente l'interface {@link http://php.net/Traversable Traversable}
 * (un {@link http://php.net/class.iterator itérateur} ou un {@link http://php.net/language.generators générateur}).
 *
 * Une opération (un traitement) est simplement un {@link http://php.net/language.types.callable callable} :
 * il peut s'agir d'une fonction nommée, d'une fonction anonyme, d'une méthode statique de classe,
 * d'une méthode d'une instance, ou d'un objet disposant d'une méthode
 * {@link http://php.net/language.oop5.magic#object.invoke __invoke()}.
 *
 * Dans tous les cas, l'opération prend en paramètre un item à traiter (généré par l'opération précédente du pipeline)
 * et retourne 0, 1 ou plusieurs items :
 *
 * <code>
 *     function ($item) : null|item|Generator;
 * </code>
 *
 * Selon le traitement effectué, les opérations peuvent être classées en plusieurs catégoriées :
 *
 * - transformer : une transformation applique un traitement sur l'item reçu en paramètre et retourne l'item modifié.
 * - filter : un filtre permet de supprimer certains items de la chaine de traitement. Il effectue un test et il
 *   retourne soit null, soit l'item transmis en paramètre.
 * - generator : un générateur permet de générer de nouveaux items à traiter. Au lieu de retourner un item unique,
 *   il retourne un générateur qui produit de nouveaux items.
 * - observer : un observateur se contente de regarder ce qui se passe (par exemple, il met à jour des compteurs ou
 *   fait des stats) mais il retourne tels quels les items qu'on lui passe en paramètre.
 * - ou une combinaison de tout ça.
 *
 * Lorsque le pipeline est exécuté, il prend en paramètre un {@link http://php.net/language.types.iterable Iterable}
 * contenant les items à traiter. Pour chacun des items, il applique séquentiellement chacune des opérations demandées
 * en fournissant à chaque opération les items générés par l'opération précédente et se charge de supprimer les items
 * qui ont été filtrés et d'injecter dans le process les nouveaux items générés. Une fois que tous les items ont été
 * traités, il retourne le résultat obtenu.
 *
 * Sur le fond, un pipeline peut être vu comme une double boucle : une première boucle pour itérer sur les
 * items à traiter puis une boucle imbriquée pour itérer sur les opérations à effectuer sur chaque item.
 *
 * L'intérêt du pipeline, c'est qu'il permet de faire la même chose mais sans avoir à charger la totalité des items
 * en mémoire (laziness). Dans l'implémentation standard, ce sont des générateurs qui sont utilisés et les items sont
 * traités un par un lorsque la collection initiale est itérée.
 *
 * Enfin, un pipeline permet de découpler plus facilement les traitements effectués par une chaine de traitement car
 * chaque opération peut être testée indépendement. Le pipeline peut alors être vu comme une composition de fonctions.
 *
 * @see https://martinfowler.com/articles/collection-pipeline/
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
interface Pipeline
{
    /**
     * Ajoute une opération à la fin du pipeline.
     *
     * @param callable          $operation  L'opération à ajouter.
     * @param int|string|null   $key        Optionnel, une clé (entier ou chaine) utilisée pour identifier l'opération.
     *                                      Par défaut (null), un numéro unique est affecté à l'opération.
     *
     * @throws InvalidArgumentException Si une clé a été indiquée et qu'il existe déjà une opération avec cette clé.
     */
    public function appendOperation(callable $operation, $key = null): void;

    /**
     * Ajoute une opération au début du pipeline.
     *
     * @param callable          $operation  L'opération à ajouter.
     * @param int|string|null   $key        Optionnel, une clé (entier ou chaine) utilisée pour identifier l'opération.
     *                                      Par défaut (null), un numéro unique est affecté à l'opération.
     *
     * @throws InvalidArgumentException Si une clé a été indiquée et qu'il existe déjà une opération avec cette clé.
     */
    public function prependOperation(callable $operation, $key = null): void;

    /**
     * Teste si le pipeline contient une opération ayant la clé indiquée.
     *
     * @param int|string $key Clé à tester.
     *
     * @return bool Retourne true s'il existe une opération avec la clé indiquée, false sinon.
     */
    public function hasOperation($key): bool;

    /**
     * Retourne une opération.
     *
     * @param int|string $key Clé de l'opération à retourner.
     *
     * @throws InvalidArgumentException Si le pipeline ne contient aucune opération ayant la clé indiquée.
     *
     * @return callable
     */
    public function getOperation($key): callable;

    /**
     * Retourne la liste des opérations qui composent le pipeline.
     *
     * @return callable[]
     */
    public function getOperations(): array;

    /**
     * Traite les items passés en paramètre.
     *
     * @param Iterable $items Un itérable contenant les items à traiter.
     *
     * @return Iterable Un itérable contenant les items traités.
     */
    public function process(Iterable $items): Iterable;

    /**
     * Traite un item.
     *
     * Permet à un pipeline d'être ajouté à un autre pipeline.
     *
     * @param mixed $item
     *
     * @return Iterable|null Le résultat du traitement.
     */
    public function __invoke($item): Iterable;
}
