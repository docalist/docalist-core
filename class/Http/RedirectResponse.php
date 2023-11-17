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

namespace Docalist\Http;

use Exception;

/**
 * Une réponse qui génère une redirection.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class RedirectResponse extends HtmlResponse
{
    public function __construct(string $url, int $status = 302, array $headers = [])
    {
        $headers['Location'] = $url;
        parent::__construct(null, $status, $headers);
    }

    public function setContent(?string $content): static
    {
        if ($content === null || $content === '') {
            $url = htmlspecialchars((string) $this->headers->get('Location'), ENT_QUOTES, 'UTF-8');

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
}
