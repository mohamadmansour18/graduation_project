<?php

namespace App\Services\Settings;

use App\Http\Resources\UserSoldTestsResource;
use App\Repositories\Auth\UserSalesRepository;

class UserSalesService
{
    public function __construct(
        private readonly UserSalesRepository $repository,
    )
    {}

    public function getSoldTests(int $userId, string $tab): UserSoldTestsResource
    {
        $purchases = $this->repository->getSoldTestPurchases(
            sellerUserId: $userId,
            tab: $tab
        );

        return new UserSoldTestsResource([
            'tab' => $tab,
            'purchases' => $purchases,
        ]);
    }
}
