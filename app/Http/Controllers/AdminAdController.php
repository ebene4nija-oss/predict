<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class AdminAdController extends Controller
{
    public function index()
    {
        $ads = [
            'ad_enabled' => Setting::get('ad_enabled', '1'),
            'ad_partner_name' => Setting::get('ad_partner_name', 'Official Betting Partner'),
            'ad_headline' => Setting::get('ad_headline', 'High Odds Multiples'),
            'ad_description' => Setting::get('ad_description', 'Get up to 200% welcome bonus on your first sports deposit.'),
            'ad_cta_text' => Setting::get('ad_cta_text', 'Claim Bonus'),
            'ad_cta_url' => Setting::get('ad_cta_url', '#'),
        ];

        return view('admin.ads.index', compact('ads'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'ad_enabled' => 'required|in:0,1',
            'ad_partner_name' => 'required|string|max:255',
            'ad_headline' => 'required|string|max:255',
            'ad_description' => 'required|string|max:500',
            'ad_cta_text' => 'required|string|max:50',
            'ad_cta_url' => 'required|string|max:500',
        ]);

        foreach ($validated as $key => $value) {
            Setting::set($key, $value);
        }

        return redirect()->route('admin.ads.index')->with('success', 'Sponsor & ad banner settings updated successfully!');
    }
}
