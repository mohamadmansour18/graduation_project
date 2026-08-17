<?php

namespace App\Support;

use Illuminate\Pagination\Cursor;
use Illuminate\Support\Collection;

class SearchCursorPaginator
{
    private const int MAX_PAGE = 10000;

    public function __construct(
        private readonly Collection $items,
        private readonly int $perPage,
        private readonly int $currentPage,
        private readonly bool $hasMorePages,
        private readonly string $context,
    ) {}

    public static function resolveCurrentPage(?string $encodedCursor, string $expectedContext): int
    {
        if (! is_string($encodedCursor) || $encodedCursor === '') {
            return 1;
        }

        $decoded = json_decode(
            base64_decode(str_replace(['-', '_'], ['+', '/'], $encodedCursor), true) ?: '',
            true
        );

        if (! is_array($decoded)) {
            return 1;
        }

        $context = $decoded['search_context'] ?? null;
        $page = filter_var($decoded['search_page'] ?? null, FILTER_VALIDATE_INT);

        if (
            ! is_string($context)
            || ! hash_equals($expectedContext, $context)
            || $page === false
            || $page < 1
            || $page > self::MAX_PAGE
        ) {
            return 1;
        }

        return $page;
    }

    public function items(): array
    {
        return $this->items->all();
    }

    public function perPage(): int
    {
        return $this->perPage;
    }

    public function nextCursor(): ?Cursor
    {
        if (! $this->hasMorePages) {
            return null;
        }

        return $this->cursorForPage($this->currentPage + 1, true);
    }

    public function previousCursor(): ?Cursor
    {
        if ($this->currentPage <= 1) {
            return null;
        }

        return $this->cursorForPage($this->currentPage - 1, false);
    }

    public function hasMorePages(): bool
    {
        return $this->hasMorePages;
    }

    private function cursorForPage(int $page, bool $pointsToNextItems): Cursor
    {
        return new Cursor([
            'search_page' => $page,
            'search_context' => $this->context,
        ], $pointsToNextItems);
    }
}
