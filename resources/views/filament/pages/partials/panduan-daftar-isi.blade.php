<ol class="space-y-0.5">
    @foreach($daftarIsi as $bagian)
        <li @class([
            'pt-1.5 first:pt-0' => $bagian['tingkat'] === 2,
            'ml-3 border-l border-gray-200 pl-3 dark:border-white/10' => $bagian['tingkat'] === 3,
        ])>
            <a href="#{{ $bagian['id'] }}"
               @class([
                   'block rounded-lg px-2 py-1.5 transition',
                   'text-sm font-medium leading-5 text-gray-700 hover:bg-gray-50 hover:text-primary-600 dark:text-gray-300 dark:hover:bg-white/5 dark:hover:text-primary-400' => $bagian['tingkat'] === 2,
                   'text-[0.8125rem] leading-5 text-gray-500 hover:bg-gray-50 hover:text-primary-600 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-primary-400' => $bagian['tingkat'] === 3,
               ])>
                {{ $bagian['teks'] }}
            </a>
        </li>
    @endforeach
</ol>
