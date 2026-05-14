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
}
//'https://play.google.com/store/apps/details?id=com.yourcompany.nerd'
