<?php

declare(strict_types=1);

$root = dirname(__DIR__);

$enumFiles = [
    'app/Enums/BanType.php' => <<<'PHP'
<?php

namespace App\Enums;

enum BanType: string
{
    case Temporary = 'temporary';
    case Permanent = 'permanent';
}
PHP,
    'app/Enums/TestType.php' => <<<'PHP'
<?php

namespace App\Enums;

enum TestType: string
{
    case Public = 'public';
    case Private = 'private';
}
PHP,
    'app/Enums/TestReviewStatus.php' => <<<'PHP'
<?php

namespace App\Enums;

enum TestReviewStatus: string
{
    case New = 'new';
    case NeedsRevision = 'needs_revision';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Deleted = 'deleted';
    case Reported = 'reported';
}
PHP,
    'app/Enums/LibraryMaterialContentKind.php' => <<<'PHP'
<?php

namespace App\Enums;

enum LibraryMaterialContentKind: string
{
    case File = 'file';
    case ImageGroup = 'image_group';
}
PHP,
    'app/Enums/VisibilityType.php' => <<<'PHP'
<?php

namespace App\Enums;

enum VisibilityType: string
{
    case Public = 'public';
    case Private = 'private';
}
PHP,
    'app/Enums/LibraryMaterialReviewStatus.php' => <<<'PHP'
<?php

namespace App\Enums;

enum LibraryMaterialReviewStatus: string
{
    case New = 'new';
    case Approved = 'approved';
    case Deleted = 'deleted';
    case Reported = 'reported';
}
PHP,
    'app/Enums/StudyTaskStatus.php' => <<<'PHP'
<?php

namespace App\Enums;

enum StudyTaskStatus: string
{
    case Todo = 'todo';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Missed = 'missed';
}
PHP,
    'app/Enums/StudyTaskRepeatPattern.php' => <<<'PHP'
<?php

namespace App\Enums;

enum StudyTaskRepeatPattern: string
{
    case None = 'none';
    case Weekly1 = 'weekly_1';
    case Weekly2 = 'weekly_2';
    case Weekly3 = 'weekly_3';
    case Weekly4 = 'weekly_4';
}
PHP,
];

$migrationFiles = [
    'database/migrations/2026_04_12_140000_create_auth_domain_tables.php' => <<<'PHP'
<?php

use App\Enums\BanType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('interest_categories', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->timestamps();
        });

        Schema::create('interests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('interest_category_id')->constrained('interest_categories')->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
            $table->unique(['interest_category_id', 'name']);
        });

        Schema::create('auth_otp_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('purpose');
            $table->string('code_hash');
            $table->string('send_to_email');
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->unsignedInteger('attempts_count')->default(0);
            $table->timestamps();
            $table->index(['user_id', 'purpose']);
        });

        Schema::create('user_bans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('imposed_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('ban_type', array_column(BanType::cases(), 'value'));
            $table->text('reason')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->foreignId('lifted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('lifted_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'ban_type']);
        });

        Schema::create('user_onboarding_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('discovery_source');
            $table->string('governorate');
            $table->string('education_level');
            $table->unsignedTinyInteger('last_completed_step')->nullable();
            $table->timestamps();
        });

        Schema::create('user_university_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('university_name');
            $table->unsignedTinyInteger('university_year');
            $table->timestamps();
        });

        Schema::create('user_school_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('school_stage');
            $table->timestamps();
        });

        Schema::create('user_interest_selections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('interest_id')->constrained('interests')->cascadeOnDelete();
            $table->unsignedTinyInteger('slot_no');
            $table->timestamps();
            $table->unique(['user_id', 'interest_id']);
            $table->unique(['user_id', 'slot_no']);
        });

        Schema::create('user_stats_summary', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year')->unique();
            $table->unsignedInteger('total_completed_mobile_users')->default(0);
            $table->unsignedInteger('male_completed_mobile_users')->default(0);
            $table->unsignedInteger('female_completed_mobile_users')->default(0);
            $table->timestamps();
        });

        Schema::create('user_stats_by_discovery_source', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->string('discovery_source');
            $table->unsignedInteger('completed_mobile_users_count')->default(0);
            $table->timestamps();
            $table->unique(['year', 'discovery_source'], 'user_stats_by_discovery_source_year_source_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_stats_by_discovery_source');
        Schema::dropIfExists('user_stats_summary');
        Schema::dropIfExists('user_interest_selections');
        Schema::dropIfExists('user_school_profiles');
        Schema::dropIfExists('user_university_profiles');
        Schema::dropIfExists('user_onboarding_profiles');
        Schema::dropIfExists('user_bans');
        Schema::dropIfExists('auth_otp_codes');
        Schema::dropIfExists('interests');
        Schema::dropIfExists('interest_categories');
        Schema::dropIfExists('roles');
    }
};
PHP,
    'database/migrations/2026_04_12_140100_create_tests_domain_tables.php' => <<<'PHP'
<?php

use App\Enums\TestReviewStatus;
use App\Enums\TestType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('test_type', array_column(TestType::cases(), 'value'));
            $table->string('difficulty_level');
            $table->unsignedInteger('duration_seconds');
            $table->unsignedTinyInteger('pass_mark_percentage');
            $table->string('language');
            $table->decimal('price', 12, 2)->nullable();
            $table->string('target_level');
            $table->enum('review_status', array_column(TestReviewStatus::cases(), 'value'))->nullable();
            $table->unsignedInteger('current_approval_version')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('last_content_updated_at')->nullable();
            $table->unsignedInteger('question_count')->default(0);
            $table->unsignedInteger('preview_question_count')->default(0);
            $table->unsignedInteger('likes_count')->default(0);
            $table->unsignedInteger('bookmarks_count')->default(0);
            $table->unsignedInteger('downloads_count')->default(0);
            $table->unsignedInteger('reviews_count')->default(0);
            $table->unsignedInteger('participants_count')->default(0);
            $table->decimal('average_rating', 4, 2)->default(0);
            $table->timestamps();
            $table->index(['creator_user_id', 'test_type']);
            $table->index(['review_status', 'published_at']);
        });

        Schema::create('test_interset_selections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained('test')->cascadeOnDelete();
            $table->foreignId('interest_id')->constrained('interests')->cascadeOnDelete();
            $table->unsignedTinyInteger('slot_no');
            $table->timestamps();
            $table->unique(['test_id', 'interest_id']);
            $table->unique(['test_id', 'slot_no']);
        });

        Schema::create('test_question', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained('test')->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->text('question_text');
            $table->text('hint_text')->nullable();
            $table->boolean('is_preview')->default(false);
            $table->unsignedTinyInteger('options_count')->default(0);
            $table->timestamps();
            $table->unique(['test_id', 'position']);
        });

        Schema::create('test_question_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_question_id')->constrained('test_question')->cascadeOnDelete();
            $table->unsignedTinyInteger('position');
            $table->text('option_text');
            $table->boolean('is_correct')->default(false);
            $table->timestamps();
            $table->unique(['test_question_id', 'position']);
        });

        Schema::create('test_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained('test')->cascadeOnDelete();
            $table->foreignId('buyer_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('seller_user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('gross_amount', 12, 2);
            $table->decimal('platform_fee_amount', 12, 2);
            $table->decimal('seller_net_amount', 12, 2);
            $table->string('currency', 10);
            $table->string('payment_provider');
            $table->string('payment_reference')->nullable();
            $table->string('payment_status');
            $table->timestamp('purchased_at');
            $table->timestamps();
            $table->index(['buyer_user_id', 'payment_status']);
            $table->index(['seller_user_id', 'payment_status']);
        });

        Schema::create('test_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained('test')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('mode');
            $table->timestamps();
        });

        Schema::create('test_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained('test')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('reason');
            $table->text('description')->nullable();
            $table->timestamp('reported_at');
            $table->timestamps();
            $table->index(['test_id', 'reason']);
        });

        Schema::create('test_review_rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained('test')->cascadeOnDelete();
            $table->unsignedInteger('round_no');
            $table->foreignId('reviewer_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('trigger_type');
            $table->string('decision');
            $table->unsignedInteger('based_on_approval_version')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
            $table->unique(['test_id', 'round_no']);
        });

        Schema::create('test_revision_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_review_round_id')->constrained('test_review_rounds')->cascadeOnDelete();
            $table->foreignId('test_id')->constrained('test')->cascadeOnDelete();
            $table->string('revision_type');
            $table->foreignId('target_question_id')->nullable()->constrained('test_question')->nullOnDelete();
            $table->string('decision');
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->text('problem_note')->nullable();
            $table->timestamps();
        });

        Schema::create('test_revision_change_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_review_round_id')->constrained('test_review_rounds')->cascadeOnDelete();
            $table->foreignId('test_id')->constrained('test')->cascadeOnDelete();
            $table->foreignId('revision_request_id')->nullable()->constrained('test_revision_requests')->nullOnDelete();
            $table->string('revision_type');
            $table->longText('before_value')->nullable();
            $table->longText('after_value')->nullable();
            $table->foreignId('changed_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('test_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained('test')->cascadeOnDelete();
            $table->enum('from_status', array_column(TestReviewStatus::cases(), 'value'))->nullable();
            $table->enum('to_status', array_column(TestReviewStatus::cases(), 'value'));
            $table->foreignId('changed_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('test_bookmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained('test')->cascadeOnDelete();
            $table->foreignId('use_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['test_id', 'use_id']);
        });

        Schema::create('test_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained('test')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['test_id', 'user_id']);
        });

        Schema::create('test_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained('test')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('review_text')->nullable();
            $table->unsignedInteger('helpful_yes_count')->default(0);
            $table->unsignedInteger('helpful_no_count')->default(0);
            $table->timestamps();
            $table->unique(['test_id', 'user_id']);
        });

        Schema::create('test_review_feedbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_review_id')->constrained('test_reviews')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('vote');
            $table->timestamps();
            $table->unique(['test_review_id', 'user_id']);
        });

        Schema::create('test_download_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained('test')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('downloadd_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_download_logs');
        Schema::dropIfExists('test_review_feedbacks');
        Schema::dropIfExists('test_reviews');
        Schema::dropIfExists('test_likes');
        Schema::dropIfExists('test_bookmarks');
        Schema::dropIfExists('test_status_histories');
        Schema::dropIfExists('test_revision_change_logs');
        Schema::dropIfExists('test_revision_requests');
        Schema::dropIfExists('test_review_rounds');
        Schema::dropIfExists('test_reports');
        Schema::dropIfExists('test_attempts');
        Schema::dropIfExists('test_purchases');
        Schema::dropIfExists('test_question_options');
        Schema::dropIfExists('test_question');
        Schema::dropIfExists('test_interset_selections');
        Schema::dropIfExists('test');
    }
};
PHP,
    'database/migrations/2026_04_12_140200_create_library_material_domain_tables.php' => <<<'PHP'
