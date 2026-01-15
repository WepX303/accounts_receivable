<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;

class ChatController extends Controller
{
    public function index()
    {
        return view('pages.chat.chat');
    }
}
