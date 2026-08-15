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
            'ad_mode' => Setting::get('ad_mode', 'house'),
            'ad_partner_name' => Setting::get('ad_partner_name', 'Official Betting Partner'),
            'ad_headline' => Setting::get('ad_headline', 'High Odds Multiples'),
            'ad_description' => Setting::get('ad_description', 'Get up to 200% welcome bonus on your first sports deposit.'),
            'ad_cta_text' => Setting::get('ad_cta_text', 'Claim Bonus'),
            'ad_cta_url' => Setting::get('ad_cta_url', '#'),
            'ad_network_head' => Setting::get('ad_network_head', ''),
            'ad_network_default' => Setting::get('ad_network_default', ''),
            'ad_network_header' => Setting::get('ad_network_header', ''),
            'ad_network_in_content' => Setting::get('ad_network_in_content', ''),
            'ad_network_sidebar' => Setting::get('ad_network_sidebar', ''),
            'ad_network_footer' => Setting::get('ad_network_footer', ''),
        ];

        return view('admin.ads.index', compact('ads'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'ad_enabled' => 'required|in:0,1',
            'ad_mode' => 'required|in:house,network',
            'ad_partner_name' => 'required|string|max:255',
            'ad_headline' => 'required|string|max:255',
            'ad_description' => 'required|string|max:500',
            'ad_cta_text' => 'required|string|max:50',
            'ad_cta_url' => 'required|string|max:500',

            // Ad tags are script by nature, so these are stored and rendered
            // verbatim. Only an admin can reach this form, and the length cap
            // is the one thing worth enforcing — a tag is a few hundred bytes,
            // anything far larger is a paste accident.
            'ad_network_head' => 'nullable|string|max:4000',
            'ad_network_default' => 'nullable|string|max:4000',
            'ad_network_header' => 'nullable|string|max:4000',
            'ad_network_in_content' => 'nullable|string|max:4000',
            'ad_network_sidebar' => 'nullable|string|max:4000',
            'ad_network_footer' => 'nullable|string|max:4000',
        ]);

        foreach ($validated as $key => $value) {
            Setting::set($key, (string) $value);
        }

        return redirect()->route('admin.ads.index')->with('success', 'Sponsor & ad banner settings updated successfully!');
    }
}
