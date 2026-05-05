<?php

namespace App\Services\Tests;

use App\Enums\TestType;
use App\Models\Test;
use Illuminate\Database\Eloquent\Builder;

class TestViewerContextService
{
    public function build(Builder|Test $test, int $viewerId): array
    {

        $isOwner = (int) $test->creator_user_id === $viewerId;
        $isFree = is_null($test->price) || (float) $test->price <= 0;
        $hasPurchased = (bool) ($test->viewer_has_purchased ?? false);
        $hasLiked = (bool) ($test->viewer_has_liked_it ?? false);
        $hasBookmarked = (bool) ($test->viewer_has_bookmarked_it ?? false);
        $isFollowingCreator = (bool) ($test->viewer_is_following_creator ?? false);
        $canAccessPaidContent = $isOwner || $isFree || $hasPurchased;

        return [
            'is_owner' => $isOwner,
            'is_free' => $isFree,
            'is_paid' => ! $isFree,
            'has_purchased' => $hasPurchased,
            'has_liked' => $hasLiked,
            'has_bookmarked' => $hasBookmarked,
            'is_following_creator' => $isFollowingCreator,

            'can_purchase' => ! $isOwner && ! $isFree && ! $hasPurchased,
            'can_download' => $canAccessPaidContent,
            'can_report' => ! $isOwner && $canAccessPaidContent,
        ];
    }

    public function buildForOwner(Builder|Test $test): array
    {
        $isFree = is_null($test->price) || (float) $test->price <= 0;
        $isPrivate = $test->test_type === TestType::Private;

        return [
            'is_owner' => true,
            'is_free' => $isFree,
            'is_paid' => ! $isFree,
            'is_private' => $isPrivate,

            'can_purchase' => false,
            'can_download' => true,
            'can_report' => false,
            'can_share' => ! $isPrivate
        ];
    }
}
