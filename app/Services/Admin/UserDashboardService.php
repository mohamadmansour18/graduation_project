<?php

namespace App\Services\Admin;

use App\DTOs\Notifications\NotificationPayload;
use App\Enums\BanType;
use App\Exceptions\Api\DashboardUserException;
use App\Exceptions\Api\ProfileException;
use App\Helpers\ImageProcessor;
use App\Jobs\SendSupervisorAccountCreatedMailJob;
use App\Models\User;
use App\Models\UserAcademicVerificationRequest;
use App\Repositories\Admin\UserDashboardRepository;
use App\Services\Notifications\NotificationCenter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Cursor;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Traits\EnumeratesValues;

class UserDashboardService
{
    public function __construct(
        private readonly UserDashboardRepository $repository,
        private readonly NotificationCenter $notificationCenter,
    ) {}

    public function listUsers(
        string $type,
        string $sortBy = 'created_at',
        int $perPage = 20,
        ?Cursor $cursor = null,
    ): \Illuminate\Pagination\CursorPaginator
    {
        return $this->repository->paginateUsersForDashboard(
            type: $type,
            sortBy: $sortBy,
            perPage: min($perPage, 50),
            cursor: $cursor,
        );
    }

    public function searchUsers(string $search, string $role, int $perPage = 20): \Illuminate\Pagination\CursorPaginator
    {
        return $this->repository->searchUsersByName(
            search: trim($search),
            role: $role,
            perPage: min($perPage, 50),
        );
    }

    public function createSupervisor(User $owner, array $data): void
    {
        $supervisor = DB::transaction(function () use ($owner, $data) {
            $supervisorRole = $this->repository->findRoleByName('supervisor');

            if (! $supervisorRole) {
                throw DashboardUserException::supervisorRoleNotFound();
            }

            $supervisor = $this->repository->createUser([
                'role_id' => $supervisorRole->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'gender' => $data['gender'],

                'email_verified_at' => now(),
                'onboarding_completed_at' => null,
            ]);

            $this->repository->createUserProfile($supervisor, [
                'phone' => $data['phone'],
                'governorate' => $data['governorate'],
            ]);

            Log::channel('audit')->info('Supervisor account created', [
                'action' => 'dashboard.supervisors.create',
                'owner_id' => $owner->id,
                'supervisor_id' => $supervisor->id,
            ]);

            return $supervisor;
        });

        SendSupervisorAccountCreatedMailJob::dispatch($supervisor, $owner)->afterCommit();
    }

    public function listBannedUsers(string $tab = 'all'): array|Collection|\LaravelIdea\Helper\App\Models\_IH_UserBan_C
    {
        return $this->repository->getActiveBannedUsers(
            tab: $tab,
        );
    }

    public function listAcademicVerificationRequests(string $sortBy = 'submitted_at'): Collection
    {
        return $this->repository->getPendingAcademicVerificationRequests(
            sortBy: $sortBy,
        );
    }

    public function getAcademicVerificationAssetContent(int $verificationRequestId, string $documentType): array
    {
        $asset = $this->repository->findAcademicVerificationAsset(
            verificationRequestId: $verificationRequestId,
            assetType: $documentType,
        );

        if (! $asset) {
            throw DashboardUserException::academicVerificationAssetNotFound();
        }

        if (! Storage::disk($asset->storage_disk)->exists($asset->storage_path)) {
            throw DashboardUserException::academicVerificationAssetFileNotFound();
        }

        $encryptedContent = Storage::disk($asset->storage_disk)->get($asset->storage_path);

        $decryptedContent = Crypt::decrypt($encryptedContent);

        return [
            'content' => $decryptedContent,
            'mime_type' => $asset->mime_type ?? 'application/octet-stream',
            'file_name' => $asset->original_name ?? $asset->asset_type,
        ];
    }

    public function showUserProfile(int $userId): array
    {

        return [
            'user' => $this->repository->getUserProfileDetails($userId),
            'approved_verification' => $this->repository
                ->getApprovedAcademicVerificationForUser($userId),
            'rating_distribution' => $this->repository
                ->getUserTestsRatingDistribution($userId),
        ];
    }

    public function showUserTests(int $userId): array
    {
        $year = now()->year;

        return [
            'tests' => $this->repository->getUserPublicTests(
                userId: $userId,
            ),

            'stats' => $this->repository->getUserPublicTestsStatsForYear(
                userId: $userId,
                year: $year,
            ),
        ];
    }

