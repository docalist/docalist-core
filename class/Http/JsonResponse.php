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

    /**
     * Indique si la réponse json sera formattée ("pretty print").
     *
     * @return boolean
     */
    public function getPretty()
    {
        return $this->pretty;
    }

    /**
     * Active ou désactive le formattage "pretty print" de a réponse jsoN.
     *
     * @param bool $pretty
     *
     * @return self
     */
    public function setPretty($pretty = true)
    {
        $this->pretty = $pretty;

        return $this;
    }

    public function setContent($content)
    {
        $options = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
        $this->pretty && $options |= JSON_PRETTY_PRINT;

        $this->content = json_encode($content, $options);
        $this->headers->set('Content-Length', strlen($this->content));

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
