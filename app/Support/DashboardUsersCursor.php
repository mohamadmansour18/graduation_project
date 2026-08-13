<?php

namespace App\Support;

use App\Exceptions\Api\DashboardUserException;
use Illuminate\Pagination\Cursor;
use JsonException;
use Throwable;

final class DashboardUsersCursor
{
    private const SORT_CURSOR_PARAMETERS = [
        'created_at' => ['users.created_at', 'users.id'],
        'name' => ['users.name', 'users.id'],
        'governorate' => [
            'dashboard_governorate_is_missing',
            'dashboard_governorate_sort',
            'users.id',
        ],
        'gender' => ['users.id'],
        'account_status' => ['ban_status.is_banned', 'users.id'],
    ];

    public static function encode(?Cursor $cursor, string $type, string $sortBy): ?string
    {
        if (! $cursor) {
            return null;
        }

        try {
            $payload = json_encode([
                'cursor' => $cursor->encode(),
                'type' => $type,
                'sort_by' => $sortBy,
            ], JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw DashboardUserException::invalidUsersCursor();
        }

        return rtrim(strtr(base64_encode($payload), '+/', '-_'), '=')
            . '.'
            . hash_hmac('sha256', $payload, (string) config('app.key'));
    }

    public static function decode(?string $encodedCursor, string $type, string $sortBy): ?Cursor
    {
        if (! $encodedCursor) {
            return null;
        }

        try {
            [$encodedPayload, $signature] = explode('.', $encodedCursor, 2);
            $payload = self::decodePayload($encodedPayload);

            if (
                ! hash_equals(
                    hash_hmac('sha256', $payload, (string) config('app.key')),
                    $signature
                )
            ) {
                throw new \UnexpectedValueException();
            }

            $data = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);

            if (
                ! is_array($data)
                || ! is_string($data['cursor'] ?? null)
                || ($data['type'] ?? null) !== $type
                || ($data['sort_by'] ?? null) !== $sortBy
            ) {
                throw new \UnexpectedValueException();
            }

            $cursor = Cursor::fromEncoded($data['cursor']);

            if (! $cursor || ! self::hasExpectedParameters($cursor, $sortBy)) {
                throw new \UnexpectedValueException();
            }

            return $cursor;
        } catch (Throwable) {
            throw DashboardUserException::invalidUsersCursor();
        }
    }

    private static function decodePayload(string $encodedPayload): string
    {
        $payload = base64_decode(
            strtr($encodedPayload, '-_', '+/') . str_repeat('=', (4 - strlen($encodedPayload) % 4) % 4),
            true,
        );

        if ($payload === false) {
            throw new \UnexpectedValueException();
        }

        return $payload;
    }

    private static function hasExpectedParameters(Cursor $cursor, string $sortBy): bool
    {
        $parameters = $cursor->toArray();
        $expected = [
            ...(self::SORT_CURSOR_PARAMETERS[$sortBy] ?? []),
            '_pointsToNextItems',
        ];

        return count($parameters) === count($expected)
            && count(array_diff(array_keys($parameters), $expected)) === 0
            && is_bool($parameters['_pointsToNextItems'] ?? null);
    }
}
