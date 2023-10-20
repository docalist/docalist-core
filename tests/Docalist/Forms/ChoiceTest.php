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

use Docalist\Forms\Choice;
use Docalist\Forms\Theme;
use Docalist\Tests\DocalistTestCase;
use InvalidArgumentException;

/**
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class ChoiceTest extends DocalistTestCase
{
    /**
     * Crée un élément.
     */
    protected function getChoice(string $name = ''): Choice
    {
        return new class($name) extends Choice {
            protected function displayOption(Theme $theme, string $value, string $label, bool $selected, bool $invalid): void
            {
            }

            protected function startOptionGroup(string $label, Theme $theme): void
            {
            }

            protected function endOptionGroup(Theme $theme): void
            {
            }
        };
    }

    public function testGetSetOptions(): void
    {
        $choice = $this->getChoice();

        $this->assertSame([], $choice->getOptions());

        $options = ['a' => 'A', 'B', 'Group' => ['C', 'D']];
        $choice->setOptions($options);
        $this->assertSame($options, $choice->getOptions());

        $options = fn() => ['a' => 'A', 'B', 'Group' => ['C', 'D']];
        $choice->setOptions($options);
        $this->assertSame($options, $choice->getOptions());

        $options = 'table:lookup';
        $choice->setOptions($options);
        $this->assertSame($options, $choice->getOptions());
    }

    public function testBadOptions(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('invalid options');

        // @phpstan-ignore-next-line
        $this->getChoice()->setOptions([$this]);
    }

    public function testBadLookup(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('invalid lookup');

        $this->getChoice()->setOptions('bad:table:lookup');
    }
}
