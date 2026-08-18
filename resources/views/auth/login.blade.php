@extends('layouts.app')

@section('title', 'Log In — Guaranteed Correct Platform')

@section('content')
    <div class="max-w-md mx-auto py-10">
        <div class="p-8 rounded-3xl glass-panel space-y-6 border border-slate-800">
            <div class="text-center space-y-1">
                <h1 class="text-2xl font-black text-white">Welcome Back</h1>
                <p class="text-xs text-slate-400">Log in to access your Guaranteed Correct account</p>
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
                    <div class="relative w-full flex items-center" style="position: relative;">
                        <input type="password" name="password" id="login-password" class="w-full px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm focus:border-sky-400 focus:outline-none pr-12" style="padding-right: 3rem;" required>
                        <button type="button" onclick="togglePassword('login-password', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-sky-400 transition-colors p-1.5 flex items-center justify-center cursor-pointer" style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); z-index: 10;" aria-label="Show password">
                            <svg class="w-5 h-5 eye-open" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <svg class="w-5 h-5 eye-closed hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L6.59 6.59m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between text-xs">
                    <label class="flex items-center space-x-2 text-slate-400 cursor-pointer">
                        <input type="checkbox" name="remember" class="rounded bg-slate-900 border-slate-700 text-sky-400 focus:ring-0">
                        <span>Remember me</span>
                    </label>
                    <a href="{{ route('password.request') }}" class="text-[#38BDF8] hover:underline font-semibold">Forgot password?</a>
                </div>

                <button type="submit" class="w-full py-3.5 rounded-xl bg-gradient-to-r from-sky-500 to-blue-600 hover:from-sky-400 hover:to-blue-500 text-white font-extrabold text-sm shadow-lg shadow-sky-500/20 transition-all">
                    Sign In &rarr;
                </button>
            </form>

            {{-- Seeded demo logins, local only. On a public site this block hands
                 every visitor the admin address and its password. --}}
            @env('local')
            <div class="p-4 rounded-xl bg-slate-900/90 border border-slate-800 text-[11px] text-slate-400 space-y-1.5">
                <div class="font-bold text-slate-300 text-xs">Test Demo Logins (Password: <code class="text-sky-400">password</code>):</div>
                <div>• Pro Subscriber: <code class="text-sky-400">subscriber@guaranteedcorrectscoretips.com</code></div>
                <div>• Expert Analyst: <code class="text-amber-400">expert@guaranteedcorrectscoretips.com</code></div>
                <div>• Free Account: <code class="text-slate-300">free@guaranteedcorrectscoretips.com</code></div>
                <div>• Admin Account: <code class="text-indigo-400">admin@guaranteedcorrectscoretips.com</code></div>
            </div>
            @endenv

            <div class="text-center text-xs text-slate-400">
                Don't have an account? <a href="{{ route('register') }}" class="text-[#38BDF8] font-bold hover:underline">Sign up</a>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    const isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    btn.querySelector('.eye-open').classList.toggle('hidden', isHidden);
    btn.querySelector('.eye-closed').classList.toggle('hidden', !isHidden);
    btn.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
}
</script>
@endpush
