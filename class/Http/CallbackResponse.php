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

    /**
     * @var callable
     */
    protected $callback;

    public function __construct(callable $callback, int $status = 200, array $headers = [])
    {
        parent::__construct(null, $status, $headers);

        $this->callback = $callback;
    }

    public function sendContent(): static
    {
        call_user_func($this->callback);

        return $this;
    }

    public function getContent(): string|false
    {
        $name = null;
        is_callable($this->callback, true, $name);

        return 'Callback: ' . $name;
    }
}
