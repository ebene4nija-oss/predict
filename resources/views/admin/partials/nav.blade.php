{{-- Admin tab bar. Previously copy-pasted into every admin view, so each new
     section meant editing eleven files and any drift went unnoticed. --}}
@php
    $adminTabs = [
        ['route' => 'admin.dashboard', 'pattern' => 'admin.dashboard', 'label' => 'Dashboard'],
        ['route' => 'admin.matches.index', 'pattern' => 'admin.matches.*', 'label' => 'Matches'],
        ['route' => 'admin.predictions.index', 'pattern' => 'admin.predictions.*', 'label' => 'Predictions'],
        ['route' => 'admin.posts.index', 'pattern' => 'admin.posts.*', 'label' => 'News'],
        ['route' => 'admin.teams.index', 'pattern' => 'admin.teams.*', 'label' => 'Teams'],
        ['route' => 'admin.users.index', 'pattern' => 'admin.users.*', 'label' => 'Users'],
        ['route' => 'admin.experts.index', 'pattern' => 'admin.experts.*', 'label' => 'Experts'],
        ['route' => 'admin.ads.index', 'pattern' => 'admin.ads.*', 'label' => 'Ads'],
        ['route' => 'admin.settings', 'pattern' => 'admin.settings*', 'label' => 'Settings'],
        ['route' => 'admin.system', 'pattern' => 'admin.system*', 'label' => 'System'],
    ];
@endphp

<div class="flex items-center space-x-1 p-1 rounded-2xl glass-panel overflow-x-auto">
    @foreach($adminTabs as $tab)
        <a href="{{ route($tab['route']) }}"
           class="px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition-all {{ request()->routeIs($tab['pattern']) ? 'bg-indigo-500 text-white' : 'text-slate-400 hover:text-white' }}">
            {{ $tab['label'] }}
        </a>
    @endforeach
</div>