<?php

use App\Enums\LibraryMaterialContentKind;
use App\Enums\LibraryMaterialReviewStatus;
use App\Enums\VisibilityType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library_material', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('imposed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('content_kind', array_column(LibraryMaterialContentKind::cases(), 'value'));
            $table->enum('visibility_type', array_column(VisibilityType::cases(), 'value'));
            $table->string('target_level');
            $table->enum('review_status', array_column(LibraryMaterialReviewStatus::cases(), 'value'))->nullable();
            $table->unsignedInteger('current_approval_version')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('asset_count')->default(0);
            $table->unsignedInteger('like_count')->default(0);
            $table->unsignedInteger('bookmarks_count')->default(0);
            $table->unsignedInteger('download_count')->default(0);
            $table->timestamps();
            $table->index(['creator_user_id', 'visibility_type']);
            $table->index(['review_status', 'published_at']);
        });

        Schema::create('library_material_asset', function (Blueprint $table) {
            $table->id();
            $table->foreignId('library_material_id')->constrained('library_material')->cascadeOnDelete();
            $table->string('asset_type');
            $table->string('storage_disk');
            $table->string('storage_path');
            $table->string('original_name');
            $table->string('mime_type');
            $table->unsignedTinyInteger('position')->default(1);
            $table->timestamps();
            $table->unique(['library_material_id', 'position']);
        });

        Schema::create('library_material_interest_selections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('library_material_id')->constrained('library_material')->cascadeOnDelete();
            $table->foreignId('interest_id')->constrained('interests')->cascadeOnDelete();
            $table->unsignedTinyInteger('slot_no');
            $table->timestamps();
            $table->unique(['library_material_id', 'interest_id'], 'library_material_interest_selection_unique');
            $table->unique(['library_material_id', 'slot_no'], 'library_material_interest_slot_unique');
        });

        Schema::create('library_material_review_rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('library_material_id')->constrained('library_material')->cascadeOnDelete();
            $table->unsignedInteger('round_no');
            $table->foreignId('reviewer_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('trigger_type');
            $table->string('decision');
            $table->unsignedInteger('based_on_approval_version')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
            $table->unique(['library_material_id', 'round_no'], 'library_material_round_unique');
        });

        Schema::create('library_material_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('library_material_id')->constrained('library_material')->cascadeOnDelete();
            $table->enum('from_status', array_column(LibraryMaterialReviewStatus::cases(), 'value'))->nullable();
            $table->enum('to_status', array_column(LibraryMaterialReviewStatus::cases(), 'value'));
            $table->foreignId('changed_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('library_material_bookmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('library_material_id')->constrained('library_material')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['library_material_id', 'user_id'], 'library_material_bookmark_unique');
        });

        Schema::create('library_material_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('library_material_id')->constrained('library_material')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['library_material_id', 'user_id'], 'library_material_like_unique');
        });

        Schema::create('library_material_download_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('library_material_id')->constrained('library_material')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('library_material_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('library_material_id')->constrained('library_material')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('approval_version')->default(0);
            $table->string('reason');
            $table->text('description')->nullable();
            $table->timestamp('reported_at');
            $table->timestamps();
            $table->index(['library_material_id', 'approval_version']);
        });

        Schema::create('library_material_report_reason_counters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('library_material_id')->constrained('library_material')->cascadeOnDelete();
            $table->unsignedInteger('approval_version')->default(0);
            $table->string('reason');
            $table->unsignedInteger('reporters_count')->default(0);
            $table->timestamps();
            $table->unique(
                ['library_material_id', 'approval_version', 'reason'],
                'library_material_report_reason_counter_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_material_report_reason_counters');
        Schema::dropIfExists('library_material_reports');
        Schema::dropIfExists('library_material_download_logs');
        Schema::dropIfExists('library_material_likes');
        Schema::dropIfExists('library_material_bookmarks');
        Schema::dropIfExists('library_material_status_histories');
        Schema::dropIfExists('library_material_review_rounds');
        Schema::dropIfExists('library_material_interest_selections');
        Schema::dropIfExists('library_material_asset');
        Schema::dropIfExists('library_material');
    }
};
PHP,
    'database/migrations/2026_04_12_140300_create_test_folder_domain_tables.php' => <<<'PHP'
<?php

use App\Enums\TestType;
use App\Enums\VisibilityType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_folder', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('color_code', 20);
            $table->enum('visibility_type', array_column(VisibilityType::cases(), 'value'));
            $table->enum('contained_test_type', array_column(TestType::cases(), 'value'));
            $table->unsignedInteger('tests_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('test_folder_bookmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_folder_id')->constrained('test_folder')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['test_folder_id', 'user_id']);
        });

        Schema::create('test_folder_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_folder_id')->constrained('test_folder')->cascadeOnDelete();
            $table->foreignId('test_id')->constrained('test')->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->timestamps();
            $table->unique(['test_folder_id', 'test_id']);
            $table->unique(['test_folder_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_folder_item');
        Schema::dropIfExists('test_folder_bookmarks');
        Schema::dropIfExists('test_folder');
    }
};
PHP,
    'database/migrations/2026_04_12_140400_create_study_plan_domain_tables.php' => <<<'PHP'
<?php

use App\Enums\StudyTaskRepeatPattern;
use App\Enums\StudyTaskStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_subject', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
            $table->unique(['user_id', 'name']);
        });

        Schema::create('study_plan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('emoji', 20)->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedSmallInteger('daily_study_minutes');
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('subjects_count')->default(0);
            $table->unsignedInteger('tasks_count')->default(0);
            $table->unsignedInteger('completed_tasks_count')->default(0);
            $table->unsignedInteger('missed_tasks_count')->default(0);
            $table->unsignedInteger('pending_tasks_count')->default(0);
            $table->timestamps();
        });

        Schema::create('study_plan_subject', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_plan_id')->constrained('study_plan')->cascadeOnDelete();
            $table->foreignId('study_subject_id')->constrained('study_subject')->cascadeOnDelete();
            $table->unsignedTinyInteger('slot_no');
            $table->timestamps();
            $table->unique(['study_plan_id', 'study_subject_id']);
            $table->unique(['study_plan_id', 'slot_no']);
        });

        Schema::create('study_task', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_plan_id')->constrained('study_plan')->cascadeOnDelete();
            $table->foreignId('study_plan_subject_id')->constrained('study_plan_subject')->cascadeOnDelete();
            $table->uuid('task_group_uuid')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->time('start_time');
            $table->unsignedSmallInteger('duration_minutes_per_day');
            $table->timestamp('deadline_at')->nullable();
            $table->integer('reminder_offset_minutes')->nullable();
            $table->string('priority');
            $table->enum('status', array_column(StudyTaskStatus::cases(), 'value'))->default(StudyTaskStatus::Todo->value);
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('missed_at')->nullable();
            $table->enum('repeat_pattern', array_column(StudyTaskRepeatPattern::cases(), 'value'))
                ->default(StudyTaskRepeatPattern::None->value);
            $table->date('recurrence_end_date')->nullable();
            $table->timestamps();
            $table->index(['study_plan_id', 'status']);
            $table->index('task_group_uuid');
        });

        Schema::create('study_task_occurrence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_task_id')->constrained('study_task')->cascadeOnDelete();
            $table->foreignId('study_plan_id')->constrained('study_plan')->cascadeOnDelete();
            $table->date('occurrence_date');
            $table->time('scheduled_start_time');
            $table->time('scheduled_end_time');
            $table->unsignedSmallInteger('duration_minutes');
            $table->timestamps();
            $table->index(['study_plan_id', 'occurrence_date']);
        });

        Schema::create('study_task_subtask', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_task_id')->constrained('study_task')->cascadeOnDelete();
            $table->string('title');
            $table->unsignedInteger('position');
            $table->boolean('is_completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['study_task_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_task_subtask');
        Schema::dropIfExists('study_task_occurrence');
        Schema::dropIfExists('study_task');
        Schema::dropIfExists('study_plan_subject');
        Schema::dropIfExists('study_plan');
        Schema::dropIfExists('study_subject');
    }
};
PHP,
    'database/migrations/2026_04_12_140500_create_settings_domain_tables.php' => <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->boolean('task_reminders_enabled')->default(true);
            $table->string('week_starts_on');
            $table->string('time_format');
            $table->string('theme_mode');
            $table->timestamps();
        });

        Schema::create('user_academic_verification_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status');
            $table->timestamp('submitted_at');
            $table->foreignId('reviewer_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });

        Schema::create('user_academic_verification_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('verification_request_id')
                ->constrained('user_academic_verification_requests')
                ->cascadeOnDelete();
            $table->string('asset_type');
            $table->string('storage_disk');
            $table->string('storage_path');
            $table->string('original_name');
            $table->string('mime_type');
            $table->unsignedBigInteger('file_size_bytes');
            $table->timestamps();
        });

        Schema::create('user_yearly_study_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('total_tasks_count')->default(0);
            $table->unsignedInteger('todo_tasks_count')->default(0);
            $table->unsignedInteger('in_progress_tasks_count')->default(0);
            $table->unsignedInteger('completed_tasks_count')->default(0);
            $table->unsignedInteger('missed_tasks_count')->default(0);
            $table->timestamps();
            $table->unique(['user_id', 'year']);
        });

        Schema::create('user_yearly_study_plan_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('study_plan_id')->constrained('study_plan')->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('total_tasks_count')->default(0);
            $table->unsignedInteger('todo_tasks_count')->default(0);
            $table->unsignedInteger('in_progress_tasks_count')->default(0);
            $table->unsignedInteger('completed_tasks_count')->default(0);
            $table->unsignedInteger('missed_tasks_count')->default(0);
            $table->timestamps();
            $table->unique(['study_plan_id', 'year']);
        });

        Schema::create('user_yearly_test_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('total_likes_received')->default(0);
            $table->unsignedInteger('total_reviews_received')->default(0);
            $table->unsignedInteger('total_bookmarks_received')->default(0);
            $table->unsignedInteger('published_tests_count')->default(0);
            $table->timestamp('first_published_test_at')->nullable();
            $table->timestamp('last_published_test_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'year']);
        });

        Schema::create('user_yearly_test_publish_month_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month_no');
            $table->unsignedInteger('published_tests_count')->default(0);
            $table->timestamps();
            $table->unique(['user_id', 'year', 'month_no'], 'user_yearly_test_publish_month_stats_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_yearly_test_publish_month_stats');
        Schema::dropIfExists('user_yearly_test_stats');
        Schema::dropIfExists('user_yearly_study_plan_stats');
        Schema::dropIfExists('user_yearly_study_stats');
        Schema::dropIfExists('user_academic_verification_assets');
        Schema::dropIfExists('user_academic_verification_requests');
        Schema::dropIfExists('user_settings');
    }
};
PHP,
    'database/migrations/2026_04_12_140600_create_profile_domain_tables.php' => <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_profile', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('phone')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('avatar_disk')->nullable();
            $table->string('avatar_path')->nullable();
            $table->string('cover_disk')->nullable();
            $table->string('cover_path')->nullable();
            $table->string('profile_slug')->unique();
            $table->timestamps();
        });

        Schema::create('user_follows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('follower_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('followed_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['follower_user_id', 'followed_user_id']);
        });

        Schema::create('user_profile_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('followers_count')->default(0);
            $table->unsignedInteger('following_count')->default(0);
            $table->unsignedInteger('published_tests_count')->default(0);
            $table->unsignedInteger('library_materials_count')->default(0);
            $table->unsignedInteger('folders_count')->default(0);
            $table->decimal('average_test_rating', 4, 2)->default(0);
            $table->unsignedInteger('total_test_likes_received')->default(0);
            $table->unsignedInteger('total_test_reviews_received')->default(0);
            $table->unsignedInteger('total_test_bookmarks_received')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_profile_stats');
        Schema::dropIfExists('user_follows');
        Schema::dropIfExists('user_profile');
    }
};
PHP,
    'database/migrations/2026_04_12_140700_create_admin_statistics_domain_tables.php' => <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_yearly_financial_stats', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year')->unique();
            $table->unsignedInteger('sold_purchase_count')->default(0);
            $table->unsignedInteger('distinct_sold_tests_count')->default(0);
            $table->decimal('gross_sales_amount', 12, 2)->default(0);
            $table->decimal('users_profit_amount', 12, 2)->default(0);
            $table->decimal('platform_net_profit_amount', 12, 2)->default(0);
            $table->decimal('average_monthly_sales_amount', 12, 2)->default(0);
            $table->decimal('average_monthly_platform_profit_amount', 12, 2)->default(0);
            $table->foreignId('most_purchased_test_id')->nullable()->constrained('test')->nullOnDelete();
            $table->unsignedInteger('most_purchased_test_purchase_count')->default(0);
            $table->timestamps();
        });

        Schema::create('admin_yearly_financial_month_stats', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month_no');
            $table->unsignedInteger('sold_purchase_count')->default(0);
            $table->unsignedInteger('distinct_sold_tests_count')->default(0);
            $table->decimal('gross_sales_amount', 12, 2)->default(0);
            $table->decimal('users_profit_amount', 12, 2)->default(0);
            $table->decimal('platform_net_profit_amount', 12, 2)->default(0);
            $table->timestamps();
            $table->unique(['year', 'month_no']);
        });

        Schema::create('admin_yearly_test_sales_stats', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->foreignId('test_id')->constrained('test')->cascadeOnDelete();
            $table->unsignedInteger('purchase_count')->default(0);
            $table->decimal('gross_sales_amount', 12, 2)->default(0);
            $table->decimal('users_profit_amount', 12, 2)->default(0);
            $table->decimal('platform_net_profit_amount', 12, 2)->default(0);
            $table->timestamps();
            $table->unique(['year', 'test_id']);
        });

        Schema::create('admin_yearly_test_activity_month_stats', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month_no');
            $table->unsignedInteger('published_tests_count')->default(0);
            $table->unsignedInteger('likes_count')->default(0);
            $table->unsignedInteger('reviews_count')->default(0);
            $table->unsignedInteger('downloads_count')->default(0);
            $table->timestamps();
            $table->unique(['year', 'month_no']);
        });

        Schema::create('admin_yearly_library_material_activity_month_stats', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month_no');
            $table->unsignedInteger('published_materials_count')->default(0);
            $table->unsignedInteger('likes_count')->default(0);
            $table->timestamps();
            $table->unique(['year', 'month_no'], 'admin_yearly_library_material_activity_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_yearly_library_material_activity_month_stats');
        Schema::dropIfExists('admin_yearly_test_activity_month_stats');
        Schema::dropIfExists('admin_yearly_test_sales_stats');
        Schema::dropIfExists('admin_yearly_financial_month_stats');
        Schema::dropIfExists('admin_yearly_financial_stats');
    }
};
PHP,
];

