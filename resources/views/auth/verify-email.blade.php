@extends('layouts.app')

@section('title', 'Verify Your Email — Guaranteed Correct Platform')

@section('content')
    <div class="max-w-md mx-auto py-10">
        <div class="p-8 rounded-3xl glass-panel space-y-6 border border-slate-800 text-center">
            <div class="space-y-1">
                <h1 class="text-2xl font-black text-white">Verify Your Email</h1>
                <p class="text-xs text-slate-400">
                    We sent a verification link to <span class="text-slate-200 font-semibold">{{ auth()->user()->email }}</span>.
                    Click it to confirm your address.
                </p>
            </div>

            <p class="text-[11px] text-slate-500">
                Verifying your email means we can reach you about your subscription and reset your
                password if you ever lose access.
            </p>

            <form action="{{ route('verification.send') }}" method="POST">
                @csrf
                <button type="submit" class="w-full py-3.5 rounded-xl bg-gradient-to-r from-sky-500 to-blue-600 hover:from-sky-400 hover:to-blue-500 text-white font-extrabold text-sm shadow-lg shadow-sky-500/20 transition-all">
                    Resend Verification Email
                </button>
            </form>

            <a href="{{ route('account') }}" class="block text-xs text-slate-400 hover:text-[#38BDF8]">Skip for now &rarr;</a>
        </div>
    </div>
@endsection
