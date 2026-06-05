<?php

declare(strict_types=1);

namespace Vendor\Engine\UseCase;

use Vendor\Engine\DTO\ExampleReadModel;
use Vendor\Engine\Internals\Repository\ExampleRepositoryInterface;

readonly class GetExampleUseCase
{
    public function __construct(
        private ExampleRepositoryInterface $repository,
    ) {
    }

    public function execute(int $id): ?ExampleReadModel
    {
        return $this->repository->findById($id);
    }
}
