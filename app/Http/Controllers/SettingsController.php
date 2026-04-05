<?php

namespace App\Http\Controllers;

use App\Models\MicrosoftEmailSettings;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function email()
    {
        $settings = MicrosoftEmailSettings::where('user_id', auth()->id())->first();
        return view('settings.email', compact('settings'));
    }

    public function updateSender(Request $request)
    {
        $request->validate(['fbr_sender_email' => 'required|email']);

        MicrosoftEmailSettings::where('user_id', auth()->id())->update([
            'fbr_sender_email' => $request->fbr_sender_email,
        ]);

        return back()->with('success', 'FBR sender email updated');
    }
}
