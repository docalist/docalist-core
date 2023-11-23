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

namespace Docalist\Test;

use Brain\Monkey;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

use ReflectionMethod;
use function Brain\Monkey\Functions\stubEscapeFunctions;
use function Brain\Monkey\Functions\stubs;
use function Brain\Monkey\Functions\stubTranslationFunctions;

/**
 * @author Daniel Ménard <daniel.menard.35@gmail.com>
 */
class DocalistTestCase extends TestCase
{
    // Adds Mockery expectations to the PHPUnit assertions count.
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        // Initialise PHPUnit
        parent::setUp();

        // Initialise Brain Monkey
        Monkey\setUp();

        // Charge les stubs des fonctions d'échappement WordPress (esc_attr, esc_js, esc_url, etc.)
        stubEscapeFunctions();

        // Charge les stubs des fonctions de traduction WordPress (__, _x, _n, etc.)
        stubTranslationFunctions();

        // Crée des stubs pour les fonctions wp_styles() et wp_scripts()
        stubs(
            ['wp_styles', 'wp_scripts'],
            function (): object {
                return new class() {
                    public function enqueue(): void
                    {
                    }
                };
            }
        );
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    protected function callNonPublic(object $object, string $method, mixed ...$args): mixed
    {
        $reflectionMethod = new ReflectionMethod(get_class($object), $method);
        $reflectionMethod->setAccessible(true);
        return $reflectionMethod->invokeArgs($object, $args);
    }
}
