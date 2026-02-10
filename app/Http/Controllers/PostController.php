<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $posts = Post::with(['user', 'images'])->latest()->simplePaginate(10);

        $data = collect($posts->items())->map(function ($p) {
            return [
                'id' => $p->id,
                'user' => $p->user->name,
                'avatar' => $p->user->avatar,
                'author_id' => $p->user_id,
                'content' => $p->content,
                'time' => $p->created_at->diffForHumans(),
                'images' => $p->images->map(function ($img) {
                    return asset('storage/' . $img->image_path);
                }),
                'video' => $p->video_path
                    ? asset('storage/' . $p->video_path)
                    : null,
                'link' => $p->link_url ? [
                    'url' => $p->link_url,
                    'title' => $p->link_title,
                    'desc' => $p->link_desc,
                    'image' => $p->link_image,
                ] : null
            ];
        });

        return response()->json([
            'data' => $data,
            'next_page' => $posts->nextPageUrl()
        ]);
    }

    public function store(Request $request)
    {
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
            'avatar' => $post->user->avatar,
            'author_id'=> $post->user_id,
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

    public function update(Request $request, Post $post)
    {
        abort_if($post->user_id !== auth()->id(), 403);

        $post->update([
            'content' => $request->content,
            'video_path' => $request->video['path'] ?? null,
            'link_url'   => $request->link['url'] ?? null,
            'link_title' => $request->link['title'] ?? null,
            'link_desc'  => $request->link['desc'] ?? null,
            'link_image' => $request->link['image'] ?? null,
        ]);

        $$newImages = collect($request->images ?? [])->map(function ($path) {
            return str_replace(url('/storage') . '/', '', $path);
        })->values();

        $oldImages = $post->images->pluck('image_path');

        $imagesToDelete = $oldImages->diff($newImages);

        foreach ($imagesToDelete as $path) {
            Storage::disk('public')->delete($path);
            $post->images()->where('image_path', $path)->delete();
        }

        $imagesToAdd = $newImages->diff($oldImages);

        foreach ($imagesToAdd as $path) {
            PostImage::create([
                'post_id'    => $post->id,
                'user_id'    => auth()->id(),
                'image_path' => $path
            ]);
        }

        $post->load(['user', 'images']);

        $payload = [
            'id' => $post->id,
            'user' => $post->user->name,
            'avatar' => $post->user->avatar,
            'author_id'=> $post->user_id,
            'content' => $post->content,
            'time' => now()->diffForHumans(),
            'images' => $p->images->map(function ($img) {
                return asset('storage/' . $img->image_path);
            }),
            'video' => $post->video_path
                ? asset('storage/' . $post->video_path)
                : null,
            'link' => $post->link_url ? [
                'url' => $post->link_url,
                'title' => $post->link_title,
                'desc' => $post->link_desc,
                'image' => $post->link_image,
            ] : null
        ];

        Http::post('http://127.0.0.1:3000/post-update', $payload);

        return response()->json($payload);
    }

    public function destroy(Post $post)
    {
        if ($post->user_id !== auth()->id()) {
            abort(403);
        }

        foreach ($post->images as $img) {
            Storage::disk('public')->delete($img->image_path);
        }

        $post->images()->delete();

        if ($post->video_path) {
            Storage::disk('public')->delete($post->video_path);
        }

        $post->delete();

        Http::post("http://localhost:3000/post-delete", [
            'id' => $post->id
        ]);

        return response()->json([
            'success' => true
        ]);
    }
}
