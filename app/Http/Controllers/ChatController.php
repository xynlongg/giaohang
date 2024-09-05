<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Events\MessageSent;
use App\Events\GreetingSent;
use App\Models\User;
use App\Models\Message;

class ChatController extends Controller
{
    public function showChat(){
            $messages = Message::with('user')->get();
            return view('chat.show', compact('messages'));
}

    public function messageReceived(Request $request){
        $rules = [
            'message' => 'required',
        ];

        $request->validate($rules);

        // Save the message to the database
        $message = Message::create([
            'user_id' => $request->user()->id,
            'message' => $request->message,
        ]);

        // Broadcast the message
        broadcast(new MessageSent($request->user(), $request->message));

        return response()->json('Message broadcast and saved');
    }

    public function greetReceived(Request $request, User $receiver){
        broadcast(new GreetingSent($receiver, "{$request->user()->name} đã chào bạn"));
        broadcast(new GreetingSent($request->user(), "Bạn đã chào {$receiver->name}"));

        return "Lời chào từ {$request->user()->name} đến {$receiver->name}";
    }
}