    public function showUserLibraryMaterials(int $userId): array
    {
        $year = now()->year;

        return [
            'materials' => $this->repository->getUserPublicLibraryMaterials(
                userId: $userId,
            ),

            'stats' => $this->repository->getUserPublicLibraryMaterialStatsForYear(
                userId: $userId,
                year: $year,
            ),
        ];
    }

    public function showUserFolders(int $userId): array
    {
        $year = now()->year;

        $folders = $this->repository->getUserPublicFolders(
            userId: $userId,
        );

        $this->repository->attachScientificInterestsToFolders($folders);

        return [
            'folders' => $folders,
            'stats' => $this->repository->getUserPublicFoldersStatsForYear(
                userId: $userId,
                year: $year,
            ),
        ];
    }

    public function banUser(User $owner, int $targetUserId, array $data): void
    {
        $notificationPayload = null;

        DB::transaction(function () use ($owner, $targetUserId, $data, &$notificationPayload) {
            $hasActiveBan = $this->repository->hasActiveBanForUserWithLock(
                userId: $targetUserId,
            );

            if ($hasActiveBan) {
                throw DashboardUserException::userAlreadyBanned();
            }

            $isPermanent = (int) $data['is_permanent'];

            if ($isPermanent) {
                $startsAt = now();
                $endsAt = null;
                $banType = BanType::Permanent->value;
            } else {
                $startsAt = Carbon::parse($data['starts_at'])->startOfDay();
                $endsAt = Carbon::parse($data['ends_at'])->endOfDay();

                $days = $startsAt->diffInDays($endsAt);

                if ($days < 1 || $days > 30) {
                    throw DashboardUserException::invalidTemporaryBanDuration();
                }

                $banType = BanType::Temporary->value;
            }

            $ban = $this->repository->createUserBan([
                'user_id' => $targetUserId,
                'imposed_by_user_id' => $owner->id,
                'ban_type' => $banType,
                'reason' => $data['reason'],
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ]);

            $notificationPayload = [
                'ban_id' => (int) $ban->id,
                'target_user_id' => (int) $targetUserId,
                'imposed_by_user_id' => (int) $owner->id,
                'ban_type' => $banType,
                'reason' => $data['reason'],
                'starts_at' => $startsAt?->toDateTimeString(),
                'ends_at' => $endsAt?->toDateTimeString(),
            ];


            Log::channel('audit')->info('User banned from dashboard', [
                'action' => 'dashboard.users.ban',
                'owner_id' => $owner->id,
                'target_user_id' => $targetUserId,
                'ban_type' => $banType,
            ]);
        });

        if ($notificationPayload !== null) {
            $this->sendUserBannedNotification($notificationPayload);
        }
    }

    public function getUserBanHistory(int $userId): \Illuminate\Support\Collection|EnumeratesValues
    {
        return $this->repository->getUserBanHistory(
            userId: $userId,
        );
    }

    public function showSupervisorProfile(int $userId): User
    {
        $user= $this->repository->getSupervisorProfile(
            userId: $userId,
        );

        if (! $user) {
            throw DashboardUserException::userNotFound();
        }

        return $user;
    }

    public function deleteSupervisor(int $ownerId, int $supervisorId): void
    {

        DB::transaction(function () use ($ownerId, $supervisorId) {
            $supervisor = $this->repository->getSupervisorForDeleteWithLock(
                supervisorId: $supervisorId,
            );

            if (! $supervisor) {
                throw DashboardUserException::supervisorNotFound();
            }

            $this->repository->softDeleteUser($supervisor);

            Log::channel('audit')->info('Supervisor soft deleted', [
                'action' => 'dashboard.supervisors.delete',
                'owner_id' => $ownerId,
                'supervisor_id' => $supervisor->id,
            ]);
        });
    }

    public function updateMyDashboardProfile(int $adminId, int $viewerId , array $data): void
    {
        if($adminId !== $viewerId)
        {
            throw ProfileException::cannotViewOwnProfile();
        }

        DB::transaction(function () use ($adminId, $data) {
            $userData = [];
            $profileData = [];

            if (array_key_exists('name', $data)) {
                $userData['name'] = $data['name'];
            }

            if (array_key_exists('gender', $data)) {
                $userData['gender'] = $data['gender'];
            }

            if (array_key_exists('governorate', $data)) {
                $profileData['governorate'] = $data['governorate'];
            }

            if (array_key_exists('phone', $data)) {
                $profileData['phone'] = $data['phone'];
            }

            if ($userData !== []) {
                $this->repository->updateUser(
                    adminId: $adminId,
                    data: $userData,
                );
            }

            if ($profileData !== []) {
                $this->repository->updateOrCreateUserProfile(
                    adminId: $adminId,
                    data: $profileData,
                );
            }
        });
    }

