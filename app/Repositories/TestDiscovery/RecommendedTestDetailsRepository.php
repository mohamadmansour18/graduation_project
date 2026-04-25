<?php

namespace App\Repositories\TestDiscovery;

use Illuminate\Support\Facades\DB;

final class RecommendedTestDetailsRepository
{
    /**
     * وظيفته:
     * - يجلب بيانات الاختبار اللازمة للواجهة
     * - يجلب بيانات صاحب الاختبار
     * - يجلب الإحصائيات السريعة من user_profile_stats
     * - يجلب الاهتمامات العلمية الخاصة بكل اختبار
     * - يجلب حالة التوثيق الأكاديمي لصاحب الحساب
     *
     * مهم جدًا:
     * هذا الكلاس لا يحسب recommendation score
     * ولا يقوم بالـ ranking،
     * بل فقط يجهز البيانات النهائية المطلوبة للواجهة.
     */

    public function findDisplayDataByTestIds(array $testIds): array
    {
        if (empty($testIds)) {
            return [];
        }

        $rows = DB::table('test')
            ->join('users', 'users.id', '=', 'test.creator_user_id')
            ->leftJoin('user_profile_stats', 'user_profile_stats.user_id', '=', 'users.id')
            ->select([
                'test.id',
                'test.title',
                'test.description',
                'test.target_level',
                'test.question_count',
                'test.average_rating',
                'test.price',
                'test.published_at',
                'users.name as owner_name',
                DB::raw('COALESCE(user_profile_stats.published_tests_count, 0) as owner_published_tests_count'),
                DB::raw('COALESCE(user_profile_stats.followers_count, 0) as owner_followers_count'),
                DB::raw('COALESCE(users.is_academically_verified, 0) as is_owner_verified'),
            ])
            ->whereIn('test.id', $testIds)
            ->get()
            ->keyBy('id');

        $interestRows = DB::table('test_interset_selections')
            ->join('interests', 'interests.id', '=', 'test_interset_selections.interest_id')
            ->select([
                'test_interset_selections.test_id',
                'interests.name as interest_name',
                'test_interset_selections.slot_no',
            ])
            ->whereIn('test_interset_selections.test_id', $testIds)
            ->orderByRaw('CASE WHEN test_interset_selections.slot_no IS NULL THEN 999 ELSE test_interset_selections.slot_no END')
            ->orderBy('test_interset_selections.id')
            ->get();

        $interestMap = [];

        foreach ($interestRows as $row) {
            $testId = (int) $row->test_id;

            $interestMap[$testId] ??= [];
            $interestMap[$testId][] = (string) $row->interest_name;
        }

        $result = [];

        foreach ($testIds as $testId) {
            $row = $rows->get($testId);

            if ($row === null) {
                continue;
            }

            $result[$testId] = [
                'test_id' => (int) $row->id,
                'owner_name' => (string) $row->owner_name,
                'owner_published_tests_count' => (int) $row->owner_published_tests_count,
                'owner_followers_count' => (int) $row->owner_followers_count,
                'is_owner_verified' => (bool) $row->is_owner_verified,

                'test_title' => (string) $row->title,
                'test_description' => (string) $row->description,
                'interest_names' => $interestMap[$testId] ?? [],
                'target_level' => (string) $row->target_level,
                'question_count' => (int) ($row->question_count ?? 0),
                'average_rating' => (float) ($row->average_rating ?? 0),
                'price' => $row->price !== null ? (float) $row->price : "0 ليرة سورية",
                'published_at' => (string) $row->published_at ,
            ];
        }

        return $result;
    }
}
