<?php

namespace App\Http\Controllers;

use App\Support\LocaleManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocaleController extends Controller
{
    public function update(Request $request, LocaleManager $localeManager): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => [
                'required',
                'string',
                Rule::in($localeManager->supportedLocales()),
            ],
        ]);

        $localeManager->persistSelection($request, $validated['locale']);

        return redirect()->to(url()->previous() ?: route('tickets.index'));
    }
}
