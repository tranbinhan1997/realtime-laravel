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
        return Post::with('user')
        ->limit(20)
        ->get()
        ->map(function ($p) {
            return [
                'id' => $p->id,
                'user' => $p->user->name,
                'content' => $p->content,
                'time' => $p->created_at->diffForHumans(),
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
            'link_url' => $request->link['url'] ?? null,
            'link_title' => $request->link['title'] ?? null,
            'link_desc' => $request->link['desc'] ?? null,
            'link_image' => $request->link['image'] ?? null,
        ]);

        PostImage::where('user_id', auth()->id())
            ->whereNull('post_id')
            ->update(['post_id' => $post->id]);


        $payload = [
            'id' => $post->id,
            'user' => $post->user->name,
            'content' => $post->content,
            'time' => $post->created_at->diffForHumans(),
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
