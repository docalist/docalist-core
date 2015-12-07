<?php
/**
 * This file is part of a "Docalist Core" plugin.
 *
 * Copyright (C) 2012-2015 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Core
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Type\Interfaces;

use Docalist\MappingBuilder;

/**
 * API permettant d'indexer un type de données dans un moteur de recherche.
 *
 * Tous les types de données docalist sont potentiellement indexables.
 *
 * Chaque type peut définir la façon dont il souhaite être indexé et fournit les données
 * qui seront stockées dans les index du moteur de recherche.
 */
interface Indexable
{
    /**
     * Initialise les mappings du type en utilisant le MappingBuilder passé en paramètre.
     *
     * @param MappingBuilder $mapping Le mapping à modifier.
     */
    public function setupMapping(MappingBuilder $mapping);

    /**
     * Stocke les données à ajouter au moteur de recherche dans le tableau passé en paramètre.
     *
     * Cette méthode convertit les données interne du type et ajoute dans le tableau passé en paramètre
     * les données qu'il souhaite indexer dans le moteur de recherche.
     *
     * En général, la conversion est très simple et ressemblera au code suivant :
     *
     * <code>
     *     $document['my-name'][] => $this->value();
     * </code>
     *
     * Cependant, un type unique peut aussi initialiser plusieurs champs différents dans le moteur de recherche.
     *
     * Par exemple, si on a un champ 'user' qui contient un ID, on peut indexer à la fois le numéro, le login
     * et le nom complet de l'utilisateur avec un code du style :
     *
     * <code>
     *     $document['user_id'][] = $this->getID()
     *     $document['user_login'][] = $this->getLogin()
     *     $document['user_name'][] = $this->getFullName()
     * </code>
     *
     * @param array $document Le tableau (document) dans lequel les données doivent être ajoutées.
     */
    public function mapData(array & $document);
}
