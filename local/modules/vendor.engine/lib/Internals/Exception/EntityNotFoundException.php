<?php

declare(strict_types=1);

namespace Vendor\Engine\Internals\Exception;

class EntityNotFoundException extends EngineException
{
    public static function forId(string $entity, int|string $id): self
    {
        return new self(sprintf('%s with ID "%s" was not found.', $entity, (string) $id));
    }
}
