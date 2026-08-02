@if (auth()->check())
    <div class="nch-topbar-user-copy">
        <span class="nch-topbar-user-name" title="{{ auth()->user()->name }}">
            {{ auth()->user()->name }}
        </span>
        <span class="nch-topbar-user-role">{{ $roleLabel }}</span>
    </div>
@endif
