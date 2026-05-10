<?php

namespace App\Services\Tests;

use App\Events\TestDownloaded;
use App\Exceptions\Api\ApiException;
use App\Exceptions\Api\TestException;
use App\Models\Test;
use App\Repositories\Tests\TestDownloadRepository;
use App\Services\Cache\CacheKeys;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

class TestDownloadService
{
    private const MAX_DOWNLOAD_QUESTIONS = 100;
    private const PDF_TEMPLATE_VERSION = 'v1';
    private const PDF_CACHE_DIR = 'pdf-cache/test-downloads';
    private const PDF_CACHE_TTL_DAYS = 7;

    public function __construct(
        private readonly TestDownloadRepository $testDownloadRepository,
    ) {}

    public function downloadPdf(int $testId, int $userId): array
    {
        $eventPayload = null;

        $test = $this->testDownloadRepository->findDownloadableTest($testId);

        if (! $test) {
            throw TestException::notFound();
        }

        if((int) $test->question_count > self::MAX_DOWNLOAD_QUESTIONS)
        {
            throw TestException::downloadFileTooLarge();
        }

        //verify if test paid that user buy it
        $isOwner = (int) $test->creator_user_id === $userId;
        $isFree = is_null($test->price) || (float) $test->price <= 0;

        $hasAccess = $isOwner || $isFree;

        if (! $hasAccess) {
            $hasAccess = $this->testDownloadRepository->hasUserPurchasedTest($testId, $userId);
        }

        if (! $hasAccess) {
            throw TestException::purchaseRequiredForDownload();
        }

        $filePath = $this->getOrGenerateCachedPdf($test);

        DB::transaction(callback: function () use ($test, $userId, &$eventPayload) {

            $created = $this->testDownloadRepository->createDownloadLogIfMissing(
                testId: (int) $test->id,
                userId: $userId,
            );

            if ($created) {
                $this->testDownloadRepository->incrementTestDownloadsCount((int) $test->id);

                $eventPayload = [
                    'testId' => (int) $test->id,
                    'userId' => $userId,
                    'downloadedAt' => now(),
                ];
            }

        });

        if ($eventPayload !== null)
        {
            event(new TestDownloaded(...$eventPayload));
        }

        return [
            'file_path' => $filePath,
            'download_name' => $this->makeDownloadName($test->title),
        ];
    }

    private function getOrGenerateCachedPdf(Test $test): string
    {
        $fingerprint = $this->makePdfFingerprint($test);
        $filePath = $this->makeCachedPdfPath($test, $fingerprint);

        if (File::exists($filePath)) {
            return $filePath;
        }

        $lockKey = CacheKeys::testPdfDownloadLock((int) $test->id);

        return Cache::lock($lockKey, 20)->block(10, function () use ($test, $filePath, $fingerprint) {
            if (File::exists($filePath)) {
                return $filePath;
            }
            return $this->generate($test, $filePath);
        });
    }

    private function generate(Test $test , string $filePath): string
    {
        $mpdfTempDirectory = storage_path('app/temp/mpdf');

        if (! File::exists($mpdfTempDirectory)) {
            File::makeDirectory($mpdfTempDirectory, 0755, true);
        }

        $tempFilePath = $filePath . '.tmp';

        try {

            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'orientation' => 'P',
                'margin_left' => 12,
                'margin_right' => 12,
                'margin_top' => 12,
                'margin_bottom' => 14,
                'tempDir' =>  $mpdfTempDirectory,
                'default_font' => 'dejavusans',
                'autoScriptToLang' => true,
                'autoLangToFont' => true,

            ]);

            $mpdf->SetTitle($test->title);
            $mpdf->SetAuthor('Nerd');
            $mpdf->SetCreator('Nerd App');

            $html = view('pdf.tests.download', [
                'test' => $test,
                'logoSrc' => $this->makeLogoSrc(),
            ])->render();

            $mpdf->WriteHTML($html);
            $mpdf->Output($tempFilePath, \Mpdf\Output\Destination::FILE);

            File::move($tempFilePath, $filePath);

            return $filePath;

        }catch (\Throwable $exception)
        {
            if (File::exists($tempFilePath)) {
                File::delete($tempFilePath);
            }

            Log::channel('errors')->error('PDF generation failed' , [
                'message' => $exception->getMessage(),
                'trace'   => $exception->getTraceAsString(),
            ]);
            throw new ApiException('! حدث خطأ غير متوقع اثناء التنفيذ', 'فشل تنزيل الملف لاسباب غير متوقعة' , 500);
        }
    }

    private function makeDownloadName(string $title): string
    {
        $safeTitle = trim($title);

        $safeTitle = preg_replace('/[\\\\\/:*?"<>|]+/u', '', $safeTitle);

        $safeTitle = preg_replace('/\s+/u', ' ', $safeTitle);

        $safeTitle = trim($safeTitle, " .\t\n\r\0\x0B");

        if ($safeTitle === '') {
            $safeTitle = 'test';
        }

        return $safeTitle . '.pdf';
    }

    private function makeLogoSrc(): ?string
    {
        $logoPath = public_path('Logo/Nerd-Logo.png');

        if (! File::exists($logoPath)) {
            return null;
        }

        $mimeType = File::mimeType($logoPath) ?: 'image/png';

        return 'data:' . $mimeType . ';base64,' . base64_encode(File::get($logoPath));
    }

    private function makePdfFingerprint(Test $test): string
    {
        $versionTime = $test->last_content_updated_at
            ? $test->last_content_updated_at->timestamp
            : optional($test->updated_at)->timestamp;

        return md5(implode('|', [
            self::PDF_TEMPLATE_VERSION,
            $test->id,
            $test->question_count,
            $test->current_approval_version,
            $versionTime,
        ]));
    }

    private function makeCachedPdfPath(Test $test, string $fingerprint): string
    {
        $directory = storage_path('app/' . self::PDF_CACHE_DIR);

        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        return $directory . '/test-' . $test->id . '-' . $fingerprint . '.pdf';
    }


}
