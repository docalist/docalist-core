<?php
/**
 * This file is part of the 'Docalist Core' plugin.
 *
 * Copyright (C) 2012-2015 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Response
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Http;

/**
 * Une réponse de type "text/html".
 */
class HtmlResponse extends TextResponse
{
    protected $defaultHeaders = [
        'Content-Type' => 'text/html; charset=UTF-8',
    ];
}
