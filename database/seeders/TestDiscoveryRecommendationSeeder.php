<?php

namespace Database\Seeders;

use App\Enums\DifficultyLevel;
use App\Enums\DiscoverySource;
use App\Enums\EducationLevel;
use App\Enums\Gender;
use App\Enums\Language;
use App\Enums\SchoolStage;
use App\Enums\SystemRole;
use App\Enums\TargetLevel;
use App\Enums\TestReviewStatus;
use App\Enums\TestType;
use App\Enums\UniversityDepartment;
use App\Enums\UniversityName;
use App\Models\Interest;
use App\Models\Role;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class TestDiscoveryRecommendationSeeder extends Seeder
{
    private const MOBILE_USERS_COUNT = 800;
    private const TESTS_COUNT = 800;
    private const INSERT_CHUNK_SIZE = 200;
    private const USER_EMAIL_DOMAIN = 'seed.nerd.local';
    private const GENERATED_TEST_TITLE_PREFIX = 'اختبار توصية';

    public function run(): void
    {
//        $this->call([
//            SystemRoleSeeder::class,
//            InterestCategorySeeder::class,
//            InterestSeeder::class,
//        ]);

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
            $this->cleanupGeneratedDataset();

            $now = CarbonImmutable::now();
            $sharedPasswordHash = Hash::make('Password@123');

            $userBlueprints = $this->buildUserBlueprints(
                mobileRoleId: $mobileRoleId,
                interests: $interests->all(),
                now: $now,
                sharedPasswordHash: $sharedPasswordHash,
            );

            $this->insertInChunks('users', array_column($userBlueprints, 'user'));

            $persistedUsers = DB::table('users')
                ->select(['id', 'email'])
                ->where('email', 'like', 'recommendation.user.%@' . self::USER_EMAIL_DOMAIN)
                ->orderBy('id')
                ->get()
                ->keyBy('email');

            if ($persistedUsers->count() !== self::MOBILE_USERS_COUNT) {
                throw new RuntimeException('عدد المستخدمين المولدين لا يطابق العدد المطلوب.');
            }

            $userOnboardingRows = [];
            $userSchoolRows = [];
            $userUniversityRows = [];
            $userInterestRows = [];
            $userProfileStatsSeed = [];
            $resolvedUsers = [];

            foreach ($userBlueprints as $blueprint) {
                $userId = (int) optional($persistedUsers->get($blueprint['user']['email']))->id;

                if ($userId <= 0) {
                    throw new RuntimeException('تعذر ربط user seeded مع السجل المحفوظ في قاعدة البيانات.');
                }

                $resolvedUsers[] = [
                    'id' => $userId,
                    'email' => $blueprint['user']['email'],
                    'name' => $blueprint['user']['name'],
                    'interest_id' => $blueprint['interest_id'],
                    'interest_name' => $blueprint['interest_name'],
                    'education_level' => $blueprint['education_level'],
                    'school_stage' => $blueprint['school_stage'],
                    'university_year' => $blueprint['university_year'],
                    'department' => $blueprint['department'],
                ];

                $userOnboardingRows[] = [
                    'user_id' => $userId,
                    'discovery_source' => $blueprint['discovery_source'],
                    'education_level' => $blueprint['education_level'],
                    'last_completed_step' => 6,
                    'created_at' => $blueprint['timestamps']['created_at'],
                    'updated_at' => $blueprint['timestamps']['updated_at'],
                ];

                $userInterestRows[] = [
                    'user_id' => $userId,
                    'interest_id' => $blueprint['interest_id'],
                    'slot_no' => 1,
                    'created_at' => $blueprint['timestamps']['created_at'],
                    'updated_at' => $blueprint['timestamps']['updated_at'],
                ];

                if ($blueprint['education_level'] === EducationLevel::School->value) {
                    $userSchoolRows[] = [
                        'user_id' => $userId,
                        'school_stage' => $blueprint['school_stage'],
                        'created_at' => $blueprint['timestamps']['created_at'],
                        'updated_at' => $blueprint['timestamps']['updated_at'],
                    ];
                } else {
                    $userUniversityRows[] = [
                        'user_id' => $userId,
                        'university_name' => UniversityName::Damascus_University->value,
                        'university_year' => $blueprint['university_year'],
                        'department' => $blueprint['department'],
                        'created_at' => $blueprint['timestamps']['created_at'],
                        'updated_at' => $blueprint['timestamps']['updated_at'],
                    ];
                }

                $userProfileStatsSeed[$userId] = [
                    'user_id' => $userId,
                    'followers_count' => ($userId * 7) % 120,
                    'following_count' => ($userId * 5) % 140,
                    'published_tests_count' => 0,
                    'library_materials_count' => ($userId * 3) % 9,
                    'folders_count' => ($userId * 2) % 7,
                    'average_test_rating' => 0,
                    'total_test_likes_received' => '0',
                    'total_test_reviews_received' => '0',
                    'total_test_bookmarks_received' => '0',
                    'created_at' => $blueprint['timestamps']['created_at'],
                    'updated_at' => $blueprint['timestamps']['updated_at'],
                ];
            }

            $testsRows = [];
            $testInterestRows = [];
            $creatorStats = [];

            foreach (range(1, self::TESTS_COUNT) as $index) {
                $creator = $resolvedUsers[($index - 1) % count($resolvedUsers)];
                $interest = $interests[(($index * 3) + 5) % $interests->count()];

                $difficulty = $this->pickValueByIndex(DifficultyLevel::cases(), $index + 2);
                $language = $this->pickValueByIndex(Language::cases(), $index + 7);
                $timestamps = $this->timestampsForIndex($now, $index);
                $targetLevel = $this->resolveTargetLevelForUser($creator, $index);

                $likesCount = 10 + (($index * 11) % 280);
                $bookmarksCount = 5 + (($index * 7) % 190);
                $reviewsCount = 2 + (($index * 5) % 90);
                $participantsCount = 35 + (($index * 13) % 650);
                $averageRating = number_format(2.5 + (($index % 25) * 0.1), 2, '.', '');
                $questionCount = 10 + ($index % 26);
                $previewQuestionCount = min(5, max(2, (int) floor($questionCount / 4)));

                $testsRows[] = [
                    'creator_user_id' => $creator['id'],
                    'title' => sprintf('%s %03d - %s', self::GENERATED_TEST_TITLE_PREFIX, $index, $interest->name),
                    'description' => $this->buildArabicTestDescription(
                        interestName: $interest->name,
                        targetLevel: $targetLevel,
                        difficulty: $difficulty,
                    ),
                    'test_type' => TestType::Public->value,
                    'difficulty_level' => $difficulty,
                    'duration_seconds' => 900 + (($index % 9) * 300),
                    'pass_mark_percentage' => 50 + ($index % 31),
                    'language' => $language,
                    'price' => $index % 4 === 0 ? null : number_format(4000 + (($index * 275) % 18000), 2, '.', ''),
                    'target_level' => $targetLevel,
                    'review_status' => TestReviewStatus::Approved->value,
                    'current_approval_version' => 1 + ($index % 3),
                    'published_at' => $timestamps['published_at'],
                    'last_content_updated_at' => $timestamps['updated_at'],
                    'question_count' => $questionCount,
                    'preview_question_count' => $previewQuestionCount,
                    'likes_count' => $likesCount,
                    'bookmarks_count' => $bookmarksCount,
                    'downloads_count' => 3 + (($index * 4) % 120),
                    'reviews_count' => $reviewsCount,
                    'participants_count' => $participantsCount,
                    'average_rating' => $averageRating,
                    'created_at' => $timestamps['created_at'],
                    'updated_at' => $timestamps['updated_at'],
                ];

                $creatorStats[$creator['id']]['published_tests_count'] = ($creatorStats[$creator['id']]['published_tests_count'] ?? 0) + 1;
                $creatorStats[$creator['id']]['likes_sum'] = ($creatorStats[$creator['id']]['likes_sum'] ?? 0) + $likesCount;
                $creatorStats[$creator['id']]['reviews_sum'] = ($creatorStats[$creator['id']]['reviews_sum'] ?? 0) + $reviewsCount;
                $creatorStats[$creator['id']]['bookmarks_sum'] = ($creatorStats[$creator['id']]['bookmarks_sum'] ?? 0) + $bookmarksCount;
                $creatorStats[$creator['id']]['ratings_sum'] = ($creatorStats[$creator['id']]['ratings_sum'] ?? 0) + (float) $averageRating;
            }

            $this->insertInChunks('user_onboarding_profiles', $userOnboardingRows);
            $this->insertInChunks('user_school_profiles', $userSchoolRows);
            $this->insertInChunks('user_university_profiles', $userUniversityRows);
            $this->insertInChunks('user_interest_selections', $userInterestRows);
            $this->insertInChunks('test', $testsRows);

            $persistedTests = DB::table('test')
                ->select(['id', 'title'])
                ->where('title', 'like', self::GENERATED_TEST_TITLE_PREFIX . ' %')
                ->orderBy('id')
                ->get();

            if ($persistedTests->count() !== self::TESTS_COUNT) {
                throw new RuntimeException('عدد الاختبارات المولدة لا يطابق العدد المطلوب.');
            }

            foreach ($persistedTests as $offset => $test) {
                $interest = $interests[(($offset + 1) * 3 + 5) % $interests->count()];
                $timestamps = $this->timestampsForIndex($now, $offset + 1);

                $testInterestRows[] = [
                    'test_id' => (int) $test->id,
                    'interest_id' => (int) $interest->id,
                    'slot_no' => 1,
                    'created_at' => $timestamps['created_at'],
                    'updated_at' => $timestamps['updated_at'],
                ];
            }

            foreach ($userProfileStatsSeed as $userId => &$profileStats) {
                $publishedTestsCount = $creatorStats[$userId]['published_tests_count'] ?? 0;
                $ratingsSum = $creatorStats[$userId]['ratings_sum'] ?? 0;

                $profileStats['published_tests_count'] = $publishedTestsCount;
                $profileStats['average_test_rating'] = $publishedTestsCount > 0
                    ? number_format($ratingsSum / $publishedTestsCount, 2, '.', '')
                    : number_format(0, 2, '.', '');
                $profileStats['total_test_likes_received'] = (string) ($creatorStats[$userId]['likes_sum'] ?? 0);
                $profileStats['total_test_reviews_received'] = (string) ($creatorStats[$userId]['reviews_sum'] ?? 0);
                $profileStats['total_test_bookmarks_received'] = (string) ($creatorStats[$userId]['bookmarks_sum'] ?? 0);
            }
            unset($profileStats);

            $this->insertInChunks('test_interset_selections', $testInterestRows);
            $this->insertInChunks('user_profile_stats', array_values($userProfileStatsSeed));
        });
    }

    private function cleanupGeneratedDataset(): void
    {
        $generatedTestIds = DB::table('test')
            ->where('title', 'like', self::GENERATED_TEST_TITLE_PREFIX . ' %')
            ->pluck('id');

        if ($generatedTestIds->isNotEmpty()) {
            DB::table('test_interset_selections')
                ->whereIn('test_id', $generatedTestIds)
                ->delete();

            DB::table('test')
                ->whereIn('id', $generatedTestIds)
                ->delete();
        }

        $generatedUserIds = DB::table('users')
            ->where('email', 'like', 'recommendation.user.%@' . self::USER_EMAIL_DOMAIN)
            ->pluck('id');

        if ($generatedUserIds->isNotEmpty()) {
            DB::table('users')
                ->whereIn('id', $generatedUserIds)
                ->delete();
        }
    }

    private function buildUserBlueprints(int $mobileRoleId, array $interests, CarbonImmutable $now, string $sharedPasswordHash): array
    {
        $maleFirstNames = ['أحمد', 'محمد', 'عمر', 'يوسف', 'عبد الرحمن', 'يزن', 'حمزة', 'كريم', 'نور', 'محمود'];
        $femaleFirstNames = ['سارة', 'لين', 'آية', 'مريم', 'جود', 'رانيا', 'رهف', 'تالا', 'لجين', 'نور'];
        $familyNames = ['الخطيب', 'النجار', 'الحموي', 'الرفاعي', 'المصري', 'العلي', 'الحسن', 'الديب', 'الزعبي', 'الشيخ'];

        $blueprints = [];

        foreach (range(1, self::MOBILE_USERS_COUNT) as $index) {
            $gender = $index % 2 === 0 ? Gender::Female->value : Gender::Male->value;
            $educationLevel = $this->educationLevelForIndex($index);
            $timestamps = $this->timestampsForIndex($now, $index);
            $interest = $interests[($index * 2 + 3) % count($interests)];
            $department = $this->pickValueByIndex(UniversityDepartment::cases(), $index);

            $blueprints[] = [
                'user' => [
                    'role_id' => $mobileRoleId,
                    'name' => $this->buildArabicUserName(
                        index: $index,
                        gender: $gender,
                        maleFirstNames: $maleFirstNames,
                        femaleFirstNames: $femaleFirstNames,
                        familyNames: $familyNames,
                    ),
                    'email' => sprintf('recommendation.user.%03d@%s', $index, self::USER_EMAIL_DOMAIN),
                    'password' => $sharedPasswordHash,
                    'email_verified_at' => $timestamps['verified_at'],
                    'onboarding_completed_at' => $timestamps['completed_at'],
                    'last_login_at' => $timestamps['last_login_at'],
                    'gender' => $gender,
                    'is_academically_verified' => $educationLevel !== EducationLevel::School->value,
                    'academically_verified_at' => $educationLevel !== EducationLevel::School->value
                        ? $timestamps['completed_at']
                        : null,
                    'created_at' => $timestamps['created_at'],
                    'updated_at' => $timestamps['updated_at'],
                ],
                'timestamps' => [
                    'created_at' => $timestamps['created_at'],
                    'updated_at' => $timestamps['updated_at'],
                ],
                'discovery_source' => $this->pickValueByIndex(DiscoverySource::cases(), $index),
                'education_level' => $educationLevel,
                'school_stage' => $educationLevel === EducationLevel::School->value
                    ? $this->schoolStageForIndex($index)
                    : null,
                'university_year' => $educationLevel === EducationLevel::University->value
                    ? (($index % 6) + 1)
                    : null,
                'department' => $department,
                'interest_id' => (int) $interest->id,
                'interest_name' => (string) $interest->name,
            ];
        }

        return $blueprints;
    }

    private function buildArabicUserName(
        int $index,
        string $gender,
        array $maleFirstNames,
        array $femaleFirstNames,
        array $familyNames
    ): string {
        $firstNamePool = $gender === Gender::Female->value ? $femaleFirstNames : $maleFirstNames;
        $firstName = $firstNamePool[($index - 1) % count($firstNamePool)];
        $familyName = $familyNames[(int) floor(($index - 1) / 3) % count($familyNames)];

        return sprintf('%s %s %d', $firstName, $familyName, $index);
    }

    private function buildArabicTestDescription(string $interestName, string $targetLevel, string $difficulty): string
    {
        return "اختبار تدريبي في {$interestName} موجه لفئة {$targetLevel} بدرجة صعوبة {$difficulty} مع أسئلة متنوعة تساعد على تقييم الفهم بشكل عملي.";
    }

    private function educationLevelForIndex(int $index): string
    {
        return match (true) {
            $index <= 220 => EducationLevel::School->value,
            $index <= 460 => EducationLevel::University->value,
            $index <= 620 => EducationLevel::Graduate->value,
            $index <= 730 => EducationLevel::Master->value,
            default => EducationLevel::PhD->value,
        };
    }

    private function schoolStageForIndex(int $index): string
    {
        $stages = [
            SchoolStage::Elementary->value,
            SchoolStage::Middle_School->value,
            SchoolStage::High_School->value,
        ];

        return $stages[($index - 1) % count($stages)];
    }

    private function resolveTargetLevelForUser(array $creator, int $index): string
    {
        $general = TargetLevel::GENERAL_INFO->value;

        return match ($creator['education_level']) {
            EducationLevel::School->value => $this->schoolTargetLevelForStage(
                stage: $creator['school_stage'],
                index: $index,
                generalFallback: $general,
            ),
            EducationLevel::University->value => $this->universityTargetLevelForYear(
                year: $creator['university_year'],
                index: $index,
                generalFallback: $general,
            ),
            EducationLevel::Graduate->value => $this->pickFromArray([
                TargetLevel::UNIVERSITY_YEAR_3->value,
                TargetLevel::UNIVERSITY_YEAR_4->value,
                TargetLevel::UNIVERSITY_YEAR_5->value,
                TargetLevel::UNIVERSITY_YEAR_6->value,
                TargetLevel::MASTER->value,
                $general,
            ], $index),
            EducationLevel::Master->value => $this->pickFromArray([
                TargetLevel::MASTER->value,
                TargetLevel::UNIVERSITY_YEAR_4->value,
                TargetLevel::UNIVERSITY_YEAR_5->value,
                TargetLevel::UNIVERSITY_YEAR_6->value,
                $general,
            ], $index),
            EducationLevel::PhD->value => $this->pickFromArray([
                TargetLevel::DOCTORATE->value,
                TargetLevel::MASTER->value,
                TargetLevel::UNIVERSITY_YEAR_6->value,
                $general,
            ], $index),
            default => $general,
        };
    }

    private function schoolTargetLevelForStage(?string $stage, int $index, string $generalFallback): string
    {
        $levels = match ($stage) {
            SchoolStage::Elementary->value => [
                TargetLevel::SCHOOL_GRADE_1->value,
                TargetLevel::SCHOOL_GRADE_2->value,
                TargetLevel::SCHOOL_GRADE_3->value,
                TargetLevel::SCHOOL_GRADE_4->value,
                TargetLevel::SCHOOL_GRADE_5->value,
                TargetLevel::SCHOOL_GRADE_6->value,
                $generalFallback,
            ],
            SchoolStage::Middle_School->value => [
                TargetLevel::SCHOOL_GRADE_7->value,
                TargetLevel::SCHOOL_GRADE_8->value,
                TargetLevel::SCHOOL_GRADE_9->value,
                $generalFallback,
            ],
            SchoolStage::High_School->value => [
                TargetLevel::SCHOOL_GRADE_10->value,
                TargetLevel::SCHOOL_GRADE_11->value,
                TargetLevel::BACCALAUREATE->value,
                $generalFallback,
            ],
            default => [$generalFallback],
        };

        return $this->pickFromArray($levels, $index);
    }

    private function universityTargetLevelForYear(?int $year, int $index, string $generalFallback): string
    {
        $levelsByYear = [
            1 => TargetLevel::UNIVERSITY_YEAR_1->value,
            2 => TargetLevel::UNIVERSITY_YEAR_2->value,
            3 => TargetLevel::UNIVERSITY_YEAR_3->value,
            4 => TargetLevel::UNIVERSITY_YEAR_4->value,
            5 => TargetLevel::UNIVERSITY_YEAR_5->value,
            6 => TargetLevel::UNIVERSITY_YEAR_6->value,
        ];

        $currentLevel = $levelsByYear[$year] ?? $generalFallback;
        $previousLevel = $levelsByYear[max(1, (int) $year - 1)] ?? $currentLevel;
        $nextLevel = $levelsByYear[min(6, (int) $year + 1)] ?? $currentLevel;

        return $this->pickFromArray([
            $currentLevel,
            $previousLevel,
            $nextLevel,
            $generalFallback,
        ], $index);
    }

    private function timestampsForIndex(CarbonImmutable $now, int $index): array
    {
        $createdAt = $now
            ->subDays(($index % 320) + 20)
            ->subHours($index % 18);

        $updatedAt = $createdAt->addDays(($index % 12) + 1);

        return [
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
            'verified_at' => $createdAt->addHours(2),
            'completed_at' => $createdAt->addHours(6),
            'last_login_at' => $updatedAt->addHours(3),
            'published_at' => $createdAt->addDays(2),
        ];
    }

    private function pickValueByIndex(array $enumCases, int $index): string
    {
        return $enumCases[($index - 1) % count($enumCases)]->value;
    }

    private function pickFromArray(array $values, int $index): string
    {
        return $values[($index - 1) % count($values)];
    }

    private function insertInChunks(string $table, array $rows): void
    {
        foreach (array_chunk($rows, self::INSERT_CHUNK_SIZE) as $chunk) {
            DB::table($table)->insert($chunk);
        }
    }
}
