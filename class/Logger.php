<?php
/**
 * This file is part of the 'Docalist Core' plugin.
 *
 * Copyright (C) 2012-2015 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Core
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 * @version     $Id$
 */

namespace Docalist;

use Psr\Log\LoggerInterface;

use Monolog\Logger as MonologLogger;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Processor\PsrLogMessageProcessor;
use Monolog\Formatter\LineFormatter;

use Exception;

/**
 * Gestionnaire de Logs.
 *
 */
class Logger {

    /**
     * Liste des loggers créés.
     */
    protected $logs = [];

    /**
     * Handler par défaut.
     *
     * @var HandlerInterface
     */
    protected $defaultHandler;

    /**
     * Retourne un logger pour le canal indiqué.
     *
     * @param string $channel
     *
     * @return LoggerInterface
     */
    public function get($channel) {
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
    protected function defaultHandler() {
        if (! isset($this->defaultHandler)) {
            $path = docalist('log-dir') . '/docalist.log';
            $this->defaultHandler = new RotatingFileHandler($path, 10, MonologLogger::DEBUG);

            $this->defaultHandler->pushProcessor(new PsrLogMessageProcessor());

            $format = "[%datetime%][%channel%][%level_name%] %message% %context% %extra%\n";
            $date = 'Y-m-d H:i:s.u';
            $formatter = new LineFormatter($format, $date, false, true);
            $this->defaultHandler->setFormatter($formatter);
        }

        return $this->defaultHandler;
    }
}