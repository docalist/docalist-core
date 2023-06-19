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
 * Une réponse qui génère un contenu de type JSON.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class JsonResponse extends Response
{
    protected $defaultHeaders = [
        'Content-Type' => 'application/json; charset=UTF-8',
    ];

    /**
     * Indique s'il faut générer du json "pretty print".
     *
     * @var bool
     */
    protected $pretty = false;

    public function __construct(mixed $data = null, $status = 200, array $headers = [], bool $pretty = false)
    {
        parent::__construct('', $status, $headers);

        $this->setPretty($pretty);
        $this->setData($data);
    }

    /**
     * Indique si la réponse json sera formattée ("pretty print").
     */
    public function getPretty(): bool
    {
        return $this->pretty;
    }

    /**
     * Active ou désactive le formattage "pretty print" de a réponse json.
     */
    public function setPretty(bool $pretty = true): static
    {
        $this->pretty = $pretty;

        return $this;
    }

    public function setData(mixed $data = null): static
    {
        $options = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
        $this->pretty && $options |= JSON_PRETTY_PRINT;

        $this->content = json_encode($data, $options);
        $this->headers->set('Content-Length', (string) strlen($this->content));

        return $this;
    }

    public function adminPage($adminPage = null)
    {
        if (is_null($adminPage)) {
            return $this->adminPage;
        }

        throw new Exception('JsonResponse::adminPage is read-only');
    }
}
