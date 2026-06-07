<?php

namespace Database\Seeders;

use App\Enums\Asset_type;
use App\Enums\Gender;
use App\Enums\LibraryMaterialContentKind;
use App\Enums\LibraryMaterialReviewStatus;
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
                $timestamps = $this->timestampsForIndex($now, $index + 1);
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

    private function timestampsForIndex(CarbonImmutable $now, int $index): array
    {
        $createdAt = $now
            ->subDays(self::MATERIALS_COUNT - $index)
            ->setTime(8 + ($index % 10), ($index * 7) % 60);

        return [
            'created_at' => $createdAt,
            'updated_at' => $createdAt->addHours(2),
            'published_at' => $createdAt->addHours(1),
        ];
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