$models = [
    [
        'class' => 'SystemRole',
        'table' => 'roles',
        'fillable' => ['name'],
        'casts' => [],
        'relations' => [
            ['type' => 'hasMany', 'name' => 'users', 'related' => 'User', 'foreignKey' => 'role_id'],
        ],
    ],
    [
        'class' => 'AuthOtpCode',
        'table' => 'auth_otp_codes',
        'fillable' => ['user_id', 'purpose', 'code_hash', 'send_to_email', 'expires_at', 'consumed_at', 'revoked_at', 'attempts_count'],
        'casts' => ['expires_at' => 'datetime', 'consumed_at' => 'datetime', 'revoked_at' => 'datetime', 'attempts_count' => 'integer'],
        'relations' => [
            ['type' => 'belongsTo', 'name' => 'user', 'related' => 'User', 'foreignKey' => 'user_id'],
        ],
    ],
    [
        'class' => 'UserBan',
        'table' => 'user_bans',
        'fillable' => ['user_id', 'imposed_by_user_id', 'ban_type', 'reason', 'starts_at', 'ends_at', 'lifted_by_user_id', 'lifted_at'],
        'casts' => ['ban_type' => 'BanType::class', 'starts_at' => 'datetime', 'ends_at' => 'datetime', 'lifted_at' => 'datetime'],
        'relations' => [
            ['type' => 'belongsTo', 'name' => 'user', 'related' => 'User', 'foreignKey' => 'user_id'],
            ['type' => 'belongsTo', 'name' => 'imposedByUser', 'related' => 'User', 'foreignKey' => 'imposed_by_user_id'],
            ['type' => 'belongsTo', 'name' => 'liftedByUser', 'related' => 'User', 'foreignKey' => 'lifted_by_user_id'],
        ],
    ],
    [
        'class' => 'UserOnboardingProfile',
        'table' => 'user_onboarding_profiles',
        'fillable' => ['user_id', 'discovery_source', 'governorate', 'education_level', 'last_completed_step'],
        'casts' => ['last_completed_step' => 'integer'],
        'relations' => [
            ['type' => 'belongsTo', 'name' => 'user', 'related' => 'User', 'foreignKey' => 'user_id'],
        ],
    ],
    [
        'class' => 'UserUniversityProfile',
        'table' => 'user_university_profiles',
        'fillable' => ['user_id', 'university_name', 'university_year'],
        'casts' => ['university_year' => 'integer'],
        'relations' => [
            ['type' => 'belongsTo', 'name' => 'user', 'related' => 'User', 'foreignKey' => 'user_id'],
        ],
    ],
    [
        'class' => 'UserSchoolProfile',
        'table' => 'user_school_profiles',
        'fillable' => ['user_id', 'school_stage'],
        'casts' => [],
        'relations' => [
            ['type' => 'belongsTo', 'name' => 'user', 'related' => 'User', 'foreignKey' => 'user_id'],
        ],
    ],
    [
        'class' => 'UserInterestSelection',
        'table' => 'user_interest_selections',
        'fillable' => ['user_id', 'interest_id', 'slot_no'],
        'casts' => ['slot_no' => 'integer'],
        'relations' => [
            ['type' => 'belongsTo', 'name' => 'user', 'related' => 'User', 'foreignKey' => 'user_id'],
            ['type' => 'belongsTo', 'name' => 'interest', 'related' => 'Interest', 'foreignKey' => 'interest_id'],
        ],
    ],
    [
        'class' => 'InterestCategory',
        'table' => 'interest_categories',
        'fillable' => ['title'],
        'casts' => [],
        'relations' => [
            ['type' => 'hasMany', 'name' => 'interests', 'related' => 'Interest', 'foreignKey' => 'interest_category_id'],
        ],
    ],
    [
        'class' => 'Interest',
        'table' => 'interests',
        'fillable' => ['interest_category_id', 'name'],
        'casts' => [],
        'relations' => [
            ['type' => 'belongsTo', 'name' => 'category', 'related' => 'InterestCategory', 'foreignKey' => 'interest_category_id'],
            ['type' => 'hasMany', 'name' => 'userSelections', 'related' => 'UserInterestSelection', 'foreignKey' => 'interest_id'],
            ['type' => 'hasMany', 'name' => 'testSelections', 'related' => 'TestIntersetSelection', 'foreignKey' => 'interest_id'],
            ['type' => 'hasMany', 'name' => 'libraryMaterialSelections', 'related' => 'LibraryMaterialInterestSelection', 'foreignKey' => 'interest_id'],
            ['type' => 'belongsToMany', 'name' => 'users', 'related' => 'User', 'pivotTable' => 'user_interest_selections', 'foreignPivotKey' => 'interest_id', 'relatedPivotKey' => 'user_id'],
            ['type' => 'belongsToMany', 'name' => 'tests', 'related' => 'Test', 'pivotTable' => 'test_interset_selections', 'foreignPivotKey' => 'interest_id', 'relatedPivotKey' => 'test_id'],
            ['type' => 'belongsToMany', 'name' => 'libraryMaterials', 'related' => 'LibraryMaterial', 'pivotTable' => 'library_material_interest_selections', 'foreignPivotKey' => 'interest_id', 'relatedPivotKey' => 'library_material_id'],
        ],
    ],
    [
        'class' => 'UserStatsSummary',
        'table' => 'user_stats_summary',
        'fillable' => ['year', 'total_completed_mobile_users', 'male_completed_mobile_users', 'female_completed_mobile_users'],
        'casts' => ['year' => 'integer', 'total_completed_mobile_users' => 'integer', 'male_completed_mobile_users' => 'integer', 'female_completed_mobile_users' => 'integer'],
        'relations' => [],
    ],
    [
        'class' => 'UserStatsByDiscoverySource',
        'table' => 'user_stats_by_discovery_source',
        'fillable' => ['year', 'discovery_source', 'completed_mobile_users_count'],
        'casts' => ['year' => 'integer', 'completed_mobile_users_count' => 'integer'],
        'relations' => [],
    ],
    [
        'class' => 'Test',
        'table' => 'test',
        'fillable' => ['creator_user_id', 'title', 'description', 'test_type', 'difficulty_level', 'duration_seconds', 'pass_mark_percentage', 'language', 'price', 'target_level', 'review_status', 'current_approval_version', 'published_at', 'last_content_updated_at', 'question_count', 'preview_question_count', 'likes_count', 'bookmarks_count', 'downloads_count', 'reviews_count', 'participants_count', 'average_rating'],
        'casts' => ['test_type' => 'TestType::class', 'duration_seconds' => 'integer', 'pass_mark_percentage' => 'integer', 'price' => 'decimal:2', 'review_status' => 'TestReviewStatus::class', 'current_approval_version' => 'integer', 'published_at' => 'datetime', 'last_content_updated_at' => 'datetime', 'question_count' => 'integer', 'preview_question_count' => 'integer', 'likes_count' => 'integer', 'bookmarks_count' => 'integer', 'downloads_count' => 'integer', 'reviews_count' => 'integer', 'participants_count' => 'integer', 'average_rating' => 'decimal:2'],
        'relations' => [
            ['type' => 'belongsTo', 'name' => 'creator', 'related' => 'User', 'foreignKey' => 'creator_user_id'],
            ['type' => 'hasMany', 'name' => 'interestSelections', 'related' => 'TestIntersetSelection', 'foreignKey' => 'test_id'],
            ['type' => 'hasMany', 'name' => 'questions', 'related' => 'TestQuestion', 'foreignKey' => 'test_id'],
            ['type' => 'hasMany', 'name' => 'purchases', 'related' => 'TestPurchase', 'foreignKey' => 'test_id'],
            ['type' => 'hasMany', 'name' => 'attempts', 'related' => 'TestAttempt', 'foreignKey' => 'test_id'],
            ['type' => 'hasMany', 'name' => 'reports', 'related' => 'TestReport', 'foreignKey' => 'test_id'],
            ['type' => 'hasMany', 'name' => 'reviewRounds', 'related' => 'TestReviewRound', 'foreignKey' => 'test_id'],
            ['type' => 'hasMany', 'name' => 'revisionRequests', 'related' => 'TestRevisionRequest', 'foreignKey' => 'test_id'],
            ['type' => 'hasMany', 'name' => 'revisionChangeLogs', 'related' => 'TestRevisionChangeLog', 'foreignKey' => 'test_id'],
            ['type' => 'hasMany', 'name' => 'statusHistories', 'related' => 'TestStatusHistory', 'foreignKey' => 'test_id'],
            ['type' => 'hasMany', 'name' => 'bookmarks', 'related' => 'TestBookmark', 'foreignKey' => 'test_id'],
            ['type' => 'hasMany', 'name' => 'likes', 'related' => 'TestLike', 'foreignKey' => 'test_id'],
            ['type' => 'hasMany', 'name' => 'reviews', 'related' => 'TestReview', 'foreignKey' => 'test_id'],
            ['type' => 'hasMany', 'name' => 'downloadLogs', 'related' => 'TestDownloadLog', 'foreignKey' => 'test_id'],
            ['type' => 'hasMany', 'name' => 'folderItems', 'related' => 'TestFolderItem', 'foreignKey' => 'test_id'],
            ['type' => 'hasMany', 'name' => 'yearlySalesStats', 'related' => 'AdminYearlyTestSalesStat', 'foreignKey' => 'test_id'],
            ['type' => 'belongsToMany', 'name' => 'interests', 'related' => 'Interest', 'pivotTable' => 'test_interset_selections', 'foreignPivotKey' => 'test_id', 'relatedPivotKey' => 'interest_id'],
            ['type' => 'belongsToMany', 'name' => 'folders', 'related' => 'TestFolder', 'pivotTable' => 'test_folder_item', 'foreignPivotKey' => 'test_id', 'relatedPivotKey' => 'test_folder_id'],
        ],
    ],
    [
        'class' => 'TestIntersetSelection',
        'table' => 'test_interset_selections',
        'fillable' => ['test_id', 'interest_id', 'slot_no'],
        'casts' => ['slot_no' => 'integer'],
        'relations' => [
            ['type' => 'belongsTo', 'name' => 'test', 'related' => 'Test', 'foreignKey' => 'test_id'],
            ['type' => 'belongsTo', 'name' => 'interest', 'related' => 'Interest', 'foreignKey' => 'interest_id'],
        ],
    ],
    [
        'class' => 'TestQuestion',
        'table' => 'test_question',
        'fillable' => ['test_id', 'position', 'question_text', 'hint_text', 'is_preview', 'options_count'],
        'casts' => ['position' => 'integer', 'is_preview' => 'boolean', 'options_count' => 'integer'],
        'relations' => [
            ['type' => 'belongsTo', 'name' => 'test', 'related' => 'Test', 'foreignKey' => 'test_id'],
            ['type' => 'hasMany', 'name' => 'options', 'related' => 'TestQuestionOption', 'foreignKey' => 'test_question_id'],
            ['type' => 'hasMany', 'name' => 'revisionRequests', 'related' => 'TestRevisionRequest', 'foreignKey' => 'target_question_id'],
        ],
    ],
    [
        'class' => 'TestQuestionOption',
        'table' => 'test_question_options',
        'fillable' => ['test_question_id', 'position', 'option_text', 'is_correct'],
        'casts' => ['position' => 'integer', 'is_correct' => 'boolean'],
        'relations' => [
            ['type' => 'belongsTo', 'name' => 'question', 'related' => 'TestQuestion', 'foreignKey' => 'test_question_id'],
        ],
    ],
    [
        'class' => 'TestPurchase',
        'table' => 'test_purchases',
        'fillable' => ['test_id', 'buyer_user_id', 'seller_user_id', 'gross_amount', 'platform_fee_amount', 'seller_net_amount', 'currency', 'payment_provider', 'payment_reference', 'payment_status', 'purchased_at'],
        'casts' => ['gross_amount' => 'decimal:2', 'platform_fee_amount' => 'decimal:2', 'seller_net_amount' => 'decimal:2', 'purchased_at' => 'datetime'],
        'relations' => [
            ['type' => 'belongsTo', 'name' => 'test', 'related' => 'Test', 'foreignKey' => 'test_id'],
            ['type' => 'belongsTo', 'name' => 'buyer', 'related' => 'User', 'foreignKey' => 'buyer_user_id'],
            ['type' => 'belongsTo', 'name' => 'seller', 'related' => 'User', 'foreignKey' => 'seller_user_id'],
        ],
    ],
    [
        'class' => 'TestAttempt',
        'table' => 'test_attempts',
        'fillable' => ['test_id', 'user_id', 'mode'],
        'casts' => [],
        'relations' => [
            ['type' => 'belongsTo', 'name' => 'test', 'related' => 'Test', 'foreignKey' => 'test_id'],
            ['type' => 'belongsTo', 'name' => 'user', 'related' => 'User', 'foreignKey' => 'user_id'],
        ],
    ],
    [
        'class' => 'TestReport',
        'table' => 'test_reports',
        'fillable' => ['test_id', 'user_id', 'reason', 'description', 'reported_at'],
        'casts' => ['reported_at' => 'datetime'],
        'relations' => [
            ['type' => 'belongsTo', 'name' => 'test', 'related' => 'Test', 'foreignKey' => 'test_id'],
            ['type' => 'belongsTo', 'name' => 'user', 'related' => 'User', 'foreignKey' => 'user_id'],
        ],
    ],
    [
        'class' => 'TestReviewRound',
        'table' => 'test_review_rounds',
        'fillable' => ['test_id', 'round_no', 'reviewer_user_id', 'trigger_type', 'decision', 'based_on_approval_version', 'started_at', 'decided_at'],
        'casts' => ['round_no' => 'integer', 'based_on_approval_version' => 'integer', 'started_at' => 'datetime', 'decided_at' => 'datetime'],
        'relations' => [
            ['type' => 'belongsTo', 'name' => 'test', 'related' => 'Test', 'foreignKey' => 'test_id'],
            ['type' => 'belongsTo', 'name' => 'reviewer', 'related' => 'User', 'foreignKey' => 'reviewer_user_id'],
            ['type' => 'hasMany', 'name' => 'revisionRequests', 'related' => 'TestRevisionRequest', 'foreignKey' => 'test_review_round_id'],
            ['type' => 'hasMany', 'name' => 'revisionChangeLogs', 'related' => 'TestRevisionChangeLog', 'foreignKey' => 'test_review_round_id'],
        ],
    ],
    [
        'class' => 'TestRevisionRequest',
        'table' => 'test_revision_requests',
        'fillable' => ['test_review_round_id', 'test_id', 'revision_type', 'target_question_id', 'decision', 'created_by_user_id', 'resolved_at', 'problem_note'],
        'casts' => ['resolved_at' => 'datetime'],
        'relations' => [
            ['type' => 'belongsTo', 'name' => 'reviewRound', 'related' => 'TestReviewRound', 'foreignKey' => 'test_review_round_id'],
            ['type' => 'belongsTo', 'name' => 'test', 'related' => 'Test', 'foreignKey' => 'test_id'],
            ['type' => 'belongsTo', 'name' => 'targetQuestion', 'related' => 'TestQuestion', 'foreignKey' => 'target_question_id'],
            ['type' => 'belongsTo', 'name' => 'createdByUser', 'related' => 'User', 'foreignKey' => 'created_by_user_id'],
            ['type' => 'hasMany', 'name' => 'changeLogs', 'related' => 'TestRevisionChangeLog', 'foreignKey' => 'revision_request_id'],
        ],
    ],
    [
        'class' => 'TestRevisionChangeLog',
        'table' => 'test_revision_change_logs',
        'fillable' => ['test_review_round_id', 'test_id', 'revision_request_id', 'revision_type', 'before_value', 'after_value', 'changed_by_user_id'],
        'casts' => [],
        'relations' => [
            ['type' => 'belongsTo', 'name' => 'reviewRound', 'related' => 'TestReviewRound', 'foreignKey' => 'test_review_round_id'],
            ['type' => 'belongsTo', 'name' => 'test', 'related' => 'Test', 'foreignKey' => 'test_id'],
            ['type' => 'belongsTo', 'name' => 'revisionRequest', 'related' => 'TestRevisionRequest', 'foreignKey' => 'revision_request_id'],
            ['type' => 'belongsTo', 'name' => 'changedByUser', 'related' => 'User', 'foreignKey' => 'changed_by_user_id'],
        ],
    ],
    [
        'class' => 'TestStatusHistory',
        'table' => 'test_status_histories',
        'fillable' => ['test_id', 'from_status', 'to_status', 'changed_by_user_id', 'note'],
        'casts' => ['from_status' => 'TestReviewStatus::class', 'to_status' => 'TestReviewStatus::class'],
        'relations' => [
            ['type' => 'belongsTo', 'name' => 'test', 'related' => 'Test', 'foreignKey' => 'test_id'],
            ['type' => 'belongsTo', 'name' => 'changedByUser', 'related' => 'User', 'foreignKey' => 'changed_by_user_id'],
        ],
    ],
    [
        'class' => 'TestBookmark',
        'table' => 'test_bookmarks',
        'fillable' => ['test_id', 'use_id'],
        'casts' => [],
        'relations' => [
            ['type' => 'belongsTo', 'name' => 'test', 'related' => 'Test', 'foreignKey' => 'test_id'],
            ['type' => 'belongsTo', 'name' => 'user', 'related' => 'User', 'foreignKey' => 'use_id'],
        ],
    ],
    [
        'class' => 'TestLike',
        'table' => 'test_likes',
        'fillable' => ['test_id', 'user_id'],
        'casts' => [],
        'relations' => [
            ['type' => 'belongsTo', 'name' => 'test', 'related' => 'Test', 'foreignKey' => 'test_id'],
            ['type' => 'belongsTo', 'name' => 'user', 'related' => 'User', 'foreignKey' => 'user_id'],
        ],
    ],
    [
        'class' => 'TestReview',
        'table' => 'test_reviews',
        'fillable' => ['test_id', 'user_id', 'rating', 'review_text', 'helpful_yes_count', 'helpful_no_count'],
        'casts' => ['rating' => 'integer', 'helpful_yes_count' => 'integer', 'helpful_no_count' => 'integer'],
        'relations' => [
            ['type' => 'belongsTo', 'name' => 'test', 'related' => 'Test', 'foreignKey' => 'test_id'],
            ['type' => 'belongsTo', 'name' => 'user', 'related' => 'User', 'foreignKey' => 'user_id'],
            ['type' => 'hasMany', 'name' => 'feedbacks', 'related' => 'TestReviewFeedback', 'foreignKey' => 'test_review_id'],
        ],
    ],
    [
        'class' => 'TestReviewFeedback',
        'table' => 'test_review_feedbacks',
        'fillable' => ['test_review_id', 'user_id', 'vote'],
        'casts' => [],
        'relations' => [
            ['type' => 'belongsTo', 'name' => 'review', 'related' => 'TestReview', 'foreignKey' => 'test_review_id'],
            ['type' => 'belongsTo', 'name' => 'user', 'related' => 'User', 'foreignKey' => 'user_id'],
        ],
    ],
    [
        'class' => 'TestDownloadLog',
        'table' => 'test_download_logs',
        'fillable' => ['test_id', 'user_id', 'downloadd_at'],
        'casts' => ['downloadd_at' => 'datetime'],
        'relations' => [
            ['type' => 'belongsTo', 'name' => 'test', 'related' => 'Test', 'foreignKey' => 'test_id'],
            ['type' => 'belongsTo', 'name' => 'user', 'related' => 'User', 'foreignKey' => 'user_id'],
        ],
    ],
    [
        'class' => 'LibraryMaterial',
        'table' => 'library_material',
        'fillable' => ['creator_user_id', 'imposed_by_user_id', 'title', 'description', 'content_kind', 'visibility_type', 'target_level', 'review_status', 'current_approval_version', 'published_at', 'asset_count', 'like_count', 'bookmarks_count', 'download_count'],
        'casts' => ['content_kind' => 'LibraryMaterialContentKind::class', 'visibility_type' => 'VisibilityType::class', 'review_status' => 'LibraryMaterialReviewStatus::class', 'current_approval_version' => 'integer', 'published_at' => 'datetime', 'asset_count' => 'integer', 'like_count' => 'integer', 'bookmarks_count' => 'integer', 'download_count' => 'integer'],
        'relations' => [
            ['type' => 'belongsTo', 'name' => 'creator', 'related' => 'User', 'foreignKey' => 'creator_user_id'],
            ['type' => 'belongsTo', 'name' => 'imposedByUser', 'related' => 'User', 'foreignKey' => 'imposed_by_user_id'],
            ['type' => 'hasMany', 'name' => 'assets', 'related' => 'LibraryMaterialAsset', 'foreignKey' => 'library_material_id'],
            ['type' => 'hasMany', 'name' => 'interestSelections', 'related' => 'LibraryMaterialInterestSelection', 'foreignKey' => 'library_material_id'],
            ['type' => 'hasMany', 'name' => 'reviewRounds', 'related' => 'LibraryMaterialReviewRound', 'foreignKey' => 'library_material_id'],
            ['type' => 'hasMany', 'name' => 'statusHistories', 'related' => 'LibraryMaterialStatusHistory', 'foreignKey' => 'library_material_id'],
            ['type' => 'hasMany', 'name' => 'bookmarks', 'related' => 'LibraryMaterialBookmark', 'foreignKey' => 'library_material_id'],
            ['type' => 'hasMany', 'name' => 'likes', 'related' => 'LibraryMaterialLike', 'foreignKey' => 'library_material_id'],
            ['type' => 'hasMany', 'name' => 'downloadLogs', 'related' => 'LibraryMaterialDownloadLog', 'foreignKey' => 'library_material_id'],
            ['type' => 'hasMany', 'name' => 'reports', 'related' => 'LibraryMaterialReport', 'foreignKey' => 'library_material_id'],
            ['type' => 'hasMany', 'name' => 'reportReasonCounters', 'related' => 'LibraryReportReasonCounter', 'foreignKey' => 'library_material_id'],
            ['type' => 'belongsToMany', 'name' => 'interests', 'related' => 'Interest', 'pivotTable' => 'library_material_interest_selections', 'foreignPivotKey' => 'library_material_id', 'relatedPivotKey' => 'interest_id'],
        ],
    ],
    [
        'class' => 'LibraryMaterialAsset',
        'table' => 'library_material_asset',
        'fillable' => ['library_material_id', 'asset_type', 'storage_disk', 'storage_path', 'original_name', 'mime_type', 'position'],
        'casts' => ['position' => 'integer'],
        'relations' => [
            ['type' => 'belongsTo', 'name' => 'libraryMaterial', 'related' => 'LibraryMaterial', 'foreignKey' => 'library_material_id'],
        ],
    ],
    [
        'class' => 'LibraryMaterialInterestSelection',
        'table' => 'library_material_interest_selections',
        'fillable' => ['library_material_id', 'interest_id', 'slot_no'],
        'casts' => ['slot_no' => 'integer'],
        'relations' => [
            ['type' => 'belongsTo', 'name' => 'libraryMaterial', 'related' => 'LibraryMaterial', 'foreignKey' => 'library_material_id'],
            ['type' => 'belongsTo', 'name' => 'interest', 'related' => 'Interest', 'foreignKey' => 'interest_id'],
        ],
    ],
    [
        'class' => 'LibraryMaterialReviewRound',
        'table' => 'library_material_review_rounds',
        'fillable' => ['library_material_id', 'round_no', 'reviewer_user_id', 'trigger_type', 'decision', 'based_on_approval_version', 'started_at', 'decided_at'],
        'casts' => ['round_no' => 'integer', 'based_on_approval_version' => 'integer', 'started_at' => 'datetime', 'decided_at' => 'datetime'],
        'relations' => [
            ['type' => 'belongsTo', 'name' => 'libraryMaterial', 'related' => 'LibraryMaterial', 'foreignKey' => 'library_material_id'],
            ['type' => 'belongsTo', 'name' => 'reviewer', 'related' => 'User', 'foreignKey' => 'reviewer_user_id'],
        ],
    ],
    [
        'class' => 'LibraryMaterialStatusHistory',
        'table' => 'library_material_status_histories',
        'fillable' => ['library_material_id', 'from_status', 'to_status', 'changed_by_user_id', 'note'],
        'casts' => ['from_status' => 'LibraryMaterialReviewStatus::class', 'to_status' => 'LibraryMaterialReviewStatus::class'],
        'relations' => [
            ['type' => 'belongsTo', 'name' => 'libraryMaterial', 'related' => 'LibraryMaterial', 'foreignKey' => 'library_material_id'],
            ['type' => 'belongsTo', 'name' => 'changedByUser', 'related' => 'User', 'foreignKey' => 'changed_by_user_id'],
        ],
    ],
    [
        'class' => 'LibraryMaterialBookmark',
        'table' => 'library_material_bookmarks',
        'fillable' => ['library_material_id', 'user_id'],
        'casts' => [],
        'relations' => [
            ['type' => 'belongsTo', 'name' => 'libraryMaterial', 'related' => 'LibraryMaterial', 'foreignKey' => 'library_material_id'],
            ['type' => 'belongsTo', 'name' => 'user', 'related' => 'User', 'foreignKey' => 'user_id'],
        ],
    ],
    [
        'class' => 'LibraryMaterialLike',
        'table' => 'library_material_likes',
        'fillable' => ['library_material_id', 'user_id'],
        'casts' => [],
        'relations' => [
            ['type' => 'belongsTo', 'name' => 'libraryMaterial', 'related' => 'LibraryMaterial', 'foreignKey' => 'library_material_id'],
            ['type' => 'belongsTo', 'name' => 'user', 'related' => 'User', 'foreignKey' => 'user_id'],
        ],
    ],
    [
        'class' => 'LibraryMaterialDownloadLog',
        'table' => 'library_material_download_logs',
        'fillable' => ['library_material_id', 'user_id'],
        'casts' => [],
        'relations' => [
            ['type' => 'belongsTo', 'name' => 'libraryMaterial', 'related' => 'LibraryMaterial', 'foreignKey' => 'library_material_id'],
            ['type' => 'belongsTo', 'name' => 'user', 'related' => 'User', 'foreignKey' => 'user_id'],
        ],
    ],
    [
        'class' => 'LibraryMaterialReport',
        'table' => 'library_material_reports',
        'fillable' => ['library_material_id', 'user_id', 'approval_version', 'reason', 'description', 'reported_at'],
        'casts' => ['approval_version' => 'integer', 'reported_at' => 'datetime'],
        'relations' => [
            ['type' => 'belongsTo', 'name' => 'libraryMaterial', 'related' => 'LibraryMaterial', 'foreignKey' => 'library_material_id'],
            ['type' => 'belongsTo', 'name' => 'user', 'related' => 'User', 'foreignKey' => 'user_id'],
        ],
    ],
    [
        'class' => 'LibraryReportReasonCounter',
        'table' => 'library_material_report_reason_counters',
        'fillable' => ['library_material_id', 'approval_version', 'reason', 'reporters_count'],
        'casts' => ['approval_version' => 'integer', 'reporters_count' => 'integer'],
        'relations' => [
            ['type' => 'belongsTo', 'name' => 'libraryMaterial', 'related' => 'LibraryMaterial', 'foreignKey' => 'library_material_id'],
        ],
    ],
    [
        'class' => 'TestFolder',
        'table' => 'test_folder',
        'fillable' => ['creator_id', 'name', 'color_code', 'visibility_type', 'contained_test_type', 'tests_count', 'published_at'],
        'casts' => ['visibility_type' => 'VisibilityType::class', 'contained_test_type' => 'TestType::class', 'tests_count' => 'integer', 'published_at' => 'datetime'],
        'relations' => [
            ['type' => 'belongsTo', 'name' => 'creator', 'related' => 'User', 'foreignKey' => 'creator_id'],
            ['type' => 'hasMany', 'name' => 'bookmarks', 'related' => 'TestFolderBookmark', 'foreignKey' => 'test_folder_id'],
            ['type' => 'hasMany', 'name' => 'items', 'related' => 'TestFolderItem', 'foreignKey' => 'test_folder_id'],
            ['type' => 'belongsToMany', 'name' => 'tests', 'related' => 'Test', 'pivotTable' => 'test_folder_item', 'foreignPivotKey' => 'test_folder_id', 'relatedPivotKey' => 'test_id'],
        ],
    ],
    [
        'class' => 'TestFolderBookmark',
        'table' => 'test_folder_bookmarks',
        'fillable' => ['test_folder_id', 'user_id'],
        'casts' => [],
        'relations' => [
            ['type' => 'belongsTo', 'name' => 'folder', 'related' => 'TestFolder', 'foreignKey' => 'test_folder_id'],
            ['type' => 'belongsTo', 'name' => 'user', 'related' => 'User', 'foreignKey' => 'user_id'],
        ],
    ],
    [
        'class' => 'TestFolderItem',
        'table' => 'test_folder_item',
        'fillable' => ['test_folder_id', 'test_id', 'position'],
        'casts' => ['position' => 'integer'],
        'relations' => [
            ['type' => 'belongsTo', 'name' => 'folder', 'related' => 'TestFolder', 'foreignKey' => 'test_folder_id'],
            ['type' => 'belongsTo', 'name' => 'test', 'related' => 'Test', 'foreignKey' => 'test_id'],
        ],
    ],
    [
        'class' => 'StudySubject',
        'table' => 'study_subject',
        'fillable' => ['user_id', 'name'],
        'casts' => [],
        'relations' => [
            ['type' => 'belongsTo', 'name' => 'user', 'related' => 'User', 'foreignKey' => 'user_id'],
            ['type' => 'hasMany', 'name' => 'studyPlanSubjects', 'related' => 'StudyPlanSubject', 'foreignKey' => 'study_subject_id'],
            ['type' => 'belongsToMany', 'name' => 'studyPlans', 'related' => 'StudyPlan', 'pivotTable' => 'study_plan_subject', 'foreignPivotKey' => 'study_subject_id', 'relatedPivotKey' => 'study_plan_id'],
        ],
    ],
    [
        'class' => 'StudyPlan',
        'table' => 'study_plan',
        'fillable' => ['user_id', 'title', 'emoji', 'start_date', 'end_date', 'daily_study_minutes', 'is_default', 'subjects_count', 'tasks_count', 'completed_tasks_count', 'missed_tasks_count', 'pending_tasks_count'],
        'casts' => ['start_date' => 'date', 'end_date' => 'date', 'daily_study_minutes' => 'integer', 'is_default' => 'boolean', 'subjects_count' => 'integer', 'tasks_count' => 'integer', 'completed_tasks_count' => 'integer', 'missed_tasks_count' => 'integer', 'pending_tasks_count' => 'integer'],
        'relations' => [
            ['type' => 'belongsTo', 'name' => 'user', 'related' => 'User', 'foreignKey' => 'user_id'],
            ['type' => 'hasMany', 'name' => 'studyPlanSubjects', 'related' => 'StudyPlanSubject', 'foreignKey' => 'study_plan_id'],
            ['type' => 'hasMany', 'name' => 'tasks', 'related' => 'StudyTask', 'foreignKey' => 'study_plan_id'],
            ['type' => 'hasMany', 'name' => 'taskOccurrences', 'related' => 'StudyTaskOccurrence', 'foreignKey' => 'study_plan_id'],
            ['type' => 'hasMany', 'name' => 'yearlyStats', 'related' => 'UserYearlyStudyPlanStat', 'foreignKey' => 'study_plan_id'],
            ['type' => 'belongsToMany', 'name' => 'subjects', 'related' => 'StudySubject', 'pivotTable' => 'study_plan_subject', 'foreignPivotKey' => 'study_plan_id', 'relatedPivotKey' => 'study_subject_id'],
        ],
    ],
    [
        'class' => 'StudyPlanSubject',
        'table' => 'study_plan_subject',
        'fillable' => ['study_plan_id', 'study_subject_id', 'slot_no'],
        'casts' => ['slot_no' => 'integer'],
        'relations' => [
            ['type' => 'belongsTo', 'name' => 'studyPlan', 'related' => 'StudyPlan', 'foreignKey' => 'study_plan_id'],
            ['type' => 'belongsTo', 'name' => 'studySubject', 'related' => 'StudySubject', 'foreignKey' => 'study_subject_id'],
            ['type' => 'hasMany', 'name' => 'tasks', 'related' => 'StudyTask', 'foreignKey' => 'study_plan_subject_id'],
        ],
    ],
    [
        'class' => 'StudyTask',
        'table' => 'study_task',
        'fillable' => ['study_plan_id', 'study_plan_subject_id', 'task_group_uuid', 'title', 'description', 'start_date', 'end_date', 'start_time', 'duration_minutes_per_day', 'deadline_at', 'reminder_offset_minutes', 'priority', 'status', 'completed_at', 'missed_at', 'repeat_pattern', 'recurrence_end_date'],
        'casts' => ['start_date' => 'date', 'end_date' => 'date', 'start_time' => 'datetime:H:i:s', 'duration_minutes_per_day' => 'integer', 'deadline_at' => 'datetime', 'reminder_offset_minutes' => 'integer', 'status' => 'StudyTaskStatus::class', 'completed_at' => 'datetime', 'missed_at' => 'datetime', 'repeat_pattern' => 'StudyTaskRepeatPattern::class', 'recurrence_end_date' => 'date'],
        'relations' => [
            ['type' => 'belongsTo', 'name' => 'studyPlan', 'related' => 'StudyPlan', 'foreignKey' => 'study_plan_id'],
            ['type' => 'belongsTo', 'name' => 'studyPlanSubject', 'related' => 'StudyPlanSubject', 'foreignKey' => 'study_plan_subject_id'],
            ['type' => 'hasMany', 'name' => 'occurrences', 'related' => 'StudyTaskOccurrence', 'foreignKey' => 'study_task_id'],
            ['type' => 'hasMany', 'name' => 'subtasks', 'related' => 'StudyTaskSubtask', 'foreignKey' => 'study_task_id'],
        ],
    ],
    [
        'class' => 'StudyTaskOccurrence',
        'table' => 'study_task_occurrence',
        'fillable' => ['study_task_id', 'study_plan_id', 'occurrence_date', 'scheduled_start_time', 'scheduled_end_time', 'duration_minutes'],
        'casts' => ['occurrence_date' => 'date', 'scheduled_start_time' => 'datetime:H:i:s', 'scheduled_end_time' => 'datetime:H:i:s', 'duration_minutes' => 'integer'],
        'relations' => [
            ['type' => 'belongsTo', 'name' => 'studyTask', 'related' => 'StudyTask', 'foreignKey' => 'study_task_id'],
            ['type' => 'belongsTo', 'name' => 'studyPlan', 'related' => 'StudyPlan', 'foreignKey' => 'study_plan_id'],
        ],
    ],
    [
        'class' => 'StudyTaskSubtask',
        'table' => 'study_task_subtask',
        'fillable' => ['study_task_id', 'title', 'position', 'is_completed', 'completed_at'],
        'casts' => ['position' => 'integer', 'is_completed' => 'boolean', 'completed_at' => 'datetime'],
        'relations' => [
            ['type' => 'belongsTo', 'name' => 'studyTask', 'related' => 'StudyTask', 'foreignKey' => 'study_task_id'],
        ],
    ],
    [
        'class' => 'UserSetting',
        'table' => 'user_settings',
        'fillable' => ['user_id', 'task_reminders_enabled', 'week_starts_on', 'time_format', 'theme_mode'],
        'casts' => ['task_reminders_enabled' => 'boolean'],
        'relations' => [
            ['type' => 'belongsTo', 'name' => 'user', 'related' => 'User', 'foreignKey' => 'user_id'],
        ],
    ],
    [
        'class' => 'UserAcademicVerificationRequest',
        'table' => 'user_academic_verification_requests',
        'fillable' => ['user_id', 'status', 'submitted_at', 'reviewer_user_id', 'reviewed_at', 'rejection_reason'],
        'casts' => ['submitted_at' => 'datetime', 'reviewed_at' => 'datetime'],
        'relations' => [
            ['type' => 'belongsTo', 'name' => 'user', 'related' => 'User', 'foreignKey' => 'user_id'],
            ['type' => 'belongsTo', 'name' => 'reviewer', 'related' => 'User', 'foreignKey' => 'reviewer_user_id'],
            ['type' => 'hasMany', 'name' => 'assets', 'related' => 'UserAcademicAsset', 'foreignKey' => 'verification_request_id'],
        ],
    ],
    [
        'class' => 'UserAcademicAsset',
        'table' => 'user_academic_verification_assets',
        'fillable' => ['verification_request_id', 'asset_type', 'storage_disk', 'storage_path', 'original_name', 'mime_type', 'file_size_bytes'],
        'casts' => ['file_size_bytes' => 'integer'],
        'relations' => [
            ['type' => 'belongsTo', 'name' => 'verificationRequest', 'related' => 'UserAcademicVerificationRequest', 'foreignKey' => 'verification_request_id'],
        ],
    ],
    [
        'class' => 'UserYearlyStudyStat',
        'table' => 'user_yearly_study_stats',
        'fillable' => ['user_id', 'year', 'total_tasks_count', 'todo_tasks_count', 'in_progress_tasks_count', 'completed_tasks_count', 'missed_tasks_count'],
        'casts' => ['year' => 'integer', 'total_tasks_count' => 'integer', 'todo_tasks_count' => 'integer', 'in_progress_tasks_count' => 'integer', 'completed_tasks_count' => 'integer', 'missed_tasks_count' => 'integer'],
        'relations' => [
            ['type' => 'belongsTo', 'name' => 'user', 'related' => 'User', 'foreignKey' => 'user_id'],
        ],
    ],
    [
        'class' => 'UserYearlyStudyPlanStat',
        'table' => 'user_yearly_study_plan_stats',
        'fillable' => ['user_id', 'study_plan_id', 'year', 'total_tasks_count', 'todo_tasks_count', 'in_progress_tasks_count', 'completed_tasks_count', 'missed_tasks_count'],
        'casts' => ['year' => 'integer', 'total_tasks_count' => 'integer', 'todo_tasks_count' => 'integer', 'in_progress_tasks_count' => 'integer', 'completed_tasks_count' => 'integer', 'missed_tasks_count' => 'integer'],
        'relations' => [
            ['type' => 'belongsTo', 'name' => 'user', 'related' => 'User', 'foreignKey' => 'user_id'],
            ['type' => 'belongsTo', 'name' => 'studyPlan', 'related' => 'StudyPlan', 'foreignKey' => 'study_plan_id'],
        ],
    ],
    [
        'class' => 'UserYearlyTestStat',
        'table' => 'user_yearly_test_stats',
        'fillable' => ['user_id', 'year', 'total_likes_received', 'total_reviews_received', 'total_bookmarks_received', 'published_tests_count', 'first_published_test_at', 'last_published_test_at'],
        'casts' => ['year' => 'integer', 'total_likes_received' => 'integer', 'total_reviews_received' => 'integer', 'total_bookmarks_received' => 'integer', 'published_tests_count' => 'integer', 'first_published_test_at' => 'datetime', 'last_published_test_at' => 'datetime'],
        'relations' => [
            ['type' => 'belongsTo', 'name' => 'user', 'related' => 'User', 'foreignKey' => 'user_id'],
        ],
    ],
    [
        'class' => 'UserYearlyTestPublishMonthStat',
        'table' => 'user_yearly_test_publish_month_stats',
        'fillable' => ['user_id', 'year', 'month_no', 'published_tests_count'],
        'casts' => ['year' => 'integer', 'month_no' => 'integer', 'published_tests_count' => 'integer'],
        'relations' => [
            ['type' => 'belongsTo', 'name' => 'user', 'related' => 'User', 'foreignKey' => 'user_id'],
        ],
    ],
    [
        'class' => 'UserProfile',
        'table' => 'user_profile',
        'fillable' => ['user_id', 'phone', 'birth_date', 'avatar_disk', 'avatar_path', 'cover_disk', 'cover_path', 'profile_slug'],
        'casts' => ['birth_date' => 'date'],
        'relations' => [
            ['type' => 'belongsTo', 'name' => 'user', 'related' => 'User', 'foreignKey' => 'user_id'],
        ],
    ],
    [
        'class' => 'UserFollow',
        'table' => 'user_follows',
        'fillable' => ['follower_user_id', 'followed_user_id'],
        'casts' => [],
        'relations' => [
            ['type' => 'belongsTo', 'name' => 'follower', 'related' => 'User', 'foreignKey' => 'follower_user_id'],
            ['type' => 'belongsTo', 'name' => 'followed', 'related' => 'User', 'foreignKey' => 'followed_user_id'],
        ],
    ],
    [
        'class' => 'UserProfileStat',
        'table' => 'user_profile_stats',
        'fillable' => ['user_id', 'followers_count', 'following_count', 'published_tests_count', 'library_materials_count', 'folders_count', 'average_test_rating', 'total_test_likes_received', 'total_test_reviews_received', 'total_test_bookmarks_received'],
        'casts' => ['followers_count' => 'integer', 'following_count' => 'integer', 'published_tests_count' => 'integer', 'library_materials_count' => 'integer', 'folders_count' => 'integer', 'average_test_rating' => 'decimal:2', 'total_test_likes_received' => 'integer', 'total_test_reviews_received' => 'integer', 'total_test_bookmarks_received' => 'integer'],
        'relations' => [
            ['type' => 'belongsTo', 'name' => 'user', 'related' => 'User', 'foreignKey' => 'user_id'],
        ],
    ],
    [
        'class' => 'AdminYearlyFinancialStat',
        'table' => 'admin_yearly_financial_stats',
        'fillable' => ['year', 'sold_purchase_count', 'distinct_sold_tests_count', 'gross_sales_amount', 'users_profit_amount', 'platform_net_profit_amount', 'average_monthly_sales_amount', 'average_monthly_platform_profit_amount', 'most_purchased_test_id', 'most_purchased_test_purchase_count'],
        'casts' => ['year' => 'integer', 'sold_purchase_count' => 'integer', 'distinct_sold_tests_count' => 'integer', 'gross_sales_amount' => 'decimal:2', 'users_profit_amount' => 'decimal:2', 'platform_net_profit_amount' => 'decimal:2', 'average_monthly_sales_amount' => 'decimal:2', 'average_monthly_platform_profit_amount' => 'decimal:2', 'most_purchased_test_purchase_count' => 'integer'],
        'relations' => [
            ['type' => 'belongsTo', 'name' => 'mostPurchasedTest', 'related' => 'Test', 'foreignKey' => 'most_purchased_test_id'],
        ],
    ],
    [
        'class' => 'AdminYearlyFinancialMonthStat',
        'table' => 'admin_yearly_financial_month_stats',
        'fillable' => ['year', 'month_no', 'sold_purchase_count', 'distinct_sold_tests_count', 'gross_sales_amount', 'users_profit_amount', 'platform_net_profit_amount'],
        'casts' => ['year' => 'integer', 'month_no' => 'integer', 'sold_purchase_count' => 'integer', 'distinct_sold_tests_count' => 'integer', 'gross_sales_amount' => 'decimal:2', 'users_profit_amount' => 'decimal:2', 'platform_net_profit_amount' => 'decimal:2'],
        'relations' => [],
    ],
    [
        'class' => 'AdminYearlyTestSalesStat',
        'table' => 'admin_yearly_test_sales_stats',
        'fillable' => ['year', 'test_id', 'purchase_count', 'gross_sales_amount', 'users_profit_amount', 'platform_net_profit_amount'],
        'casts' => ['year' => 'integer', 'purchase_count' => 'integer', 'gross_sales_amount' => 'decimal:2', 'users_profit_amount' => 'decimal:2', 'platform_net_profit_amount' => 'decimal:2'],
        'relations' => [
            ['type' => 'belongsTo', 'name' => 'test', 'related' => 'Test', 'foreignKey' => 'test_id'],
        ],
    ],
    [
        'class' => 'AdminYearlyTestActivityMonthStat',
        'table' => 'admin_yearly_test_activity_month_stats',
        'fillable' => ['year', 'month_no', 'published_tests_count', 'likes_count', 'reviews_count', 'downloads_count'],
        'casts' => ['year' => 'integer', 'month_no' => 'integer', 'published_tests_count' => 'integer', 'likes_count' => 'integer', 'reviews_count' => 'integer', 'downloads_count' => 'integer'],
        'relations' => [],
    ],
    [
        'class' => 'AdminYearlyLibraryMaterialActivityMonthStat',
        'table' => 'admin_yearly_library_material_activity_month_stats',
        'fillable' => ['year', 'month_no', 'published_materials_count', 'likes_count'],
        'casts' => ['year' => 'integer', 'month_no' => 'integer', 'published_materials_count' => 'integer', 'likes_count' => 'integer'],
        'relations' => [],
    ],
];

