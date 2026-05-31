<?php

namespace App\Services\Tests;

use App\Exceptions\Api\TestException;
use App\Repositories\Tests\TestFilterRepository;

class TestFilterService
{
    public function __construct(
        private readonly TestFilterRepository $repository,
    ) {}

    public function filter(array $filters, int $userId)
    {
        $paginator = $this->repository->filter($filters, $userId);

        if ($paginator->isEmpty()) {
            throw TestException::noTestsMatchFilter();
        }

        return $paginator;
    }
}
