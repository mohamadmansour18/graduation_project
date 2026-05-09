<?php

namespace App\Services\Profile;

use App\Exceptions\Api\FollowException;
use App\Repositories\Profile\FollowRepository;
use Illuminate\Support\Facades\DB;

class FollowService
{
    public function __construct(
        private readonly FollowRepository $followRepository
    ) {}

    public function follow(int $followerUserId, int $followedUserId): void
    {
        if ($followerUserId === $followedUserId) {
            throw FollowException::cannotFollowYourself();
        }

        DB::transaction(function () use ($followerUserId, $followedUserId) {

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
    }
}
