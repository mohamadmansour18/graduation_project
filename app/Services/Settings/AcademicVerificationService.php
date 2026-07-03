<?php

namespace App\Services\Settings;

use App\DTOs\Notifications\NotificationPayload;
use App\Enums\AcademicAssetType;
use App\Enums\Status;
use App\Exceptions\Api\SettingsException;
use App\Helpers\BuildActor;
use App\Repositories\Settings\AcademicVerificationRepository;
use App\Services\Notifications\NotificationCenter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AcademicVerificationService
{
    public function __construct(
        private readonly AcademicVerificationRepository $repository,
        private readonly NotificationCenter $notificationCenter,
    )
    {}

    public function getStatus(int $userId): array
    {
        $latestRequest = $this->repository->getLatestRequestForUser($userId);
        $cancellationCount = $this->repository->getUserCancellationCount($userId);
        $remainingCancellations = max(0, 2 - $cancellationCount);

        if (! $latestRequest) {
            return [
                'has_request' => false,
                'status' => null,
                'submitted_at' => null,
                'approved_at' => null,
                'show_certificate_publicly' => false,
                'cancellation_count' => $cancellationCount,
                'remaining_cancellations' => $remainingCancellations,
            ];
        }

        if ($latestRequest->status === Status::APPROVED) {
            return [
                'has_request' => true,
                'status' => Status::APPROVED->value,
                'submitted_at' => optional($latestRequest->submitted_at)->toDateString(),
                'approved_at' => optional($latestRequest->reviewed_at)->toDateString(),
                'show_certificate_publicly' => (bool) $latestRequest->show_certificate_publicly,
                'cancellation_count' => $cancellationCount,
                'remaining_cancellations' => $remainingCancellations,
            ];
        }

        if ($latestRequest->status === Status::PENDING) {
            return [
                'has_request' => true,
                'status' =>  Status::PENDING->value,
                'submitted_at' => optional($latestRequest->submitted_at)->toDateString(),
                'approved_at' => null,
                'show_certificate_publicly' => false,
                'cancellation_count' => $cancellationCount,
                'remaining_cancellations' => $remainingCancellations,
            ];
        }

        return [
            'has_request' => true,
            'status' => Status::REJECTED->value,
            'submitted_at' => optional($latestRequest->submitted_at)->toDateString(),
            'approved_at' => null,
            'show_certificate_publicly' => false,
            'rejection_reason' => $latestRequest->rejection_reason,
            'cancellation_count' => $cancellationCount,
            'remaining_cancellations' => $remainingCancellations,
        ];
    }

    public function submitRequest(int $userId, UploadedFile $certificateImage, UploadedFile $identityImage): void
    {
        if ($this->repository->getUserCancellationCount($userId) >= 2) {
            throw SettingsException::cancellationLimitReached();
        }

        if ($this->repository->hasPendingRequest($userId)) {
            throw SettingsException::pendingRequestAlreadyExists();
        }

        if ($this->repository->hasApprovedRequest($userId)) {
            throw SettingsException::alreadyVerified();
        }

        $storedPaths = [];
        $notificationPayload = null;

        try {
            DB::transaction(function () use ($userId, $certificateImage, $identityImage , &$storedPaths , &$notificationPayload) {
                $verificationRequest = $this->repository->createRequest($userId);

                $certificatePath = $this->storeEncryptedAcademicAsset(
                    file: $certificateImage,
                    directory: 'academic-verification/certificates'
                );

                $storedPaths[] = $certificatePath;

                $identityPath = $this->storeEncryptedAcademicAsset(
                    file: $identityImage,
                    directory: 'academic-verification/identities'
                );

                $storedPaths[] = $identityPath;

                $this->repository->createAsset(
                    verificationRequestId: $verificationRequest->id,
                    assetType: AcademicAssetType::University_Certificate->value,
                    storagePath: $certificatePath,
                    originalName: $certificateImage->getClientOriginalName(),
                    mimeType: $certificateImage->getMimeType()
                );

                $this->repository->createAsset(
                    verificationRequestId: $verificationRequest->id,
                    assetType: AcademicAssetType::Identity_Card->value,
                    storagePath: $identityPath,
                    originalName: $identityImage->getClientOriginalName(),
                    mimeType: $identityImage->getMimeType()
                );

                $notificationPayload = [
                    'verification_request_id' => (int) $verificationRequest->id,
                    'user_id' => (int) $userId,
                    'status' => 'pending',
                    'certificate_mime_type' => $certificateImage->getMimeType(),
                    'identity_mime_type' => $identityImage->getMimeType(),
                ];

                if ($notificationPayload !== null) {
                    $this->sendAcademicVerificationSubmittedNotification($notificationPayload);
                }

            });
        }catch (\Throwable $exception) {
            foreach ($storedPaths as $path) {
                Storage::disk('local')->delete($path);
            }

            throw $exception;
        }
    }

    public function updateCertificateVisibility(int $userId, bool $showPublicly): void
    {
        $approvedRequest = $this->repository->getApprovedRequestForUser($userId);

        if (! $approvedRequest) {
            throw SettingsException::approvedRequestRequired();
        }

        $this->repository->updateCertificateVisibility(
            verificationRequest: $approvedRequest,
            showPublicly: $showPublicly
        );
    }

    private function storeEncryptedAcademicAsset(UploadedFile $file, string $directory): string
    {
        $encryptedContent = Crypt::encrypt($file->get());

        $fileName = Str::uuid()->toString() . '.enc';

        $path = $directory . '/' . $fileName;

        Storage::disk('local')->put($path, $encryptedContent);

        return $path;
    }

    public function cancelRequest(int $userId): void
    {
        $cancellationCount = $this->repository->getUserCancellationCount($userId);

        if ($cancellationCount >= 2) {
            throw SettingsException::cancellationLimitReached();
        }

        $verificationRequest = $this->repository->getLatestCancellableRequestForUser($userId);

        if (! $verificationRequest) {
            throw SettingsException::cancellableRequestNotFound();
        }

        $assetPaths = $verificationRequest->verificationRequestUserAcademicVerificationAssets
            ->pluck('storage_path')
            ->filter()
            ->values()
            ->all();

        DB::transaction(function () use ($userId, $verificationRequest) {
            $this->repository->deleteRequestAssets($verificationRequest);

            $this->repository->deleteRequest($verificationRequest);

            $this->repository->incrementUserCancellationCount($userId);

            $this->repository->resetUserAcademicVerification($userId);
        });

        foreach ($assetPaths as $path) {
            Storage::disk('local')->delete($path);
        }
    }

    private function sendAcademicVerificationSubmittedNotification(array $data): void
    {
        $reviewerIds = $this->repository->getDashboardVerificationReviewerUserIds();

        if (empty($reviewerIds)) {
            return;
        }

        $payload = NotificationPayload::make(
            title: 'طلب توثيق جديد',
            body: " أرسل طلب توثيق لحسابه الشخصي",
            metadata: [
                'type' => 'academic_verification_submitted',
                'category' => 'verification',

                'presentation' => [
                    'mode' => 'user',
                    'floor_color' => null,
                    'icon' => null,
                ],

                'actor' => BuildActor::buildUserActor((int) $data['user_id']),

                'navigation' => [
                    'screen' => 'user_details',
                    'action' => 'open',
                ],

                'params' => [
                    'verification_request_id' => (int) $data['verification_request_id'],
                    'user_id' => (int) $data['user_id'],
                ],

            ],
        );

        $this->notificationCenter->sendToWeb(
            userIds: $reviewerIds,
            payload: $payload,
        );
    }
}
