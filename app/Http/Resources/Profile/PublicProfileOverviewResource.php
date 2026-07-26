<?php

namespace App\Http\Resources\Profile;

use App\Helpers\DateProcessor;
use App\Helpers\ImageProcessor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicProfileOverviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        $user = $this->resource['user'];
        $ratingCounts = $this->resource['rating_counts'];

        $stats = $user->userProfileStat;
        $profile = $user->userProfile;
        $onboarding = $user->userOnboardingProfile;
        $userAcademic = $user->latestAcademicVerificationRequest;

        $totalReviews = (int) ($stats?->total_test_reviews_received ?? 0);

        return [
            'header' => [
                'user_id' => $user->id,
                'name' => $user->name,
                'avatar_url' => ImageProcessor::urlOrDefault($profile?->avatar_path , 'defaults/default-avatar.svg' , $profile?->avatar_disk),
                'cover_url' => ImageProcessor::url($profile?->cover_path , $profile?->cover_disk ),
                'followers_count' => (int) ($stats?->followers_count ?? 0),
                'following_count' => (int) ($stats?->following_count ?? 0),
                'published_tests_count' => (int) ($stats?->published_tests_count ?? 0),
                'is_academically_verified' => (bool) $user->is_academically_verified,
                'viewer_is_following' => (bool) $user->viewer_is_following,
            ],

            'basic_info' => [
                'show_certificate_publicly'=> (bool) ($userAcademic?->show_certificate_publicly ?? false),
                'education_level' => $onboarding->education_level,
                'governorate' => $profile?->governorate ?? "لم يتم التحديد",
                'gender' => $user->gender,
                'joined_at' => DateProcessor::fromTimestamp($user->created_at),
                'interests' => $user->userInterestSelections->pluck('interest.name')->filter()->values()->toArray(),
            ],

            'reviews' => [
                'average_rating' => round((float) ($stats?->average_test_rating ?? 0), 1),
                'total_reviews_count' => $totalReviews,
                'rating_distribution' => (object) $this->ratingDistribution($ratingCounts, $totalReviews),
            ],

            'general_statistics' => [
                'test_likes_count' => (int) ($stats?->total_test_likes_received ?? 0),
                'test_comments_count' => (int) ($stats?->total_test_reviews_received ?? 0),
                'test_bookmarks_count' => (int) ($stats?->total_test_bookmarks_received ?? 0),
            ],
        ];
    }

    private function ratingDistribution($ratingCounts, int $totalReviews): array
    {
        $distribution = [];

        for ($rating = 1; $rating <= 5; $rating++) {
            $count = (int) ($ratingCounts[$rating] ?? 0);

            $distribution[(string) $rating] = [
                'count' => $count,
                'percentage' => $totalReviews > 0
                    ? round(($count / $totalReviews) * 100, 1)
                    : 0.0,
            ];
        }

        return $distribution;
    }

}
