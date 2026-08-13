<?php

namespace App\Http\Resources\Settings;

use App\Http\Resources\Profile\PublicProfileTestResource;
use Illuminate\Http\Request;

class MyPurchasedTestResource extends PublicProfileTestResource
{
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'purchased_at' => $this->purchased_at,
        ];
    }
}
