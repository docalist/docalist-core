<?php declare(strict_types=1);
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Tools\Views;

use Docalist\AdminPage;
use Docalist\Tools\Tool;

/**
 * Liste les outils disponibles.
 */

/* @var AdminPage   $this               */  // La page parent.
/* @var Tool[][]    $toolsByCategory    */  // Une liste d'outils regroupés par catégorie : category => array(Tool)
?>
<div class="wrap">
    <h1><?= $this->menuTitle() ?></h1>

    <ul><?php
    foreach ($toolsByCategory as $category => $tools) { ?>
        <li>
            <h2><?= $category ?></h2>
            <ul class="ul-square"><?php
            foreach ($tools as $id => $tool) { /* @var Tool $tool */ ?>
                <li>
                    <h3>
                        <a href="<?= esc_attr($this->getUrl('run', ['tool' => $id])) ?>">
                            <?= $tool->getLabel() ?>
                        </a>
                    </h3>
                    <p class="description">
                        <?= $tool->getDescription() ?>
                    </p>
                </li><?php
            } ?>
            </ul>
        </li><?php
    } ?>
    </ul>
</div>
