<?php

namespace App\Helpers;

class BuildActor
{
    public static function buildUserActor(?int $userId): ?array
    {
        if (! $userId) {
            return null;
        }

        $user = \App\Models\User::query()
            ->with('userProfile:id,user_id,avatar_disk,avatar_path')
            ->select(['id', 'name'])
            ->find($userId);

        if (! $user) {
            return [
                'id' => $userId,
                'name' => null,
                'avatar_url' => null,
            ];
        }

        return [
            'id' => (int) $user->id,
            'name' => $user->name ?? 'مستخدم',
            'avatar_url' => ImageProcessor::urlOrDefault(
                $user->userProfile?->avatar_path,
                'defaults/default-avatar.svg',
                $user->userProfile?->avatar_disk
            ),
        ];
    }
}
