<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    /**
     * Handle Asset Upload
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:5120', // 5MB Limit
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filename = Str::random(10) . '_' . time() . '.' . $file->getClientOriginalExtension();
            
            $path = $file->storeAs('public/assets', $filename);
            $url = asset(Storage::url($path));

            return response()->json([
                'success' => true,
                'url' => $url,
                'name' => $file->getClientOriginalName()
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Upload failed.'], 400);
    }

    /**
     * List Assets (Optional)
     */
    public function index()
    {
        $files = Storage::files('public/assets');
        $assets = array_map(function($file) {
            return [
                'name' => basename($file),
                'url' => asset(Storage::url($file)),
            ];
        }, $files);

        return response()->json($assets);
    }
}
