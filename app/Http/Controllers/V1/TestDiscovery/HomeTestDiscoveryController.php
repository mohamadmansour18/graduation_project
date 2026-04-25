<?php

namespace App\Http\Controllers\V1\TestDiscovery;

use App\Http\Controllers\Controller;
use App\Http\Requests\TestDiscovery\ListHomeTestDiscoveryRequest;
use App\Http\Resources\TestDiscovery\RecommendedTestResource;
use App\Services\TestDiscovery\HomeRecommendedTestsService;
use App\Trait\ApiResponse;

class HomeTestDiscoveryController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly HomeRecommendedTestsService $homeRecommendedTestsService,
    ) {
    }

    public function index(ListHomeTestDiscoveryRequest $request)
    {
        $user = $request->user();

        $result = $this->homeRecommendedTestsService->listForUser(
            userId: (int) $user->id,
            tab: (string) $request->validated('tab'),
        );

        return $this->dataResponse([
            'current_tab' => $result['current_tab'],
            'items_count' => count($result['tests']),
            'tests' => RecommendedTestResource::collection(collect($result['tests'])),
        ]);
    }
}
