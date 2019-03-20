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

/**
 * Tokenizer.
 *
 * Convertit un texte en minuscules non accentuées
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class Tokenizer
{
    /**
     * Table de conversion des caractères pour le tokenizer.
     *
     * @var array
     */
    protected static $tokenizer = [
        // U0000 - Latin de base (http://fr.wikipedia.org/wiki/Table_des_caractères_Unicode/U0000)
        'A' => 'a',    'B' => 'b',    'C' => 'c',    'D' => 'd',    'E' => 'e',    'F' => 'f',
        'G' => 'g',    'H' => 'h',    'I' => 'i',    'J' => 'j',    'K' => 'k',    'L' => 'l',
        'M' => 'm',    'N' => 'n',    'O' => 'o',    'P' => 'p',    'Q' => 'q',    'R' => 'r',
        'S' => 's',    'T' => 't',    'U' => 'u',    'V' => 'v',    'W' => 'w',    'X' => 'x',
        'Y' => 'y',    'Z' => 'z',

        // U0080 - Supplément Latin-1 (http://fr.wikipedia.org/wiki/Table_des_caractères_Unicode/U0080)
        'À' => 'a',    'Á' => 'a',    'Â' => 'a',    'Ã' => 'a',    'Ä' => 'a',    'Å' => 'a',
        'Æ' => 'ae',   'Ç' => 'c',    'È' => 'e',    'É' => 'e',    'Ê' => 'e',    'Ë' => 'e',
        'Ì' => 'i',    'Í' => 'i',    'Î' => 'i',    'Ï' => 'i',    'Ð' => 'd',    'Ñ' => 'n',
        'Ò' => 'o',    'Ó' => 'o',    'Ô' => 'o',    'Õ' => 'o',    'Ö' => 'o',    'Ø' => 'o',
        'Ù' => 'u',
        'Ú' => 'u',    'Û' => 'u',    'Ü' => 'u',    'Ý' => 'y',    'Þ' => 'th',   'ß' => 'ss',
        'à' => 'a',    'á' => 'a',    'â' => 'a',    'ã' => 'a',    'ä' => 'a',    'å' => 'a',
        'æ' => 'ae',   'ç' => 'c',    'è' => 'e',    'é' => 'e',    'ê' => 'e',    'ë' => 'e',
        'ì' => 'i',    'í' => 'i',    'î' => 'i',    'ï' => 'i',    'ð' => 'd',    'ñ' => 'n',
        'ò' => 'o',    'ó' => 'o',    'ô' => 'o',    'õ' => 'o',    'ö' => 'o',    'ø' => 'o',
        'ù' => 'u',    'ú' => 'u',    'û' => 'u',    'ü' => 'u',    'ý' => 'y',    'þ' => 'th',
        'ÿ' => 'y',

        // U0100 - Latin étendu A (http://fr.wikipedia.org/wiki/Table_des_caractères_Unicode/U0100)
        'Ā' => 'a',    'ā' => 'a',    'Ă' => 'a',    'ă' => 'a',    'Ą' => 'a',    'ą' => 'a',
        'Ć' => 'c',    'ć' => 'c',    'Ĉ' => 'c',    'ĉ' => 'c',    'Ċ' => 'c',    'ċ' => 'c',
        'Č' => 'c',    'č' => 'c',    'Ď' => 'd',    'ď' => 'd',    'Đ' => 'd',    'đ' => 'd',
        'Ē' => 'e',    'ē' => 'e',    'Ĕ' => 'e',    'ĕ' => 'e',    'Ė' => 'e',    'ė' => 'e',
        'Ę' => 'e',    'ę' => 'e',    'Ě' => 'e',    'ě' => 'e',    'Ĝ' => 'g',    'ĝ' => 'g',
        'Ğ' => 'g',    'ğ' => 'g',    'Ġ' => 'g',    'ġ' => 'g',    'Ģ' => 'g',    'ģ' => 'g',
        'Ĥ' => 'h',    'ĥ' => 'h',    'Ħ' => 'h',    'ħ' => 'h',    'Ĩ' => 'i',    'ĩ' => 'i',
        'Ī' => 'i',    'ī' => 'i',    'Ĭ' => 'i',    'ĭ' => 'i',    'Į' => 'i',    'į' => 'i',
        'İ' => 'i',    'ı' => 'i',    'Ĳ' => 'ij',   'ĳ' => 'ij',   'Ĵ' => 'j',    'ĵ' => 'j',
        'Ķ' => 'k',    'ķ' => 'k',    'ĸ' => 'k',    'Ĺ' => 'l',    'ĺ' => 'l',    'Ļ' => 'l',
        'ļ' => 'l',    'Ľ' => 'L',    'ľ' => 'l',    'Ŀ' => 'l',    'ŀ' => 'l',    'Ł' => 'l',
        'ł' => 'l',    'Ń' => 'n',    'ń' => 'n',    'Ņ' => 'n',    'ņ' => 'n',    'Ň' => 'n',
        'ň' => 'n',    'ŉ' => 'n',    'Ŋ' => 'n',    'ŋ' => 'n',    'Ō' => 'O',    'ō' => 'o',
        'Ŏ' => 'o',    'ŏ' => 'o',    'Ő' => 'o',    'ő' => 'o',    'Œ' => 'oe',   'œ' => 'oe',
        'Ŕ' => 'r',    'ŕ' => 'r',    'Ŗ' => 'r',    'ŗ' => 'r',    'Ř' => 'r',    'ř' => 'r',
        'Ś' => 's',    'ś' => 's',    'Ŝ' => 's',    'ŝ' => 's',    'Ş' => 's',    'ş' => 's',
        'Š' => 's',    'š' => 's',    'Ţ' => 't',    'ţ' => 't',    'Ť' => 't',    'ť' => 't',
        'Ŧ' => 't',    'ŧ' => 't',    'Ũ' => 'u',    'ũ' => 'u',    'Ū' => 'u',    'ū' => 'u',
        'Ŭ' => 'u',    'ŭ' => 'u',    'Ů' => 'u',    'ů' => 'u',    'Ű' => 'u',    'ű' => 'u',
        'Ų' => 'u',    'ų' => 'u',    'Ŵ' => 'w',    'ŵ' => 'w',    'Ŷ' => 'y',    'ŷ' => 'y',
        'Ÿ' => 'y',    'Ź' => 'Z',    'ź' => 'z',    'Ż' => 'Z',    'ż' => 'z',    'Ž' => 'Z',
        'ž' => 'z',    'ſ' => 's',

        // U0180 - Latin étendu B (http://fr.wikipedia.org/wiki/Table_des_caractères_Unicode/U0180)
        // Voir ce qu'il faut garder : slovène/croate, roumain,
        // 'Ș' => 's',    'ș' => 's',    'Ț' => 't',    'ț' => 't',   // Supplément pour le roumain

        // U20A0 - Symboles monétaires (http://fr.wikipedia.org/wiki/Table_des_caractères_Unicode/U20A0)
        // '€' => 'E',

        // autres symboles monétaires : Livre : 00A3 £, dollar 0024 $, etc.

        // Caractères dont on ne veut pas dans les mots
        // str_word_count inclut dans les mots les caractères a-z, l'apostrophe et le tiret.
        // on ne veut conserver que les caractères a-z. Neutralise les deux autres
        "'" => ' ',    '-' => ' ',
    ];

    /**
     * Table de conversion des caractères pour uppercaseNoAccents().
     *
     * @var array
     */
    protected static $uppercaseNoAccents = [
        // U0000 - Latin de base (http://fr.wikipedia.org/wiki/Table_des_caractères_Unicode/U0000)
        'a' => 'A',    'b' => 'B',    'c' => 'C',    'd' => 'D',    'e' => 'E',    'f' => 'F',
        'g' => 'G',    'h' => 'H',    'i' => 'I',    'j' => 'J',    'k' => 'K',    'l' => 'L',
        'm' => 'M',    'n' => 'N',    'o' => 'O',    'p' => 'P',    'q' => 'Q',    'r' => 'R',
        's' => 'S',    't' => 'T',    'u' => 'U',    'v' => 'V',    'w' => 'W',    'x' => 'X',
        'y' => 'Y',    'z' => 'Z',

        // U0080 - Supplément Latin-1 (http://fr.wikipedia.org/wiki/Table_des_caractères_Unicode/U0080)
        'À' => 'A',    'Á' => 'A',    'Â' => 'A',    'Ã' => 'A',    'Ä' => 'A',    'Å' => 'A',
        'Æ' => 'AE',   'Ç' => 'C',    'È' => 'E',    'É' => 'E',    'Ê' => 'E',    'Ë' => 'E',
        'Ì' => 'I',    'Í' => 'I',    'Î' => 'I',    'Ï' => 'I',    'Ð' => 'D',    'Ñ' => 'N',
        'Ò' => 'O',    'Ó' => 'O',    'Ô' => 'O',    'Õ' => 'O',    'Ö' => 'O',    'Ø' => 'O',
        'Ù' => 'U',
        'Ú' => 'U',    'Û' => 'U',    'Ü' => 'U',    'Ý' => 'Y',    'Þ' => 'TH',   'ß' => 'SS',
        'à' => 'A',    'á' => 'A',    'â' => 'A',    'ã' => 'A',    'ä' => 'A',    'å' => 'A',
        'æ' => 'AE',   'ç' => 'C',    'è' => 'E',    'é' => 'E',    'ê' => 'E',    'ë' => 'E',
        'ì' => 'I',    'í' => 'I',    'î' => 'I',    'ï' => 'I',    'ð' => 'D',    'ñ' => 'N',
        'ò' => 'O',    'ó' => 'O',    'ô' => 'O',    'õ' => 'O',    'ö' => 'O',    'ø' => 'O',
        'ù' => 'U',    'ú' => 'U',    'û' => 'U',    'ü' => 'U',    'ý' => 'Y',    'þ' => 'TH',
        'ÿ' => 'Y',

        // U0100 - Latin étendu A (http://fr.wikipedia.org/wiki/Table_des_caractères_Unicode/U0100)
        'Ā' => 'A',    'ā' => 'A',    'Ă' => 'A',    'ă' => 'A',    'Ą' => 'A',    'ą' => 'A',
        'Ć' => 'C',    'ć' => 'C',    'Ĉ' => 'C',    'ĉ' => 'C',    'Ċ' => 'C',    'ċ' => 'C',
        'Č' => 'C',    'č' => 'C',    'Ď' => 'D',    'ď' => 'D',    'Đ' => 'D',    'đ' => 'D',
        'Ē' => 'E',    'ē' => 'E',    'Ĕ' => 'E',    'ĕ' => 'E',    'Ė' => 'E',    'ė' => 'E',
        'Ę' => 'E',    'ę' => 'E',    'Ě' => 'E',    'ě' => 'E',    'Ĝ' => 'G',    'ĝ' => 'G',
        'Ğ' => 'G',    'ğ' => 'G',    'Ġ' => 'G',    'ġ' => 'G',    'Ģ' => 'G',    'ģ' => 'G',
        'Ĥ' => 'H',    'ĥ' => 'H',    'Ħ' => 'H',    'ħ' => 'H',    'Ĩ' => 'I',    'ĩ' => 'I',
        'Ī' => 'I',    'ī' => 'I',    'Ĭ' => 'I',    'ĭ' => 'I',    'Į' => 'I',    'į' => 'I',
        'İ' => 'J',    'ı' => 'I',    'Ĳ' => 'IJ',   'ĳ' => 'IJ',   'Ĵ' => 'J',    'ĵ' => 'J',
        'Ķ' => 'K',    'ķ' => 'K',    'ĸ' => 'K',    'Ĺ' => 'L',    'ĺ' => 'L',    'Ļ' => 'L',
        'ļ' => 'L',    'Ľ' => 'L',    'ľ' => 'L',    'Ŀ' => 'L',    'ŀ' => 'L',    'Ł' => 'L',
        'ł' => 'L',    'Ń' => 'N',    'ń' => 'N',    'Ņ' => 'N',    'ņ' => 'N',    'Ň' => 'N',
        'ň' => 'N',    'ŉ' => 'N',    'Ŋ' => 'N',    'ŋ' => 'N',    'Ō' => 'O',    'ō' => 'O',
        'Ŏ' => 'O',    'ŏ' => 'O',    'Ő' => 'O',    'ő' => 'O',    'Œ' => 'OE',   'œ' => 'OE',
        'Ŕ' => 'R',    'ŕ' => 'R',    'Ŗ' => 'R',    'ŗ' => 'R',    'Ř' => 'R',    'ř' => 'R',
        'Ś' => 'S',    'ś' => 'S',    'Ŝ' => 'S',    'ŝ' => 'S',    'Ş' => 'S',    'ş' => 'S',
        'Š' => 'S',    'š' => 'S',    'Ţ' => 'T',    'ţ' => 'T',    'Ť' => 'T',    'ť' => 'T',
        'Ŧ' => 'T',    'ŧ' => 'T',    'Ũ' => 'U',    'ũ' => 'U',    'Ū' => 'U',    'ū' => 'U',
        'Ŭ' => 'U',    'ŭ' => 'U',    'Ů' => 'U',    'ů' => 'U',    'Ű' => 'U',    'ű' => 'U',
        'Ų' => 'U',    'ų' => 'U',    'Ŵ' => 'W',    'ŵ' => 'W',    'Ŷ' => 'Y',    'ŷ' => 'Y',
        'Ÿ' => 'Y',    'Ź' => 'Z',    'ź' => 'Z',    'Ż' => 'Z',    'ż' => 'Z',    'Ž' => 'Z',
        'ž' => 'Z',    'ſ' => 'S',

        // U0180 - Latin étendu B (http://fr.wikipedia.org/wiki/Table_des_caractères_Unicode/U0180)
        // Voir ce qu'il faut garder : slovène/croate, roumain,
        'Ș' => 'S',    'ș' => 'S',    'Ț' => 'T',    'ț' => 'T',   // Supplément pour le roumain

        // U20A0 - Symboles monétaires (http://fr.wikipedia.org/wiki/Table_des_caractères_Unicode/U20A0)
        // '€' => 'E',

        // autres symboles monétaires : Livre : 00A3 £, dollar 0024 $, etc.
    ];

    public static function tokenize($text)
    {
        $text = strtr($text, self::$tokenizer);
        $text = str_word_count($text, 1, '0123456789@_');

        return $text;
    }

    /**
     * Convertit le texte en majuscules en remplaçant les majuscules accentuées
     * par la lettre majuscule de base.
     *
     * Remarque : remplace également les ligatures par les lettres
     * correspondantes.
     *
     * @param string $text
     * @return string
     */
    public static function uppercaseNoAccents($text)
    {
        return strtr($text, self::$uppercaseNoAccents);
    }
}
