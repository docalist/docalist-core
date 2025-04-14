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

namespace Docalist\Pipeline;

use Generator;
use InvalidArgumentException;

/**
 * Implémentation standard de l'interface Pipeline basée sur des
 * {@link http://php.net/language.generators générateurs}.
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

    public function appendOperation(callable $operation, int|string|null $key = null): void
    {
        if (is_null($key)) {
            $this->operations[] = $operation;

            return;
        }

        if (isset($this->operations[$key])) {
            throw new InvalidArgumentException('An operation with key "'.$key.'" already exists');
        }

        $this->operations[$key] = $operation;
    }

    public function prependOperation(callable $operation, int|string|null $key = null): void
    {
        $this->operations = array_reverse($this->operations, true);
        try {
            $this->appendOperation($operation, $key);
        } catch (InvalidArgumentException $exception) {
            $this->operations = array_reverse($this->operations, true);
            throw $exception;
        }
        $this->operations = array_reverse($this->operations, true);
    }

    public function hasOperation(int|string $key): bool
    {
        return isset($this->operations[$key]);
    }

    public function getOperation(int|string $key): callable
    {
        if (!isset($this->operations[$key])) {
            throw new InvalidArgumentException('Operation "'.$key.'" not found');
        }

        return $this->operations[$key];
    }

    public function getOperations(): array
    {
        return $this->operations;
    }

    public function process(iterable $items): iterable
    {
        return array_reduce($this->operations, function (iterable $items, callable $stage) {
            foreach ($items as $item) {
                $result = $stage($item);
                if (!is_null($result)) {
                    ($result instanceof Generator) ? yield from $result : yield $result;
                }
            }
        }, $items);
    }

    public function __invoke(mixed $item): ?iterable // send / envoie UN item
    {
        return $this->process([$item]);
    }
}
