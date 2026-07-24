<?php

if (! function_exists('naira')) {
    function naira(int $kobo): string
    {
        return '₦'.number_format($kobo / 100);
    }
}

if (! function_exists('app_logo_url')) {
    function app_logo_url(): ?string
    {
        $path = \App\Models\Setting::get('app_logo_path');

        return $path ? \Illuminate\Support\Facades\Storage::disk('public')->url($path) : null;
    }
}