$userModelSpec = [
    'class' => 'User',
    'table' => 'users',
    'fillable' => ['role_id', 'name', 'email', 'password', 'email_verified_at', 'onboarding_complered_at', 'last_login_at', 'gender'],
    'casts' => ['email_verified_at' => 'datetime', 'onboarding_complered_at' => 'datetime', 'last_login_at' => 'datetime', 'password' => 'hashed'],
    'relations' => [
        ['type' => 'belongsTo', 'name' => 'role', 'related' => 'SystemRole', 'foreignKey' => 'role_id'],
        ['type' => 'hasMany', 'name' => 'otpCodes', 'related' => 'AuthOtpCode', 'foreignKey' => 'user_id'],
        ['type' => 'hasMany', 'name' => 'bans', 'related' => 'UserBan', 'foreignKey' => 'user_id'],
        ['type' => 'hasMany', 'name' => 'imposedBans', 'related' => 'UserBan', 'foreignKey' => 'imposed_by_user_id'],
        ['type' => 'hasMany', 'name' => 'liftedBans', 'related' => 'UserBan', 'foreignKey' => 'lifted_by_user_id'],
        ['type' => 'hasOne', 'name' => 'onboardingProfile', 'related' => 'UserOnboardingProfile', 'foreignKey' => 'user_id'],
        ['type' => 'hasOne', 'name' => 'universityProfile', 'related' => 'UserUniversityProfile', 'foreignKey' => 'user_id'],
        ['type' => 'hasOne', 'name' => 'schoolProfile', 'related' => 'UserSchoolProfile', 'foreignKey' => 'user_id'],
        ['type' => 'hasMany', 'name' => 'interestSelections', 'related' => 'UserInterestSelection', 'foreignKey' => 'user_id'],
        ['type' => 'belongsToMany', 'name' => 'interests', 'related' => 'Interest', 'pivotTable' => 'user_interest_selections', 'foreignPivotKey' => 'user_id', 'relatedPivotKey' => 'interest_id'],
        ['type' => 'hasMany', 'name' => 'createdTests', 'related' => 'Test', 'foreignKey' => 'creator_user_id'],
        ['type' => 'hasMany', 'name' => 'testPurchasesAsBuyer', 'related' => 'TestPurchase', 'foreignKey' => 'buyer_user_id'],
        ['type' => 'hasMany', 'name' => 'testPurchasesAsSeller', 'related' => 'TestPurchase', 'foreignKey' => 'seller_user_id'],
        ['type' => 'hasMany', 'name' => 'testAttempts', 'related' => 'TestAttempt', 'foreignKey' => 'user_id'],
        ['type' => 'hasMany', 'name' => 'testReports', 'related' => 'TestReport', 'foreignKey' => 'user_id'],
        ['type' => 'hasMany', 'name' => 'assignedTestReviewRounds', 'related' => 'TestReviewRound', 'foreignKey' => 'reviewer_user_id'],
        ['type' => 'hasMany', 'name' => 'createdTestRevisionRequests', 'related' => 'TestRevisionRequest', 'foreignKey' => 'created_by_user_id'],
        ['type' => 'hasMany', 'name' => 'testRevisionChangeLogs', 'related' => 'TestRevisionChangeLog', 'foreignKey' => 'changed_by_user_id'],
        ['type' => 'hasMany', 'name' => 'testStatusHistories', 'related' => 'TestStatusHistory', 'foreignKey' => 'changed_by_user_id'],
        ['type' => 'hasMany', 'name' => 'testBookmarks', 'related' => 'TestBookmark', 'foreignKey' => 'use_id'],
        ['type' => 'hasMany', 'name' => 'testLikes', 'related' => 'TestLike', 'foreignKey' => 'user_id'],
        ['type' => 'hasMany', 'name' => 'testReviews', 'related' => 'TestReview', 'foreignKey' => 'user_id'],
        ['type' => 'hasMany', 'name' => 'testReviewFeedbacks', 'related' => 'TestReviewFeedback', 'foreignKey' => 'user_id'],
        ['type' => 'hasMany', 'name' => 'testDownloadLogs', 'related' => 'TestDownloadLog', 'foreignKey' => 'user_id'],
        ['type' => 'hasMany', 'name' => 'libraryMaterials', 'related' => 'LibraryMaterial', 'foreignKey' => 'creator_user_id'],
        ['type' => 'hasMany', 'name' => 'imposedLibraryMaterials', 'related' => 'LibraryMaterial', 'foreignKey' => 'imposed_by_user_id'],
        ['type' => 'hasMany', 'name' => 'libraryMaterialReviewRounds', 'related' => 'LibraryMaterialReviewRound', 'foreignKey' => 'reviewer_user_id'],
        ['type' => 'hasMany', 'name' => 'libraryMaterialStatusHistories', 'related' => 'LibraryMaterialStatusHistory', 'foreignKey' => 'changed_by_user_id'],
        ['type' => 'hasMany', 'name' => 'libraryMaterialBookmarks', 'related' => 'LibraryMaterialBookmark', 'foreignKey' => 'user_id'],
        ['type' => 'hasMany', 'name' => 'libraryMaterialLikes', 'related' => 'LibraryMaterialLike', 'foreignKey' => 'user_id'],
        ['type' => 'hasMany', 'name' => 'libraryMaterialDownloadLogs', 'related' => 'LibraryMaterialDownloadLog', 'foreignKey' => 'user_id'],
        ['type' => 'hasMany', 'name' => 'libraryMaterialReports', 'related' => 'LibraryMaterialReport', 'foreignKey' => 'user_id'],
        ['type' => 'hasMany', 'name' => 'testFolders', 'related' => 'TestFolder', 'foreignKey' => 'creator_id'],
        ['type' => 'hasMany', 'name' => 'testFolderBookmarks', 'related' => 'TestFolderBookmark', 'foreignKey' => 'user_id'],
        ['type' => 'hasMany', 'name' => 'studySubjects', 'related' => 'StudySubject', 'foreignKey' => 'user_id'],
        ['type' => 'hasMany', 'name' => 'studyPlans', 'related' => 'StudyPlan', 'foreignKey' => 'user_id'],
        ['type' => 'hasOne', 'name' => 'settings', 'related' => 'UserSetting', 'foreignKey' => 'user_id'],
        ['type' => 'hasMany', 'name' => 'academicVerificationRequests', 'related' => 'UserAcademicVerificationRequest', 'foreignKey' => 'user_id'],
        ['type' => 'hasMany', 'name' => 'reviewedAcademicVerificationRequests', 'related' => 'UserAcademicVerificationRequest', 'foreignKey' => 'reviewer_user_id'],
        ['type' => 'hasMany', 'name' => 'yearlyStudyStats', 'related' => 'UserYearlyStudyStat', 'foreignKey' => 'user_id'],
        ['type' => 'hasMany', 'name' => 'yearlyStudyPlanStats', 'related' => 'UserYearlyStudyPlanStat', 'foreignKey' => 'user_id'],
        ['type' => 'hasMany', 'name' => 'yearlyTestStats', 'related' => 'UserYearlyTestStat', 'foreignKey' => 'user_id'],
        ['type' => 'hasMany', 'name' => 'yearlyTestPublishMonthStats', 'related' => 'UserYearlyTestPublishMonthStat', 'foreignKey' => 'user_id'],
        ['type' => 'hasOne', 'name' => 'profile', 'related' => 'UserProfile', 'foreignKey' => 'user_id'],
        ['type' => 'hasMany', 'name' => 'followingRelations', 'related' => 'UserFollow', 'foreignKey' => 'follower_user_id'],
        ['type' => 'hasMany', 'name' => 'followerRelations', 'related' => 'UserFollow', 'foreignKey' => 'followed_user_id'],
        ['type' => 'belongsToMany', 'name' => 'following', 'related' => 'User', 'pivotTable' => 'user_follows', 'foreignPivotKey' => 'follower_user_id', 'relatedPivotKey' => 'followed_user_id'],
        ['type' => 'belongsToMany', 'name' => 'followers', 'related' => 'User', 'pivotTable' => 'user_follows', 'foreignPivotKey' => 'followed_user_id', 'relatedPivotKey' => 'follower_user_id'],
        ['type' => 'hasOne', 'name' => 'profileStats', 'related' => 'UserProfileStat', 'foreignKey' => 'user_id'],
    ],
];

