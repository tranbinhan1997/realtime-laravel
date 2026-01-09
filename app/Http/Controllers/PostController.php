<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PostController extends Controller
{
    public function index()
    {
        return Post::with(['user', 'images'])
        ->limit(20)
        ->get()
        ->map(function ($p) {
            return [
                'id' => $p->id,
                'user' => $p->user->name,
                'content' => $p->content,
                'time' => $p->created_at->diffForHumans(),
                'images' => $p->images->map(function ($img) {
                    return asset('storage/' . $img->image_path);
                }),
                'video' => $p->video_path ? asset('storage/' . $p->video_path) : null,
                'link' => $p->link_url ? [
                    'url' => $p->link_url,
                    'title' => $p->link_title,
                    'desc' => $p->link_desc,
                    'image' => $p->link_image,
                ] : null
            ];
        });
    }

    public function store(Request $request)
    {
        $request->validate([
            'content' => 'required|min:1'
        ]);

        $link = $request->input('link');

        $post = Post::create([
            'user_id' => auth()->id(),
            'content' => $request->content,
            'video_path' => $request->video['path'] ?? null,
            'link_url' => $request->link['url'] ?? null,
            'link_title' => $request->link['title'] ?? null,
            'link_desc' => $request->link['desc'] ?? null,
            'link_image' => $request->link['image'] ?? null,
        ]);

        foreach ($request->images ?? [] as $path) {
            PostImage::create([
                'post_id' => $post->id,
                'user_id' => auth()->id(),
                'image_path' => $path
            ]);
        }


        $payload = [
            'id' => $post->id,
            'user' => $post->user->name,
            'content' => $post->content,
            'time' => $post->created_at->diffForHumans(),
            'images' => $post->images->map(function ($img) {
                return asset('storage/' . $img->image_path);
            }),
            'video' => $post->video_path ? asset('storage/' . $post->video_path): null,
            'link' => $link ? [
                'url' => $post->link_url,
                'title' => $post->link_title,
                'desc' => $post->link_desc,
                'image' => $post->link_image
            ] : null
        ];

        Http::post("http://localhost:3000/post", $payload);

        return $payload;
    }
}
