<?php declare(strict_types=1);
/**
 * This file is part of Docalist Core.
 *
 * Copyright (C) 2012-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
namespace Docalist\Pipeline;

use Docalist\Pipeline\Pipeline;
use Generator;
use InvalidArgumentException;

/**
 * Implémentation de l'interface Pipeline.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class StandardPipeline implements Pipeline
{
    /**
     * La liste des opérations qui composent le pipeline.
     *
     * @var callable[]
     */
    protected $operations = [];

    /**
     * Crée un nouveau pipeline qui exécute séquentiellement les opérations indiquées.
     *
     * @param callable[] $operations La liste des opérations qui composent le pipeline.
     */
    public function __construct(array $operations = [])
    {
        $this->setOperations($operations);
    }

    public function setOperations(array $operations): Pipeline
    {
        foreach ($operations as $key => $operation) {
            $this->appendOperation($operation, $key);
        }

        return $this;
    }

    public function getOperations(): array
    {
        return $this->operations;
    }

    public function appendOperation(callable $operation, $key = null): Pipeline
    {
        if (is_null($key)) {
            $this->operations[] = $operation;

            return $this;
        }

        if (isset($this->operations[$key])) {
            throw new InvalidArgumentException('An operation with "' . $key . '" already exists');
        }
        $this->operations[$key] = $operation;

        return $this;
    }

    public function prependOperation(callable $operation, $key = null): Pipeline
    {
        $this->operations = array_reverse($this->operations, true);
        try {
            $this->appendOperation($operation, $key);
        } catch (InvalidArgumentException $exception) {
            $this->operations = array_reverse($this->operations, true);
            throw $exception;
        }
        $this->operations = array_reverse($this->operations, true);

        return $this;
    }

    public function hasOperation($key): bool
    {
        return isset($this->operations[$key]);
    }

    public function getOperation($key): callable
    {
        // Génère une exception si l'opération indiquée n'existe pas
        if (! isset($this->operations[$key])) {
            throw new InvalidArgumentException('Operation "' . $key . '" not found');
        }

        // Ok
        return $this->operations[$key];
    }

    public function setOperation($key, callable $operation): callable
    {
        // Génère une exception si l'opération indiquée n'existe pas
        $this->getOperation($key);

        // Modifie l'opération associée à la clé indiquée
        $this->operations[$key] = $operation;

        // Ok
        return $this;
    }

    public function removeOperation($key): Pipeline
    {
        // Génère une exception si l'opération indiquée n'existe pas
        $this->getOperation($key);

        // Supprime l'opération
        unset($this->operations[$key]);

        // Ok
        return $this;
    }

    public function process(Iterable $items): Iterable
    {
        return array_reduce($this->operations, function (Iterable $items, callable $stage) {
            foreach ($items as $key => $item) {
                $result = $stage($item);
                if (! is_null($result)) {
                    ($result instanceof Generator) ? yield from $result : yield $result;
                }
            }
        }, $items);
    }

    public function __invoke($item): Iterable // send / envoie UN item
    {
        return $this->process([$item]);
    }
}
