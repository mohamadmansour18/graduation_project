<?php

namespace Database\Seeders;

use App\Enums\Asset_type;
use App\Enums\Gender;
use App\Enums\LibraryDecision;
use App\Enums\LibraryMaterialContentKind;
use App\Enums\LibraryMaterialReviewStatus;
use App\Enums\LibraryReportReason;
use App\Enums\LibraryTriggerType;
use App\Enums\SystemRole;
use App\Enums\TargetLevel;
use App\Enums\VisibilityType;
use App\Models\Interest;
use App\Models\Role;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class LibraryMaterialArabicSeeder extends Seeder
{
    private const int MATERIALS_COUNT = 400;
    private const int MATERIALS_PER_SEEDED_YEAR = 200;
    private const int CURRENT_MATERIAL_YEAR = 2026;
    private const int PREVIOUS_MATERIAL_YEAR = 2025;
    private const int REPORTED_MATERIALS_COUNT = 150;
    private const string USER_EMAIL_DOMAIN = 'library.seed.nerd.local';
    private const string MATERIAL_TITLE_PREFIX = 'مكتبة نيرد';

    public function run(): void
    {
        $mobileRoleId = Role::query()
            ->where('name', SystemRole::Mobile_User->value)
            ->value('id');

        if ($mobileRoleId === null) {
            throw new RuntimeException('تعذر العثور على role الخاصة بـ mobile_user.');
        }

        $interests = Interest::query()
            ->select(['id', 'name'])
            ->orderBy('id')
            ->get()
            ->values();

        if ($interests->isEmpty()) {
            throw new RuntimeException('تعذر العثور على interests، تأكد من تشغيل InterestSeeder أولاً.');
        }

        DB::transaction(function () use ($mobileRoleId, $interests): void {
            $now = CarbonImmutable::now();
            $usersByEmail = $this->ensureSeedUsers($mobileRoleId, $now);

            DB::table('library_material')
                ->where('title', 'like', self::MATERIAL_TITLE_PREFIX . '%')
                ->delete();

            foreach ($this->materialBlueprints($interests) as $index => $material) {
                $creator = $usersByEmail[$material['creator_email']];
                $timestamps = $this->materialTimestampsForIndex($index + 1);
                $assetCount = count($material['assets']);
                $likeUserIds = $this->pickUserIds($usersByEmail, $index + 2, $material['likes_count']);
                $bookmarkUserIds = $this->pickUserIds($usersByEmail, $index + 5, $material['bookmarks_count']);
                $downloadUserIds = $this->pickUserIds($usersByEmail, $index + 8, $material['download_count']);

                $libraryMaterialId = DB::table('library_material')->insertGetId([
                    'creator_user_id' => $creator->id,
                    'title' => $material['title'],
                    'description' => $material['description'],
                    'content_kind' => $material['content_kind']->value,
                    'visibility_type' => VisibilityType::Public->value,
                    'target_level' => $material['target_level']->value,
                    'review_status' => LibraryMaterialReviewStatus::Approved->value,
                    'current_approval_version' => 1,
                    'published_at' => $timestamps['published_at'],
                    'asset_count' => $assetCount,
                    'like_count' => count($likeUserIds),
                    'bookmarks_count' => count($bookmarkUserIds),
                    'download_count' => count($downloadUserIds),
                    'created_at' => $timestamps['created_at'],
                    'updated_at' => $timestamps['updated_at'],
                ]);

                $this->insertAssets($libraryMaterialId, $material['assets'], $timestamps);
                $this->insertInterestSelections($libraryMaterialId, $material['interest_ids'], $timestamps);
                $this->insertUserInteractions('library_material_likes', $libraryMaterialId, $likeUserIds, $timestamps);
                $this->insertUserInteractions('library_material_bookmarks', $libraryMaterialId, $bookmarkUserIds, $timestamps);
                $this->insertUserInteractions('library_material_download_logs', $libraryMaterialId, $downloadUserIds, $timestamps);
            }

            $this->insertMaterialReports($usersByEmail);
            $this->insertMaterialReviewWorkflows();

            $this->rebuildAdminYearlyLibraryMaterialActivityMonthStats([
                self::PREVIOUS_MATERIAL_YEAR,
                self::CURRENT_MATERIAL_YEAR,
            ]);
        });
    }

    private function ensureSeedUsers(int $mobileRoleId, CarbonImmutable $now): array
    {
        $passwordHash = Hash::make('Password@123');
        $users = collect([
            ['name' => 'ليان العبدالله', 'email' => 'library.user.01@' . self::USER_EMAIL_DOMAIN, 'gender' => Gender::Female],
            ['name' => 'أحمد الخطيب', 'email' => 'library.user.02@' . self::USER_EMAIL_DOMAIN, 'gender' => Gender::Male],
            ['name' => 'سارة محمود', 'email' => 'library.user.03@' . self::USER_EMAIL_DOMAIN, 'gender' => Gender::Female],
            ['name' => 'عمر الحسن', 'email' => 'library.user.04@' . self::USER_EMAIL_DOMAIN, 'gender' => Gender::Male],
            ['name' => 'نور الديب', 'email' => 'library.user.05@' . self::USER_EMAIL_DOMAIN, 'gender' => Gender::Female],
            ['name' => 'محمد النجار', 'email' => 'library.user.06@' . self::USER_EMAIL_DOMAIN, 'gender' => Gender::Male],
            ['name' => 'رنا سليمان', 'email' => 'library.user.07@' . self::USER_EMAIL_DOMAIN, 'gender' => Gender::Female],
            ['name' => 'كريم الشامي', 'email' => 'library.user.08@' . self::USER_EMAIL_DOMAIN, 'gender' => Gender::Male],
            ['name' => 'هبة يوسف', 'email' => 'library.user.09@' . self::USER_EMAIL_DOMAIN, 'gender' => Gender::Female],
            ['name' => 'مازن قاسم', 'email' => 'library.user.10@' . self::USER_EMAIL_DOMAIN, 'gender' => Gender::Male],
            ['name' => 'ريم منصور', 'email' => 'library.user.11@' . self::USER_EMAIL_DOMAIN, 'gender' => Gender::Female],
            ['name' => 'يزن فارس', 'email' => 'library.user.12@' . self::USER_EMAIL_DOMAIN, 'gender' => Gender::Male],
        ]);

        $rows = $users->map(fn(array $user): array => [
            'role_id' => $mobileRoleId,
            'name' => $user['name'],
            'email' => $user['email'],
            'password' => $passwordHash,
            'email_verified_at' => $now,
            'onboarding_completed_at' => $now,
            'last_login_at' => $now->subDays(2),
            'gender' => $user['gender']->value,
            'is_academically_verified' => true,
            'academically_verified_at' => $now->subDays(20),
            'created_at' => $now->subMonths(2),
            'updated_at' => $now,
        ])->all();

        DB::table('users')->upsert(
            $rows,
            ['email'],
            [
                'role_id',
                'name',
                'password',
                'email_verified_at',
                'onboarding_completed_at',
                'last_login_at',
                'gender',
                'is_academically_verified',
                'academically_verified_at',
                'updated_at',
            ],
        );

        return DB::table('users')
            ->whereIn('email', $users->pluck('email')->all())
            ->orderBy('id')
            ->get()
            ->keyBy('email')
            ->all();
    }

    private function insertAssets(int $libraryMaterialId, array $assets, array $timestamps): void
    {
        $rows = [];

        foreach ($assets as $position => $asset) {
            $rows[] = [
                'library_material_id' => $libraryMaterialId,
                'asset_type' => $asset['asset_type']->value,
                'storage_disk' => 'public',
                'storage_path' => $asset['storage_path'],
                'original_name' => $asset['original_name'],
                'mime_type' => $asset['mime_type'],
                'position' => $position + 1,
                'created_at' => $timestamps['created_at'],
                'updated_at' => $timestamps['updated_at'],
            ];
        }

        DB::table('library_material_asset')->insert($rows);
    }

    private function insertInterestSelections(int $libraryMaterialId, array $interestIds, array $timestamps): void
    {
        $rows = [];

        foreach ($interestIds as $slot => $interestId) {
            $rows[] = [
                'library_material_id' => $libraryMaterialId,
                'interest_id' => $interestId,
                'slot_no' => $slot + 1,
                'created_at' => $timestamps['created_at'],
                'updated_at' => $timestamps['updated_at'],
            ];
        }

        DB::table('library_material_interest_selections')->insert($rows);
    }

    private function insertUserInteractions(string $table, int $libraryMaterialId, array $userIds, array $timestamps): void
    {
        if ($userIds === []) {
            return;
        }

        $rows = array_map(fn(int $userId): array => [
            'library_material_id' => $libraryMaterialId,
            'user_id' => $userId,
            'created_at' => $timestamps['created_at'],
            'updated_at' => $timestamps['updated_at'],
        ], $userIds);

        DB::table($table)->insert($rows);
    }

    private function pickUserIds(array $usersByEmail, int $startOffset, int $count): array
    {
        $users = array_values($usersByEmail);
        $picked = [];

        for ($step = 0; $step < min($count, count($users)); $step++) {
            $picked[] = (int) $users[($startOffset + $step) % count($users)]->id;
        }

        return array_values(array_unique($picked));
    }

    private function insertMaterialReports(array $usersByEmail): void
    {
        $materials = DB::table('library_material')
            ->select(['id', 'creator_user_id', 'current_approval_version', 'published_at'])
            ->where('title', 'like', self::MATERIAL_TITLE_PREFIX . '%')
            ->inRandomOrder()
            ->limit(self::REPORTED_MATERIALS_COUNT)
            ->get();

        $userIds = array_map(
            static fn(object $user): int => (int) $user->id,
            array_values($usersByEmail),
        );
        $reasons = array_column(LibraryReportReason::cases(), 'value');
        $reportRows = [];
        $counterRows = [];

        foreach ($materials as $material) {
            $availablePairs = [];

            foreach ($userIds as $userId) {
                if ($userId === (int) $material->creator_user_id) {
                    continue;
                }

                foreach ($reasons as $reason) {
                    $availablePairs[] = [
                        'user_id' => $userId,
                        'reason' => $reason,
                    ];
                }
            }

            $selectedPairs = collect($availablePairs)
                ->shuffle()
                ->take(random_int(10, 20))
                ->values();
            $reportedAt = CarbonImmutable::parse($material->published_at);
            $reasonCounters = [];

            foreach ($selectedPairs as $offset => $pair) {
                $reportTimestamp = $reportedAt->addMinutes($offset + 1);

                $reportRows[] = [
                    'library_material_id' => (int) $material->id,
                    'user_id' => $pair['user_id'],
                    'approval_version' => (int) $material->current_approval_version,
                    'reason' => $pair['reason'],
                    'description' => 'بلاغ تجريبي على المحتوى المنشور.',
                    'reported_at' => $reportTimestamp,
                    'created_at' => $reportTimestamp,
                    'updated_at' => $reportTimestamp,
                ];

                $reasonCounters[$pair['reason']] = ($reasonCounters[$pair['reason']] ?? 0) + 1;
            }

            foreach ($reasonCounters as $reason => $reportersCount) {
                $counterRows[] = [
                    'library_material_id' => (int) $material->id,
                    'approval_version' => (int) $material->current_approval_version,
                    'reason' => $reason,
                    'reporters_count' => $reportersCount,
                    'created_at' => $reportedAt,
                    'updated_at' => $reportedAt,
                ];
            }
        }

        foreach (array_chunk($reportRows, 500) as $chunk) {
            DB::table('library_material_reports')->insert($chunk);
        }

        foreach (array_chunk($counterRows, 500) as $chunk) {
            DB::table('library_report_reason_counters')->insert($chunk);
        }
    }

    private function insertMaterialReviewWorkflows(): void
    {
        $adminUserIds = DB::table('users')
            ->join('roles', 'roles.id', '=', 'users.role_id')
            ->whereBetween('users.id', [1, 15])
            ->whereIn('roles.name', [
                SystemRole::Owner->value,
                SystemRole::Supervisor->value,
            ])
            ->orderBy('users.id')
            ->pluck('users.id')
            ->map(static fn(mixed $userId): int => (int) $userId)
            ->all();

        if ($adminUserIds === []) {
            throw new RuntimeException('تعذر العثور على مشرف أو مالك ضمن المستخدمين ذوي المعرفات من 1 إلى 15.');
        }

        $materials = DB::table('library_material')
            ->select([
                'id',
                'creator_user_id',
                'review_status',
                'current_approval_version',
                'created_at',
                'published_at',
            ])
            ->where('title', 'like', self::MATERIAL_TITLE_PREFIX . '%')
            ->orderBy('id')
            ->get();

        $reportedAtByMaterialId = DB::table('library_material_reports')
            ->whereIn('library_material_id', $materials->pluck('id'))
            ->selectRaw('library_material_id, MAX(reported_at) as reported_at')
            ->groupBy('library_material_id')
            ->get()
            ->keyBy('library_material_id');

        $roundRows = [];
        $historyRows = [];

        foreach ($materials as $offset => $material) {
            if (
                $material->review_status !== LibraryMaterialReviewStatus::Approved->value
                || (int) $material->current_approval_version !== 1
            ) {
                continue;
            }

            $materialId = (int) $material->id;
            $creatorUserId = (int) $material->creator_user_id;
            $adminUserId = $adminUserIds[$offset % count($adminUserIds)];
            $createdAt = CarbonImmutable::parse($material->created_at);
            $approvedAt = CarbonImmutable::parse($material->published_at);
            $initialRoundStartedAt = $createdAt->addMinutes(30);

            $roundRows[] = [
                'library_material_id' => $materialId,
                'round_no' => 1,
                'reviewer_user_id' => $adminUserId,
                'trigger_type' => LibraryTriggerType::Initial_Submission->value,
                'decision' => LibraryDecision::Approved->value,
                'based_on_approval_version' => 0,
                'started_at' => $initialRoundStartedAt,
                'decided_at' => $approvedAt,
                'created_at' => $initialRoundStartedAt,
                'updated_at' => $approvedAt,
            ];

            $historyRows[] = [
                'library_material_id' => $materialId,
                'from_status' => null,
                'to_status' => LibraryMaterialReviewStatus::New->value,
                'changed_by_user_id' => $creatorUserId,
                'note' => 'تم إرسال المحتوى العام للمراجعة.',
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ];

            $historyRows[] = [
                'library_material_id' => $materialId,
                'from_status' => LibraryMaterialReviewStatus::New->value,
                'to_status' => LibraryMaterialReviewStatus::Approved->value,
                'changed_by_user_id' => $adminUserId,
                'note' => 'تمت الموافقة على المحتوى من المشرف.',
                'created_at' => $approvedAt,
                'updated_at' => $approvedAt,
            ];

            $reportedMaterial = $reportedAtByMaterialId->get($materialId);

            if ($reportedMaterial === null) {
                continue;
            }

            $reportedAt = CarbonImmutable::parse($reportedMaterial->reported_at);
            $reportDecisionAt = $reportedAt->addHours(2);
            $reportAdminUserId = $adminUserIds[($offset + 1) % count($adminUserIds)];

            $roundRows[] = [
                'library_material_id' => $materialId,
                'round_no' => 2,
                'reviewer_user_id' => $reportAdminUserId,
                'trigger_type' => LibraryTriggerType::Auto_Report->value,
                'decision' => LibraryDecision::Approved->value,
                'based_on_approval_version' => 1,
                'started_at' => $reportedAt,
                'decided_at' => $reportDecisionAt,
                'created_at' => $reportedAt,
                'updated_at' => $reportDecisionAt,
            ];

            $historyRows[] = [
                'library_material_id' => $materialId,
                'from_status' => LibraryMaterialReviewStatus::Approved->value,
                'to_status' => LibraryMaterialReviewStatus::Reported->value,
                'changed_by_user_id' => null,
                'note' => 'تم نقل المحتوى تلقائياً إلى حالة مبلغ عنه.',
                'created_at' => $reportedAt,
                'updated_at' => $reportedAt,
            ];

            $historyRows[] = [
                'library_material_id' => $materialId,
                'from_status' => LibraryMaterialReviewStatus::Reported->value,
                'to_status' => LibraryMaterialReviewStatus::Approved->value,
                'changed_by_user_id' => $reportAdminUserId,
                'note' => 'تمت الموافقة على المحتوى مرة أخرى بعد مراجعة البلاغات.',
                'created_at' => $reportDecisionAt,
                'updated_at' => $reportDecisionAt,
            ];
        }

        foreach (array_chunk($roundRows, 500) as $chunk) {
            DB::table('library_material_review_rounds')->insert($chunk);
        }

        foreach (array_chunk($historyRows, 500) as $chunk) {
            DB::table('library_material_status_histories')->insert($chunk);
        }
    }

    private function materialTimestampsForIndex(int $index): array
    {
        $year = $index <= self::MATERIALS_PER_SEEDED_YEAR
            ? self::CURRENT_MATERIAL_YEAR
            : self::PREVIOUS_MATERIAL_YEAR;

        $yearIndex = (($index - 1) % self::MATERIALS_PER_SEEDED_YEAR) + 1;
        $month = ((($yearIndex * 19) + ((int) floor(($yearIndex - 1) / 2) * 11)) % 12) + 1;
        $daysInMonth = CarbonImmutable::create($year, $month, 1)->daysInMonth;
        $day = (($yearIndex * 11) % ($daysInMonth - 2)) + 1;
        $hour = 8 + (($yearIndex * 5) % 10);
        $minute = ($yearIndex * 13) % 60;

        $createdAt = CarbonImmutable::create($year, $month, $day, $hour, $minute);
        $publishedAt = $createdAt->addHours(1);

        return [
            'created_at' => $createdAt,
            'updated_at' => $publishedAt->addHour(),
            'published_at' => $publishedAt,
        ];
    }

    private function rebuildAdminYearlyLibraryMaterialActivityMonthStats(array $years): void
    {
        $now = CarbonImmutable::now();
        $stats = [];

        foreach ($years as $year) {
            foreach (range(1, 12) as $month) {
                $stats[$year][$month] = [
                    'year' => $year,
                    'month_no' => $month,
                    'published_materials_count' => 0,
                    'likes_count' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        $publishedCounts = DB::table('library_material')
            ->selectRaw('YEAR(published_at) as year, MONTH(published_at) as month_no, COUNT(*) as aggregate_count')
            ->where('title', 'like', self::MATERIAL_TITLE_PREFIX . '%')
            ->whereNotNull('published_at')
            ->whereIn(DB::raw('YEAR(published_at)'), $years)
            ->groupByRaw('YEAR(published_at), MONTH(published_at)')
            ->get();

        foreach ($publishedCounts as $row) {
            $stats[(int) $row->year][(int) $row->month_no]['published_materials_count'] = (int) $row->aggregate_count;
        }

        $likeCounts = DB::table('library_material_likes')
            ->join('library_material', 'library_material.id', '=', 'library_material_likes.library_material_id')
            ->selectRaw('YEAR(library_material_likes.created_at) as year, MONTH(library_material_likes.created_at) as month_no, COUNT(*) as aggregate_count')
            ->where('library_material.title', 'like', self::MATERIAL_TITLE_PREFIX . '%')
            ->whereIn(DB::raw('YEAR(library_material_likes.created_at)'), $years)
            ->groupByRaw('YEAR(library_material_likes.created_at), MONTH(library_material_likes.created_at)')
            ->get();

        foreach ($likeCounts as $row) {
            $stats[(int) $row->year][(int) $row->month_no]['likes_count'] = (int) $row->aggregate_count;
        }

        $rows = [];

        foreach ($stats as $months) {
            foreach ($months as $monthStats) {
                $rows[] = $monthStats;
            }
        }

        DB::table('admin_yearly_library_material_activity_month_stats')->upsert(
            $rows,
            ['year', 'month_no'],
            [
                'published_materials_count',
                'likes_count',
                'updated_at',
            ],
        );
    }

    private function materialBlueprints(Collection $interests): array
    {
        $blueprints = [];

        foreach (range(1, self::MATERIALS_COUNT) as $index) {
            $contentKind = $index % 2 === 0
                ? LibraryMaterialContentKind::ImageGroup
                : LibraryMaterialContentKind::File;
            $primaryInterest = $interests[($index - 1) % $interests->count()];
            $interestIds = $this->pickInterestIds($interests, $index);
            $topic = $this->topicForIndex($index);
            $format = $contentKind === LibraryMaterialContentKind::File ? 'ملف' : 'صور';

            $blueprints[] = [
                'creator_email' => sprintf('library.user.%02d@%s', (($index - 1) % 12) + 1, self::USER_EMAIL_DOMAIN),
                'title' => sprintf('%s %03d - %s %s', self::MATERIAL_TITLE_PREFIX, $index, $topic['title'], $primaryInterest->name),
                'description' => sprintf(
                    '%s حول %s ضمن مجال %s، مكتوب بلغة عربية واضحة ومناسب للمراجعة والتطبيق العملي.',
                    $format,
                    $topic['description'],
                    $primaryInterest->name,
                ),
                'content_kind' => $contentKind,
                'target_level' => $this->targetLevelForIndex($index),
                'interest_ids' => $interestIds,
                'likes_count' => 2 + ($index % 9),
                'bookmarks_count' => 1 + (($index * 2) % 8),
                'download_count' => 1 + (($index * 3) % 9),
                'assets' => $this->assetsForMaterial($index, $contentKind, $primaryInterest->name, $topic['slug']),
            ];
        }

        return $blueprints;
    }

    private function pickInterestIds(Collection $interests, int $index): array
    {
        $wantedCount = min(1 + ($index % 3), $interests->count());
        $interestIds = [];

        for ($step = 0; $step < $wantedCount; $step++) {
            $interestIds[] = (int) $interests[(($index - 1) + ($step * 5)) % $interests->count()]->id;
        }

        return array_values(array_unique($interestIds));
    }

    private function assetsForMaterial(
        int $index,
        LibraryMaterialContentKind $contentKind,
        string $interestName,
        string $topicSlug,
    ): array {
        $safeInterestName = str_replace(' ', '-', $interestName);

        if ($contentKind === LibraryMaterialContentKind::File) {
            $extension = $index % 5 === 0 ? 'docx' : 'pdf';

            return [[
                'asset_type' => Asset_type::File,
                'storage_path' => sprintf('seed/library/files/material-%03d-%s.%s', $index, $topicSlug, $extension),
                'original_name' => sprintf('%03d-%s-%s.%s', $index, $topicSlug, $safeInterestName, $extension),
                'mime_type' => $extension === 'docx'
                    ? 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                    : 'application/pdf',
            ]];
        }

        $extension = $index % 4 === 0 ? 'png' : 'jpg';
        $mimeType = $extension === 'png' ? 'image/png' : 'image/jpeg';
        $assetCount = 1 + ($index % 3);
        $assets = [];

        foreach (range(1, $assetCount) as $position) {
            $assets[] = [
                'asset_type' => Asset_type::Image,
                'storage_path' => sprintf('seed/library/images/material-%03d-%s-%d.%s', $index, $topicSlug, $position, $extension),
                'original_name' => sprintf('%03d-%s-%s-%d.%s', $index, $topicSlug, $safeInterestName, $position, $extension),
                'mime_type' => $mimeType,
            ];
        }

        return $assets;
    }

    private function targetLevelForIndex(int $index): TargetLevel
    {
        $levels = [
            TargetLevel::GENERAL_INFO,
            TargetLevel::BACCALAUREATE,
            TargetLevel::UNIVERSITY_YEAR_1,
            TargetLevel::UNIVERSITY_YEAR_2,
            TargetLevel::UNIVERSITY_YEAR_3,
            TargetLevel::UNIVERSITY_YEAR_4,
            TargetLevel::SCHOOL_GRADE_10,
            TargetLevel::SCHOOL_GRADE_11,
            TargetLevel::MASTER,
        ];

        return $levels[$index % count($levels)];
    }

    private function topicForIndex(int $index): array
    {
        $topics = [
            ['title' => 'ملخص أساسيات', 'description' => 'المفاهيم الأساسية والأفكار المتكررة', 'slug' => 'basics-summary'],
            ['title' => 'دليل مراجعة', 'description' => 'النقاط المهمة قبل الامتحان', 'slug' => 'review-guide'],
            ['title' => 'أمثلة محلولة في', 'description' => 'أمثلة محلولة بخطوات مختصرة', 'slug' => 'solved-examples'],
            ['title' => 'خريطة ذهنية عن', 'description' => 'العلاقات بين المفاهيم والمصطلحات', 'slug' => 'mind-map'],
            ['title' => 'بطاقات تدريبية في', 'description' => 'أسئلة قصيرة وتمارين سريعة', 'slug' => 'practice-cards'],
            ['title' => 'مرجع سريع في', 'description' => 'القوانين والتعريفات والجداول', 'slug' => 'quick-reference'],
            ['title' => 'شرح مبسط في', 'description' => 'شرح تدريجي مناسب للطلاب', 'slug' => 'simple-explanation'],
            ['title' => 'مخططات تعليمية عن', 'description' => 'مخططات وصور منظمة للمراجعة البصرية', 'slug' => 'learning-diagrams'],
            ['title' => 'تمارين تطبيقية في', 'description' => 'تمارين تساعد على تثبيت الفكرة', 'slug' => 'applied-exercises'],
            ['title' => 'ملاحظات مركزة في', 'description' => 'ملاحظات مختصرة ومنظمة', 'slug' => 'focused-notes'],
        ];

        return $topics[($index - 1) % count($topics)];
    }
}
