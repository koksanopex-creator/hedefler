<?php

namespace App\Http\Controllers;

use App\Models\EmailLogM;
use Illuminate\Http\Request;

class EmailLogC extends Controller
{
    public function index()
    {
        $logs = EmailLogM::with('user')->orderBy('sent_at', 'desc')->paginate(50);
        return view('settings.email_logs.index', compact('logs'));
    }
}
