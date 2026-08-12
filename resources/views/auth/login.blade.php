@extends('layouts.app')

@section('title', 'Log In — Prophet AI Platform')

@section('content')
    <div class="max-w-md mx-auto py-10">
        <div class="p-8 rounded-3xl glass-panel space-y-6 border border-slate-800">
            <div class="text-center space-y-1">
                <h1 class="text-2xl font-black text-white">Welcome Back</h1>
                <p class="text-xs text-slate-400">Log in to access your Prophet AI account</p>
            </div>

            <form action="{{ route('login') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Email Address</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm focus:border-sky-400 focus:outline-none" required autofocus>
                    @error('email')
                        <span class="text-xs text-rose-400 mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Password</label>
                    <input type="password" name="password" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm focus:border-sky-400 focus:outline-none" required>
                </div>

                <div class="flex items-center justify-between text-xs">
                    <label class="flex items-center space-x-2 text-slate-400 cursor-pointer">
                        <input type="checkbox" name="remember" class="rounded bg-slate-900 border-slate-700 text-sky-400 focus:ring-0">
                        <span>Remember me</span>
                    </label>
                </div>

                <button type="submit" class="w-full py-3.5 rounded-xl bg-gradient-to-r from-sky-500 to-blue-600 hover:from-sky-400 hover:to-blue-500 text-white font-extrabold text-sm shadow-lg shadow-sky-500/20 transition-all">
                    Sign In &rarr;
                </button>
            </form>

            <!-- Quick Demo Login Credentials Helper -->
            <div class="p-4 rounded-xl bg-slate-900/90 border border-slate-800 text-[11px] text-slate-400 space-y-1.5">
                <div class="font-bold text-slate-300 text-xs">Test Demo Logins (Password: <code class="text-sky-400">password</code>):</div>
                <div>• Pro Subscriber: <code class="text-sky-400">subscriber@prophet.ai</code></div>
                <div>• Expert Analyst: <code class="text-amber-400">expert@prophet.ai</code></div>
                <div>• Free Account: <code class="text-slate-300">free@prophet.ai</code></div>
                <div>• Admin Account: <code class="text-indigo-400">admin@prophet.ai</code></div>
            </div>

            <div class="text-center text-xs text-slate-400">
                Don't have an account? <a href="{{ route('register') }}" class="text-[#38BDF8] font-bold hover:underline">Sign up</a>
            </div>
        </div>
    </div>
@endsection
