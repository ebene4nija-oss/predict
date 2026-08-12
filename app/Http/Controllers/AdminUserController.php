<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Subscription;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('subscription');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role = $request->get('role')) {
            $query->where('role', $role);
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.users.index', compact('users'));
    }

    public function updateRole(Request $request, User $user)
    {
        $validated = $request->validate([
            'role' => 'required|in:admin,expert,subscriber,free',
        ]);

        $user->update(['role' => $validated['role']]);

        // Auto-create subscription if upgrading to subscriber
        if ($validated['role'] === 'subscriber') {
            Subscription::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'gateway' => 'admin_grant',
                    'gateway_subscription_id' => 'admin_grant_' . $user->id . '_' . time(),
                    'status' => 'active',
                    'plan' => 'monthly_pro',
                    'renews_at' => now()->addMonth(),
                    'grace_period_ends_at' => null,
                ]
            );
        }

        return redirect()->route('admin.users.index')->with('success', "{$user->name} role updated to {$validated['role']}.");
    }

    public function extendSubscription(Request $request, User $user)
    {
        $validated = $request->validate([
            'extend_days' => 'required|integer|min:1|max:365',
        ]);

        $sub = $user->subscription;
        if ($sub) {
            $sub->update([
                'status' => 'active',
                'renews_at' => ($sub->renews_at ? $sub->renews_at->addDays($validated['extend_days']) : now()->addDays($validated['extend_days'])),
                'grace_period_ends_at' => null,
            ]);
        } else {
            Subscription::create([
                'user_id' => $user->id,
                'gateway' => 'admin_grant',
                'gateway_subscription_id' => 'admin_ext_' . $user->id . '_' . time(),
                'status' => 'active',
                'plan' => 'monthly_pro',
                'renews_at' => now()->addDays($validated['extend_days']),
            ]);
            $user->update(['role' => 'subscriber']);
        }

        return redirect()->route('admin.users.index')->with('success', "{$user->name} subscription extended by {$validated['extend_days']} days.");
    }

    public function cancelSubscription(User $user)
    {
        $sub = $user->subscription;
        if ($sub) {
            $sub->update(['status' => 'cancelled']);
        }
        $user->update(['role' => 'free']);

        return redirect()->route('admin.users.index')->with('success', "{$user->name} subscription cancelled and reverted to free tier.");
    }
}
