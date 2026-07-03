<?php

namespace App\Services\Profile;

use App\DTOs\Notifications\NotificationPayload;
use App\Exceptions\Api\FollowException;
use App\Helpers\BuildActor;
use App\Repositories\Profile\FollowRepository;
use App\Services\Cache\CacheKeys;
use App\Services\Notifications\NotificationCenter;
use Illuminate\Support\Facades\DB;

class FollowService
{
    public function __construct(
        private readonly FollowRepository $followRepository,
        private readonly NotificationCenter $notificationCenter,
    ) {}

    public function follow(int $followerUserId, int $followedUserId): void
    {
        if ($followerUserId === $followedUserId) {
            throw FollowException::cannotFollowYourself();
        }

        $notificationPayload = null;

        DB::transaction(function () use ($followerUserId, $followedUserId, &$notificationPayload) {

            if (! $this->followRepository->userExists($followedUserId)) {
                throw FollowException::userNotFound();
            }

            $this->followRepository->ensureProfileStatsRow($followerUserId);
            $this->followRepository->ensureProfileStatsRow($followedUserId);

            $created = $this->followRepository->createFollowIfMissing(
                followerUserId: $followerUserId,
                followedUserId: $followedUserId
            );

            if (! $created) {
                throw FollowException::alreadyFollowing();
            }

            $this->followRepository->incrementFollowingCount($followerUserId);
            $this->followRepository->incrementFollowersCount($followedUserId);
        });

        CacheKeys::clearMyBasicProfileInfo($followerUserId);
        CacheKeys::clearMyBasicProfileInfo($followedUserId);

        if ($notificationPayload !== null) {
            $this->sendUserFollowedNotification($notificationPayload);
        }
    }

    public function unfollow(int $followerUserId, int $followedUserId): void
    {
        if ($followerUserId === $followedUserId) {
            throw FollowException::cannotUnfollowYourself();
        }

        DB::transaction(function () use ($followerUserId, $followedUserId) {
            if (! $this->followRepository->userExists($followedUserId)) {
                throw FollowException::userNotFound();
            }

            $this->followRepository->ensureProfileStatsRow($followerUserId);
            $this->followRepository->ensureProfileStatsRow($followedUserId);

            $deleted = $this->followRepository->deleteFollowIfExists(
                followerUserId: $followerUserId,
                followedUserId: $followedUserId
            );

            if (! $deleted) {
                throw FollowException::notFollowing();
            }

            $this->followRepository->decrementFollowingCount($followerUserId);
            $this->followRepository->decrementFollowersCount($followedUserId);
        });

        CacheKeys::clearMyBasicProfileInfo($followerUserId);
        CacheKeys::clearMyBasicProfileInfo($followedUserId);
    }


    private function sendUserFollowedNotification(array $data): void
    {
        $payload = NotificationPayload::make(
            title: 'عملية متابعة لحسابك',
            body: 'بدأ بمتابعتك',
            metadata: [
                'type' => 'user_followed_you',
                'category' => 'social',

                'presentation' => [
                    'mode' => 'user',
                    'floor_color' => null,
                    'icon' => null,
                ],

                'actor' => BuildActor::buildUserActor((int) $data['follower_user_id']),

                'navigation' => [
                    'screen' => 'user_profile',
                    'action' => 'open',
                ],

                'params' => [
                    'user_id' => (int) $data['follower_user_id'],
                ],
            ],
        );

        $this->notificationCenter->sendToUser(
            userId: (int) $data['followed_user_id'],
            payload: $payload,
        );
    }
}
