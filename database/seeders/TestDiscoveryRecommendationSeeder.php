<?php

namespace Database\Seeders;

use App\Enums\DifficultyLevel;
use App\Enums\DiscoverySource;
use App\Enums\Decision;
use App\Enums\EducationLevel;
use App\Enums\Gender;
use App\Enums\Governorate;
use App\Enums\Language;
use App\Enums\PaymentStatus;
use App\Enums\RevisionType;
use App\Enums\SchoolStage;
use App\Enums\SystemRole;
use App\Enums\TestReviewRoundsTriggerType;
use App\Enums\TargetLevel;
use App\Enums\TestReviewStatus;
use App\Enums\TestType;
use App\Enums\UniversityDepartment;
use App\Enums\UniversityName;
use App\Enums\Vote;
use App\Models\Interest;
use App\Models\Role;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class TestDiscoveryRecommendationSeeder extends Seeder
{
    private const int MOBILE_USERS_COUNT = 800;
    private const int TESTS_COUNT = 800;
    private const int TESTS_PER_SEEDED_YEAR = 400;
    private const int MANAGEMENT_BOARD_WORKFLOW_TESTS_COUNT = 400;
    private const int CURRENT_TEST_YEAR = 2026;
    private const int PREVIOUS_TEST_YEAR = 2025;
    private const int INSERT_CHUNK_SIZE = 200;
    private const string USER_EMAIL_DOMAIN = 'seed.nerd.local';
    private const string GENERATED_TEST_TITLE_PREFIX = 'اختبار توصية';

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
            $userProfileRows = [];
            $userProfileStatsSeed = [];
            $resolvedUsers = [];

            foreach ($userBlueprints as $blueprint) {
                $userId = (int) optional($persistedUsers->get($blueprint['user']['email']))->id;

                if ($userId <= 0) {
                    throw new RuntimeException('تعذر ربط المستخدم المولد مع السجل المحفوظ في قاعدة البيانات.');
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

                $userProfileRows[] = [
                    'user_id' => $userId,
                    'phone' => null,
                    'birth_date' => null,
                    'avatar_disk' => null,
                    'avatar_path' => null,
                    'cover_disk' => null,
                    'cover_path' => null,
                    'profile_slug' => null,
                    'governorate' => $this->pickValueByIndex(Governorate::cases(), $userId),
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
                    'followers_count' => 0,
                    'following_count' => 0,
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

            [$userFollowRows, $userFollowStats] = $this->buildUserFollowRows(
                users: $resolvedUsers,
                now: $now,
            );

            foreach ($userProfileStatsSeed as $userId => &$profileStats) {
                $profileStats['followers_count'] = $userFollowStats[$userId]['followers_count'] ?? 0;
                $profileStats['following_count'] = $userFollowStats[$userId]['following_count'] ?? 0;
            }
            unset($profileStats);

            $testsRows = [];
            $testInterestRows = [];
            $creatorStats = [];

            foreach (range(1, self::TESTS_COUNT) as $index) {
                $creator = $resolvedUsers[($index - 1) % count($resolvedUsers)];
                $interest = $interests[(($index * 3) + 5) % $interests->count()];

                $difficulty = $this->pickValueByIndex(DifficultyLevel::cases(), $index + 2);
                $language = $this->pickValueByIndex(Language::cases(), $index + 7);
                $timestamps = $this->testTimestampsForIndex($index);
                $targetLevel = $this->resolveTargetLevelForUser($creator, $index);

                $likesCount = $this->engagementCountForIndex($index, 11);
                $bookmarksCount = $this->engagementCountForIndex($index, 17);
                $downloadsCount = 3 + (($index * 4) % 120);
                $participantsCount = 35 + (($index * 13) % 650);
                $questionCount = $this->questionCountForIndex($index);
                $previewQuestionCount = $this->previewQuestionCountFor($questionCount, $index);

                $managementBoardScenario = $this->managementBoardScenarioForIndex($index, $timestamps['created_at']);
                $reviewStatus = $managementBoardScenario['review_status'] ?? TestReviewStatus::Approved->value;
                $currentApprovalVersion = $managementBoardScenario['current_approval_version'] ?? (1 + ($index % 3));
                $publishedAt = $managementBoardScenario !== null && array_key_exists('published_at', $managementBoardScenario)
                    ? $managementBoardScenario['published_at']
                    : $timestamps['published_at'];
                $lastContentUpdatedAt = $managementBoardScenario !== null && array_key_exists('last_content_updated_at', $managementBoardScenario)
                    ? $managementBoardScenario['last_content_updated_at']
                    : $timestamps['updated_at'];
                $deletedAt = $managementBoardScenario['deleted_at'] ?? null;

                if ($managementBoardScenario !== null && $reviewStatus !== TestReviewStatus::Approved->value) {
                    $likesCount = 0;
                    $bookmarksCount = 0;
                    $downloadsCount = 0;
                    $participantsCount = 0;
                }

                $price = $index % 4 === 0 ? null : number_format(4000 + (($index * 275) % 18000), 2, '.', '');

                if (($managementBoardScenario['force_free'] ?? false) === true) {
                    $price = null;
                }

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
                    'price' => $price,
                    'target_level' => $targetLevel,
                    'review_status' => $reviewStatus,
                    'current_approval_version' => $currentApprovalVersion,
                    'published_at' => $publishedAt,
                    'last_content_updated_at' => $lastContentUpdatedAt,
                    'question_count' => $questionCount,
                    'preview_question_count' => $previewQuestionCount,
                    'likes_count' => $likesCount,
                    'bookmarks_count' => $bookmarksCount,
                    'downloads_count' => $downloadsCount,
                    'reviews_count' => 0,
                    'participants_count' => $participantsCount,
                    'average_rating' => number_format(0, 2, '.', ''),
                    'deleted_at' => $deletedAt,
                    'created_at' => $timestamps['created_at'],
                    'updated_at' => $timestamps['updated_at'],
                ];

                if ($reviewStatus === TestReviewStatus::Approved->value) {
                    $creatorStats[$creator['id']]['published_tests_count'] = ($creatorStats[$creator['id']]['published_tests_count'] ?? 0) + 1;
                    $creatorStats[$creator['id']]['likes_sum'] = ($creatorStats[$creator['id']]['likes_sum'] ?? 0) + $likesCount;
                    $creatorStats[$creator['id']]['bookmarks_sum'] = ($creatorStats[$creator['id']]['bookmarks_sum'] ?? 0) + $bookmarksCount;
                }
            }

            $this->insertInChunks('user_onboarding_profiles', $userOnboardingRows);
            $this->insertInChunks('user_school_profiles', $userSchoolRows);
            $this->insertInChunks('user_university_profiles', $userUniversityRows);
            $this->insertInChunks('user_interest_selections', $userInterestRows);
            $this->insertInChunks('user_profile', $userProfileRows);
            $this->insertInChunks('user_follows', $userFollowRows);
            $this->insertInChunks('test', $testsRows);

            $resolvedUserIds = array_column($resolvedUsers, 'id');

            $persistedTests = DB::table('test')
                ->select(['id', 'title', 'creator_user_id', 'price', 'published_at'])
                ->whereIn('creator_user_id', $resolvedUserIds)
                ->orderBy('id')
                ->get();

            if ($persistedTests->count() !== self::TESTS_COUNT) {
                throw new RuntimeException('عدد الاختبارات المولدة لا يطابق العدد المطلوب.');
            }

            $this->seedTestQuestionsAndOptions(
                tests: $persistedTests->all(),
                testBlueprints: $testsRows,
                now: $now,
            );

            $this->seedTestManagementBoardWorkflow(
                tests: $persistedTests->all(),
                testBlueprints: $testsRows,
            );

            $this->seedTestEngagements(
                tests: $persistedTests->all(),
                testBlueprints: $testsRows,
                users: $resolvedUsers,
                now: $now,
            );

            foreach ($persistedTests as $offset => $test) {
                $interest = $interests[(($offset + 1) * 3 + 5) % $interests->count()];
                $timestamps = $this->testTimestampsForIndex($offset + 1);

                $testInterestRows[] = [
                    'test_id' => (int) $test->id,
                    'interest_id' => (int) $interest->id,
                    'slot_no' => 1,
                    'created_at' => $timestamps['created_at'],
                    'updated_at' => $timestamps['updated_at'],
                ];
            }

            [$reviewRows, $feedbackRows, $testReviewAggregates] = $this->buildReviewDataset(
                tests: $persistedTests->all(),
                testBlueprints: $testsRows,
                users: $resolvedUsers,
                now: $now,
            );

            foreach ($userProfileStatsSeed as $userId => &$profileStats) {
                $publishedTestsCount = $creatorStats[$userId]['published_tests_count'] ?? 0;
                $ratingsSum = $creatorStats[$userId]['ratings_sum'] ?? 0;
                $reviewsSum = $creatorStats[$userId]['reviews_sum'] ?? 0;

                $profileStats['published_tests_count'] = $publishedTestsCount;
                $profileStats['average_test_rating'] = $publishedTestsCount > 0
                    ? number_format($ratingsSum / $publishedTestsCount, 2, '.', '')
                    : number_format(0, 2, '.', '');
                $profileStats['total_test_likes_received'] = (string) ($creatorStats[$userId]['likes_sum'] ?? 0);
                $profileStats['total_test_reviews_received'] = (string) $reviewsSum;
                $profileStats['total_test_bookmarks_received'] = (string) ($creatorStats[$userId]['bookmarks_sum'] ?? 0);
            }
            unset($profileStats);

            $this->insertInChunks('test_interset_selections', $testInterestRows);
            $this->insertInChunks('test_reviews', $reviewRows);
            $this->insertInChunks('test_review_feedbacks', $feedbackRows);

            foreach ($testReviewAggregates as $aggregate) {
                DB::table('test')
                    ->where('id', $aggregate['test_id'])
                    ->update([
                        'reviews_count' => $aggregate['reviews_count'],
                        'average_rating' => $aggregate['average_rating'],
                    ]);

                $creatorStats[$aggregate['creator_user_id']]['reviews_sum'] =
                    ($creatorStats[$aggregate['creator_user_id']]['reviews_sum'] ?? 0) + $aggregate['reviews_count'];

                $creatorStats[$aggregate['creator_user_id']]['ratings_sum'] =
                    ($creatorStats[$aggregate['creator_user_id']]['ratings_sum'] ?? 0) + (float) $aggregate['average_rating'];
            }

            foreach ($userProfileStatsSeed as $userId => &$profileStats) {
                $publishedTestsCount = $creatorStats[$userId]['published_tests_count'] ?? 0;
                $ratingsSum = $creatorStats[$userId]['ratings_sum'] ?? 0;

                $profileStats['average_test_rating'] = $publishedTestsCount > 0
                    ? number_format($ratingsSum / $publishedTestsCount, 2, '.', '')
                    : number_format(0, 2, '.', '');
                $profileStats['total_test_reviews_received'] = (string) ($creatorStats[$userId]['reviews_sum'] ?? 0);
            }
            unset($profileStats);

            $this->seedTestPurchasesAndFinancialStats(
                tests: $persistedTests->all(),
                users: $resolvedUsers,
            );

            $this->insertInChunks('user_profile_stats', array_values($userProfileStatsSeed));
            $this->rebuildGeneratedUserStats($resolvedUserIds);
            $this->rebuildAdminYearlyTestActivityMonthStats([
                self::PREVIOUS_TEST_YEAR,
                self::CURRENT_TEST_YEAR,
            ]);
        });
    }

    private function cleanupGeneratedDataset(): void
    {
        $generatedUserIds = DB::table('users')
            ->where('email', 'like', 'recommendation.user.%@' . self::USER_EMAIL_DOMAIN)
            ->pluck('id');

        $generatedTestIds = DB::table('test')
            ->where(function ($query) use ($generatedUserIds) {
                $query->whereIn('creator_user_id', $generatedUserIds)
                    ->orWhere('title', 'like', self::GENERATED_TEST_TITLE_PREFIX . ' %');
            })
            ->pluck('id');

        if ($generatedTestIds->isNotEmpty()) {
            DB::table('test_interset_selections')
                ->whereIn('test_id', $generatedTestIds)
                ->delete();

            DB::table('test')
                ->whereIn('id', $generatedTestIds)
                ->delete();
        }

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
                    'is_academically_verified' => false,
                    'academically_verified_at' => null,
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

    private function buildUserFollowRows(array $users, CarbonImmutable $now): array
    {
        $followRows = [];
        $followStats = [];

        foreach ($users as $user) {
            $userId = (int) $user['id'];
            $followStats[$userId] = [
                'followers_count' => 0,
                'following_count' => 0,
            ];
        }

        foreach ($users as $offset => $follower) {
            $followerUserId = (int) $follower['id'];
            $followCount = 20 + (($offset * 11) % 21);
            $followedUsers = $this->pickDistinctUsers(
                users: $users,
                count: $followCount,
                excludedIds: [$followerUserId],
                startIndex: (($offset + 1) * 19)
            );

            foreach ($followedUsers as $followOffset => $followedUser) {
                $followedUserId = (int) $followedUser['id'];
                $followedAt = $now
                    ->subDays((($offset + 1) * 3 + $followOffset) % 120)
                    ->subMinutes($followOffset + 1);

                $followRows[] = [
                    'follower_user_id' => $followerUserId,
                    'followed_user_id' => $followedUserId,
                    'created_at' => $followedAt,
                    'updated_at' => $followedAt,
                ];

                $followStats[$followerUserId]['following_count']++;
                $followStats[$followedUserId]['followers_count']++;
            }
        }

        return [$followRows, $followStats];
    }

    private function buildArabicTestDescription(string $interestName, string $targetLevel, string $difficulty): string
    {
        return "اختبار تدريبي في {$interestName} موجه لفئة {$targetLevel} بدرجة صعوبة {$difficulty} مع أسئلة متنوعة تساعد على تقييم الفهم بشكل عملي.";
    }


    private function managementBoardScenarioForIndex(int $index, CarbonImmutable $createdAt): ?array
    {
        $workflowSequence = $this->managementBoardWorkflowSequenceForIndex($index);

        if ($workflowSequence === null) {
            return null;
        }

        $timeline = $this->managementBoardTimelineForTestCreatedAt($createdAt);
        $scenario = ($workflowSequence - 1) % 8;

        return match ($scenario) {
            0 => [
                'key' => 'new',
                'review_status' => TestReviewStatus::New->value,
                'current_approval_version' => 0,
                'published_at' => null,
                'last_content_updated_at' => $timeline['initial_status'],
                'deleted_at' => null,
            ],
            1 => [
                'key' => 'approved',
                'review_status' => TestReviewStatus::Approved->value,
                'current_approval_version' => 1,
                'published_at' => $timeline['approved'],
                'last_content_updated_at' => $timeline['approved'],
                'deleted_at' => null,
            ],
            2 => [
                'key' => 'deleted_from_new',
                'review_status' => TestReviewStatus::Deleted->value,
                'current_approval_version' => 0,
                'published_at' => null,
                'last_content_updated_at' => $timeline['deleted'],
                'deleted_at' => $timeline['deleted'],
            ],
            3 => [
                'key' => 'reported_after_approval',
                'review_status' => TestReviewStatus::Reported->value,
                'current_approval_version' => 1,
                'published_at' => $timeline['approved'],
                'last_content_updated_at' => $timeline['reported'],
                'deleted_at' => null,
                'force_free' => true,
            ],
            4 => [
                'key' => 'needs_revision_waiting',
                'review_status' => TestReviewStatus::NeedsRevision->value,
                'current_approval_version' => 0,
                'published_at' => null,
                'last_content_updated_at' => $timeline['needs_revision'],
                'deleted_at' => null,
            ],
            5 => [
                'key' => 'under_review_after_revision',
                'review_status' => TestReviewStatus::UnderReview->value,
                'current_approval_version' => 0,
                'published_at' => null,
                'last_content_updated_at' => $timeline['under_review'],
                'deleted_at' => null,
            ],
            6 => [
                'key' => 'approved_after_revision',
                'review_status' => TestReviewStatus::Approved->value,
                'current_approval_version' => 1,
                'published_at' => $timeline['approved_after_revision'],
                'last_content_updated_at' => $timeline['approved_after_revision'],
                'deleted_at' => null,
            ],
            7 => [
                'key' => 'deleted_after_revision',
                'review_status' => TestReviewStatus::Deleted->value,
                'current_approval_version' => 0,
                'published_at' => null,
                'last_content_updated_at' => $timeline['deleted_after_revision'],
                'deleted_at' => $timeline['deleted_after_revision'],
            ],
        };
    }

    private function managementBoardWorkflowSequenceForIndex(int $index): ?int
    {
        if ($index % 2 === 0) {
            return null;
        }

        $workflowSequence = intdiv($index - 1, 2) + 1;

        return $workflowSequence <= self::MANAGEMENT_BOARD_WORKFLOW_TESTS_COUNT
            ? $workflowSequence
            : null;
    }

    private function managementBoardTimelineForTestCreatedAt(CarbonImmutable $createdAt): array
    {
        $plannedPublishedAt = $createdAt->addDays(2);

        return [
            'round_started' => $createdAt->addMinutes(30),
            'initial_status' => $createdAt->addHour(),
            'needs_revision' => $createdAt->addHours(8),
            'approved' => $plannedPublishedAt,
            'deleted' => $createdAt->addHours(10),
            'under_review' => $createdAt->addDay(),
            'reported' => $plannedPublishedAt->addHours(6),
            'approved_after_revision' => $plannedPublishedAt,
            'deleted_after_revision' => $plannedPublishedAt->addHours(6),
        ];
    }

    private function seedTestManagementBoardWorkflow(array $tests, array $testBlueprints): void
    {
        foreach ($tests as $offset => $test) {
            $index = $offset + 1;
            $testBlueprint = $testBlueprints[$offset] ?? null;

            if ($testBlueprint === null) {
                throw new RuntimeException('تعذر مطابقة بيانات لوحة إدارة الاختبارات مع الاختبار المحفوظ.');
            }

            if (($testBlueprint['test_type'] ?? null) !== TestType::Public->value) {
                continue;
            }

            $scenario = $this->managementBoardScenarioForIndex($index, $testBlueprint['created_at']);

            if ($scenario === null) {
                continue;
            }

            $timeline = $this->managementBoardTimelineForTestCreatedAt($testBlueprint['created_at']);
            $testId = (int) $test->id;
            $creatorUserId = (int) $test->creator_user_id;

            $initialRoundDecision = match ($scenario['key']) {
                'new' => Decision::Pending->value,
                'approved', 'reported_after_approval' => Decision::Approved->value,
                'deleted_from_new' => Decision::Deleted->value,
                'needs_revision_waiting',
                'under_review_after_revision',
                'approved_after_revision',
                'deleted_after_revision' => Decision::Needs_Revision->value,
            };

            $initialRoundDecidedAt = match ($scenario['key']) {
                'approved', 'reported_after_approval' => $timeline['approved'],
                'deleted_from_new' => $timeline['deleted'],
                'needs_revision_waiting',
                'under_review_after_revision',
                'approved_after_revision',
                'deleted_after_revision' => $timeline['needs_revision'],
                default => null,
            };

            $initialRoundId = $this->createSeedTestReviewRound(
                testId: $testId,
                roundNo: 1,
                reviewerUserId: null,
                triggerType: TestReviewRoundsTriggerType::Initial_Submission->value,
                decision: $initialRoundDecision,
                basedOnApprovalVersion: 0,
                startedAt: $timeline['round_started'],
                decidedAt: $initialRoundDecidedAt,
            );

            $this->createSeedTestStatusHistory(
                testId: $testId,
                testReviewRoundId: null,
                fromStatus: null,
                toStatus: TestReviewStatus::New->value,
                changedByUserId: $creatorUserId,
                note: 'تم إنشاء سجل الحالة الابتدائي للاختبار العام.',
                changedAt: $timeline['initial_status'],
            );

            switch ($scenario['key']) {
                case 'new':
                    break;

                case 'approved':
                    $this->createSeedTestStatusHistory(
                        testId: $testId,
                        testReviewRoundId: $initialRoundId,
                        fromStatus: TestReviewStatus::New->value,
                        toStatus: TestReviewStatus::Approved->value,
                        changedByUserId: null,
                        note: 'تمت الموافقة على الاختبار من لوحة الإدارة.',
                        changedAt: $timeline['approved'],
                    );
                    break;

                case 'deleted_from_new':
                    $this->createSeedTestStatusHistory(
                        testId: $testId,
                        testReviewRoundId: $initialRoundId,
                        fromStatus: TestReviewStatus::New->value,
                        toStatus: TestReviewStatus::Deleted->value,
                        changedByUserId: null,
                        note: 'تم حذف الاختبار أثناء المراجعة الابتدائية.',
                        changedAt: $timeline['deleted'],
                    );
                    break;

                case 'reported_after_approval':
                    $this->createSeedTestStatusHistory(
                        testId: $testId,
                        testReviewRoundId: $initialRoundId,
                        fromStatus: TestReviewStatus::New->value,
                        toStatus: TestReviewStatus::Approved->value,
                        changedByUserId: null,
                        note: 'تمت الموافقة على الاختبار قبل وصول البلاغات إلى الحد المطلوب.',
                        changedAt: $timeline['approved'],
                    );

                    $reportRoundId = $this->createSeedTestReviewRound(
                        testId: $testId,
                        roundNo: 2,
                        reviewerUserId: null,
                        triggerType: TestReviewRoundsTriggerType::Auto_Reported->value,
                        decision: Decision::Pending->value,
                        basedOnApprovalVersion: 1,
                        startedAt: $timeline['reported']->subMinutes(5),
                        decidedAt: null,
                    );

                    $this->createSeedTestStatusHistory(
                        testId: $testId,
                        testReviewRoundId: $reportRoundId,
                        fromStatus: TestReviewStatus::Approved->value,
                        toStatus: TestReviewStatus::Reported->value,
                        changedByUserId: null,
                        note: 'تم نقل الاختبار تلقائياً إلى حالة مبلغ عنه.',
                        changedAt: $timeline['reported'],
                    );
                    break;

                case 'needs_revision_waiting':
                    $this->createSeedTestStatusHistory(
                        testId: $testId,
                        testReviewRoundId: $initialRoundId,
                        fromStatus: TestReviewStatus::New->value,
                        toStatus: TestReviewStatus::NeedsRevision->value,
                        changedByUserId: null,
                        note: 'تم طلب تعديل من المشرف.',
                        changedAt: $timeline['needs_revision'],
                    );

                    $this->createSeedTestRevisionRequest(
                        roundId: $initialRoundId,
                        testId: $testId,
                        createdByUserId: $creatorUserId,
                        resolvedAt: null,
                        problemNote: 'يرجى توضيح الهدف العلمي في وصف الاختبار.',
                        createdAt: $timeline['needs_revision'],
                    );
                    break;

                case 'under_review_after_revision':
                    $this->createSeedTestStatusHistory(
                        testId: $testId,
                        testReviewRoundId: $initialRoundId,
                        fromStatus: TestReviewStatus::New->value,
                        toStatus: TestReviewStatus::NeedsRevision->value,
                        changedByUserId: null,
                        note: 'تم طلب تعديل من المشرف.',
                        changedAt: $timeline['needs_revision'],
                    );

                    $revisionRequestId = $this->createSeedTestRevisionRequest(
                        roundId: $initialRoundId,
                        testId: $testId,
                        createdByUserId: $creatorUserId,
                        resolvedAt: $timeline['under_review'],
                        problemNote: 'يرجى توضيح الهدف العلمي في وصف الاختبار.',
                        createdAt: $timeline['needs_revision'],
                    );

                    $resubmissionRoundId = $this->createSeedTestReviewRound(
                        testId: $testId,
                        roundNo: 2,
                        reviewerUserId: null,
                        triggerType: TestReviewRoundsTriggerType::Owner_Resubmission->value,
                        decision: Decision::Pending->value,
                        basedOnApprovalVersion: 0,
                        startedAt: $timeline['under_review'],
                        decidedAt: null,
                    );

                    $this->createSeedTestRevisionChangeLog(
                        roundId: $initialRoundId,
                        testId: $testId,
                        revisionRequestId: $revisionRequestId,
                        beforeValue: 'وصف مختصر لا يوضح الهدف العلمي بشكل كاف.',
                        afterValue: (string) $testBlueprint['description'],
                        changedByUserId: $creatorUserId,
                        changedAt: $timeline['under_review']->subMinutes(3),
                    );

                    $this->createSeedTestStatusHistory(
                        testId: $testId,
                        testReviewRoundId: $resubmissionRoundId,
                        fromStatus: TestReviewStatus::NeedsRevision->value,
                        toStatus: TestReviewStatus::UnderReview->value,
                        changedByUserId: $creatorUserId,
                        note: 'تمت إعادة إرسال الاختبار بعد تنفيذ التعديلات المطلوبة.',
                        changedAt: $timeline['under_review'],
                    );
                    break;

                case 'approved_after_revision':
                    $this->createSeedTestStatusHistory(
                        testId: $testId,
                        testReviewRoundId: $initialRoundId,
                        fromStatus: TestReviewStatus::New->value,
                        toStatus: TestReviewStatus::NeedsRevision->value,
                        changedByUserId: null,
                        note: 'تم طلب تعديل من المشرف قبل الموافقة.',
                        changedAt: $timeline['needs_revision'],
                    );

                    $revisionRequestId = $this->createSeedTestRevisionRequest(
                        roundId: $initialRoundId,
                        testId: $testId,
                        createdByUserId: $creatorUserId,
                        resolvedAt: $timeline['under_review'],
                        problemNote: 'يرجى توضيح الهدف العلمي في وصف الاختبار.',
                        createdAt: $timeline['needs_revision'],
                    );

                    $resubmissionRoundId = $this->createSeedTestReviewRound(
                        testId: $testId,
                        roundNo: 2,
                        reviewerUserId: null,
                        triggerType: TestReviewRoundsTriggerType::Owner_Resubmission->value,
                        decision: Decision::Approved->value,
                        basedOnApprovalVersion: 0,
                        startedAt: $timeline['under_review'],
                        decidedAt: $timeline['approved_after_revision'],
                    );

                    $this->createSeedTestRevisionChangeLog(
                        roundId: $initialRoundId,
                        testId: $testId,
                        revisionRequestId: $revisionRequestId,
                        beforeValue: 'وصف مختصر لا يوضح الهدف العلمي بشكل كاف.',
                        afterValue: (string) $testBlueprint['description'],
                        changedByUserId: $creatorUserId,
                        changedAt: $timeline['under_review']->subMinutes(3),
                    );

                    $this->createSeedTestStatusHistory(
                        testId: $testId,
                        testReviewRoundId: $resubmissionRoundId,
                        fromStatus: TestReviewStatus::NeedsRevision->value,
                        toStatus: TestReviewStatus::UnderReview->value,
                        changedByUserId: $creatorUserId,
                        note: 'تمت إعادة إرسال الاختبار بعد تنفيذ التعديلات المطلوبة.',
                        changedAt: $timeline['under_review'],
                    );

                    $this->createSeedTestStatusHistory(
                        testId: $testId,
                        testReviewRoundId: $resubmissionRoundId,
                        fromStatus: TestReviewStatus::UnderReview->value,
                        toStatus: TestReviewStatus::Approved->value,
                        changedByUserId: null,
                        note: 'تمت الموافقة على الاختبار بعد تعديل المالك.',
                        changedAt: $timeline['approved_after_revision'],
                    );
                    break;

                case 'deleted_after_revision':
                    $this->createSeedTestStatusHistory(
                        testId: $testId,
                        testReviewRoundId: $initialRoundId,
                        fromStatus: TestReviewStatus::New->value,
                        toStatus: TestReviewStatus::NeedsRevision->value,
                        changedByUserId: null,
                        note: 'تم طلب تعديل من المشرف قبل الحذف.',
                        changedAt: $timeline['needs_revision'],
                    );

                    $revisionRequestId = $this->createSeedTestRevisionRequest(
                        roundId: $initialRoundId,
                        testId: $testId,
                        createdByUserId: $creatorUserId,
                        resolvedAt: $timeline['under_review'],
                        problemNote: 'يرجى توضيح الهدف العلمي في وصف الاختبار.',
                        createdAt: $timeline['needs_revision'],
                    );

                    $resubmissionRoundId = $this->createSeedTestReviewRound(
                        testId: $testId,
                        roundNo: 2,
                        reviewerUserId: null,
                        triggerType: TestReviewRoundsTriggerType::Owner_Resubmission->value,
                        decision: Decision::Deleted->value,
                        basedOnApprovalVersion: 0,
                        startedAt: $timeline['under_review'],
                        decidedAt: $timeline['deleted_after_revision'],
                    );

                    $this->createSeedTestRevisionChangeLog(
                        roundId: $initialRoundId,
                        testId: $testId,
                        revisionRequestId: $revisionRequestId,
                        beforeValue: 'وصف مختصر لا يوضح الهدف العلمي بشكل كاف.',
                        afterValue: (string) $testBlueprint['description'],
                        changedByUserId: $creatorUserId,
                        changedAt: $timeline['under_review']->subMinutes(3),
                    );

                    $this->createSeedTestStatusHistory(
                        testId: $testId,
                        testReviewRoundId: $resubmissionRoundId,
                        fromStatus: TestReviewStatus::NeedsRevision->value,
                        toStatus: TestReviewStatus::UnderReview->value,
                        changedByUserId: $creatorUserId,
                        note: 'تمت إعادة إرسال الاختبار بعد تنفيذ التعديلات المطلوبة.',
                        changedAt: $timeline['under_review'],
                    );

                    $this->createSeedTestStatusHistory(
                        testId: $testId,
                        testReviewRoundId: $resubmissionRoundId,
                        fromStatus: TestReviewStatus::UnderReview->value,
                        toStatus: TestReviewStatus::Deleted->value,
                        changedByUserId: null,
                        note: 'تم حذف الاختبار بعد تعديل المالك.',
                        changedAt: $timeline['deleted_after_revision'],
                    );
                    break;
            }
        }
    }

    private function createSeedTestReviewRound(
        int $testId,
        int $roundNo,
        ?int $reviewerUserId,
        string $triggerType,
        string $decision,
        int $basedOnApprovalVersion,
        CarbonImmutable $startedAt,
        ?CarbonImmutable $decidedAt,
    ): int {
        return (int) DB::table('test_review_rounds')->insertGetId([
            'test_id' => $testId,
            'round_no' => $roundNo,
            'reviewer_user_id' => $reviewerUserId,
            'trigger_type' => $triggerType,
            'decision' => $decision,
            'based_on_approval_version' => $basedOnApprovalVersion,
            'started_at' => $startedAt,
            'decided_at' => $decidedAt,
            'created_at' => $startedAt,
            'updated_at' => $decidedAt ?? $startedAt,
        ]);
    }

    private function createSeedTestStatusHistory(
        int $testId,
        ?int $testReviewRoundId,
        ?string $fromStatus,
        string $toStatus,
        ?int $changedByUserId,
        string $note,
        CarbonImmutable $changedAt,
    ): void {
        DB::table('test_status_histories')->insert([
            'test_id' => $testId,
            'test_review_round_id' => $testReviewRoundId,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'changed_by_user_id' => match ($toStatus) {
                TestReviewStatus::New->value,
                TestReviewStatus::UnderReview->value => $changedByUserId,
                TestReviewStatus::Reported->value => null,
                TestReviewStatus::Approved->value,
                TestReviewStatus::Deleted->value,
                TestReviewStatus::NeedsRevision->value => random_int(1, 15),
            },
            'note' => $note,
            'created_at' => $changedAt,
            'updated_at' => $changedAt,
        ]);
    }

    private function createSeedTestRevisionRequest(
        int $roundId,
        int $testId,
        int $createdByUserId,
        ?CarbonImmutable $resolvedAt,
        string $problemNote,
        CarbonImmutable $createdAt,
    ): int {
        return (int) DB::table('test_revision_requests')->insertGetId([
            'test_review_round_id' => $roundId,
            'test_id' => $testId,
            'revision_type' => RevisionType::TestDescription->value,
            'target_question_id' => null,
            'target_option_id' => null,
            'created_by_user_id' => $createdByUserId,
            'resolved_at' => $resolvedAt,
            'problem_note' => $problemNote,
            'created_at' => $createdAt,
            'updated_at' => $resolvedAt ?? $createdAt,
        ]);
    }

    private function createSeedTestRevisionChangeLog(
        int $roundId,
        int $testId,
        int $revisionRequestId,
        string $beforeValue,
        string $afterValue,
        int $changedByUserId,
        CarbonImmutable $changedAt,
    ): void {
        DB::table('test_revision_change_logs')->insert([
            'test_review_round_id' => $roundId,
            'test_id' => $testId,
            'revision_request_id' => $revisionRequestId,
            'target_question_id' => null,
            'target_option_id' => null,
            'revision_type' => RevisionType::TestDescription->value,
            'before_value' => $beforeValue,
            'after_value' => $afterValue,
            'changed_by_user_id' => $changedByUserId,
            'created_at' => $changedAt,
            'updated_at' => $changedAt,
        ]);
    }

    private function seedTestEngagements(array $tests, array $testBlueprints, array $users, CarbonImmutable $now): void
    {
        $bookmarkRows = [];
        $likeRows = [];
        $downloadRows = [];

        foreach ($tests as $offset => $test) {
            $testBlueprint = $testBlueprints[$offset] ?? null;

            if ($testBlueprint === null) {
                throw new RuntimeException('تعذر مطابقة بيانات التفاعل مع الاختبار المحفوظ.');
            }

            if (($testBlueprint['review_status'] ?? null) !== TestReviewStatus::Approved->value
                || ($testBlueprint['published_at'] ?? null) === null) {
                continue;
            }

            $testIndex = $offset + 1;
            $testId = (int) $test->id;
            $creatorUserId = (int) $test->creator_user_id;
            $baseTimestamps = $this->testTimestampsForIndex($testIndex);

            $bookmarkUsers = $this->pickDistinctUsers(
                users: $users,
                count: (int) $testBlueprint['bookmarks_count'],
                excludedIds: [$creatorUserId],
                startIndex: $testIndex * 13
            );

            foreach ($bookmarkUsers as $bookmarkOffset => $user) {
                $bookmarkTimestamp = $baseTimestamps['published_at']->addDays(1)->addMinutes($bookmarkOffset + 1);

                $bookmarkRows[] = [
                    'test_id' => $testId,
                    'user_id' => $user['id'],
                    'created_at' => $bookmarkTimestamp,
                    'updated_at' => $bookmarkTimestamp,
                ];
            }

            $likeUsers = $this->pickDistinctUsers(
                users: $users,
                count: (int) $testBlueprint['likes_count'],
                excludedIds: [$creatorUserId],
                startIndex: ($testIndex * 17) + 5
            );

            foreach ($likeUsers as $likeOffset => $user) {
                $likeTimestamp = $baseTimestamps['published_at']->addDays(2)->addMinutes($likeOffset + 1);

                $likeRows[] = [
                    'test_id' => $testId,
                    'user_id' => $user['id'],
                    'created_at' => $likeTimestamp,
                    'updated_at' => $likeTimestamp,
                ];
            }

            $downloadUsers = $this->pickDistinctUsers(
                users: $users,
                count: (int) $testBlueprint['downloads_count'],
                excludedIds: [$creatorUserId],
                startIndex: ($testIndex * 23) + 9
            );

            foreach ($downloadUsers as $downloadOffset => $user) {
                $downloadTimestamp = $baseTimestamps['published_at']->addDays(3)->addMinutes($downloadOffset + 1);

                $downloadRows[] = [
                    'test_id' => $testId,
                    'user_id' => $user['id'],
                    'created_at' => $downloadTimestamp,
                    'updated_at' => $downloadTimestamp,
                ];
            }
        }

        $this->insertInChunks('test_bookmarks', $bookmarkRows);
        $this->insertInChunks('test_likes', $likeRows);
        $this->insertInChunks('test_download_logs', $downloadRows);
    }

    private function seedTestQuestionsAndOptions(array $tests, array $testBlueprints, CarbonImmutable $now): void
    {
        $nextQuestionId = ((int) DB::table('test_question')->max('id')) + 1;
        $nextOptionId = ((int) DB::table('test_question_options')->max('id')) + 1;
        $questionRows = [];
        $optionRows = [];

        foreach ($tests as $offset => $test) {
            $testBlueprint = $testBlueprints[$offset] ?? null;

            if ($testBlueprint === null) {
                throw new RuntimeException('تعذر مطابقة بيانات الاختبار المحفوظة مع مخطط البيانات المولد.');
            }

            $testIndex = $offset + 1;
            $testId = (int) $test->id;
            $questionCount = (int) $testBlueprint['question_count'];
            $previewQuestionCount = (int) $testBlueprint['preview_question_count'];
            $language = (string) $testBlueprint['language'];
            $interestName = $this->extractInterestNameFromTitle((string) $testBlueprint['title']);
            $targetLevel = (string) $testBlueprint['target_level'];
            $difficulty = (string) $testBlueprint['difficulty_level'];

            for ($position = 1; $position <= $questionCount; $position++) {
                $questionId = $nextQuestionId++;
                $timestamps = $this->questionTimestampsFor(
                    baseCreatedAt: $testBlueprint['created_at'],
                    baseUpdatedAt: $testBlueprint['updated_at'],
                    questionPosition: $position,
                    questionCount: $questionCount,
                    now: $now,
                );
                $options = $this->buildQuestionOptions(
                    testIndex: $testIndex,
                    questionPosition: $position,
                    language: $language,
                    interestName: $interestName,
                    difficulty: $difficulty,
                );

                $questionRows[] = [
                    'id' => $questionId,
                    'test_id' => $testId,
                    'position' => $position,
                    'question_text' => $this->buildQuestionText(
                        testIndex: $testIndex,
                        questionPosition: $position,
                        language: $language,
                        interestName: $interestName,
                        targetLevel: $targetLevel,
                        difficulty: $difficulty,
                    ),
                    'hint_text' => $this->buildQuestionHint(
                        testIndex: $testIndex,
                        questionPosition: $position,
                        language: $language,
                        interestName: $interestName,
                    ),
                    'is_preview' => $position <= $previewQuestionCount,
                    'options_count' => count($options),
                    'created_at' => $timestamps['created_at'],
                    'updated_at' => $timestamps['updated_at'],
                ];

                foreach ($options as $optionIndex => $option) {
                    $optionRows[] = [
                        'id' => $nextOptionId++,
                        'test_question_id' => $questionId,
                        'position' => $optionIndex + 1,
                        'option_text' => $option['text'],
                        'is_correct' => $option['is_correct'],
                        'created_at' => $timestamps['created_at'],
                        'updated_at' => $timestamps['updated_at'],
                    ];
                }

                if (count($optionRows) >= self::INSERT_CHUNK_SIZE * 4) {
                    if ($questionRows !== []) {
                        $this->insertInChunks('test_question', $questionRows);
                        $questionRows = [];
                    }

                    $this->insertInChunks('test_question_options', $optionRows);
                    $optionRows = [];
                }

                if (count($questionRows) >= self::INSERT_CHUNK_SIZE) {
                    $this->insertInChunks('test_question', $questionRows);
                    $questionRows = [];
                }
            }
        }

        if ($questionRows !== []) {
            $this->insertInChunks('test_question', $questionRows);
        }

        if ($optionRows !== []) {
            $this->insertInChunks('test_question_options', $optionRows);
        }
    }

    private function buildReviewDataset(array $tests, array $testBlueprints, array $users, CarbonImmutable $now): array
    {
        $reviewRows = [];
        $feedbackRows = [];
        $testReviewAggregates = [];
        $nextReviewId = ((int) DB::table('test_reviews')->max('id')) + 1;

        foreach ($tests as $offset => $test) {
            $testBlueprint = $testBlueprints[$offset] ?? null;

            if ($testBlueprint === null) {
                throw new RuntimeException('تعذر مطابقة بيانات المراجعة مع الاختبار المحفوظ.');
            }

            if (($testBlueprint['review_status'] ?? null) !== TestReviewStatus::Approved->value
                || ($testBlueprint['published_at'] ?? null) === null) {
                continue;
            }

            $testIndex = $offset + 1;
            $testId = (int) $test->id;
            $creatorUserId = (int) $test->creator_user_id;
            $reviewCount = 3 + ($testIndex % 4);
            $publishedAt = CarbonImmutable::parse((string) $testBlueprint['published_at']);

            $ratingSum = 0;

            for ($reviewOffset = 1; $reviewOffset <= $reviewCount; $reviewOffset++) {
                $reviewer = $this->findDistinctUser(
                    users: $users,
                    excludedIds: [$creatorUserId],
                    startIndex: $testIndex * 11 + $reviewOffset
                );

                $reviewId = $nextReviewId++;
                $rating = (($testIndex + $reviewOffset) % 5) + 1;
                $feedbackCount = 1 + (($testIndex + $reviewOffset) % 3);
                $helpfulYesCount = 0;
                $helpfulNoCount = 0;
                $reviewTimestamp = $publishedAt->addDays(4)->addHours($reviewOffset);

                if ($reviewTimestamp->greaterThan($now)) {
                    $reviewTimestamp = $publishedAt->addHours($reviewOffset);
                }

                $usedFeedbackUserIds = [$creatorUserId, $reviewer['id']];

                for ($feedbackOffset = 1; $feedbackOffset <= $feedbackCount; $feedbackOffset++) {
                    $feedbackUser = $this->findDistinctUser(
                        users: $users,
                        excludedIds: $usedFeedbackUserIds,
                        startIndex: ($testIndex * 17) + ($reviewOffset * 5) + $feedbackOffset
                    );

                    $usedFeedbackUserIds[] = $feedbackUser['id'];

                    $vote = (($testIndex + $reviewOffset + $feedbackOffset) % 4 === 0)
                        ? Vote::No->value
                        : Vote::Yes->value;

                    if ($vote === Vote::Yes->value) {
                        $helpfulYesCount++;
                    } else {
                        $helpfulNoCount++;
                    }

                    $feedbackRows[] = [
                        'test_review_id' => $reviewId,
                        'user_id' => $feedbackUser['id'],
                        'vote' => $vote,
                        'created_at' => $reviewTimestamp->addHours($feedbackOffset),
                        'updated_at' => $reviewTimestamp->addHours($feedbackOffset),
                    ];
                }

                $reviewRows[] = [
                    'id' => $reviewId,
                    'test_id' => $testId,
                    'user_id' => $reviewer['id'],
                    'rating' => $rating,
                    'review_text' => $this->buildArabicReviewText($rating, $testIndex, $reviewOffset),
                    'helpful_yes_count' => $helpfulYesCount,
                    'helpful_no_count' => $helpfulNoCount,
                    'created_at' => $reviewTimestamp,
                    'updated_at' => $reviewTimestamp,
                ];

                $ratingSum += $rating;
            }

            $testReviewAggregates[] = [
                'test_id' => $testId,
                'creator_user_id' => $creatorUserId,
                'reviews_count' => $reviewCount,
                'average_rating' => number_format($ratingSum / $reviewCount, 2, '.', ''),
            ];
        }

        return [$reviewRows, $feedbackRows, $testReviewAggregates];
    }

    private function seedTestPurchasesAndFinancialStats(array $tests, array $users): void
    {
        $paidTestsByYear = [
            self::CURRENT_TEST_YEAR => [],
            self::PREVIOUS_TEST_YEAR => [],
        ];

        foreach ($tests as $test) {
            if ($test->price === null || (float) $test->price <= 0 || $test->published_at === null) {
                continue;
            }

            $publishedAt = CarbonImmutable::parse((string) $test->published_at);
            $year = (int) $publishedAt->year;

            if (! array_key_exists($year, $paidTestsByYear)) {
                continue;
            }

            $paidTestsByYear[$year][] = $test;
        }

        foreach ($paidTestsByYear as $year => $paidTests) {
            if ($paidTests === []) {
                throw new RuntimeException("لا توجد اختبارات مدفوعة مناسبة لتوليد عمليات شراء سنة {$year}.");
            }
        }

        $purchaseRows = [];
        $currency = (string) config('payments.pricing_currency', config('payments.default_currency', 'syp'));
        $paymentProvider = (string) config('payments.default_provider', 'stripe');
        $platformFeePercent = (float) config('payments.platform_fee_percent', 10);

        foreach ($users as $offset => $buyer) {
            $buyerIndex = $offset + 1;
            $purchaseYear = $buyerIndex <= (int) floor(count($users) / 2)
                ? self::CURRENT_TEST_YEAR
                : self::PREVIOUS_TEST_YEAR;
            $purchaseCount = 1 + (($buyerIndex * 7) % 5);
            $usedTestIds = [];

            for ($purchaseOffset = 0; $purchaseOffset < $purchaseCount; $purchaseOffset++) {
                $test = $this->findPurchasableTestForBuyer(
                    tests: $paidTestsByYear[$purchaseYear],
                    buyerUserId: (int) $buyer['id'],
                    usedTestIds: $usedTestIds,
                    startIndex: ($buyerIndex * 17) + ($purchaseOffset * 11),
                );

                $usedTestIds[] = (int) $test->id;

                $grossAmount = round((float) $test->price, 2);
                $platformFeeAmount = round(($grossAmount * $platformFeePercent) / 100, 2);
                $sellerNetAmount = round($grossAmount - $platformFeeAmount, 2);
                $purchasedAt = CarbonImmutable::parse((string) $test->published_at)
                    ->addHours(2 + $purchaseOffset)
                    ->addMinutes($buyerIndex % 60);

                $purchaseRows[] = [
                    'test_id' => (int) $test->id,
                    'buyer_user_id' => (int) $buyer['id'],
                    'seller_user_id' => (int) $test->creator_user_id,
                    'gross_amount' => number_format($grossAmount, 2, '.', ''),
                    'platform_fee_amount' => number_format($platformFeeAmount, 2, '.', ''),
                    'seller_net_amount' => number_format($sellerNetAmount, 2, '.', ''),
                    'currency' => $currency,
                    'payment_provider' => $paymentProvider,
                    'payment_reference' => sprintf(
                        'seed-test-purchase-%d-%d-%d',
                        $purchaseYear,
                        (int) $buyer['id'],
                        (int) $test->id,
                    ),
                    'payment_status' => PaymentStatus::Paid->value,
                    'purchased_at' => $purchasedAt,
                    'created_at' => $purchasedAt,
                    'updated_at' => $purchasedAt,
                ];
            }
        }

        $this->insertInChunks('test_purchases', $purchaseRows);
        $this->rebuildAdminFinancialStats([
            self::PREVIOUS_TEST_YEAR,
            self::CURRENT_TEST_YEAR,
        ]);
    }

    private function findPurchasableTestForBuyer(array $tests, int $buyerUserId, array $usedTestIds, int $startIndex): object
    {
        $count = count($tests);

        for ($attempt = 0; $attempt < $count; $attempt++) {
            $candidate = $tests[($startIndex + $attempt) % $count];

            if ((int) $candidate->creator_user_id === $buyerUserId) {
                continue;
            }

            if (in_array((int) $candidate->id, $usedTestIds, true)) {
                continue;
            }

            return $candidate;
        }

        throw new RuntimeException('تعذر العثور على اختبار مدفوع مناسب لتوليد عملية شراء.');
    }

    private function buildArabicReviewText(int $rating, int $testIndex, int $reviewOffset): string
    {
        $comments = [
            5 => 'اختبار ممتاز ومنظم جداً، والأسئلة فيه واضحة وتغطي الفكرة الأساسية بدقة.',
            4 => 'اختبار جيد جداً ومفيد للمراجعة السريعة، وفيه تنوع مناسب في الأسئلة.',
            3 => 'الاختبار جيد بشكل عام، لكنه يحتاج بعض التوازن في مستوى الصعوبة بين الأسئلة.',
            2 => 'الفكرة مفيدة لكن بعض الأسئلة غير دقيقة وتحتاج تحسيناً في الصياغة.',
            1 => 'الاختبار يحتاج مراجعة أكبر من ناحية الصياغة وتسلسل الأسئلة والمحتوى.',
        ];

        return $comments[$rating] . " رقم المراجعة {$reviewOffset} للاختبار {$testIndex}.";
    }

    private function questionCountForIndex(int $index): int
    {
        return 5 + (($index * 7) % 96);
    }

    private function engagementCountForIndex(int $index, int $multiplier): int
    {
        return 21 + (($index * $multiplier) % 10);
    }

    private function previewQuestionCountFor(int $questionCount, int $index): int
    {
        return min(5, max(2, min($questionCount, 2 + ($index % 4))));
    }

    private function extractInterestNameFromTitle(string $title): string
    {
        $parts = explode(' - ', $title, 2);

        return trim($parts[1] ?? $title);
    }

    private function questionTimestampsFor(
        mixed $baseCreatedAt,
        mixed $baseUpdatedAt,
        int $questionPosition,
        int $questionCount,
        CarbonImmutable $now
    ): array {
        $createdAt = CarbonImmutable::parse((string) $baseCreatedAt)->addMinutes($questionPosition);
        $maxUpdatedAt = CarbonImmutable::parse((string) $baseUpdatedAt);
        $calculatedUpdatedAt = $createdAt->addMinutes(max(1, (int) floor($questionCount / 3)));
        $updatedAt = $calculatedUpdatedAt->greaterThan($maxUpdatedAt) ? $maxUpdatedAt : $calculatedUpdatedAt;

        if ($updatedAt->greaterThan($now)) {
            $updatedAt = $now;
        }

        return [
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
        ];
    }

    private function buildQuestionText(
        int $testIndex,
        int $questionPosition,
        string $language,
        string $interestName,
        string $targetLevel,
        string $difficulty
    ): string {
        $variant = ($testIndex + $questionPosition) % 4;

        return match ($variant) {
            0 => "ما الهدف الأساسي من توظيف {$interestName} في سؤال موجه إلى {$targetLevel} وبدرجة {$difficulty}؟",
            1 => "في سياق {$interestName}، ما الخطوة الأولى الأكثر منطقية للوصول إلى فهم صحيح للمفهوم؟",
            2 => "أي عبارة تعبّر بدقة أكبر عن أفضل ممارسة مرتبطة بـ {$interestName} لهذه الفئة الدراسية؟",
            default => "عند حل مسألة ضمن {$interestName}، ما التصرف الأنسب الذي يعكس فهماً عملياً للمحتوى؟",
        };
    }

    private function buildQuestionHint(
        int $testIndex,
        int $questionPosition,
        string $language,
        string $interestName
    ): ?string {
        if ((($testIndex * 2) + $questionPosition) % 3 !== 0) {
            return null;
        }

        return "ابدأ بالفكرة الأساسية في {$interestName} ثم استبعد الخيارات التي تركز على تفاصيل جانبية.";
    }

    private function buildQuestionOptions(
        int $testIndex,
        int $questionPosition,
        string $language,
        string $interestName,
        string $difficulty
    ): array {
        $optionsCount = 2 + (($testIndex + $questionPosition) % 4);
        $correctPosition = ($testIndex + ($questionPosition * 2)) % $optionsCount;
        $options = [];

        for ($optionIndex = 0; $optionIndex < $optionsCount; $optionIndex++) {
            $isCorrect = $optionIndex === $correctPosition;

            $options[] = [
                'text' => $this->buildOptionText(
                    language: $language,
                    interestName: $interestName,
                    difficulty: $difficulty,
                    variantIndex: $optionIndex,
                    isCorrect: $isCorrect,
                ),
                'is_correct' => $isCorrect,
            ];
        }

        return $options;
    }

    private function buildOptionText(
        string $language,
        string $interestName,
        string $difficulty,
        int $variantIndex,
        bool $isCorrect
    ): string {
        $correctArabic = [
            "البدء بفهم الفكرة المركزية في {$interestName} ثم تطبيقها على مثال واضح.",
            "تحليل المعطيات أولاً ثم اختيار الإجراء الذي يحقق الهدف التعليمي بدقة.",
            "التركيز على المفهوم الأساسي واستبعاد التفاصيل التي لا تغيّر القرار النهائي.",
            "مقارنة الخيارات وفق المعنى العملي للمفهوم وليس وفق الكلمات المتشابهة فقط.",
            "ربط {$interestName} بالسياق الصحيح قبل الانتقال إلى الحل النهائي.",
        ];
        $wrongArabic = [
            "اختيار أول إجابة تبدو مألوفة حتى لو لم ترتبط بسياق السؤال.",
            "الاعتماد على حفظ المصطلح فقط دون فهم طريقة استخدامه.",
            "تجاهل المعطيات الأساسية والتركيز على كلمة واحدة داخل السؤال.",
            "اختيار الإجابة الأطول على افتراض أنها الأدق دائماً.",
            "تبديل الخطوات المنطقية والاكتفاء بتخمين سريع.",
        ];
        $correctEnglish = [
            "Identify the core idea of {$interestName}, then apply it to the given context.",
            "Start from the learning objective and choose the option that fits the scenario logically.",
            "Filter out distracting details and focus on the concept that drives the decision.",
            "Match the concept to a practical use case before selecting the final answer.",
            "Evaluate the options by meaning, not by surface wording alone.",
        ];
        $wrongEnglish = [
            "Pick the first familiar term even if it does not match the scenario.",
            "Ignore the context and rely only on a memorized keyword.",
            "Assume the longest answer is always the best answer.",
            "Choose the option that sounds advanced without checking the objective.",
            "Skip the concept and guess based on wording similarity only.",
        ];
        $correctMixed = [
            "ابدأ من core concept في {$interestName} ثم طبّقه على scenario واضح.",
            "راجع learning objective أولاً ثم اختر option ينسجم مع المعنى.",
            "استبعد distractors وركّز على الفكرة التي تغيّر decision فعلاً.",
            "اربط المصطلح بالسياق العملي قبل اختيار final answer.",
            "قيّم الخيارات حسب meaning وليس حسب الكلمات المتشابهة فقط.",
        ];
        $wrongMixed = [
            "اختر أي option يحتوي على keyword مألوفة حتى لو كان خارج context.",
            "تجاهل scenario واعتمد على memorization فقط.",
            "افترض أن most detailed answer هي الصحيحة دائماً.",
            "ابدأ من guess سريع من دون مراجعة learning objective.",
            "ركّز على wording واترك المعنى الأساسي للمفهوم.",
        ];

        $index = $variantIndex % 5;

        return match ($language) {
            Language::English->value => $isCorrect
                ? $correctEnglish[$index]
                : $wrongEnglish[$index] . " This is weak for a {$difficulty} {$interestName} question.",
            Language::Mixed->value => $isCorrect
                ? $correctMixed[$index]
                : $wrongMixed[$index] . " وهذا لا يناسب {$interestName}.",
            default => $isCorrect
                ? $correctArabic[$index]
                : $wrongArabic[$index] . " وهذا لا يحقق مستوى {$difficulty} في {$interestName}.",
        };
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

    private function testTimestampsForIndex(int $index): array
    {
        $year = $index <= self::TESTS_PER_SEEDED_YEAR
            ? self::CURRENT_TEST_YEAR
            : self::PREVIOUS_TEST_YEAR;

        $yearIndex = (($index - 1) % self::TESTS_PER_SEEDED_YEAR) + 1;
        $month = ((($yearIndex * 19) + ((int) floor(($yearIndex - 1) / 2) * 11)) % 12) + 1;
        $daysInMonth = CarbonImmutable::create($year, $month, 1)->daysInMonth;
        $day = (($yearIndex * 11) % ($daysInMonth - 3)) + 1;
        $hour = 8 + (($yearIndex * 5) % 10);
        $minute = ($yearIndex * 13) % 60;

        $createdAt = CarbonImmutable::create($year, $month, $day, $hour, $minute);
        $publishedAt = $createdAt->addDays(2);
        $updatedAt = $publishedAt->addHours(6);

        return [
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
            'verified_at' => $createdAt->addHours(2),
            'completed_at' => $createdAt->addHours(6),
            'last_login_at' => $updatedAt->addHours(3),
            'published_at' => $publishedAt,
        ];
    }

    private function rebuildAdminYearlyTestActivityMonthStats(array $years): void
    {
        $now = CarbonImmutable::now();
        $stats = [];

        foreach ($years as $year) {
            foreach (range(1, 12) as $month) {
                $stats[$year][$month] = [
                    'year' => $year,
                    'month_no' => $month,
                    'published_tests_count' => 0,
                    'likes_count' => 0,
                    'reviews_count' => 0,
                    'downloads_count' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach ($this->monthlyCounts('test', 'published_at', $years) as $row) {
            $stats[(int) $row->year][(int) $row->month_no]['published_tests_count'] = (int) $row->aggregate_count;
        }

        foreach ($this->monthlyCounts('test_likes', 'created_at', $years) as $row) {
            $stats[(int) $row->year][(int) $row->month_no]['likes_count'] = (int) $row->aggregate_count;
        }

        foreach ($this->monthlyCounts('test_reviews', 'created_at', $years) as $row) {
            $stats[(int) $row->year][(int) $row->month_no]['reviews_count'] = (int) $row->aggregate_count;
        }

        foreach ($this->monthlyCounts('test_download_logs', 'created_at', $years) as $row) {
            $stats[(int) $row->year][(int) $row->month_no]['downloads_count'] = (int) $row->aggregate_count;
        }

        $rows = [];

        foreach ($stats as $months) {
            foreach ($months as $monthStats) {
                $rows[] = $monthStats;
            }
        }

        DB::table('admin_yearly_test_activity_month_stats')->upsert(
            $rows,
            ['year', 'month_no'],
            [
                'published_tests_count',
                'likes_count',
                'reviews_count',
                'downloads_count',
                'updated_at',
            ],
        );
    }

    private function rebuildGeneratedUserStats(array $userIds): void
    {
        if ($userIds === []) {
            return;
        }

        $now = CarbonImmutable::now();

        $summaryRows = DB::table('users')
            ->join('roles', 'roles.id', '=', 'users.role_id')
            ->whereIn('users.id', $userIds)
            ->where('roles.name', SystemRole::Mobile_User->value)
            ->whereNotNull('users.onboarding_completed_at')
            ->selectRaw('YEAR(users.onboarding_completed_at) as year')
            ->selectRaw('COUNT(*) as total_completed_mobile_users')
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN users.gender = ? THEN 1 ELSE 0 END), 0) as male_completed_mobile_users',
                [Gender::Male->value]
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN users.gender = ? THEN 1 ELSE 0 END), 0) as female_completed_mobile_users',
                [Gender::Female->value]
            )
            ->groupByRaw('YEAR(users.onboarding_completed_at)')
            ->get()
            ->map(fn($row): array => [
                'year' => (int) $row->year,
                'total_completed_mobile_users' => (int) $row->total_completed_mobile_users,
                'male_completed_mobile_users' => (int) $row->male_completed_mobile_users,
                'female_completed_mobile_users' => (int) $row->female_completed_mobile_users,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->values()
            ->all();

        $years = array_values(array_unique(array_column($summaryRows, 'year')));
        sort($years);

        if ($summaryRows !== []) {
            DB::table('user_stats_summary')->upsert(
                $summaryRows,
                ['year'],
                [
                    'total_completed_mobile_users',
                    'male_completed_mobile_users',
                    'female_completed_mobile_users',
                    'updated_at',
                ],
            );
        }

        if ($years === []) {
            return;
        }

        $sourceStats = [];

        foreach ($years as $year) {
            foreach (DiscoverySource::cases() as $source) {
                $sourceStats[$year][$source->value] = [
                    'year' => $year,
                    'discovery_source' => $source->value,
                    'completed_mobile_users_count' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        $sourceCounts = DB::table('user_onboarding_profiles')
            ->whereIn('user_id', $userIds)
            ->whereNotNull('discovery_source')
            ->whereIn(DB::raw('YEAR(created_at)'), $years)
            ->selectRaw('YEAR(created_at) as year, discovery_source, COUNT(*) as aggregate_count')
            ->groupByRaw('YEAR(created_at), discovery_source')
            ->get();

        foreach ($sourceCounts as $row) {
            $sourceStats[(int) $row->year][(string) $row->discovery_source]['completed_mobile_users_count'] = (int) $row->aggregate_count;
        }

        $sourceRows = [];

        foreach ($sourceStats as $yearStats) {
            foreach ($yearStats as $sourceRow) {
                $sourceRows[] = $sourceRow;
            }
        }

        DB::table('user_stats_by_discovery_source')->upsert(
            $sourceRows,
            ['year', 'discovery_source'],
            [
                'completed_mobile_users_count',
                'updated_at',
            ],
        );
    }

    private function rebuildAdminFinancialStats(array $years): void
    {
        $now = CarbonImmutable::now();
        $paidStatus = PaymentStatus::Paid->value;
        $yearlyStats = [];
        $monthlyStats = [];

        foreach ($years as $year) {
            $yearlyStats[$year] = [
                'year' => $year,
                'sold_purchase_count' => 0,
                'distinct_sold_tests_count' => 0,
                'gross_sales_amount' => number_format(0, 2, '.', ''),
                'users_profit_amount' => number_format(0, 2, '.', ''),
                'platform_net_profit_amount' => number_format(0, 2, '.', ''),
                'average_monthly_sales_amount' => number_format(0, 2, '.', ''),
                'average_monthly_platform_profit_amount' => number_format(0, 2, '.', ''),
                'most_purchased_test_id' => null,
                'most_purchased_test_purchase_count' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            foreach (range(1, 12) as $month) {
                $monthlyStats[$year][$month] = [
                    'year' => $year,
                    'month_no' => $month,
                    'sold_purchase_count' => 0,
                    'distinct_sold_tests_count' => 0,
                    'gross_sales_amount' => number_format(0, 2, '.', ''),
                    'users_profit_amount' => number_format(0, 2, '.', ''),
                    'platform_net_profit_amount' => number_format(0, 2, '.', ''),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        $annualRows = DB::table('test_purchases')
            ->where('payment_status', $paidStatus)
            ->whereNotNull('purchased_at')
            ->whereIn(DB::raw('YEAR(purchased_at)'), $years)
            ->selectRaw('YEAR(purchased_at) as year')
            ->selectRaw('COUNT(*) as sold_purchase_count')
            ->selectRaw('COUNT(DISTINCT test_id) as distinct_sold_tests_count')
            ->selectRaw('COALESCE(SUM(gross_amount), 0) as gross_sales_amount')
            ->selectRaw('COALESCE(SUM(seller_net_amount), 0) as users_profit_amount')
            ->selectRaw('COALESCE(SUM(platform_fee_amount), 0) as platform_net_profit_amount')
            ->groupByRaw('YEAR(purchased_at)')
            ->get();

        foreach ($annualRows as $row) {
            $year = (int) $row->year;

            $yearlyStats[$year]['sold_purchase_count'] = (int) $row->sold_purchase_count;
            $yearlyStats[$year]['distinct_sold_tests_count'] = (int) $row->distinct_sold_tests_count;
            $yearlyStats[$year]['gross_sales_amount'] = $this->money($row->gross_sales_amount);
            $yearlyStats[$year]['users_profit_amount'] = $this->money($row->users_profit_amount);
            $yearlyStats[$year]['platform_net_profit_amount'] = $this->money($row->platform_net_profit_amount);
            $yearlyStats[$year]['average_monthly_sales_amount'] = $this->divideMoneyBy12($row->gross_sales_amount);
            $yearlyStats[$year]['average_monthly_platform_profit_amount'] = $this->divideMoneyBy12($row->platform_net_profit_amount);
        }

        foreach ($years as $year) {
            $mostPurchasedTest = DB::table('test_purchases')
                ->where('payment_status', $paidStatus)
                ->whereNotNull('purchased_at')
                ->whereYear('purchased_at', $year)
                ->select('test_id')
                ->selectRaw('COUNT(*) as purchase_count')
                ->groupBy('test_id')
                ->orderByDesc('purchase_count')
                ->orderBy('test_id')
                ->first();

            $yearlyStats[$year]['most_purchased_test_id'] = $mostPurchasedTest?->test_id;
            $yearlyStats[$year]['most_purchased_test_purchase_count'] = (int) ($mostPurchasedTest?->purchase_count ?? 0);
        }

        DB::table('admin_yearly_financial_stats')->upsert(
            array_values($yearlyStats),
            ['year'],
            [
                'sold_purchase_count',
                'distinct_sold_tests_count',
                'gross_sales_amount',
                'users_profit_amount',
                'platform_net_profit_amount',
                'average_monthly_sales_amount',
                'average_monthly_platform_profit_amount',
                'most_purchased_test_id',
                'most_purchased_test_purchase_count',
                'updated_at',
            ],
        );

        $monthlyRows = DB::table('test_purchases')
            ->where('payment_status', $paidStatus)
            ->whereNotNull('purchased_at')
            ->whereIn(DB::raw('YEAR(purchased_at)'), $years)
            ->selectRaw('YEAR(purchased_at) as year, MONTH(purchased_at) as month_no')
            ->selectRaw('COUNT(*) as sold_purchase_count')
            ->selectRaw('COUNT(DISTINCT test_id) as distinct_sold_tests_count')
            ->selectRaw('COALESCE(SUM(gross_amount), 0) as gross_sales_amount')
            ->selectRaw('COALESCE(SUM(seller_net_amount), 0) as users_profit_amount')
            ->selectRaw('COALESCE(SUM(platform_fee_amount), 0) as platform_net_profit_amount')
            ->groupByRaw('YEAR(purchased_at), MONTH(purchased_at)')
            ->get();

        foreach ($monthlyRows as $row) {
            $year = (int) $row->year;
            $month = (int) $row->month_no;

            $monthlyStats[$year][$month]['sold_purchase_count'] = (int) $row->sold_purchase_count;
            $monthlyStats[$year][$month]['distinct_sold_tests_count'] = (int) $row->distinct_sold_tests_count;
            $monthlyStats[$year][$month]['gross_sales_amount'] = $this->money($row->gross_sales_amount);
            $monthlyStats[$year][$month]['users_profit_amount'] = $this->money($row->users_profit_amount);
            $monthlyStats[$year][$month]['platform_net_profit_amount'] = $this->money($row->platform_net_profit_amount);
        }

        $monthRows = [];

        foreach ($monthlyStats as $months) {
            foreach ($months as $monthStats) {
                $monthRows[] = $monthStats;
            }
        }

        DB::table('admin_yearly_financial_month_stats')->upsert(
            $monthRows,
            ['year', 'month_no'],
            [
                'sold_purchase_count',
                'distinct_sold_tests_count',
                'gross_sales_amount',
                'users_profit_amount',
                'platform_net_profit_amount',
                'updated_at',
            ],
        );

        DB::table('admin_yearly_test_sales_stats')
            ->whereIn('year', $years)
            ->delete();

        $testSalesRows = DB::table('test_purchases')
            ->where('payment_status', $paidStatus)
            ->whereNotNull('purchased_at')
            ->whereIn(DB::raw('YEAR(purchased_at)'), $years)
            ->selectRaw('YEAR(purchased_at) as year, test_id')
            ->selectRaw('COUNT(*) as purchase_count')
            ->selectRaw('COALESCE(SUM(gross_amount), 0) as gross_sales_amount')
            ->selectRaw('COALESCE(SUM(seller_net_amount), 0) as users_profit_amount')
            ->selectRaw('COALESCE(SUM(platform_fee_amount), 0) as platform_net_profit_amount')
            ->groupByRaw('YEAR(purchased_at), test_id')
            ->get()
            ->map(fn($row): array => [
                'year' => (int) $row->year,
                'test_id' => (int) $row->test_id,
                'purchase_count' => (int) $row->purchase_count,
                'gross_sales_amount' => $this->money($row->gross_sales_amount),
                'users_profit_amount' => $this->money($row->users_profit_amount),
                'platform_net_profit_amount' => $this->money($row->platform_net_profit_amount),
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->values()
            ->all();

        $this->insertInChunks('admin_yearly_test_sales_stats', $testSalesRows);
    }

    private function money(mixed $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }

    private function divideMoneyBy12(mixed $amount): string
    {
        if (function_exists('bcdiv')) {
            return bcdiv((string) $amount, '12', 2);
        }

        return number_format(((float) $amount) / 12, 2, '.', '');
    }

    private function monthlyCounts(string $table, string $dateColumn, array $years)
    {
        return DB::table($table)
            ->selectRaw("YEAR({$dateColumn}) as year, MONTH({$dateColumn}) as month_no, COUNT(*) as aggregate_count")
            ->whereNotNull($dateColumn)
            ->whereIn(DB::raw("YEAR({$dateColumn})"), $years)
            ->groupByRaw("YEAR({$dateColumn}), MONTH({$dateColumn})")
            ->get();
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

    private function findDistinctUser(array $users, array $excludedIds, int $startIndex): array
    {
        $count = count($users);

        for ($attempt = 0; $attempt < $count; $attempt++) {
            $candidate = $users[($startIndex + $attempt) % $count];

            if (! in_array($candidate['id'], $excludedIds, true)) {
                return $candidate;
            }
        }

        throw new RuntimeException('تعذر العثور على مستخدم مناسب لتوليد بيانات المراجعات.');
    }

    private function pickDistinctUsers(array $users, int $count, array $excludedIds, int $startIndex): array
    {
        $selectedUsers = [];
        $usedIds = $excludedIds;

        for ($offset = 0; $offset < $count; $offset++) {
            $user = $this->findDistinctUser(
                users: $users,
                excludedIds: $usedIds,
                startIndex: $startIndex + ($offset * 3)
            );

            $selectedUsers[] = $user;
            $usedIds[] = $user['id'];
        }

        return $selectedUsers;
    }

    private function insertInChunks(string $table, array $rows): void
    {
        foreach (array_chunk($rows, self::INSERT_CHUNK_SIZE) as $chunk) {
            DB::table($table)->insert($chunk);
        }
    }
}
