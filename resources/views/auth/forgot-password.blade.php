@extends('layouts.app')

@section('title', 'Reset Your Password — Guaranteed Correct Platform')

@section('content')
    <div class="max-w-md mx-auto py-10">
        <div class="p-8 rounded-3xl glass-panel space-y-6 border border-slate-800">
            <div class="text-center space-y-1">
                <h1 class="text-2xl font-black text-white">Forgot Your Password?</h1>
                <p class="text-xs text-slate-400">Enter your email address and we'll send you a reset link.</p>
            </div>

            <form action="{{ route('password.email') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase mb-2">Email Address</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm focus:border-sky-400 focus:outline-none" required autofocus>
                    @error('email')
                        <span class="text-xs text-rose-400 mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <button type="submit" class="w-full py-3.5 rounded-xl bg-gradient-to-r from-sky-500 to-blue-600 hover:from-sky-400 hover:to-blue-500 text-white font-extrabold text-sm shadow-lg shadow-sky-500/20 transition-all">
                    Send Reset Link &rarr;
                </button>
            </form>

            <div class="text-center text-xs text-slate-400">
                Remembered it? <a href="{{ route('login') }}" class="text-[#38BDF8] hover:underline font-semibold">Back to sign in</a>
            </div>
        </div>
    </div>
@endsection
