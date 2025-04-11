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

namespace Docalist\Tools\Views;

use Docalist\Tools\ToolsPage;
use Docalist\Tools\Tool;

/**
 * Affiche l'exécution d'un outil.
 */

/** @var ToolsPage              $this La page parent */
/** @var Tool                   $tool L'outil à exécuter */
/** @var array<string,string>   $args Paramètres */
?>
<div class="wrap">
    <h1><?= $tool->getLabel() ?></h1><?php
    $tool->run($args); ?>
</div>
