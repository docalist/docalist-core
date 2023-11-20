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

namespace Docalist;

use Psr\Log\LoggerInterface;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Processor\PsrLogMessageProcessor;
use Monolog\Formatter\LineFormatter;

/**
 * Gestionnaire de Logs.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class LogManager
{
    /**
     * Handler par défaut.
     */
    protected ?HandlerInterface $defaultHandler;

    /**
     * Retourne un logger pour le canal indiqué.
     *
     * @param string $channel
     *
     * @return LoggerInterface
     */
    public function get($channel)
    {
        // Permet à l'application de définir le logger
        $logger = apply_filters('docalist_setup_logger', null, $channel);

        // Fournit un logger par défaut si l'application n'en a pas créé
        if (is_null($logger)) {
            $logger = new MonologLogger($channel);
            $logger->pushHandler($this->defaultHandler());
        }

        return $logger;
    }

    /**
     * Retourne le handler par défaut.
     *
     * @return HandlerInterface
     */
    protected function defaultHandler()
    {
        if (! isset($this->defaultHandler)) {
            $path = docalist('log-dir') . '/docalist.log';
            $this->defaultHandler = new RotatingFileHandler($path, 10, MonologLogger::ERROR);

            $this->defaultHandler->pushProcessor(new PsrLogMessageProcessor());

            $format = "[%datetime%][%channel%][%level_name%] %message% %context% %extra%\n";
            $date = 'Y-m-d H:i:s.u';
            $formatter = new LineFormatter($format, $date, false, true);
            $this->defaultHandler->setFormatter($formatter);
        }

        return $this->defaultHandler;
    }
}
