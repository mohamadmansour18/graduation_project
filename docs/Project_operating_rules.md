# Project Operating Rules

> This document is used by AI coding tools and assistants to understand the operating rules, conventions, and architectural constraints of this Laravel API project. Any generated code, refactoring, API design, or debugging suggestion must respect these rules unless the existing codebase clearly proves otherwise.

---

## 1. Project architecture

- This is a **Laravel 10 API project**.
- Preferred architecture is:

```text
Route -> Controller -> Request (if necessary) -> Service -> Repository
```

- Use **Gate** and **Policy** classes for authorization.
- Do **not** bypass the Service layer unless the existing code already does so intentionally.
- Controllers should stay thin and should mainly handle:
  - receiving validated requests,
  - calling the proper Service method,
  - returning a unified API response.
- Business logic should live in Services.
- Database access and complex queries should live in Repositories when applicable.
- Avoid placing business rules directly inside Controllers.
- Keep the code modular, scalable, and easy to test.

---

## 2. Runtime and infrastructure

- The project uses **Laravel Sail**.
- The project uses **Laravel Octane**.
- Keep scalability in mind when proposing changes.
- Avoid solutions that create unnecessary tight coupling or reduce scalability.
- Avoid storing request-specific mutable state in long-lived services because Octane may keep workers alive between requests.
- Any proposed singleton/service should be safe under Octane.
- Any cache, queue, or logging solution should be compatible with a production Laravel environment.

---

## 3. Unified API response system

The project uses a unified response system through a reusable `ApiResponse` trait.

All Controllers should use this trait when returning API responses instead of manually writing JSON response structures.

### 3.1 Trait structure

```php
trait ApiResponse
{
    protected function successResponse(string $title = '! تمت العملية بنجاح' , string $message = '' , int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'title' => $title,
            'message' => $message,
            'status_code' => $statusCode,
        ], $statusCode);
    }

    protected function dataResponse(mixed $data = null , string $title = '! تم جلب البيانات بنجاح', int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'title' => $title,
            'data' => $data,
            'status_code' => $statusCode,
        ], $statusCode);
    }

    protected function paginatedResponse(LengthAwarePaginator $paginator , string $title = '! تم جلب البيانات بنجاح', int $statusCode = 200): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $title,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'has_more_pages' => $paginator->hasMorePages(),
            ],
            'status_code' => $statusCode,
        ], $statusCode);
    }

    protected function downloadResponse(string $filePath , ?string $downloadName = null, array $headers = []): BinaryFileResponse {
        return response()
            ->download($filePath, $downloadName, $headers);
    }

    protected function downloadAndDeleteResponse(string $filePath , ?string $downloadName = null, array $headers = []): BinaryFileResponse {
        return response()
            ->download($filePath, $downloadName, $headers)
            ->deleteFileAfterSend(true);
    }
}
```

### 3.2 Response rules for AI tools

- Use `successResponse()` when an operation succeeds and does not need to return data.
- Use `dataResponse()` when returning a single object, array, DTO result, or non-paginated collection.
- Use `paginatedResponse()` when returning a `LengthAwarePaginator`.
- Use `downloadResponse()` when returning a file download while keeping the file after sending.
- Use `downloadAndDeleteResponse()` when returning a temporary file that should be deleted after sending.
- Do not invent a different response structure unless the user explicitly asks for a new response format.
- Keep Arabic response titles and messages whenever possible.
- Prefer consistent keys:
  - `success`
  - `title` or `message` depending on the existing helper
  - `data`
  - `meta`
  - `status_code`
- If modifying the trait in the future, preserve backward compatibility with existing API clients.

---

## 4. Business-rule exception system

The project uses a custom exception system for business-rule errors.

Business-rule errors should not be handled by returning manual JSON responses from Services. Instead, Services should throw domain-specific exceptions that extend the base `ApiException` class.

### 4.1 Base exception class

```php
class ApiException extends Exception
{
    public function __construct(
        protected string $title,
        string $message,
        protected int $status = 400,
        protected array $extraContext = []
    ){
        parent::__construct($message, $status);
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getContext(): array
    {
        return $this->extraContext;
    }
}
```

### 4.2 Domain-specific exception classes

Each module can define a specific exception class that extends `ApiException`.

Example:

```php
class AuthenticationException extends ApiException
{
    //--------------------------------[REGISTER]--------------------------------//
    public static function emailAlreadyRegistered(): self
    {
        return new self(
            title: '! فشل إنشاء الحساب',
            message: 'البريد الإلكتروني مستخدم بالفعل يرجى استخدام بريد إلكتروني آخر',
            status: 422
        );
    }

    //--------------------------------[LOGIN]--------------------------------//

    public static function invalidCredentials(): self
    {
        return new self(
            title: '! فشل تسجيل الدخول',
            message: 'البريد الإلكتروني أو كلمة المرور غير صحيحين',
            status: 401
        );
    }
}
```

### 4.3 Exception usage rules for AI tools

- Use `ApiException` descendants for business-rule violations.
- Do not throw generic `Exception` for expected business cases.
- Do not return manual error responses from the Service layer.
- Service methods should throw meaningful module exceptions such as:
  - `AuthenticationException`
  - `TestException`
  - `LibraryMaterialException`
  - `StudyPlanException`
  - or any existing module-specific exception class.
