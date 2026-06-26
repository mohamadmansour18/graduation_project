<?php

namespace App\Http\Resources;

use App\Helpers\DateProcessor;
use App\Helpers\ImageProcessor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardUserProfileResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        $user = $this->resource['user'];
        $verification = $this->resource['approved_verification'];
        $rating = $this->resource['rating_distribution'];

        return [
            'header' => [
                'cover' => ImageProcessor::urlOrDefault(
                    $user?->userProfile?->cover_path,
                    'defaults/default-cover.svg',
                    $user?->userProfile?->cover_disk,
                ),

                'avatar' => ImageProcessor::urlOrDefault(
                    $user?->userProfile?->avatar_path,
                    'defaults/default-avatar.svg',
                    $user?->userProfile?->avatar_disk,
                ),

                'name' => $user->name,
                'is_academically_verified' => (bool) $user->is_academically_verified,

                'followers_count' => (int) ($user?->userProfileStat?->followers_count ?? 0),
                'following_count' => (int) ($user?->userProfileStat?->following_count ?? 0),
                'published_tests_count' => (int) ($user?->userProfileStat?->published_tests_count ?? 0),

                'academically_verified_at' => DateProcessor::fromTimestamp($user->academically_verified_at) ?? "لم يقم بتأكيد مستواه الأكاديمي",

                'verified_by' => $verification?->reviewerUser ? [
                    'name' => $verification->reviewerUser->name,

                    'avatar' => ImageProcessor::urlOrDefault(
                        $verification->reviewerUser?->userProfile?->avatar_path,
                        'defaults/default-avatar.svg',
                        $verification->reviewerUser?->userProfile?->avatar_disk,
                    ),

                    'role' => $verification->reviewerUser?->role?->name,
                ] : null,
            ],

            'basic_info' => [
                'education_level' => $user?->userOnboardingProfile?->education_level,
                'governorate' => $user?->userProfile?->governorate ?? "غير محدد",
                'gender' => $user->gender,
                'joined_at' => DateProcessor::fromTimestamp($user->created_at),

                'interests' => $user->userInterestSelections->pluck('interest.name')->filter()->values()->toArray(),
            ],

            'general_stats' => [
                'test_likes_count' => (int) ($user?->userProfileStat?->total_test_likes_received ?? 0),
                'test_reviews_count' => (int) ($user?->userProfileStat?->total_test_reviews_received ?? 0),
                'test_bookmark_count' => (int) ($user?->userProfileStat?->total_test_bookmarks_received ?? 0),
            ],

            'reviews' => [
                'total_ratings' => $rating['total_ratings'],
                'average_rating' => round((float) ($user?->userProfileStat?->average_test_rating ?? 0), 1),
                'rating_distribution' => (object) $rating['distribution'],
            ],
        ];
    }
}
