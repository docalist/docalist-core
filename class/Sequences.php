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

namespace Docalist;

use InvalidArgumentException;

/**
 * Gestionnaire de sequences.
 *
 * Ce service permet d'implémenter une séquence, c'est un dire un numéro qui ne fait que croître : le champ ref des
 * notices, un numéro de batch, un compteur de visites, etc.
 *
 * Les séquences sont organisées en groupes. Chaque groupe a un nom de code unique (par exemple le nom du custom post
 * type).  Au sein de chaque groupe, on peut avoir une ou plusieurs séquences, chacune ayant un nom unique au sein
 * du groupe (par exemple le nom du champ ref).
 *
 * Les méthodes de cette classe permettent d'incrémenter une séquence de façon atomique, d'affecter une valeur à une
 * séquence et de réinitialiser une séquence (les séquences commencent à 1).
 *
 * En interne, les séquences sont stockées dans la table wp_options de wordpress avec des clés de la forme
 * "{groupe}_last_{sequence}" (par exemple "dbprisme_last_ref"). Pour cette raison, la longueur totale du nom de
 * la séquence ne doit pas dépasser 64 caractères (taille actuelle du champ option_name dans la table wp_options).
 * Une exception sera générée en cas de dépassement.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class Sequences
{
    /**
     * Retourne le nom de la séquence.
     *
     * Le nom de séquence correspond au nom de l'option qui sera créée dans la table wp_options de wordpress si
     * cette séquence est utilisée.
     *
     * @param string $group Nom du groupe.
     * @param string $sequence Nom de la séquence.
     *
     * @return string
     *
     * @throws InvalidArgumentException Si la longueur totale du nom de la séquence est supérieure à 64 caractères.
     */
    public function getSequenceName($group, $sequence)
    {
        // Valide le nom du groupe
        if (!preg_match('~^[a-z][a-z0-9-]*$~', $group)) {
            throw new InvalidArgumentException("Invalid sequence group : $group");
        }

        // Valide le nom de la séquence
        if ($sequence && ! ctype_alnum($sequence)) {
            throw new InvalidArgumentException("Invalid sequence name : $sequence");
        }

        // Construit le nom de l'option
        $name = $group . '_last_' . $sequence;

        // Le nom de l'option ne doit pas dépasser 64 caractères
        if (strlen($name) > 64) { // Taille maxi du champ option_name dans wp_options
            throw new InvalidArgumentException("Sequence name too long: $name");
        }

        // Ok
        return $name;
    }

    /**
     * Retourne la valeur actuelle d'une séquence.
     *
     * @param string $group Nom du groupe.
     * @param string $sequence Nom de la séquence.
     *
     * @return int La valeur actuelle de la séquence (0 si la séquence n'existe pas encore dans la table wp_options).
     */
    public function get($group, $sequence)
    {
        /** @var \wpdb */
        $wpdb = docalist('wordpress-database');

        // Nom de l'option dans la table wp_options
        $name = $this->getSequenceName($group, $sequence);

        // Requête SQL à exécuter. Adapté de :
        // @see http://answers.oreilly.com/topic/172-how-to-use-sequence-generators-as-counters-in-mysql/
        $sql = "SELECT `option_value` FROM `$wpdb->options` WHERE `option_name` = '$name'";

        // Exécute la requête (pas de prepare car on contrôle les paramètres)
        $row = $wpdb->get_row($sql);

        // Retourne la valeur de la séquence ou zéro si elle n'existe pas
        return is_object($row) ? (int) $row->option_value : 0;
    }

    /**
     * Modifie la valeur d'une séquence.
     *
     * @param string $group Nom du groupe.
     * @param string $sequence Nom de la séquence.
     * @param int $value La valeur de la séquence.
     *
     * @return int $value.
     */
    public function set($group, $sequence, $value)
    {
        /** @var \wpdb */
        $wpdb = docalist('wordpress-database');

        // Nom de l'option dans la table wp_options
        $name = $this->getSequenceName($group, $sequence);

        // Value doit être un entier
        $value = (int) $value;

        // Requête SQL à exécuter. Adapté de :
        // @see http://stackoverflow.com/a/10081527
        $sql = "INSERT INTO `$wpdb->options` (`option_name`, `option_value`, `autoload`) "
             . "VALUES('$name', $value, 'no') "
             . 'ON DUPLICATE KEY UPDATE `option_value` = VALUES(`option_value`)';

        // Exécute la requête (pas de prepare car on contrôle les paramètres)
        $wpdb->query($sql);

        return $value;
    }

    /**
     * Incrémente une séquence et retourne la valeur obtenue.
     *
     * Lors du premier appel, la méthode crée la séquence et retourne la valeur 1.
     * Lors des appels suivants, la séquence est incrémentée et la méthode retourne sa valeur actuelle.
     *
     * @param string $group Nom du groupe.
     * @param string $sequence Nom de la séquence.
     *
     * @return int La valeur de la séquence.
     */
    public function increment($group, $sequence)
    {
        /** @var \wpdb */
        $wpdb = docalist('wordpress-database');

        // Nom de l'option dans la table wp_options
        $name = $this->getSequenceName($group, $sequence);

        // Requête SQL à exécuter. Adapté de :
        // @see http://answers.oreilly.com/topic/172-how-to-use-sequence-generators-as-counters-in-mysql/
        $sql = "INSERT INTO `$wpdb->options` (`option_name`, `option_value`, `autoload`) "
             . "VALUES('$name', 1, 'no') "
             . 'ON DUPLICATE KEY UPDATE `option_value` = LAST_INSERT_ID(`option_value` + 1)';

        // Exécute la requête (pas de prepare car on contrôle les paramètres)
        $affectedRows = $wpdb->query($sql);

        return $affectedRows === 1 ? 1 : $wpdb->insert_id;

        // Explications sur la requête SQL :
        // @see http://dev.mysql.com/doc/refman/5.0/en/insert-on-duplicate.html
        //
        // With ON DUPLICATE KEY UPDATE, the affected-rows value per row is
        // - 1 if the row is inserted as a new row
        // - 2 if an existing row is updated
        // - 0 if an existing row is set to its current values (i.e. n'a pas
        //   été modifiée. Attention, dépend du flag CLIENT_FOUND_ROWS)
        //
        // If a table contains an AUTO_INCREMENT column and INSERT ... UPDATE
        // inserts a row, the LAST_INSERT_ID() function returns the
        // AUTO_INCREMENT value (i.e. l'ID de l'enreg créé).
        // If the statement updates a row instead, LAST_INSERT_ID() is not
        // meaningful. However, you can work around this by using
        // LAST_INSERT_ID(expr) : c'est ce qu'on fait sur la dernière ligne,
        // on "initialise" la valeur qui sera retournée par LAST_INSERT_ID().
        //
        // Dans notre cas :
        // - premier appel, la ligne est insérée, insert retourne "affected-rows=1"
        //   donc on sait que le compteur est à 1
        // - appel suivant, la ligne est updatée, insert retourne "affected-rows=2"
        //   il faut appeller LAST_INSERT_ID() pour obtenir la valeur du compteur.
    }

    /**
     * Réinitialise (supprime) une séquence, ou toutes les séquences d'un groupe si aucun nom de séquence
     * n'est passé en paramètre.
     *
     * @param string $group Nom du groupe.
     * @param string $sequence Nom de la séquence.
     *
     * @return int Le nombre de séquences supprimées.
     */
    public function clear($group, $sequence = null)
    {
        /** @var \wpdb */
        $wpdb = docalist('wordpress-database');

        if (!empty($sequence)) {
            $op = '=';
            $value = $this->getSequenceName($group, $sequence);
        } else {
            $op = ' LIKE ';
            $value = $this->getSequenceName($group, '') . '%';
        }

        $sql = "DELETE FROM `$wpdb->options` WHERE `option_name` $op '$value'";

        return (int) $wpdb->query($sql);
    }

    /**
     * Stocke la valeur passée en paramêtre dans la séquence indiquée si la valeur indiquée est supérieure à la
     * valeur actuelle de la séquence.
     *
     * Cette méthode permet de mettre à jour une séquence quand le numéro est fournit par l'extérieur.
     *
     * Exemple d'utilisation :
     *
     * <code>
     *     if (empty($ref))) $ref = docalist('sequences')->increment('notice', 'ref');
     *     else docalist('sequences')->setIfGreater($ref);
     * </code>
     *
     * @param string $group Nom du groupe.
     * @param string $sequence Nom de la séquence.
     * @param int $value Nouvelle valeur de la séquence.
     *
     * @return int Un code indiquant l'opération réalisée :
     * - 0 : la séquence n'a pas été modifiée (sa valeur actuelle est supérieure ou égale à $value).
     * - 1 : la séquence n'existait pas encore, elle a été créée et initialisée à $value.
     * - 2 : la séquence a été mise à jour (la séquence existait mais sa valeur était inférieure à $value, elle a
     *       été initialisée à $value).
     */
    public function setIfGreater($group, $sequence, $value)
    {
        /** @var \wpdb */
        $wpdb = docalist('wordpress-database');

        // Nom de l'option dans la table wp_options
        $name = $this->getSequenceName($group, $sequence);

        // Value doit être un entier
        $value = (int) $value;

        // Requête SQL à exécuter. Adapté de :
        // @see http://stackoverflow.com/a/10081527
        $sql = "INSERT INTO `$wpdb->options` (`option_name`, `option_value`, `autoload`) "
             . "VALUES('$name', $value, 'no') "
             . 'ON DUPLICATE KEY UPDATE '
             . '`option_value`=GREATEST(CAST(`option_value` AS SIGNED), VALUES(`option_value`))';

        // Exécute la requête (pas de prepare car on contrôle les paramètres)
        return (int) $wpdb->query($sql);
    }
}
