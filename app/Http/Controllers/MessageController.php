<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MessageController extends Controller
{
    public function getMessage($userId)
    {
        return Message::where(function ($q) use ($userId) {
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
                    'time'          => $m->created_at->diffForHumans(),
                    'user'          => $user->name,
                    'avatar'        => $user->avatar,
                ];
            });
    }

    public function sendMessage(Request $request)
    {
        $message = Message::create([
            'from_user_id' => auth()->id(),
            'to_user_id'   => $request->to_user_id,
            'content'      => $request->content,
        ]);

        $payload = [
            'id'           => $message->id,
            'from_user_id' => $message->from_user_id,
            'to_user_id'   => $message->to_user_id,
            'content'      => $message->content,
            'time'         => $message->created_at->diffForHumans(),
            'user'         => auth()->user()->name,
            'avatar'       => auth()->user()->avatar,
        ];

        Http::post('http://localhost:3000/message-send', $payload);

        return $payload;
    }
}
