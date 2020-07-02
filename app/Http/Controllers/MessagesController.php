<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Handlers\WorkermanHandler;
use Workerman\Worker;

use Workerman\Lib\Timer;

class MessagesController extends Controller
{
    //
    public function messagesShow()
    {

        return view('test');

    }

    public function sundMessages()
    {

    }
}
