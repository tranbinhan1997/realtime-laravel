<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostComment;
use App\Models\PostImage;
use App\Models\PostReaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $posts = Post::with(['user', 'images', 'comments.user'])->latest()->simplePaginate(10);

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
                ] : null,
                'reactions' => $p->reactions()->selectRaw('type, COUNT(*) as total')->groupBy('type')->pluck('total', 'type'),
                'comments' => $p->comments()
                    ->whereNull('parent_id')
                    ->with(['user', 'replies.user'])
                    ->get()
                    ->map(function ($c) {
                        return [
                            'id' => $c->id,
                            'content' => $c->content,
                            'user' => $c->user->name,
                            'avatar' => $c->user->avatar,
                            'parent_id' => null,
                            'replies' => $c->replies->map(function ($r) {
                                return [
                                    'id' => $r->id,
                                    'content' => $r->content,
                                    'user' => $r->user->name,
                                    'avatar' => $r->user->avatar,
                                    'parent_id' => $r->parent_id,
                                ];
                            })
                        ];
                    }),
                'comment_count' => $p->comments()->whereNull('parent_id')->count(),
                'user_reaction' => optional($p->reactions()->where('user_id', auth()->id())->first())->type,
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

    public function react(Request $request, $postId)
    {
        $request->validate([
            'type' => 'required|in:like,love,haha,wow,sad,angry'
        ]);
        $reaction = PostReaction::where('post_id', $postId)->where('user_id', auth()->id())->first();
        if ($reaction) {
            if ($reaction->type === $request->type) {
                $reaction->delete();
                $reacted = false;
            } else {
                $reaction->update([
                    'type' => $request->type
                ]);
                $reacted = true;
            }
        } else {
            PostReaction::create([
                'post_id' => $postId,
                'user_id' => auth()->id(),
                'type'    => $request->type
            ]);
            $reacted = true;
        }
        $summary = PostReaction::where('post_id', $postId)->selectRaw('type, COUNT(*) as total')->groupBy('type')->pluck('total', 'type');
        $payload = [
            'post_id' => $postId,
            'summary' => $summary,
            'user_id' => auth()->id(),
            'user_reaction' => $reacted ? $request->type : null
        ];
        Http::post('http://localhost:3000/post-react', $payload);
        return $payload;
    }

    public function comment(Request $request, $postId)
    {
        $request->validate([
            'content' => 'required|string',
            'parent_id' => 'nullable|exists:post_comments,id'
        ]);
        $parentId = $request->parent_id;
        if ($parentId) {
            $parent = PostComment::find($parentId);
            if ($parent && $parent->parent_id) {
                $parentId = $parent->parent_id;
            }
        }
        $comment = PostComment::create([
            'parent_id' => $request->parent_id,
            'post_id' => $postId,
            'user_id' => auth()->id(),
            'content' => $request->content
        ]);
        $commentCount = PostComment::where('post_id', $postId)->count();
        $payload = [
            'parent_id' => $comment->parent_id,
            'post_id' => $postId,
            'id' => $comment->id,
            'content' => $comment->content,
            'user' => auth()->user()->name,
            'avatar' => auth()->user()->avatar,
            'time' => $comment->created_at->diffForHumans(),
            'comment_count' => $commentCount
        ];
        Http::post("http://localhost:3000/post-comment", $payload);
        return $payload;
    }
}
