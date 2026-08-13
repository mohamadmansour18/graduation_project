<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BanUserRequest;
use App\Http\Requests\Admin\ListAcademicVerificationRequestsRequest;
use App\Http\Requests\Admin\ListBannedUsersRequest;
use App\Http\Requests\Admin\ListDashboardUsersRequest;
use App\Http\Requests\Admin\SearchDashboardUsersRequest;
use App\Http\Requests\Admin\ShowAcademicVerificationAssetRequest;
use App\Http\Requests\Admin\StoreSupervisorRequest;
use App\Http\Requests\Admin\UpdateDashboardPasswordRequest;
use App\Http\Requests\Admin\UpdateDashboardProfileRequest;
use App\Http\Resources\AcademicVerificationRequestResource;
use App\Http\Resources\BannedUserResource;
use App\Http\Resources\DashboardUserFoldersResource;
use App\Http\Resources\DashboardUserLibraryMaterialsResource;
use App\Http\Resources\DashboardUserProfileResource;
use App\Http\Resources\DashboardUserResource;
use App\Http\Resources\DashboardUserTestsResource;
use App\Http\Resources\SupervisorProfileResource;
use App\Http\Resources\UserBanHistoryResource;
use App\Models\User;
use App\Models\UserAcademicVerificationRequest;
use App\Services\Admin\UserDashboardService;
use App\Support\DashboardUsersCursor;
use App\Trait\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\CursorPaginator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserDashboardController extends Controller
{
    use ApiResponse;

    public function users(ListDashboardUsersRequest $request, UserDashboardService $service): JsonResponse
    {
        $type = $request->validated('type');
        $sortBy = $request->validated('sort_by', 'created_at');

        $result = $service->listUsers(
            type: $type,
            sortBy: $sortBy,
            perPage: (int) $request->validated('per_page', 20),
            cursor: DashboardUsersCursor::decode(
                $request->validated('cursor'),
                $type,
                $sortBy,
            ),
        );

        return $this->usersCursorPaginatedResponse(
            paginator: $result,
            data: DashboardUserResource::collection($result->items()),
            type: $type,
            sortBy: $sortBy,
        );
    }

    private function usersCursorPaginatedResponse(
        CursorPaginator $paginator,
        mixed $data,
        string $type,
        string $sortBy,
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => '! تم جلب المستخدمين بنجاح',
            'data' => $data,
            'meta' => [
                'per_page' => $paginator->perPage(),
                'next_cursor' => DashboardUsersCursor::encode($paginator->nextCursor(), $type, $sortBy),
                'prev_cursor' => DashboardUsersCursor::encode($paginator->previousCursor(), $type, $sortBy),
                'has_more_pages' => $paginator->hasMorePages(),
            ],
            'status_code' => 200,
        ]);
    }

    public function searchUsers(SearchDashboardUsersRequest $request, UserDashboardService $service): JsonResponse
    {
        $result = $service->searchUsers(
            search: $request->validated('search'),
            role: $request->validated('role'),
            perPage: (int) $request->validated('per_page', 20),
        );

        return $this->cursorPaginatedResponse(
            paginator: $result,
            data: DashboardUserResource::collection($result->items()),
            title: '! تم جلب المستخدمين بنجاح',
        );
    }

    public function storeSupervisor(StoreSupervisorRequest $request, UserDashboardService $service): JsonResponse
    {
        $service->createSupervisor(
            owner: $request->user(),
            data: $request->validated(),
        );

        return $this->successResponse(
            title: '! تمت إضافة المشرف بنجاح',
            message: 'تم إنشاء حساب المشرف بنجاح'
        );
    }

    public function bannedUsers(ListBannedUsersRequest $request, UserDashboardService $dashboardUserService): JsonResponse
    {
        $bannedUsers = $dashboardUserService->listBannedUsers(
            tab: $request->validated('tab', 'all'),
        );

        return $this->dataResponse(
            data : BannedUserResource::collection($bannedUsers),
        );
    }

    public function academicVerificationRequests(ListAcademicVerificationRequestsRequest $request, UserDashboardService $dashboardUserService): JsonResponse
    {
        $requests = $dashboardUserService->listAcademicVerificationRequests(
            sortBy: $request->validated('sort_by', 'submitted_at'),
        );

        return $this->dataResponse(
            data : AcademicVerificationRequestResource::collection($requests),
        );
    }

    public function showAcademicVerificationAsset(ShowAcademicVerificationAssetRequest $request, int $verificationRequestId, UserDashboardService $dashboardUserService): \Illuminate\Http\Response
    {
        $assetData = $dashboardUserService->getAcademicVerificationAssetContent(
            verificationRequestId: $verificationRequestId,
            documentType: $request->validated('document_type'),
        );

        return response($assetData['content'], 200, [
            'Content-Type' => $assetData['mime_type'],
            'Content-Disposition' => 'inline; filename="' . basename($assetData['file_name']) . '"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
        ]);
    }

    public function showUserProfile(UserDashboardService $dashboardUserService, int $userId): JsonResponse
    {
        $profile = $dashboardUserService->showUserProfile(
            userId: $userId,
        );

        return $this->dataResponse(
            data: new DashboardUserProfileResource($profile),
            title: '! تم جلب معلومات المستخدم بنجاح'
        );
    }

    public function showUserTests(int $userId, UserDashboardService $dashboardUserService): JsonResponse
    {
        $result = $dashboardUserService->showUserTests(
            userId: $userId,
        );

        return $this->dataResponse(
            data: new DashboardUserTestsResource($result),
            title: '! تم جلب معلومات اختبارات المستخدم بنجاح'
        );
    }

    public function showUserLibraryMaterials(int $userId, UserDashboardService $dashboardUserService): JsonResponse
    {
        $result = $dashboardUserService->showUserLibraryMaterials(
            userId: $userId,
        );

        return $this->dataResponse(
            data: new DashboardUserLibraryMaterialsResource($result),
            title: '! تم جلب معلومات محتوى المستخدم بنجاح'
        );
    }

    public function showUserFolders(int $userId, UserDashboardService $dashboardUserService): JsonResponse
    {
        $result = $dashboardUserService->showUserFolders(
            userId: $userId,
        );

        return $this->dataResponse(
            data: new DashboardUserFoldersResource($result),
            title: '! تم جلب معلومات قوائم المستخدم بنجاح'
        );
    }

    public function banUser(BanUserRequest $request, int $userId, UserDashboardService $dashboardUserService): JsonResponse {
        $dashboardUserService->banUser(
            owner: $request->user(),
            targetUserId: $userId,
            data: $request->validated(),
        );

        return $this->successResponse(
            title: '! تم حظر المستخدم بنجاح',
            message: 'تم تطبيق الحظر على حساب المستخدم'
        );
    }

    public function userBanHistory(int $userId, UserDashboardService $dashboardUserService): JsonResponse
    {
        $history = $dashboardUserService->getUserBanHistory(
            userId: $userId,
        );

        return $this->dataResponse(
            data: UserBanHistoryResource::collection($history),
            title: '! تم جلب سجل حظر المستخدم بنجاح'
        );
    }

    public function showSupervisor(int $userId, UserDashboardService $dashboardUserService): JsonResponse
    {
        $result = $dashboardUserService->showSupervisorProfile(
            userId: $userId,
        );

        return $this->dataResponse(
            data: new SupervisorProfileResource($result),
            title: '! تم جلب معلومات المشرف بنجاح'
        );
    }

    public function deleteSupervisor(int $supervisorId, UserDashboardService $dashboardUserService): JsonResponse
    {
        $dashboardUserService->deleteSupervisor(
            ownerId: \Auth::id(),
            supervisorId: $supervisorId,
        );

        return $this->successResponse(
            title: '! تم حذف المشرف بنجاح',
            message: 'تم حذف حساب المشرف من النظام'
        );
    }

    public function updateMyDashboardProfile(UpdateDashboardProfileRequest $request , int $adminId, UserDashboardService $dashboardUserService): JsonResponse
    {
        $dashboardUserService->updateMyDashboardProfile(
            adminId: $adminId,
            viewerId: \Auth::id(),
            data: $request->validated(),
        );

        return $this->successResponse(
            title: '! تم تعديل الملف الشخصي بنجاح',
            message: 'تم حفظ التعديلات بنجاح'
        );
    }

    public function updateMyDashboardPassword(UpdateDashboardPasswordRequest $request, UserDashboardService $dashboardUserService): JsonResponse
    {
        $dashboardUserService->updateMyDashboardPassword(
            user: $request->user(),
            oldPassword: $request->validated('old_password'),
            newPassword: $request->validated('new_password'),
        );

        return $this->successResponse(
            title: '! تم تعديل كلمة المرور بنجاح',
            message: 'تم حفظ كلمة المرور الجديدة بنجاح'
        );
    }

    public function liftBan(int $userId, UserDashboardService $dashboardUserService): JsonResponse
    {
        $dashboardUserService->liftUserBan(
            targetUserId: $userId,
            adminUserId: \Auth::id()
        );

        return $this->successResponse(
            message: 'تم رفع الحظر عن المستخدم بنجاح'
        );
    }
}
