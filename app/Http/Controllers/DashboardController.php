<?php

namespace App\Http\Controllers;

use App\Models\CallLog;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();

        $recentCalls = CallLog::with('contact')
            ->where('user_id', $user->id)
            ->latest('called_at')
            ->take(10)
            ->get();

        $contactsCount = Contact::where('user_id', $user->id)->count();
        $callsToday = CallLog::where('user_id', $user->id)
            ->whereDate('called_at', today())
            ->count();
        $outboundToday = CallLog::where('user_id', $user->id)
            ->whereDate('called_at', today())
            ->where('direction', 'outbound')
            ->count();

        return view('dashboard', [
            'recentCalls' => $recentCalls,
            'contactsCount' => $contactsCount,
            'callsToday' => $callsToday,
            'outboundToday' => $outboundToday,
        ]);
    }
}