- Use static named constructors for readable business errors.
- Static methods should describe the exact error case clearly.
- Arabic titles and messages are preferred.
- HTTP status codes should match the business case:
  - `400` for invalid business operation,
  - `401` for authentication failure,
  - `403` for authorization/access denial,
  - `404` when a required resource does not exist,
  - `409` for conflict cases,
  - `422` for validation-like business failures.
- Use `extraContext` only for useful debugging/logging context, not for exposing sensitive data.
- Never include passwords, OTP codes, JWT tokens, or private user data inside `extraContext`.

### 4.4 Expected handling behavior

- The global exception handler should convert `ApiException` into the unified API error response.
- The response should preserve:
  - exception title,
  - exception message,
  - exception HTTP status,
  - request metadata when available.
- AI tools should not duplicate exception handling inside every Controller unless the project already requires that in a specific place.

---

## 5. Logging system

The project has a professional logging setup with separated channels for application logs, error logs, and audit logs.

### 5.1 Logging configuration

```php
'default' => env('LOG_CHANNEL', 'stack'),

'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['daily'],
        'ignore_exceptions' => false,
    ],

    'daily' => [
        'driver' => 'daily',
        'path' => storage_path('logs/applicationLog/application.log'),
        'level' => env('LOG_LEVEL', 'info'),
        'days' => 20,
        'replace_placeholders' => true,
    ],

    'errors' => [
        'driver' => 'daily',
        'path' => storage_path('logs/ErrorLog/errors.log'),
        'level' => env('LOG_LEVEL', 'error'),
        'days' => 40,
        'replace_placeholders' => true,
    ],

    'audit' => [
        'driver' => 'daily',
        'path' => storage_path('logs/AuditLog/audit.log'),
        'level' => env('LOG_AUDIT_LEVEL', 'info'),
        'days' => 60,
        'replace_placeholders' => true,
    ],
],
```

### 5.2 Logging channel purposes

#### `daily`

Use this channel for normal application logs and useful operational information.

Examples:

- important flow milestones,
- successful scheduled tasks,
- non-sensitive debugging context,
- service-level informational events.

#### `errors`

Use this channel for unexpected errors and exceptions that need investigation.

Examples:

- unhandled exceptions,
- failed external service calls,
- failed background jobs,
- unexpected database or filesystem errors.

#### `audit`

Use this channel for important user/admin actions that may need future tracking.

Examples:

- login attempts when relevant,
- admin decisions,
- approval/rejection actions,
- publishing/unpublishing content,
- financial or purchase-related actions,
- sensitive profile or account changes.

### 5.3 Logging rules for AI tools

- Use the correct log channel according to the purpose of the event.
- Do not log sensitive information:
  - passwords,
  - OTP codes,
  - JWT tokens,
  - full payment credentials,
  - private files,
  - unnecessary personal data.
- Prefer structured logging context arrays.
- Include useful identifiers when safe:
  - `user_id`,
  - `test_id`,
  - `material_id`,
  - `request_id`,
  - `action`,
  - `status`.
- Do not over-log high-frequency actions unless there is a clear audit or debugging need.
- For expected business-rule failures, prefer throwing `ApiException` descendants instead of logging every case as an error.
- Unexpected system failures should be logged in the `errors` channel.
- Important administrative or security-sensitive actions should be logged in the `audit` channel.

---

## 6. Validation and Requests

- Use Form Request classes when validation logic is non-trivial or reused.
- Keep validation messages in Arabic when possible.
- Do not rely on validation alone for business rules that depend on database state or current user context.
- Validation errors and business-rule errors are separate concepts:
  - request format/data validation belongs in Request classes,
  - domain/business decisions belong in Services and should throw `ApiException` descendants.

---

## 7. Authorization

- Use Laravel Gates and Policies for authorization.
- Do not hide authorization logic inside repositories.
- Authorization should be checked before performing protected actions.
- For unauthorized access, return a consistent error response through the project exception/handler flow.

---

## 8. Service and Repository rules

- Services own business orchestration.
- Repositories own database querying and persistence details.
- Repositories should not return HTTP responses.
- Services should not directly format JSON responses.
- Controllers should not contain complex query logic.
- Keep DTOs or structured arrays consistent when moving data between layers.
- When updating counters or high-frequency values, consider race conditions and use atomic database operations where appropriate.

---

## 9. Queue, events, and listeners

- Use Events and Listeners when the side effect is conceptually separate from the main request flow.
- Use Queues for non-critical side effects that do not need to block the user response.
- Be careful when queueing tasks that affect data the user expects to see immediately.
- For immediate counters or UI-critical state, update the required data synchronously first, then dispatch background work if needed.
- Queue jobs should be idempotent when possible.
- Failed jobs should be observable through logs and Laravel Horizon if configured.

---

## 10. Reference documents

- Database schema and business logic are located in the project reference file:
  - `AI_DATABASE_CONTEXT.md` — use this file to understand database schema, relationships, and business logic.
- AI tools must read and respect the database context before proposing schema changes, migrations, relationships, or query logic.
- Do not rename tables, columns, or enums unless explicitly requested.
- Preserve existing project naming conventions, even when a name contains a typo, unless the user explicitly decides to rename it.

---

## 11. General code style rules

- Prefer clear, readable, production-oriented code.
- Keep Arabic API messages where user-facing.
- Keep code compatible with Laravel 10.
- Keep solutions scalable and maintainable.
- Do not introduce unnecessary packages.
- Do not add architectural complexity unless it solves a real project need.
- When suggesting code, follow the existing project patterns first.
