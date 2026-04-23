<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Schema;

class LocaleManager
{
    public const SESSION_KEY = 'app.locale';
    public const COOKIE_KEY = 'app_locale';

    public function supportedLocales(): array
    {
        $supportedLocales = array_values(array_filter(
            config('helpdesk.supported_locales', []),
            fn ($locale) => is_string($locale) && $locale !== '',
        ));

        if ($supportedLocales !== []) {
            return $supportedLocales;
        }

        return [config('app.locale', 'en')];
    }

    public function defaultLocale(): string
    {
        return $this->normalize('en')
            ?? $this->normalize(config('app.fallback_locale', 'en'))
            ?? $this->normalize(config('app.locale', 'en'))
            ?? $this->supportedLocales()[0];
    }

    public function normalize(?string $locale): ?string
    {
        if (! is_string($locale) || $locale === '') {
            return null;
        }

        $locale = strtolower($locale);

        return in_array($locale, $this->supportedLocales(), true)
            ? $locale
            : null;
    }

    public function isSupported(?string $locale): bool
    {
        return $this->normalize($locale) !== null;
    }

    public function resolveForRequest(Request $request): string
    {
        return $this->requestScopedLocale($request)
            ?? $this->authenticatedUserLocale()
            ?? $this->normalize($request->session()->get(self::SESSION_KEY))
            ?? $this->normalize($request->cookie(self::COOKIE_KEY))
            ?? $this->browserLocale($request)
            ?? $this->defaultLocale();
    }

    public function applyToRequest(Request $request): string
    {
        $locale = $this->resolveForRequest($request);

        app()->setLocale($locale);
        $request->setLocale($locale);
        $request->session()->put(self::SESSION_KEY, $locale);

        if ($request->cookie(self::COOKIE_KEY) !== $locale) {
            Cookie::queue(cookie()->forever(self::COOKIE_KEY, $locale));
        }

        return $locale;
    }

    public function persistSelection(Request $request, string $locale): string
    {
        $normalizedLocale = $this->normalize($locale) ?? $this->defaultLocale();

        $request->session()->put(self::SESSION_KEY, $normalizedLocale);
        Cookie::queue(cookie()->forever(self::COOKIE_KEY, $normalizedLocale));

        $authenticatedUser = auth()->user();

        if (
            $this->supportsPreferredLocale()
            && $authenticatedUser instanceof User
            && $authenticatedUser->preferred_locale !== $normalizedLocale
        ) {
            $authenticatedUser->forceFill([
                'preferred_locale' => $normalizedLocale,
            ])->saveQuietly();
        }

        app()->setLocale($normalizedLocale);
        $request->setLocale($normalizedLocale);

        return $normalizedLocale;
    }

    private function authenticatedUserLocale(): ?string
    {
        $authenticatedUser = auth()->user();

        if (! $authenticatedUser instanceof User) {
            return null;
        }

        if (! $this->supportsPreferredLocale()) {
            return null;
        }

        return $this->normalize($authenticatedUser->preferred_locale);
    }

    private function requestScopedLocale(Request $request): ?string
    {
        return $this->normalize($request->header('X-Helpdesk-Locale'))
            ?? $this->normalize($request->input('_locale'));
    }

    private function browserLocale(Request $request): ?string
    {
        foreach ($request->getLanguages() as $language) {
            foreach ($this->browserLocaleCandidates($language) as $candidate) {
                $normalizedLocale = $this->normalize($candidate);

                if ($normalizedLocale !== null) {
                    return $normalizedLocale;
                }
            }
        }

        return null;
    }

    private function browserLocaleCandidates(string $language): array
    {
        $normalizedLanguage = strtolower(str_replace('_', '-', $language));
        $candidates = [$normalizedLanguage];

        if (str_contains($normalizedLanguage, '-')) {
            $candidates[] = explode('-', $normalizedLanguage)[0];
        }

        return array_values(array_unique(array_filter($candidates)));
    }

    private function supportsPreferredLocale(): bool
    {
        static $supportsPreferredLocale;

        if ($supportsPreferredLocale === null) {
            $supportsPreferredLocale = Schema::hasColumn('users', 'preferred_locale');
        }

        return $supportsPreferredLocale;
    }
}
