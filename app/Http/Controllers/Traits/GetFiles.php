<?php

namespace App\Http\Controllers\Traits;

use App\Models\File;

trait GetFiles
{
    public function getFiles($files)
    {
        $uploadedFiles = collect();

        foreach($files as $file) {
            $uploadedFile = $this->getFile($file);
            $uploadedFiles->push($uploadedFile);
        }

        return $uploadedFiles;
    }

    /**
     * Process single file
     */
    public function getFile($file)
    {
        $uploadedFile = File::firstOrCreate(
            ['id' => $file['uuid']],
            [
                'path' => str_replace('tmp/', '', $file['path']),
                'name' => $file['name'] ?? null,
                'mime_type' => $file['mime_type'] ?? null,
            ]
        );

        return $uploadedFile;
    }
}
