<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Http;

/**
 * Une réponse dont le contenu est généré par un callback.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class CallbackResponse extends Response
{
    protected $defaultHeaders = [
        'Content-Type' => 'text/html; charset=UTF-8',
    ];

    protected $callback;

    public function __construct($callback = null, $status = 200, $headers = [])
    {
        parent::__construct(null, $status, $headers);

        $this->callback = $callback;
    }

    public function sendContent()
    {
        call_user_func($this->callback);
    }

    public function getContent()
    {
        $name = null;
        is_callable($this->callback, true, $name);

        return 'Callback: ' . $name;
    }
}
