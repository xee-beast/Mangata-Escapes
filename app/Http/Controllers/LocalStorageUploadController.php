<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class LocalStorageUploadController extends Controller
{
    /**
     * Store an uploaded file to the public disk (for local development).
     * Returns the same format as Vapor's signed storage for compatibility.
     */
    public function store(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file'],
        ]);

        $file = $request->file('file');
        $uuid = (string) Str::uuid();
        $extension = $file->getClientOriginalExtension();
        $path = 'tmp/media/' . $uuid . ($extension ? '.' . $extension : '');

        $file->storeAs('tmp/media', $uuid . ($extension ? '.' . $extension : ''), 'public');

        return response()->json([
            'uuid' => $uuid,
            'key' => $path,
        ], 201);
    }
}
