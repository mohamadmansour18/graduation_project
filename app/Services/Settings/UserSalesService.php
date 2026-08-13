<?php

namespace App\Services\Settings;

use App\Http\Resources\UserSoldTestsResource;
use App\Repositories\Auth\UserSalesRepository;
use Illuminate\Database\Eloquent\Collection;

class UserSalesService
{
    public function __construct(
        private readonly UserSalesRepository $repository,
    )
    {}

    public function getMyPurchasedTests(int $buyerUserId, string $tab): Collection
    {
        return $this->repository->getPurchasedTests(
            buyerUserId: $buyerUserId,
            tab: $tab,
        );
    }

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
