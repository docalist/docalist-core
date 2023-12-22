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

namespace Docalist\Container;

use InvalidArgumentException;

/**
 * Gère l'appel des listeners déclarés via ContainerBuilder::onClassLoad().
 *
 * @phpstan-import-type ClassLoadListener from ContainerBuilderInterface
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
final class AutoloadListener
{
    /**
     * @var callable|null
     */
    private $handler;

    /**
     * @param array<class-string, array<ClassLoadListener>> $classLoadListeners
     */
    public function register(ContainerInterface $container, array $classLoadListeners): void
    {
        if (!is_null($this->handler)) {
            throw new InvalidArgumentException('AutoloadListener is already registered');
        }

        $this->handler = static function (string $className) use ($container, $classLoadListeners): void {
            static $recurse = false;

            if ($recurse || !isset($classLoadListeners[$className])) {
                return;
            }

            error_log("Loading $className");

            $recurse = true;
            spl_autoload_call($className);
            $recurse = false;

            if (class_exists($className, false)) {
                foreach ($classLoadListeners[$className] as $callable) {
                    $callable($container, $className);
                }
            }
        };

        spl_autoload_register($this->handler, true, true);
    }

    public function __destruct()
    {
        if (!is_null($this->handler)) {
            spl_autoload_unregister($this->handler);
        }
    }
}
