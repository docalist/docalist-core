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

namespace Docalist\Tests\Forms;

use Docalist\Forms\Checkbox;
use Docalist\Forms\CodeEditor;
use Docalist\Forms\Div;
use Docalist\Tests\DocalistTestCase;

/**
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class CodeEditorTest extends DocalistTestCase
{
    public function testGetOptions(): void
    {
        $editor = new CodeEditor();
        $options = $editor->getOptions();
        $this->assertArrayHasKey('codemirror', $options);
    }

    public function testSetOptions(): void
    {
        $editor = new CodeEditor();
        $editor->setOptions(['test' => 'daniel', 'codemirror' => ['test' => 'daniel2']]);
        $options = $editor->getOptions();
        $this->assertArrayHasKey('test', $options);
        $this->assertArrayHasKey('codemirror', $options);

        $codemirrorOptions = $options['codemirror'];
        $this->assertIsArray($codemirrorOptions);

        $this->assertArrayHasKey('test', $codemirrorOptions);
    }

}