foreach ($enumFiles as $path => $content) {
    writeFile($root . DIRECTORY_SEPARATOR . $path, $content);
}

foreach ($migrationFiles as $path => $content) {
    writeFile($root . DIRECTORY_SEPARATOR . $path, $content);
}

foreach ($models as $model) {
    $content = buildModelContent($model);
    writeFile($root . DIRECTORY_SEPARATOR . 'app/Models/' . $model['class'] . '.php', $content);
}

writeFile($root . DIRECTORY_SEPARATOR . 'app/Models/User.php', buildUserModelContent($userModelSpec));

echo 'Domain schema files generated.' . PHP_EOL;

function buildModelContent(array $spec): string
{
    return buildBaseModelContent($spec, false);
}

function buildUserModelContent(array $spec): string
{
    return buildBaseModelContent($spec, true);
}

function buildBaseModelContent(array $spec, bool $isUser): string
{
    $imports = [
        'Illuminate\Database\Eloquent\Factories\HasFactory',
        'Illuminate\Database\Eloquent\Model',
        'Illuminate\Database\Eloquent\Relations\BelongsTo',
        'Illuminate\Database\Eloquent\Relations\BelongsToMany',
        'Illuminate\Database\Eloquent\Relations\HasMany',
        'Illuminate\Database\Eloquent\Relations\HasOne',
    ];

    if ($isUser) {
        $imports = array_merge($imports, [
            'Illuminate\Foundation\Auth\User as Authenticatable',
            'Illuminate\Notifications\Notifiable',
            'Laravel\Sanctum\HasApiTokens',
            'PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject',
        ]);
        $imports = array_filter($imports, static fn (string $import): bool => $import !== 'Illuminate\Database\Eloquent\Model');
    }

    foreach ($spec['casts'] as $cast) {
        if (str_ends_with($cast, '::class')) {
            $imports[] = 'App\\Enums\\' . str_replace('::class', '', $cast);
        }
    }

    foreach ($spec['relations'] as $relation) {
        $imports[] = 'App\\Models\\' . $relation['related'];
    }

    $imports = array_unique($imports);
    sort($imports);

    $extends = $isUser ? 'Authenticatable' : 'Model';
    $implements = $isUser ? ' implements JWTSubject' : '';
    $traits = $isUser ? '    use HasApiTokens, HasFactory, Notifiable;' : '    use HasFactory;';

    $body = [];
    $body[] = '<?php';
    $body[] = '';
    $body[] = 'namespace App\Models;';
    $body[] = '';
    foreach ($imports as $import) {
        $body[] = 'use ' . $import . ';';
    }
    $body[] = '';
    $body[] = 'class ' . $spec['class'] . ' extends ' . $extends . $implements;
    $body[] = '{';
    $body[] = $traits;
    $body[] = '';
    $body[] = "    protected \$table = '" . $spec['table'] . "';";
    $body[] = '';
    $body[] = '    protected $fillable = [';
    foreach ($spec['fillable'] as $field) {
        $body[] = "        '" . $field . "',";
    }
    $body[] = '    ];';
    $body[] = '';
    if ($isUser) {
        $body[] = '    protected $hidden = [';
        $body[] = "        'password',";
        $body[] = '    ];';
        $body[] = '';
    }
    $body[] = '    protected $casts = [';
    foreach ($spec['casts'] as $field => $cast) {
        $body[] = "        '" . $field . "' => " . normalizeCast($cast) . ',';
    }
    $body[] = '    ];';
    $body[] = '';

    if ($isUser) {
        $body[] = '    public function getJWTIdentifier()';
        $body[] = '    {';
        $body[] = '        return $this->getKey();';
        $body[] = '    }';
        $body[] = '';
        $body[] = '    public function getJWTCustomClaims(): array';
        $body[] = '    {';
        $body[] = '        return [];';
        $body[] = '    }';
        $body[] = '';
    }

    foreach ($spec['relations'] as $relation) {
        $body = array_merge($body, buildRelationMethod($relation));
        $body[] = '';
    }

    if (end($body) === '') {
        array_pop($body);
    }

    $body[] = '}';

    return implode(PHP_EOL, $body) . PHP_EOL;
}

