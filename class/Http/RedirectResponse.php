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

/**
 * Une redirection.
 */
class RedirectResponse extends HtmlResponse
{
    public function __construct($url, $status = 302, $headers = [])
    {
        $headers['Location'] = $url;
        parent::__construct(null, $status, $headers);
    }

    public function setContent($content)
    {
        if (empty($content)) {
            $url = htmlspecialchars($this->headers->get('Location'), ENT_QUOTES, 'UTF-8');

            $content = "<!DOCTYPE html>
                <html>
                    <head>
                        <meta http-equiv='refresh' content='0;url=$url' />
                        <script type='text/javascript'>window.location='$url';</script>
                        <title>This page has moved</title>
                    </head>
                    <body>
                        <h1>$this->statusCode - $this->statusText</h1>
                        <p>This page has moved to <a href='$url'>$url</a>.</p>
                    </body>
                </html>";
        }

        return parent::setContent($content);
    }

    public function adminPage($adminPage = null)
    {
        if (is_null($adminPage)) {
            return $this->adminPage;
        }

        throw new Exception('RedirectResponse::adminPage is read-only');
    }
}
