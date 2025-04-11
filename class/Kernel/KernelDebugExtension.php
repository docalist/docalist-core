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

namespace Docalist\Kernel;

use Docalist\Container\ContainerBuilderInterface;
use Docalist\Container\ContainerInterface;

/**
 * Extension chargée lorsque le kernel est en mode debug.
 *
 */
final class KernelDebugExtension extends KernelExtension
{
    private string $requestId;
    private int $indent = -1;

    /**
     * {@inheritDoc}
     */
    final public function __construct()
    {
        error_log("===================================================================================================");
        $this->requestId = uniqid();

        $requestMethod = $_SERVER['REQUEST_METHOD'];
        assert(is_string($requestMethod));
        $requestUri = $_SERVER['REQUEST_URI'];
        assert(is_string($requestUri));

        error_log(sprintf(
            '[%s] %s %s',
            $this->requestId,
            $requestMethod,
            $requestUri
        ));
        $this->elapsed('Kernel loaded');
    }

    /**
     * {@inheritDoc}
     */
    public function build(ContainerBuilderInterface $containerBuilder): void
    {
        $containerBuilder

        ->listen($containerBuilder::LISTEN_BEFORE_FACTORY_CALL, function($service, $container, $id) {
            ++$this->indent;

            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
            foreach ($trace as $frame) {
                // @phpstan-ignore-next-line
                if (str_ends_with($frame['file'],'docalist-core/docalist.php') || str_ends_with($frame['file'],'docalist-core/class/Container/Container.php')) {
                    continue;
                }
                break;
            }

            // @phpstan-ignore-next-line
            $file = $frame['file'];
            $root = dirname(__DIR__, 5).'/web/plugins/';
            if (str_starts_with($file, $root)) {
                $file = substr($file, strlen($root));
            }
            // @phpstan-ignore-next-line
            $function = $frame['function'];
            // @phpstan-ignore-next-line
            $line = $frame['line'];

            $log = sprintf(
                '[%s] %s%s(%s) in %s:%s',
                $this->requestId,
                str_repeat('|  ', $this->indent),
                $function,
                $id,
                $file,
                $line
            );
            error_log($log);

        })

        ->listen($containerBuilder::LISTEN_AFTER_FACTORY_CALL, function($service, $container, $id) {
            --$this->indent;
        })
        ;
        $this->elapsed('Kernel built');
    }

    /**
     * {@inheritDoc}
     */
    public function boot(ContainerInterface $container): void
    {
        $this->elapsed('Kernel booted');
    }

    /**
     * {@inheritDoc}
     */
    public function shutdown(): void
    {
        $this->elapsed('Kernel shutdown');
    }

    private function elapsed(string $message): void
    {
        error_log(sprintf('[%s] %s: %.3f ms', $this->requestId, $message, Kernel::getInstance()->getElapsedTime() / 1e+6));
    }

}
