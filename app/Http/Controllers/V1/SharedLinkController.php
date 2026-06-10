<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;

class SharedLinkController extends Controller
{
    public function test(string $slug)
    {
        return view('share.test' , [
            'deepLink' => "nerd://tests/{$slug}",
            'fallbackUrl' => url('/app-not-installed'),
        ]);
    }

    public function libraryMaterial(string $slug)
    {
        return view('share.library-material', [
            'deepLink' => "nerd://library/{$slug}",
            'fallbackUrl' => url('/app-not-installed'),
        ]);
    }

    public function profile(string $slug)
    {
        return view('share.profile', [
            'deepLink' => "nerd://profiles/{$slug}",
            'fallbackUrl' => url('/app-not-installed'),
        ]);
    }
}
//'https://play.google.com/store/apps/details?id=com.yourcompany.nerd'
