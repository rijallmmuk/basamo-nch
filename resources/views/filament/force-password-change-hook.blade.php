{{--
    Modal pemblokir untuk SETIAP akun panel yang masih wajib mengganti sandi awal.

    Syaratnya harus sama dengan middleware EnsureAdminPasswordChanged (yang hanya melihat
    `must_change_password`). Sebelumnya di sini disaring ke superadmin/operator saja,
    sehingga pengajar dan dpmd terkunci mati: middleware terus memantulkan mereka ke
    dashboard, sementara modal penggantinya tidak pernah muncul. Hook ini hanya dirender
    di dalam layout panel, jadi akun portal warga tidak terpengaruh.
--}}
@auth
    @if (auth()->user()->must_change_password)
        @livewire('force-password-change')
    @endif
@endauth
