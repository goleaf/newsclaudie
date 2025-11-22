<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\SetLocaleRequest;
use Illuminate\Http\RedirectResponse;

final class LocaleController extends Controller
{
    /**
     * Persist the selected locale in the session.
     */
    public function update(SetLocaleRequest $request): RedirectResponse
    {
        $locale = $request->validated()['locale'];

        $request->session()->put('locale', $locale);
        app()->setLocale($locale);

        return back();
    }
}


