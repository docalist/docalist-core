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

use Docalist\Forms\Tag;
use Docalist\Forms\Themes;
use Docalist\Tests\DocalistTestCase;
use InvalidArgumentException;

/**
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class TagTest extends DocalistTestCase
{
    public function testGetSetTag(): void
    {
        $tag = new Tag('p', 'hello');
        $this->assertSame('p', $tag->getTag());
        $this->assertSame('hello', $tag->getContent());
        $tag->setTag('br');
        $this->assertSame('br', $tag->getTag());

        $tag->setTag('input');
        $this->assertSame('input', $tag->getTag());
        $this->assertSame([], $tag->getAttributes());

        $tag->setTag('input[firstname]');
        $this->assertSame('input', $tag->getTag());
        $this->assertSame('firstname', $tag->getAttribute('name'));

        $tag->setTag('input[firstname]#given');
        $this->assertSame('input', $tag->getTag());
        $this->assertSame('firstname', $tag->getAttribute('name'));
        $this->assertSame('given', $tag->getAttribute('id'));

        $tag->setTag('input[firstname]#given.required');
        $this->assertSame('input', $tag->getTag());
        $this->assertSame('firstname', $tag->getAttribute('name'));
        $this->assertSame('given', $tag->getAttribute('id'));
        $this->assertSame('required', $tag->getAttribute('class'));

        $tag->setTag('input[firstname]#given.required.error');
        $this->assertSame('input', $tag->getTag());
        $this->assertSame('firstname', $tag->getAttribute('name'));
        $this->assertSame('given', $tag->getAttribute('id'));
        $this->assertSame('required error', $tag->getAttribute('class'));
    }

    public function testSetContent(): void
    {
        $tag = new Tag('h1', 'titre');
        $this->assertSame('titre', $tag->getContent());

        $tag = new Tag('h1', '');
        $this->assertSame('', $tag->getContent());

        $tag = new Tag('h1');
        $this->assertSame('', $tag->getContent());
    }

    public function testDisplay(): void
    {
        $theme = Themes::get('base');
        $theme->setDialect('xhtml');
        $theme->setIndent(false);

        $tag = new Tag('p');
        $this->assertSame('<p></p>', $tag->render($theme));

        $tag = new Tag('hr');
        $this->assertSame('<hr/>', $tag->render($theme));

        $tag = new Tag('input[firstname]');
        $this->assertSame('<input name="firstname"/>', $tag->render($theme));

        $tag = new Tag('input[firstname]#given');
        $this->assertSame('<input name="firstname" id="given"/>', $tag->render($theme));

        $tag = new Tag('input[firstname]#given.required');
        $this->assertSame('<input name="firstname" id="given" class="required"/>', $tag->render($theme));

        $tag = new Tag('input[firstname]#given.required.error');
        $this->assertSame('<input name="firstname" id="given" class="required error"/>', $tag->render($theme));
    }

    /**
     * Vérifie qu'une exception est générée avec un nom de tag incorrect.
     */
    public function testInvalidTagName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Incorrect tag');

        new Tag('hello world');
    }
}
