<?php

namespace App\Http\Resources;

use App\Enums\TestReviewStatus;
use App\Helpers\DateProcessor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TestStatusHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        match ($this->to_status)
        {
            TestReviewStatus::Reported->value, TestReviewStatus::Deleted->value => $note = $this->note,
            TestReviewStatus::New->value => $note = 'هذا الاختبار في حالة مسودة الان ولن يظهر للعامة الا ان يتم مراجعة محتواه العلمي من قبل مركز الاشراف',
            TestReviewStatus::UnderReview->value => $note = 'الاختبار في حالة مراجعة من قبل مركز الاشراف الخاص بالتطبيق للتأكد من انك قمت بالتعديلات اللازمة وفي حال صحة التعديلات سيتم نشر الاختبار للعامة',
            TestReviewStatus::Approved->value => $note = 'تم الموافقة على نشر اختبارك للعامة واصبح بإمكان الجميع رؤيته والاطلاع عليه',
            TestReviewStatus::NeedsRevision->value => $note = 'الاختبار الخاص بك يحتاج الى تعديل محتواه وفقا للتعليمات الذي طلبها مركز الاشراف',
            default => $note = null
        };

        return [
            'history_id' => $this->id,
            'round_id' => $this->test_review_round_id,
            'status' => [
                'from_status' => $this->from_status,
                'to_status' => $this->to_status ?? "ليس للاختبار حالة ينتقل اليها لانه في حالته الابتدائية",
            ],

            'description' => $note,
            'happened_at' => DateProcessor::fromTimestamp($this->created_at),
        ];
    }
}
