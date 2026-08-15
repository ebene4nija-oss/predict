@extends('layouts.app')

@section('title', 'Choose a New Password — Guaranteed Correct Platform')

@section('content')
    <div class="max-w-md mx-auto py-10">
        <div class="p-8 rounded-3xl glass-panel space-y-6 border border-slate-800">
            <div class="text-center space-y-1">
                <h1 class="text-2xl font-black text-white">Choose a New Password</h1>
                <p class="text-xs text-slate-400">Pick something you haven't used elsewhere.</p>
            </div>

            <form action="{{ route('password.update') }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Email Address</label>
                    <input type="email" name="email" value="{{ old('email', $email) }}" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm focus:border-sky-400 focus:outline-none" required>
                    @error('email')
                        <span class="text-xs text-rose-400 mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase mb-2">New Password</label>
                    <input type="password" name="password" autocomplete="new-password" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm focus:border-sky-400 focus:outline-none" required autofocus>
                    @error('password')
                        <span class="text-xs text-rose-400 mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Confirm New Password</label>
                    <input type="password" name="password_confirmation" autocomplete="new-password" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm focus:border-sky-400 focus:outline-none" required>
                </div>

                <button type="submit" class="w-full py-3.5 rounded-xl bg-gradient-to-r from-sky-500 to-blue-600 hover:from-sky-400 hover:to-blue-500 text-white font-extrabold text-sm shadow-lg shadow-sky-500/20 transition-all">
                    Reset Password &rarr;
                </button>
            </form>
        </div>
    </div>
@endsection
