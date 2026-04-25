<?php

namespace App\Repositories\TestDiscovery;

use App\Enums\TestReviewStatus;
use App\Enums\TestType;
use App\Services\TestDiscovery\DTO\DiscoveryContextData;
use App\Services\TestDiscovery\DTO\TestCandidateData;
use App\Services\TestDiscovery\DTO\UserDiscoveryProfileData;
use App\Services\TestDiscovery\Enums\DiscoveryTab;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * هذا هو الـ repository الأساسي لطبقة المرشحين.
 * وظيفته:
 * - جلب candidate IDs من 3 سلال:
 * 1) مطابق للاهتمامات
 * 2) مطابق للمستوى target level
 * 3) fallback عام
  * ثم:
 * - تحميل بيانات الاختبارات نفسها
 * - تحميل interests المرتبطة بها
 * - تحويلها إلى TestCandidateData
 * مهم:
 * هذا الملف لا يقوم بعملية ranking النهائية،
 * بل فقط يبني candidate pool.
 */

class TestDiscoveryRepository
{

    public function findCandidatesForDiscovery(UserDiscoveryProfileData $userProfile, DiscoveryContextData $context): array {

        // Determine size of Pool limit (int value , EX:200 result)
        $candidatePoolLimit = $context->resolvedCandidatePoolLimit();

        // Determine size of each bucket (in the recommendation system , samples are brought from more than one source and each source called bucket , EX: interset bucket - education level bucket - fallback bucket) , min size number 20
        $bucketLimit = max(20, (int) ceil($candidatePoolLimit / 2));

        //First bringing tests that match user intersets (match between user interset & test interset , return array of test IDs)
        $interestMatchedIds = $this->findInterestMatchedTestIds(
            userProfile: $userProfile,
            context: $context,
            limit: $bucketLimit,
        );

        //Second bringing tests that match user education level (match between user education level & test target level , return array of test IDs)
        $targetLevelMatchedIds = $this->findTargetLevelMatchedTestIds(
            userProfile: $userProfile,
            context: $context,
            limit: $bucketLimit,
        );

        //Finally bringing tests that not match user interset & education level (return array of tests IDs)
        $fallbackIds = $this->findFallbackTestIds(
            userProfile: $userProfile,
            context: $context,
            limit: $bucketLimit,
        );

        //Merge Three array of test IDs ordered "interset Ids -> education level Ids -> fallback Ids" & delete repeated test ids
        $mergedIds = $this->mergeUniqueIdsInOrder(
            $interestMatchedIds,
            $targetLevelMatchedIds,
            $fallbackIds,
        );

        //Slice big array of all Ids to candidatePoolLimit number to take them to the next step (ranking & score)
        $candidateIds = array_slice($mergedIds, 0, $candidatePoolLimit);

        //if there are no candidate test ids , return empty array
        if(empty($candidateIds))
        {
            return [];
        }

        $testsRows = DB::table('test')
            ->select([
                'id',
                'creator_user_id',
                'title',
                'description',
                'price',
                'target_level',
                'published_at',
                'participants_count',
                'likes_count',
                'average_rating',
            ])
            ->whereIn('id', $candidateIds)
            ->get()
            ->keyBy('id');                      #keyBy make key of each element in array is id


        //get interest of each test it was brought from table "test_interset_selections"
        $interestRows = DB::table('test_interset_selections')
            ->select([
                'test_id',
                'interest_id',
            ])
            ->whereIn('test_id', $candidateIds)
            ->get();


        //transformation interest row array from :[['test_id' => 1, 'interest_id' => 10] , ['test_id' => 1, 'interest_id' => 20] , ...] TO [ test_id => [interest_id, interest_id, interest_id] ]
        $testInterestMap = [];
        foreach ($interestRows as $row) {
            $testId = (int) $row->test_id;
            $interestId = (int) $row->interest_id;

            $testInterestMap[$testId] ??= [];               #if key not exists in array , create empty array for hem then in second line add element to these empty array
            $testInterestMap[$testId][] = $interestId;
        }

        $userInterestIds = $userProfile->interestIds;
        $allPreferredTargetLevels = $userProfile->allPreferredTargetLevels();

        //It helps in identifying the source ot the test (is it come from Common intersets Or educational level ?) useful for debugging
        $interestBucketLookup = array_fill_keys($interestMatchedIds, true);
        $targetLevelBucketLookup = array_fill_keys($targetLevelMatchedIds, true);

        //Finally but object "TestCandidateData" of each Candidate test id in array
        $candidates = [];
        foreach ($candidateIds as $testId) {

            //get data of each test from candidate test
            $row = $testsRows->get($testId);

            if ($row === null) {
                continue;
            }

            //get interset ids of each test from candidate test
            $interestIds = $testInterestMap[$testId] ?? [] ;

            //find intersect between user & test interest
            $matchedInterestIds = array_values(array_intersect($interestIds, $userInterestIds));

            //Is the target level of the test among the user preferred levels (primary - secondary - broad) ?
            $matchedByTargetLevel = in_array( (string) $row->target_level , $allPreferredTargetLevels, true);

            //Determining the default bucket (we start by assuming the test came from fallback bucket then we check if there is bucket more powerful him)
            $candidateBucket = 'fallback';
            if (isset($interestBucketLookup[$testId])) {
                $candidateBucket = 'interest_match';
            } elseif (isset($targetLevelBucketLookup[$testId])) {
                $candidateBucket = 'target_level_match';
            }

            //Fill candidate array of "TestCandidateData" objects
            $candidates[] = new TestCandidateData(
                id: (int) $row->id,
                creatorUserId: (int) $row->creator_user_id,
                title: (string) $row->title,
                description: $row->description !== null ? (string) $row->description : null,
                price: $row->price !== null ? (float) $row->price : null,
                targetLevel: $row->target_level,
                publishedAt: $row->published_at !== null ? (string) $row->published_at : null,
                participantsCount: (int) ($row->participants_count ?? 0),
                likesCount: (int) ($row->likes_count ?? 0),
                averageRating: (float) ($row->average_rating ?? 0),
                interestIds: $interestIds,
                matchedInterestIds: $matchedInterestIds,
                matchedByTargetLevel: $matchedByTargetLevel,
                candidateBucket: $candidateBucket,
            );
        }

        return $candidates;
    }


