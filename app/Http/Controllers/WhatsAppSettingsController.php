<?php

namespace App\Http\Controllers;

use App\Models\WhatsAppAccount;
use Illuminate\Support\Facades\Auth;

class WhatsAppSettingsController extends Controller
{
    public function index()
    {
        $accounts = WhatsAppAccount::where('user_id', Auth::id())->get();
        return view('admin.whatsapp.settings', compact('accounts'));
    }
}
