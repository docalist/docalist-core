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

use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

use function Docalist\deprecated;

/**
 * La classe Response représente le résultat de l'exécution d'une requête http.
 *
 * L'implémentation actuelle est basée sur l'objet Response du composant Symfony HttpFoundation.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class Response extends SymfonyResponse
{
    /**
     * Entêtes http par défaut pour ce type de réponse.
     *
     * @var string[]
     */
    protected $defaultHeaders;

    /**
     * Si la réponse est affichée depuis le back-office, indique s'il faut ou
     * non générer les entêtes et les menus de wordpress.
     */
    protected bool $isAdminPage = false;

    /**
     * @param array<string,mixed> $headers
     */
    public function __construct(?string $content = '', int $status = 200, array $headers = [])
    {
        !empty($this->defaultHeaders) && $headers += $this->defaultHeaders;

        parent::__construct($content, $status, $headers);
    }

    // public function prepare(SymfonyRequest $request = null)
    // {
    //     return parent::prepare($request ?: Request::createFromGlobals());
    // }

    public function adminPage(?bool $adminPage = null): self|bool
    {
        deprecated(static::class . '::adminPage()', 'getIsAdminPage() or setIsAdminPage()', '2023-05-12');

        if (is_null($adminPage)) {
            return $this->getIsAdminPage();
        }

        $this->setIsAdminPage($adminPage);

        return $this;
    }

    public function setIsAdminPage(bool $isAdminPage): void
    {
        $this->isAdminPage = (bool) $isAdminPage;
    }

    public function getIsAdminPage(): bool
    {
        return $this->isAdminPage;
    }
}
