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
 * Une réponse de type "text/plain".
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class TextResponse extends Response
{
    protected $defaultHeaders = [
        'Content-Type' => 'text/plain; charset=UTF-8',
        'X-Content-Type-Options' => 'nosniff',
    ];
}
