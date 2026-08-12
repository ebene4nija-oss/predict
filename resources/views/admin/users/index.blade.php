@extends('layouts.app')

@section('title', 'User & Subscription Manager — Admin Portal')

@section('content')
    <div class="max-w-6xl mx-auto space-y-6 py-4">
        <!-- Admin Navigation Bar -->
        <div class="flex items-center space-x-1 p-1 rounded-2xl glass-panel mb-6 overflow-x-auto">
            <a href="{{ route('admin.dashboard') }}" class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('admin.dashboard') ? 'bg-indigo-500 text-white' : 'text-slate-400 hover:text-white' }}">Dashboard</a>
            <a href="{{ route('admin.matches.index') }}" class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('admin.matches.*') ? 'bg-indigo-500 text-white' : 'text-slate-400 hover:text-white' }}">Matches</a>
            <a href="{{ route('admin.predictions.index') }}" class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('admin.predictions.*') ? 'bg-indigo-500 text-white' : 'text-slate-400 hover:text-white' }}">Predictions</a>
            <a href="{{ route('admin.users.index') }}" class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('admin.users.*') ? 'bg-indigo-500 text-white' : 'text-slate-400 hover:text-white' }}">Users</a>
            <a href="{{ route('admin.experts.index') }}" class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('admin.experts.*') ? 'bg-indigo-500 text-white' : 'text-slate-400 hover:text-white' }}">Experts</a>
            <a href="{{ route('admin.ads.index') }}" class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('admin.ads.*') ? 'bg-indigo-500 text-white' : 'text-slate-400 hover:text-white' }}">Ads</a>
            <a href="{{ route('admin.settings') }}" class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ request()->routeIs('admin.settings*') ? 'bg-indigo-500 text-white' : 'text-slate-400 hover:text-white' }}">Settings</a>
        </div>

        <!-- Flash Message Banner -->
        @if(session('success'))
            <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-semibold flex items-center space-x-2">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <!-- Page Header -->
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-white">User & Subscription Manager</h1>
                <p class="text-xs sm:text-sm text-slate-400 mt-1">Manage user roles, grant complimentary PRO access, and control subscription statuses.</p>
            </div>
        </div>

        <!-- Search & Filter Form -->
        <div class="p-4 rounded-2xl glass-panel border border-slate-800">
            <form action="{{ route('admin.users.index') }}" method="GET" class="flex flex-col sm:flex-row items-center gap-3">
                <div class="relative flex-1 w-full">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by user name or email..." class="w-full pl-9 pr-4 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-white text-xs placeholder-slate-500 focus:border-indigo-400 focus:outline-none">
                    <svg class="w-4 h-4 text-slate-500 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>

                <div class="w-full sm:w-48">
                    <select name="role" class="w-full px-3 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-white text-xs focus:border-indigo-400 focus:outline-none">
                        <option value="">All Roles</option>
                        <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="expert" {{ request('role') === 'expert' ? 'selected' : '' }}>Expert</option>
                        <option value="subscriber" {{ request('role') === 'subscriber' ? 'selected' : '' }}>Subscriber (PRO)</option>
                        <option value="free" {{ request('role') === 'free' ? 'selected' : '' }}>Free User</option>
                    </select>
                </div>

                <button type="submit" class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs transition-all shadow-md shadow-indigo-500/20">
                    Filter Users
                </button>
            </form>
        </div>

        <!-- Users Table -->
        <div class="rounded-2xl glass-panel border border-slate-800 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-300">
                    <thead class="bg-slate-900/80 border-b border-slate-800 text-[10px] uppercase font-bold text-slate-400">
                        <tr>
                            <th class="px-4 py-3.5">Name</th>
                            <th class="px-4 py-3.5">Email</th>
                            <th class="px-4 py-3.5">Role</th>
                            <th class="px-4 py-3.5">Subscription Status</th>
                            <th class="px-4 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @forelse($users as $user)
                            <tr class="hover:bg-slate-800/30 transition-colors">
                                <td class="px-4 py-3 font-bold text-white whitespace-nowrap">
                                    {{ $user->name }}
                                </td>
                                <td class="px-4 py-3 text-slate-400 font-mono whitespace-nowrap">
                                    {{ $user->email }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if($user->role === 'admin')
                                        <span class="px-2.5 py-1 rounded-full bg-indigo-500/10 text-indigo-400 border border-indigo-500/30 text-[10px] font-extrabold uppercase">
                                            Admin
                                        </span>
                                    @elseif($user->role === 'expert')
                                        <span class="px-2.5 py-1 rounded-full bg-amber-500/10 text-amber-400 border border-amber-500/30 text-[10px] font-extrabold uppercase">
                                            Expert
                                        </span>
                                    @elseif($user->role === 'subscriber')
                                        <span class="px-2.5 py-1 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 text-[10px] font-extrabold uppercase">
                                            Subscriber
                                        </span>
                                    @else
                                        <span class="px-2.5 py-1 rounded-full bg-slate-800 text-slate-400 border border-slate-700 text-[10px] font-bold uppercase">
                                            Free
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if($user->subscription && $user->subscription->isActive())
                                        <div class="inline-flex items-center space-x-1.5 px-2.5 py-1 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 text-[10px] font-bold">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                            <span>Active</span>
                                            @if($user->subscription->renews_at)
                                                <span class="text-slate-400 font-normal">({{ $user->subscription->renews_at->format('M d, Y') }})</span>
                                            @endif
                                        </div>
                                    @elseif($user->subscription && $user->subscription->status === 'cancelled')
                                        <span class="px-2.5 py-1 rounded-full bg-rose-500/10 text-rose-400 border border-rose-500/30 text-[10px] font-bold">
                                            Cancelled
                                        </span>
                                    @else
                                        <span class="px-2.5 py-1 rounded-full bg-slate-800 text-slate-500 text-[10px] font-medium">
                                            None / Expired
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap items-center justify-end gap-2">
                                        <!-- 1. Role Change Form -->
                                        <form action="{{ route('admin.users.role', $user) }}" method="POST" class="inline-flex items-center space-x-1">
                                            @csrf
                                            <select name="role" class="px-2 py-1 bg-slate-900 border border-slate-700 text-white rounded-lg text-xs focus:border-indigo-400 focus:outline-none">
                                                <option value="free" {{ $user->role === 'free' ? 'selected' : '' }}>Free</option>
                                                <option value="subscriber" {{ $user->role === 'subscriber' ? 'selected' : '' }}>Subscriber</option>
                                                <option value="expert" {{ $user->role === 'expert' ? 'selected' : '' }}>Expert</option>
                                                <option value="admin" {{ $user->role === 'admin' ? 'selected' : '' }}>Admin</option>
                                            </select>
                                            <button type="submit" class="px-2.5 py-1 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold transition-colors">
                                                Update
                                            </button>
                                        </form>

                                        <!-- 2. Extend Subscription Form -->
                                        <form action="{{ route('admin.users.extend', $user) }}" method="POST" class="inline-flex items-center space-x-1">
                                            @csrf
                                            <input type="number" name="extend_days" placeholder="30" min="1" max="365" value="30" required class="w-16 px-2 py-1 bg-slate-900 border border-slate-700 text-white rounded-lg text-xs font-mono focus:border-emerald-400 focus:outline-none">
                                            <button type="submit" class="px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold transition-colors">
                                                Extend
                                            </button>
                                        </form>

                                        <!-- 3. Cancel Subscription Form -->
                                        <form action="{{ route('admin.users.cancel', $user) }}" method="POST" class="inline-block">
                                            @csrf
                                            <button type="submit" onclick="return confirm('Are you sure you want to cancel subscription for {{ $user->name }}?')" class="px-2.5 py-1 rounded-lg bg-rose-600/80 hover:bg-rose-600 text-white text-xs font-semibold transition-colors">
                                                Cancel Sub
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-slate-400">
                                    No users found matching criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <div class="pt-4">
            {{ $users->appends(request()->query())->links() }}
        </div>
    </div>
@endsection