    public function updateMyDashboardPassword(User $user, string $oldPassword, string $newPassword): void
    {
        if (! Hash::check($oldPassword, $user->password)) {
            throw DashboardUserException::invalidOldPassword();
        }

        if (Hash::check($newPassword, $user->password)) {
            throw DashboardUserException::newPasswordMustBeDifferent();
        }

        $this->repository->updateUserPassword(
            user: $user,
            hashedPassword: Hash::make($newPassword),
        );

        Log::channel('audit')->info('Dashboard password updated', [
            'action' => 'dashboard.profile.password.update',
            'admin_id' => $user->id,
        ]);
    }

    public function liftUserBan(int $targetUserId, int $adminUserId): void
    {
        $notificationPayload = null;

        DB::transaction(function () use ($targetUserId, $adminUserId, &$notificationPayload) {
            $ban = $this->repository
                ->getLatestLiftableBanForUserWithLock($targetUserId);

            if (! $ban) {
                throw DashboardUserException::userHasNoActiveBan();
            }

            $this->repository->liftBan(
                ban: $ban,
                liftedByUserId: $adminUserId
            );

            $notificationPayload = [
                'ban_id' => (int) $ban->id,
                'target_user_id' => (int) $targetUserId,
                'lifted_by_user_id' => (int) $adminUserId,
                'ban_type' => $ban->ban_type,
                'ban_reason' => $ban->reason,
                'starts_at' => $ban->starts_at?->toDateTimeString(),
                'ends_at' => $ban->ends_at?->toDateTimeString(),
                'lifted_at' => now()->toDateTimeString(),
            ];

            Log::channel('audit')->info('User ban lifted', [
                'target_user_id' => $targetUserId,
                'lifted_by_user_id' => $adminUserId,
                'ban_id' => $ban->id,
            ]);
        });

        if ($notificationPayload !== null) {
            $this->sendUserBanLiftedNotification($notificationPayload);
        }
    }

    private function sendUserBannedNotification(array $data): void
    {
        $isPermanent = $data['ban_type'] === BanType::Permanent->value;

        $title = $isPermanent
            ? 'تم حظر حسابك بشكل دائم'
            : 'تم حظر حسابك مؤقتًا';

        $body = $isPermanent
            ? "تم حظر حسابك بشكل دائم. السبب: {$data['reason']}"
            : "تم حظر حسابك مؤقتًا حتى تاريخ {$data['ends_at']}. السبب: {$data['reason']}";

        $payload = NotificationPayload::make(
            title: $title,
            body: $body,
            metadata: [
                'type' => 'user_banned',
                'category' => 'moderation',

                'presentation' => [
                    'mode' => 'system',
                    'floor_color' => '#FFE7E7',
                    'icon' => ImageProcessor::urlOrDefault('system-notification/ban.svg' , 'defaults/notification.svg' , 'public'),
                ],

                'actor' => null,


                'navigation' => [
                    'screen' => 'my_profile',
                    'action' => 'open',

                ],

                'params' => [
                    'ban_id' => (int) $data['ban_id'],
                    'user_id' => (int) $data['target_user_id'],
                    'ban_type' => $data['ban_type'],
                    'is_permanent' => $isPermanent,
                ],

            ],
        );

        $this->notificationCenter->sendToUser(
            userId: (int) $data['target_user_id'],
            payload: $payload,
        );
    }

    private function sendUserBanLiftedNotification(array $data): void
    {
        $payload = NotificationPayload::make(
            title: 'تم فك الحظر عن حسابك',
            body: 'تم فك الحظر عن حسابك، ويمكنك الآن استخدام المنصة من جديد.',
            metadata: [
                'type' => 'user_ban_lifted',
                'category' => 'moderation',

                'presentation' => [
                    'mode' => 'system',
                    'floor_color' => '#E4FFE5',
                    'icon' => ImageProcessor::urlOrDefault('system-notification/unban.svg' , 'defaults/notification.svg' , 'public'),
                ],

                'actor' => null,

                'navigation' => [
                    'screen' => 'my_profile',
                    'action' => 'open',
                ],

                'params' => [
                    'ban_id' => (int) $data['ban_id'],
                    'user_id' => (int) $data['target_user_id'],
                ],

            ],
        );

        $this->notificationCenter->sendToUser(
            userId: (int) $data['target_user_id'],
            payload: $payload,
        );
    }
}
