<?php

namespace Tests\Feature\Admin;

use App\Enums\LibraryMaterialContentKind;
use App\Enums\LibraryMaterialReviewStatus;
use App\Enums\VisibilityType;
use App\Http\Requests\Admin\ListDashboardLibraryMaterialsRequest;
use App\Repositories\Admin\LibraryDashboardRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class LibraryDashboardRepositoryTest extends TestCase
{
    private LibraryDashboardRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);

        DB::purge('sqlite');

        Schema::create('library_material', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('creator_user_id');
            $table->string('title');
            $table->string('description');
            $table->string('content_kind');
            $table->string('visibility_type');
            $table->string('review_status');
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('like_count')->default(0);
            $table->timestamps();
        });

        Schema::create('library_material_asset', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('library_material_id');
            $table->string('storage_path');
            $table->unsignedInteger('position');
        });

        Schema::create('interests', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
        });

        Schema::create('library_material_interest_selections', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('library_material_id');
            $table->unsignedBigInteger('interest_id');
            $table->unsignedInteger('slot_no');
        });

        $this->repository = app(LibraryDashboardRepository::class);
    }

    public function test_request_accepts_status_sort_options(): void
    {
        $rules = app(ListDashboardLibraryMaterialsRequest::class)->rules();

        foreach (['new', 'approved', 'reported'] as $sortBy) {
            $validator = Validator::make(['sort_by' => $sortBy], $rules);

            $this->assertFalse($validator->fails(), "The sort_by value [{$sortBy}] was rejected.");
        }
    }

    /**
     * @dataProvider statusSortProvider
     */
    public function test_status_sort_filters_public_materials_by_requested_status(
        string $sortBy,
        LibraryMaterialReviewStatus $status
    ): void {
        $olderId = $this->insertMaterial($status, VisibilityType::Public, '2026-06-20 10:00:00');
        $newerId = $this->insertMaterial($status, VisibilityType::Public, '2026-06-21 10:00:00');

        $this->insertMaterial($status, VisibilityType::Private, '2026-06-22 10:00:00');
        $this->insertMaterial($this->differentStatus($status), VisibilityType::Public, '2026-06-23 10:00:00');

        $materials = $this->repository
            ->paginateApprovedMaterials($sortBy, 20)
            ->getCollection();

        $this->assertSame([$newerId, $olderId], $materials->pluck('id')->all());
    }

    public static function statusSortProvider(): array
    {
        return [
            'new' => ['new', LibraryMaterialReviewStatus::New],
            'approved' => ['approved', LibraryMaterialReviewStatus::Approved],
            'reported' => ['reported', LibraryMaterialReviewStatus::Reported],
        ];
    }

    public function test_existing_sort_options_remain_limited_to_public_approved_materials(): void
    {
        $lessLikedApprovedId = $this->insertMaterial(
            LibraryMaterialReviewStatus::Approved,
            VisibilityType::Public,
            '2026-06-20 10:00:00',
            5
        );
        $mostLikedApprovedId = $this->insertMaterial(
            LibraryMaterialReviewStatus::Approved,
            VisibilityType::Public,
            '2026-06-19 10:00:00',
            10
        );

        $this->insertMaterial(LibraryMaterialReviewStatus::New, VisibilityType::Public, '2026-06-22 10:00:00', 100);
        $this->insertMaterial(LibraryMaterialReviewStatus::Reported, VisibilityType::Public, '2026-06-23 10:00:00', 100);
        $this->insertMaterial(LibraryMaterialReviewStatus::Approved, VisibilityType::Private, '2026-06-24 10:00:00', 100);

        $materials = $this->repository
            ->paginateApprovedMaterials('most_liked', 20)
            ->getCollection();

        $this->assertSame(
            [$mostLikedApprovedId, $lessLikedApprovedId],
            $materials->pluck('id')->all()
        );
    }

    private function insertMaterial(
        LibraryMaterialReviewStatus $status,
        VisibilityType $visibility,
        string $createdAt,
        int $likeCount = 0
    ): int {
        return DB::table('library_material')->insertGetId([
            'creator_user_id' => 1,
            'title' => "Material {$status->name} {$createdAt}",
            'description' => 'Description',
            'content_kind' => LibraryMaterialContentKind::File->value,
            'visibility_type' => $visibility->value,
            'review_status' => $status->value,
            'published_at' => $status === LibraryMaterialReviewStatus::Approved ? $createdAt : null,
            'like_count' => $likeCount,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    private function differentStatus(LibraryMaterialReviewStatus $status): LibraryMaterialReviewStatus
    {
        return $status === LibraryMaterialReviewStatus::Approved
            ? LibraryMaterialReviewStatus::New
            : LibraryMaterialReviewStatus::Approved;
    }
}
