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

use Exception;

class JsonResponse extends Response
{
    protected $defaultHeaders = [
        'Content-Type' => 'application/json; charset=UTF-8',
    ];

    public function setContent($content)
    {
        $options = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
        WP_DEBUG && $options |= JSON_PRETTY_PRINT;
        $this->content = json_encode($content, $options);
    }

    public function adminPage($adminPage = null)
    {
        if (is_null($adminPage)) {
            return $this->adminPage;
        }

        throw new Exception('JsonResponse::adminPage is read-only');
    }
}
