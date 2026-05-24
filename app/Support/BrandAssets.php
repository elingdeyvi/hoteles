<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

class BrandAssets
{
    public static function logoUrl(?string $propertyCode = null): string
    {
        if ($propertyCode) {
            foreach (['svg', 'png'] as $ext) {
                $relative = "images/brands/{$propertyCode}-logo.{$ext}";
                if (File::exists(public_path($relative))) {
                    return asset($relative);
                }
            }
        }

        if (File::exists(public_path('images/logo-corporate.svg'))) {
            return asset('images/logo-corporate.svg');
        }

        if (File::exists(public_path('images/logo-corporate.png'))) {
            return asset('images/logo-corporate.png');
        }

        return asset('favicon.svg');
    }

    public static function faviconUrl(): string
    {
        if (File::exists(public_path('favicon.svg'))) {
            return asset('favicon.svg');
        }

        if (File::exists(public_path('favicon.png'))) {
            return asset('favicon.png');
        }

        return asset('images/default/favicon.png');
    }

    public static function primaryColor(?string $propertyCode = null): string
    {
        return match ($propertyCode) {
            'sierra-verde' => '#1e4d3a',
            default => '#1a365d',
        };
    }
}
