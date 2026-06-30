<?php

namespace App\Repositories\Settings;

use App\Enums\Status;
use App\Models\User;
use App\Models\UserAcademicAsset;
use App\Models\UserAcademicVerificationRequest;

class AcademicVerificationRepository
{
    public function getLatestRequestForUser(int $userId): ?UserAcademicVerificationRequest
    {
        return UserAcademicVerificationRequest::query()
            ->where('user_id', $userId)
            ->latest('id')
            ->first();
    }

    public function hasPendingRequest(int $userId): bool
    {
        return UserAcademicVerificationRequest::query()
            ->where('user_id', $userId)
            ->where('status', Status::PENDING->value)
            ->exists();
    }

    public function hasApprovedRequest(int $userId): bool
    {
        return UserAcademicVerificationRequest::query()
            ->where('user_id', $userId)
            ->where('status', Status::APPROVED->value)
            ->exists();
    }

    public function getApprovedRequestForUser(int $userId): ?UserAcademicVerificationRequest
    {
        return UserAcademicVerificationRequest::query()
            ->where('user_id', $userId)
            ->where('status', Status::APPROVED->value)
            ->latest('id')
            ->first();
    }

    public function createRequest(int $userId): UserAcademicVerificationRequest
    {
        return UserAcademicVerificationRequest::query()->create([
            'user_id' => $userId,
            'status' => Status::PENDING->value,
            'submitted_at' => now(),
            'show_certificate_publicly' => false,
        ]);
    }

    public function createAsset(int $verificationRequestId, string $assetType, string $storagePath, string $originalName, string $mimeType): UserAcademicAsset
    {
        return UserAcademicAsset::query()->create([
            'verification_request_id' => $verificationRequestId,
            'asset_type' => $assetType,
            'storage_disk' => 'local',
            'storage_path' => $storagePath,
            'original_name' => $originalName,
            'mime_type' => $mimeType,
        ]);
    }

    public function updateCertificateVisibility(UserAcademicVerificationRequest $verificationRequest, bool $showPublicly): bool
    {
        return $verificationRequest->update([
            'show_certificate_publicly' => $showPublicly,
        ]);
    }

    public function getLatestCancellableRequestForUser(int $userId): ?UserAcademicVerificationRequest
    {
        return UserAcademicVerificationRequest::query()
            ->where('user_id', $userId)
            ->whereIn('status', [
                Status::PENDING->value,
                Status::APPROVED->value,
            ])
            ->with('verificationRequestUserAcademicVerificationAssets')
            ->latest('id')
            ->first();
    }

    public function getUserCancellationCount(int $userId): int
    {
        return (int) User::query()
            ->whereKey($userId)
            ->value('academic_verification_cancel_count');
    }

    public function incrementUserCancellationCount(int $userId): void
    {
        User::query()
            ->whereKey($userId)
            ->increment('academic_verification_cancel_count');
    }

    public function resetUserAcademicVerification(int $userId): void
    {
        User::query()
            ->whereKey($userId)
            ->update([
                'is_academically_verified' => false,
                'academically_verified_at' => null,
            ]);
    }

    public function deleteRequestAssets(UserAcademicVerificationRequest $request): void
    {
        UserAcademicAsset::query()
            ->where('verification_request_id', $request->id)
            ->delete();
    }

    public function deleteRequest(UserAcademicVerificationRequest $request): void
    {
        $request->delete();
    }
}
