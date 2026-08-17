<?php

namespace Tests\Feature\Library;

use App\Enums\LibraryMaterialContentKind;
use App\Enums\LibraryMaterialReviewStatus;
use App\Enums\TargetLevel;
use App\Enums\VisibilityType;
use App\Models\LibraryMaterial;
use App\Repositories\Library\LibraryMaterialRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LibraryMaterialSearchRepositoryTest extends TestCase
{
    private LibraryMaterialRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'scout.driver' => 'collection',
            'scout.queue' => false,
        ]);

        DB::purge('sqlite');

        Schema::create('library_material', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('creator_user_id');
            $table->string('title');
            $table->string('description');
            $table->string('content_kind');
            $table->string('visibility_type');
            $table->string('target_level');
            $table->string('review_status');
            $table->unsignedInteger('current_approval_version')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('asset_count')->default(0);
            $table->unsignedInteger('like_count')->default(0);
            $table->unsignedInteger('bookmarks_count')->default(0);
            $table->unsignedInteger('download_count')->default(0);
            $table->timestamps();
        });

        Schema::create('library_material_asset', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('library_material_id');
            $table->unsignedInteger('position')->default(1);
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

        $this->repository = app(LibraryMaterialRepository::class);
    }

    public function test_search_filters_and_paginates_inside_the_search_engine(): void
    {
        foreach (range(1, 12) as $position) {
            $this->insertMaterial(
                creatorUserId: 2,
                title: "دليل البرمجة {$position}",
                visibility: VisibilityType::Public,
                reviewStatus: LibraryMaterialReviewStatus::Approved,
            );
        }

        foreach (range(1, 7) as $position) {
            $this->insertMaterial(
                creatorUserId: 1,
                title: "ملاحظات البرمجة الخاصة {$position}",
                visibility: VisibilityType::Private,
                reviewStatus: LibraryMaterialReviewStatus::Approved,
            );
        }

        $this->insertMaterial(
            creatorUserId: 2,
            title: 'محتوى برمجة غير معتمد',
            visibility: VisibilityType::Public,
            reviewStatus: LibraryMaterialReviewStatus::New,
        );

        $firstPage = $this->repository->searchMaterials(
            userId: 1,
            query: 'البرمجة',
            mode: 'all_public',
            perPage: 5,
        );

        $secondPage = $this->repository->searchMaterials(
            userId: 1,
            query: 'البرمجة',
            mode: 'all_public',
            perPage: 5,
            cursor: $firstPage->nextCursor()?->encode(),
        );

        $firstPageIds = collect($firstPage->items())->pluck('id');
        $secondPageIds = collect($secondPage->items())->pluck('id');

        $this->assertCount(5, $firstPage->items());
        $this->assertCount(5, $secondPage->items());
        $this->assertTrue($firstPage->hasMorePages());
        $this->assertNotNull($firstPage->nextCursor());
        $this->assertNotNull($secondPage->previousCursor());
        $this->assertEmpty($firstPageIds->intersect($secondPageIds));

        foreach ([...$firstPage->items(), ...$secondPage->items()] as $material) {
            $this->assertSame(2, (int) $material->creator_user_id);
            $this->assertSame(VisibilityType::Public, $material->visibility_type);
            $this->assertSame(LibraryMaterialReviewStatus::Approved, $material->review_status);
        }

        $ownedFirstPage = $this->repository->searchMaterials(
            userId: 1,
            query: 'البرمجة',
            mode: 'user_owned',
            perPage: 5,
        );

        $ownedSecondPage = $this->repository->searchMaterials(
            userId: 1,
            query: 'البرمجة',
            mode: 'user_owned',
            perPage: 5,
            cursor: $ownedFirstPage->nextCursor()?->encode(),
        );

        $this->assertCount(5, $ownedFirstPage->items());
        $this->assertCount(2, $ownedSecondPage->items());
        $this->assertFalse($ownedSecondPage->hasMorePages());
    }

    public function test_search_uses_title_only_and_searchable_data_contains_filter_fields(): void
    {
        $materialId = $this->insertMaterial(
            creatorUserId: 2,
            title: 'دليل قواعد البيانات',
            description: 'مصطلحوصفي لا يوجد في العنوان',
            visibility: VisibilityType::Public,
            reviewStatus: LibraryMaterialReviewStatus::Approved,
            likeCount: 17,
        );

        DB::table('interests')->insert([
            ['id' => 10, 'name' => 'برمجة'],
            ['id' => 11, 'name' => 'قواعد بيانات'],
        ]);

        DB::table('library_material_interest_selections')->insert([
            [
                'library_material_id' => $materialId,
                'interest_id' => 10,
                'slot_no' => 1,
            ],
            [
                'library_material_id' => $materialId,
                'interest_id' => 11,
                'slot_no' => 2,
            ],
        ]);

        $descriptionSearch = $this->repository->searchMaterials(
            userId: 1,
            query: 'مصطلحوصفي',
            mode: 'all_public',
            perPage: 5,
        );

        $this->assertSame([], $descriptionSearch->items());

        $searchableData = LibraryMaterial::query()->findOrFail($materialId)->toSearchableArray();

        $this->assertArrayNotHasKey('description', $searchableData);
        $this->assertSame([10, 11], $searchableData['interest_ids']);
        $this->assertSame(17, $searchableData['like_count']);
    }

    private function insertMaterial(
        int $creatorUserId,
        string $title,
        VisibilityType $visibility,
        LibraryMaterialReviewStatus $reviewStatus,
        string $description = 'وصف تعليمي عام',
        int $likeCount = 0,
    ): int {
        return DB::table('library_material')->insertGetId([
            'creator_user_id' => $creatorUserId,
            'title' => $title,
            'description' => $description,
            'content_kind' => LibraryMaterialContentKind::File->value,
            'visibility_type' => $visibility->value,
            'target_level' => TargetLevel::GENERAL_INFO->value,
            'review_status' => $reviewStatus->value,
            'current_approval_version' => $reviewStatus === LibraryMaterialReviewStatus::Approved ? 1 : 0,
            'published_at' => $reviewStatus === LibraryMaterialReviewStatus::Approved ? now() : null,
            'asset_count' => 0,
            'like_count' => $likeCount,
            'bookmarks_count' => 0,
            'download_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
