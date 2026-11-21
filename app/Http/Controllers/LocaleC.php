<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class LocaleC extends Controller
{
    /**
     * Switch application locale.
     */
    public function switch(Request $request, string $locale)
    {
        if (in_array($locale, ['tr', 'en'])) {
            Session::put('locale', $locale);
        }

        return redirect()->back();
    }
}

