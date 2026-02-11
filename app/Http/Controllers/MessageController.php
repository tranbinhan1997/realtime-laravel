<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\MessageImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MessageController extends Controller
{
    public function getMessage($userId)
    {
        return Message::with('images')->where(function ($q) use ($userId) {
                $q->where('from_user_id', auth()->id())
                ->where('to_user_id', $userId);
            })
            ->orWhere(function ($q) use ($userId) {
                $q->where('from_user_id', $userId)
                ->where('to_user_id', auth()->id());
            })
            ->orderBy('created_at')
            ->get()
            ->map(function ($m) {
                $user = $m->from_user_id === auth()->id() ? auth()->user() : \App\Models\User::find($m->from_user_id);

                return [
                    'id'            => $m->id,
                    'from_user_id'  => $m->from_user_id,
                    'to_user_id'    => $m->to_user_id,
                    'content'       => $m->content,
                    'video'         => $m->video_path ? asset('storage/' . $m->video_path) : null,
                    'images'        => $m->images->map(function ($img) {
                        return asset('storage/' . $img->image_path);
                    }),
                    'time'          => $m->created_at->diffForHumans(),
                    'user'          => $user->name,
                    'avatar'        => $user->avatar,
                ];
            });
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'to_user_id' => 'required|exists:users,id',
            'content'    => 'nullable|string',
            'images.*'   => 'nullable|image|max:2048'
        ]);

        $videoPath = null;
        if ($request->hasFile('video')) {
            $videoPath = $request->file('video')
                ->store('messages/videos', 'public');
        }

        $message = Message::create([
            'from_user_id' => auth()->id(),
            'to_user_id'   => $request->to_user_id,
            'content'      => $request->content,
            'video_path'   => $videoPath
        ]);

        $images = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $path = $file->store('messages', 'public');
                MessageImage::create([
                    'message_id' => $message->id,
                    'user_id'    => auth()->id(),
                    'image_path' => $path
                ]);
                $images[] = asset('storage/' . $path);
            }
        }

        $payload = [
            'id'           => $message->id,
            'from_user_id' => $message->from_user_id,
            'to_user_id'   => $message->to_user_id,
            'content'      => $message->content,
            'images'       => $images,
            'video'        => $videoPath ? asset('storage/' . $videoPath) : null,
            'time'         => $message->created_at->diffForHumans(),
            'user'         => auth()->user()->name,
            'avatar'       => auth()->user()->avatar,
        ];

        Http::post('http://localhost:3000/message-send', $payload);

        return $payload;
    }
}
