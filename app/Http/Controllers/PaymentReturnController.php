<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentReturnController extends Controller
{
    public function success(Request $request): View
    {
        return view('payments.return-success', [
            'deepLink' => $this->deepLink('success', $request),
        ]);
    }

    public function cancel(Request $request): View
    {
        return view('payments.return-cancel', [
            'deepLink' => $this->deepLink('cancel', $request),
        ]);
    }

    private function deepLink(string $result, Request $request): string
    {
        $attemptId = filter_var($request->query('payment_attempt_id'), FILTER_VALIDATE_INT);
        $scheme = trim((string) config('payments.app_deep_link_scheme', 'nerd'), ':/');
        $query = $attemptId && $attemptId > 0
            ? '?payment_attempt_id=' . $attemptId
            : '';

        return "{$scheme}://payment/return/{$result}{$query}";
    }
}
