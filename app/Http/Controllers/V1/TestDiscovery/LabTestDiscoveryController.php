<?php

namespace App\Http\Controllers\V1\TestDiscovery;

use App\Http\Controllers\Controller;
use App\Http\Requests\TestDiscovery\ListLabTestDiscoveryRequest;
use App\Http\Resources\TestDiscovery\LabFeaturedRecommendedTestResource;
use App\Http\Resources\TestDiscovery\LabRecommendedTestListItemResource;
use App\Services\TestDiscovery\LabRecommendedTestsService;
use App\Trait\ApiResponse;

class LabTestDiscoveryController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly LabRecommendedTestsService $labRecommendedTestsService,
    ) {
    }

    public function index(ListLabTestDiscoveryRequest $request)
    {
        $user = $request->user();

        $result = $this->labRecommendedTestsService->listForUser($user->id, $request->validated('tab'), $request->validated('page'),);

        return $this->dataResponse([
            'current_tab' => $result['current_tab'],
            'featured_top_rated_count' => count($result['featured_top_rated']),
            'featured_top_rated' => LabFeaturedRecommendedTestResource::collection(collect($result['featured_top_rated'])),

            'items_count' => count($result['list']),
            'items' => LabRecommendedTestListItemResource::collection(collect($result['list'])),

            'pagination' => $result['pagination'],
        ]);
    }
}
