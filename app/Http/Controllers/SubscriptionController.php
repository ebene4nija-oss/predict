<?php

namespace App\Http\Controllers;

use App\Services\Payment\FlutterwaveService;
use App\Services\Payment\PayPalService;
use App\Services\Payment\PricingResolver;
use App\Services\Payment\SubscriptionManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SubscriptionController extends Controller
{
    public function pricing(Request $request, PricingResolver $pricing)
    {
        return view('subscribe', ['pricing' => $pricing->resolve($request)]);
    }

    public function checkout(
        Request $request,
        FlutterwaveService $flwService,
        PayPalService $ppService,
        PricingResolver $pricing
    ) {
        $user = $request->user();

        // Gateway follows the visitor's market unless they have explicitly
        // picked one: a naira checkout is useless to a payer in Europe, and a
        // PayPal checkout is useless to most of the Nigerian base.
        $resolved = $pricing->resolve($request);

        $gateway = match ($request->input('gateway')) {
            'paypal' => 'paypal',
            'flutterwave' => 'flutterwave',
            default => $resolved['gateway'],
        };

        $url = $gateway === 'paypal'
            ? $ppService->createSubscriptionLink($user, $resolved['amount'], $resolved['currency'])
            : $flwService->createSubscriptionLink($user, $resolved['amount'], $resolved['currency']);

        if (! $url) {
            return redirect()->route('subscription.pricing')
                ->with('error', 'Payments are temporarily unavailable. Please try again shortly.');
        }

        return redirect()->away($url);
    }

    /**
     * Return leg of the checkout flow.
     *
     * The query string is attacker-controlled, so it is treated purely as a
     * lookup reference: the signed-in user is the only identity we trust, and
     * the payment itself is confirmed server-to-server with the gateway before
     * any access is granted.
     */
    public function callback(
        Request $request,
        FlutterwaveService $flwService,
        PayPalService $ppService,
        SubscriptionManager $subscriptions
    ) {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login')
                ->with('info', 'Please sign in to finish activating your subscription.');
        }

        $gateway = $request->query('gateway') === 'paypal' ? 'paypal' : 'flutterwave';

        $reference = $gateway === 'paypal'
            ? $ppService->verifySubscription((string) $request->query('subscription_id', ''), $user)
            : $flwService->verifyTransaction((string) $request->query('tx_ref', ''), $user);

        if (! $reference) {
            Log::warning('Subscription callback verification failed', [
                'user_id' => $user->id,
                'gateway' => $gateway,
                'ip' => $request->ip(),
            ]);

            // Covers both a rejected payment and one the gateway has accepted
            // but not yet billed, which the webhook activates a moment later.
            return redirect()->route('subscription.pricing')
                ->with('error', 'We could not confirm your payment yet. If you have just completed checkout it may take a moment to clear — please check your account shortly, and contact support if you were charged.');
        }

        $subscriptions->activate($user, $gateway, $reference);

        return redirect()->route('top.picks')
            ->with('success', 'Subscription activated successfully! Welcome to Guaranteed Correct Pro.');
    }

    /**
     * Gateway-to-server webhook. Every payload must carry a valid signature.
     */
    public function handleWebhook(Request $request, FlutterwaveService $flwService, PayPalService $ppService)
    {
        if ($request->has('event_type')) {
            if (! $ppService->verifyWebhookSignature($request)) {
                return response()->json(['status' => 'invalid signature'], 401);
            }

            $ppService->handleWebhook($request->all());

            return response()->json(['status' => 'success']);
        }

        if ($request->has('event')) {
            if (! $flwService->verifyWebhookSignature($request)) {
                return response()->json(['status' => 'invalid signature'], 401);
            }

            $flwService->handleWebhook($request->all());

            return response()->json(['status' => 'success']);
        }

        return response()->json(['status' => 'ignored'], 400);
    }

    public function account(Request $request)
    {
        $user = $request->user();

        return view('account', [
            'user' => $user,
            'subscription' => $user->subscription,
        ]);
    }

    public function cancel(Request $request, SubscriptionManager $subscriptions)
    {
        $subscriptions->cancel($request->user());

        return redirect()->route('account')->with('info', 'Your subscription has been cancelled.');
    }
}
