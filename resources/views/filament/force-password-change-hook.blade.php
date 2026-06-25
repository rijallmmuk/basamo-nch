{{-- Render modal pemblokir hanya untuk admin yang masih wajib ganti sandi (OTP awal). --}}
@auth
    @if (auth()->user()->must_change_password && in_array(auth()->user()->role, ['super_admin', 'desa_admin'], true))
        @livewire('force-password-change')
    @endif
@endauth
