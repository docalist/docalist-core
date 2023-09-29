<?php
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2023 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Docalist\Forms;

/**
 * Un formulaire.
 *
 * Référence W3C :
 * {@link http://www.w3.org/TR/html5/forms.html#the-form-element The form element}.
 *
 * @author Daniel Ménard <daniel.menard.35@gmail.com>
 */
final class Form extends Container
{
    /**
     * Crée un nouveau formulaire.
     *
     * @param string $action L'action du formulaire
     * @param string $method la méthode du formulaire : "get" ou "post", post par défaut
     */
    public function __construct(string $action = '', string $method = 'post')
    {
        parent::__construct('', ['action' => $action, 'method' => $method]);
    }
}