    /**
     * هذه الدالة تبني base query المشتركة بين جميع أنواع candidate buckets.
     * القواعد الثابتة هنا:
     * - الاختبار public
     * - الاختبار approved
     * - استبعاد اختبارات المستخدم نفسه
     * - إذا كان التاب free نأخذ المجاني فقط
     */

    private function baseVisibleTestsQuery(UserDiscoveryProfileData $userProfile, DiscoveryContextData $context): Builder
    {
        $query = DB::table('test')
            ->where('test.test_type', TestType::Public->value)
            ->where('test.review_status', TestReviewStatus::Approved->value)
            ->where('test.creator_user_id', '!=', $userProfile->userId);

        if ($context->tab === DiscoveryTab::FREE) {
            $query->whereNull('test.price');
        }

        return $query;
    }


    /**
     * هذه الدالة تجلب ids اختبارات تطابق اهتمامات المستخدم.
     * هنا نستخدم join على test_interset_selections
     * ثم نبحث عن أي تقاطع مع interests الخاصة بالمستخدم.
     */
    private function findInterestMatchedTestIds(UserDiscoveryProfileData $userProfile, DiscoveryContextData $context, int $limit): array {
        if (empty($userProfile->interestIds)) {
            return [];
        }

        $query = $this->baseVisibleTestsQuery($userProfile, $context)
            ->whereExists(function ($subQuery) use ($userProfile) {
                $subQuery->selectRaw('1')
                    ->from('test_interset_selections')
                    ->whereColumn('test_interset_selections.test_id', 'test.id')
                    ->whereIn('test_interset_selections.interest_id', $userProfile->interestIds);
            })
            ->select('test.id');

        $this->applyCandidatePreSort($query, $context);

        return $query
            ->limit($limit)
            ->pluck('test.id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
    }


    /**
     * هذه الدالة تجلب ids اختبارات تطابق target levels المناسبة للمستخدم.
     *
     * نستخدم هنا allPreferredTargetLevels التي جمعناها سابقًا
     * من primary + secondary + broad.
     */
    private function findTargetLevelMatchedTestIds(UserDiscoveryProfileData $userProfile, DiscoveryContextData $context, int $limit): array
    {
        $preferredTargetLevels = $userProfile->allPreferredTargetLevels();

        if (empty($preferredTargetLevels)) {
            return [];
        }

        $query = $this->baseVisibleTestsQuery($userProfile, $context)
            ->whereIn('test.target_level', $preferredTargetLevels)
            ->select('test.id');

        $this->applyCandidatePreSort($query, $context);

        return $query
            ->limit($limit)
            ->pluck('test.id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
    }


    /**
     * هذه الدالة هي fallback bucket.
     * إذا لم يكن عندنا تطابق قوي في الاهتمامات أو target level،
     * نرجع إلى اختبارات عامة approved مناسبة للتاب.
     */
    private function findFallbackTestIds(UserDiscoveryProfileData $userProfile, DiscoveryContextData $context, int $limit): array
    {
        $query = $this->baseVisibleTestsQuery($userProfile, $context)
            ->select('test.id');

        $this->applyCandidatePreSort($query, $context);

        return $query
            ->limit($limit)
            ->pluck('test.id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
    }

    /**
     * هذه الدالة لا تقوم بالـ ranking النهائي،
     * لكنها تعطي pre-sort أولي على مستوى candidate generation
     * الفكرة:
     * - في new نحب الأحدث أولًا
     * - في most_participated نحب الأكثر مشاركة أولًا
     * - في trending/free نستخدم خليطًا أوليًا من الشعبية + الحداثة
     */
    private function applyCandidatePreSort($query, DiscoveryContextData $context): void
    {
        match ($context->tab) {
            DiscoveryTab::NEW => $query
                ->orderByDesc('test.published_at')
                ->orderByDesc('test.id'),

            DiscoveryTab::MOST_PARTICIPATED => $query
                ->orderByDesc('test.participants_count')
                ->orderByDesc('test.average_rating')
                ->orderByDesc('test.published_at'),

            DiscoveryTab::FREE,
            DiscoveryTab::TRENDING => $query
                ->orderByDesc('test.participants_count')
                ->orderByDesc('test.likes_count')
                ->orderByDesc('test.average_rating')
                ->orderByDesc('test.published_at'),
        };
    }

    /**
     * دمج عدة قوائم ids مع الحفاظ على ترتيب أول ظهور
     * وإزالة التكرار.
     */
    private function mergeUniqueIdsInOrder(array ...$groups): array
    {
        $seen = [];
        $merged = [];

        foreach ($groups as $group) {
            foreach ($group as $id) {
                $id = (int) $id;

                if ($id <= 0 || isset($seen[$id])) {
                    continue;
                }

                $seen[$id] = true;
                $merged[] = $id;
            }
        }

        return $merged;
    }
}
