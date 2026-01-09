<?php

namespace App\Http\Controllers;

use App\Models\PostImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class UploadController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'upload' => 'required|image|max:5120'
        ]);

        $image = Image::make($request->file('upload'))
            ->resize(1200, null, function ($c) {
                $c->aspectRatio();
                $c->upsize();
            })
            ->encode('jpg', 85);

        $name = uniqid() . '.jpg';
        $path = "posts/$name";

        Storage::disk('public')->put($path, $image);

        return response()->json([
            'url'  => asset('storage/' . $path),
            'path' => $path
        ]);
    }

    public function uploadVideo(Request $request)
    {
        $request->validate([
            'video' => 'required|mimes:mp4,webm|max:51200'
        ]);

        $path = $request->file('video')->store('videos', 'public');

        return response()->json([
            'url' => asset('storage/' . $path),
            'path' => $path
        ]);
    }
}
