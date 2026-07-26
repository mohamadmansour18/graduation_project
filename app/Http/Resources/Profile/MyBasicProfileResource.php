<?php

namespace App\Http\Resources\Profile;

use App\Helpers\ImageProcessor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MyBasicProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $this->resource['user'];
        $interests = $this->resource['interests'];

        return [
            'header' => [
                'avatar_url' => ImageProcessor::urlOrDefault($user['avatar_path'] , 'defaults/default-avatar.svg' , $user['avatar_disk']),
                'cover_url' => ImageProcessor::url($user['cover_path'] , 'defaults/default-cover.svg' , $user['cover_disk']),
                'name' => $user['name'],
                'is_academically_verified' => (bool) ($user['is_academically_verified'] ?? false),
                'followers_count' => (int) ($user['followers_count'] ?? 0),
                'following_count' => (int) ($user['following_count'] ?? 0),
                'published_tests_count' => (int) ($user['published_tests_count'] ?? 0),
            ],

            'personal_information' => [
                'name' => $user['name'],
                'email' => $user['email'],
                'governorate' => $user['governorate'],
                'phone' => $user['phone'] ?? null,
                'birth_date' => $user['birth_date'] ?? null,
                'gender' => $user['gender'],
            ],

            'academic_information' => $this->academicInformation($user),

            'scientific_interests' => $interests,
        ];
    }

    private function academicInformation(array $user): array
    {
        $educationLevel = $user['education_level'] ?? null;

        return match ($educationLevel) {
            'جامعة' => [
                'education_level' => $educationLevel,
                'university_name' => $user['university_name'],
                'department' => $user['department'],
                'university_year' => $user['university_year'],
                'school_stage' => null,
            ],

            'مدرسة' => [
                'education_level' => $educationLevel,
                'university_name' => null,
                'department' => null,
                'university_year' => null,
                'school_stage' => $user['school_stage'],
            ],

            'خريج', 'ماجستير', 'دكتوراه' => [
                'education_level' => $educationLevel,
                'university_name' => $user['university_name'],
                'department' => $user['department'],
                'university_year' => null,
                'school_stage' => $user['school_stage'],
            ],


            default => [
                'education_level' => $educationLevel,
                'education_level_label' => null,
                'university_name' => null,
                'department' => null,
                'school_stage' => null,
            ],
        };
    }
}