function buildRelationMethod(array $relation): array
{
    $related = $relation['related'] . '::class';
    $method = [];
    $returnType = match ($relation['type']) {
        'belongsTo' => 'BelongsTo',
        'belongsToMany' => 'BelongsToMany',
        'hasMany' => 'HasMany',
        'hasOne' => 'HasOne',
    };

    $method[] = '    public function ' . $relation['name'] . '(): ' . $returnType;
    $method[] = '    {';

    if ($relation['type'] === 'belongsTo') {
        $foreignKey = $relation['foreignKey'];
        $ownerKey = $relation['ownerKey'] ?? 'id';
        $method[] = "        return \$this->belongsTo({$related}, '{$foreignKey}', '{$ownerKey}');";
    } elseif ($relation['type'] === 'hasMany') {
        $foreignKey = $relation['foreignKey'];
        $localKey = $relation['localKey'] ?? 'id';
        $method[] = "        return \$this->hasMany({$related}, '{$foreignKey}', '{$localKey}');";
    } elseif ($relation['type'] === 'hasOne') {
        $foreignKey = $relation['foreignKey'];
        $localKey = $relation['localKey'] ?? 'id';
        $method[] = "        return \$this->hasOne({$related}, '{$foreignKey}', '{$localKey}');";
    } else {
        $pivotTable = $relation['pivotTable'];
        $foreignPivotKey = $relation['foreignPivotKey'];
        $relatedPivotKey = $relation['relatedPivotKey'];
        $parentKey = $relation['parentKey'] ?? 'id';
        $relatedKey = $relation['relatedKey'] ?? 'id';
        $method[] = "        return \$this->belongsToMany({$related}, '{$pivotTable}', '{$foreignPivotKey}', '{$relatedPivotKey}', '{$parentKey}', '{$relatedKey}');";
    }

    $method[] = '    }';

    return $method;
}

function normalizeCast(string $cast): string
{
    if (str_ends_with($cast, '::class')) {
        return $cast;
    }

    return "'" . $cast . "'";
}

function writeFile(string $path, string $content): void
{
    $directory = dirname($path);

    if (! is_dir($directory)) {
        mkdir($directory, 0777, true);
    }

    file_put_contents($path, $content);
}
