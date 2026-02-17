<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class DomPdfFontService
{
    public static function ensureFontsArePrepared(): void
    {
        $flagFile = '/tmp/.fonts_copied'; // To check if fonts have already been copied

        if (file_exists($flagFile)) {
            return;
        }

        $source = base_path('storage/fonts');
        $destination = storage_path('fonts');

        if (!file_exists($destination)) {
            mkdir($destination, 0777, true);
        }

        if (is_dir($source) && self::isDirEmpty($destination)) {
            File::copyDirectory($source, $destination);
        }

        file_put_contents($flagFile, 'ok');
    }

    private static function isDirEmpty(string $dir): bool
    {
        return is_readable($dir) && count(scandir($dir)) === 2;
    }
}
