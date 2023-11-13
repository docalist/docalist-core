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
 * Une réponse dont le résultat est généré par une vue.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class ViewResponse extends HtmlResponse
{
    /**
     * Nom de la vue à exécuter.
     */
    protected string $view;

    /**
     * @var array<string,mixed> Données à transmettre à la vue.
     */
    protected array $data;

    /**
     * @param string              $view Le nom de la vue à exécuter
     * @param array<string,mixed> $data les données à transmettre à la vue
     * @param int                 $status
     * @param array<string,mixed> $headers
     */
    public function __construct(string $view, array $data = [], int $status = 200, array $headers = [])
    {
        parent::__construct(null, $status, $headers);

        $this->view = $view;
        $this->data = $data;
    }

    public function sendContent(): static
    {
        docalist('views')->display($this->view, $this->data);

        return $this;
    }

    public function getContent(): string|false
    {
        return docalist('views')->render($this->view, $this->data);
    }
}
