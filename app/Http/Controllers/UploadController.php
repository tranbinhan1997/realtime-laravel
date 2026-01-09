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
        Storage::disk('public')->put("posts/$name", $image);

        $url = asset("storage/posts/$name");

        PostImage::create([
            'user_id' => auth()->id(),
            'url' => $url
        ]);

        return response()->json([
            'url' => $url
        ]);
    }
}
