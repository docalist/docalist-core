<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 */
namespace Docalist\Http;

/**
 * Une réponse de type "text/html".
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class HtmlResponse extends TextResponse
{
    protected $defaultHeaders = [
        'Content-Type' => 'text/html; charset=UTF-8',
    ];
}
