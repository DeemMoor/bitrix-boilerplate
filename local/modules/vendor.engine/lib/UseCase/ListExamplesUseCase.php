<?php

declare(strict_types=1);

namespace Vendor\Engine\UseCase;

use Vendor\Engine\DTO\ExampleReadModel;
use Vendor\Engine\Internals\Repository\ExampleRepositoryInterface;

readonly class ListExamplesUseCase
{
    public function __construct(
        private ExampleRepositoryInterface $repository,
    ) {
    }

    /**
     * @return ExampleReadModel[]
     */
    public function execute(): array
    {
        return $this->repository->findAllActive();
    }
}
