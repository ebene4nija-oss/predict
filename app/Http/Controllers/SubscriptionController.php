<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Subscription;
use App\Services\Payment\FlutterwaveService;
use App\Services\Payment\PayPalService;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function pricing()
    {
        return view('subscribe');
    }

    public function checkout(Request $request, FlutterwaveService $flwService, PayPalService $ppService)
    {
        $user = $request->user();
        if (!$user) {
            return redirect()->route('login')->with('info', 'Please sign in or register to upgrade your account.');
        }

        $gateway = $request->input('gateway', 'flutterwave');

        if ($gateway === 'paypal') {
            $url = $ppService->createSubscriptionLink($user);
        } else {
            $url = $flwService->createSubscriptionLink($user);
        }

        return redirect()->away($url);
    }

    public function callback(Request $request)
    {
        $gateway = $request->query('gateway', 'flutterwave');
        $userId = $request->query('user_id');

        $user = $userId ? User::find($userId) : $request->user();

        if ($user) {
            Subscription::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'gateway' => $gateway,
                    'gateway_subscription_id' => ($gateway === 'paypal' ? 'I-PP' : 'flw_') . time(),
                    'status' => 'active',
                    'plan' => 'monthly_pro',
                    'renews_at' => now()->addMonth(),
                    'grace_period_ends_at' => null,
                ]
            );

            $user->update(['role' => 'subscriber']);

            return redirect()->route('top.picks')->with('success', 'Subscription activated successfully! Welcome to Prophet AI Pro.');
        }

        return redirect()->route('subscription.pricing')->with('error', 'Unable to complete subscription verification.');
    }

    public function handleWebhook(Request $request, FlutterwaveService $flwService, PayPalService $ppService)
    {
        $payload = $request->all();

        if ($request->has('event')) {
            $flwService->handleWebhook($payload);
        } elseif ($request->has('event_type')) {
            $ppService->handleWebhook($payload);
        }

        return response()->json(['status' => 'success']);
    }

    public function account(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return redirect()->route('login');
        }

        $subscription = $user->subscription;

        return view('account', compact('user', 'subscription'));
    }

    public function cancel(Request $request)
    {
        $user = $request->user();
        if ($user && $user->subscription) {
            $user->subscription->update(['status' => 'cancelled']);
            $user->update(['role' => 'free']);
        }

        return redirect()->route('account')->with('info', 'Your subscription has been cancelled.');
    }
}
