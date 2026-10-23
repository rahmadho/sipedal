{{--
    Komponen ikon garis.
    Pemakaian: <x-ikon nama="home" ukuran="18" />
    Semua ikon memakai stroke="currentColor" agar otomatis mengikuti warna tema.
--}}
@props(['nama' => 'dot', 'ukuran' => 18, 'tebal' => 1.8])

@php
    $jalur = [
        'home'        => '<path d="M3 10.2 12 3l9 7.2"/><path d="M5 9.5V21h14V9.5"/><path d="M9.5 21v-6h5v6"/>',
        'clipboard'   => '<rect x="8" y="2.5" width="8" height="4" rx="1"/><path d="M16 4.5h2a2 2 0 0 1 2 2V20a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6.5a2 2 0 0 1 2-2h2"/><path d="M8 12h8M8 16h5"/>',
        'check-circle'=> '<circle cx="12" cy="12" r="9"/><path d="m8.5 12.2 2.5 2.5 4.5-4.9"/>',
        'inbox'       => '<path d="M21 12h-5l-1.5 2.5h-5L8 12H3"/><path d="M5.5 5h13l2.5 7v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-5z"/>',
        'shield'      => '<path d="M12 2.5 20 6v6c0 5-3.4 8.6-8 9.5-4.6-.9-8-4.5-8-9.5V6z"/><path d="m9 12 2 2 4-4"/>',
        'building'    => '<path d="M4 21V6l7-3v18"/><path d="M11 21h9V10l-9-4"/><path d="M7 9v.01M7 13v.01M7 17v.01M15 12v.01M15 16v.01"/>',
        'layers'      => '<path d="m12 3 8 4.5-8 4.5-8-4.5z"/><path d="m4 12 8 4.5 8-4.5"/><path d="m4 16.5 8 4.5 8-4.5"/>',
        'chart'       => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
        'radar'       => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.4" fill="currentColor" stroke="none"/><path d="M12 12 18 6"/>',
        'refresh'     => '<path d="M20 11a8 8 0 1 0-2.3 5.7"/><path d="M20 4v7h-7"/>',
        'terminal'    => '<rect x="2.5" y="4" width="19" height="16" rx="2.5"/><path d="m7 10 2.5 2L7 14M12.5 14.5H17"/>',
        'sliders'     => '<path d="M4 7h9M17 7h3M4 17h3M11 17h9"/><circle cx="15" cy="7" r="2.4"/><circle cx="9" cy="17" r="2.4"/>',
        'user'        => '<circle cx="12" cy="8" r="4"/><path d="M4.5 21a7.5 7.5 0 0 1 15 0"/>',
        'wallet'      => '<rect x="2.5" y="6" width="19" height="13" rx="2.5"/><path d="M2.5 10h19"/><circle cx="17" cy="14.5" r="1.3" fill="currentColor" stroke="none"/>',
        'clock'       => '<circle cx="12" cy="12" r="9"/><path d="M12 7.5V12l3.2 2"/>',
        'trending'    => '<path d="m3 17 6-6 4 4 8-8"/><path d="M15 7h6v6"/>',
        'scale'       => '<path d="M12 3v18M7 21h10"/><path d="m5 8 3.5 6H1.5z"/><path d="m19 8 3.5 6h-7z"/><path d="M3 6h18"/>',
        'search'      => '<circle cx="10.5" cy="10.5" r="6.5"/><path d="m15.5 15.5 5 5"/>',
        'bell'        => '<path d="M18 9a6 6 0 1 0-12 0c0 5-2 6-2 6h16s-2-1-2-6"/><path d="M10.5 20a2 2 0 0 0 3 0"/>',
        'chevron-down'=> '<path d="m6 9 6 6 6-6"/>',
        'chevron-right'=>'<path d="m9 6 6 6-6 6"/>',
        'chevron-left'=> '<path d="m15 6-6 6 6 6"/>',
        'x'           => '<path d="M18 6 6 18M6 6l12 12"/>',
        'menu'        => '<path d="M3 6h18M3 12h18M3 18h18"/>',
        'logout'      => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>',
        'eye'         => '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>',
        'eye-off'     => '<path d="M9.9 5.2A9.9 9.9 0 0 1 12 5c6.4 0 10 7 10 7a17 17 0 0 1-3 3.9M6.2 6.3A16.7 16.7 0 0 0 2 12s3.6 7 10 7a9.8 9.8 0 0 0 4.2-.9"/><path d="M3 3l18 18"/>',
        'lock'        => '<rect x="4" y="10.5" width="16" height="10.5" rx="2.2"/><path d="M8 10.5V7a4 4 0 0 1 8 0v3.5"/>',
        'mail'        => '<rect x="2.5" y="5" width="19" height="14" rx="2.2"/><path d="m3 7 9 6 9-6"/>',
        'download'    => '<path d="M12 3v12"/><path d="m7.5 10.5 4.5 4.5 4.5-4.5"/><path d="M4 20h16"/>',
        'filter'      => '<path d="M3 5h18l-7 8v6l-4-2v-4z"/>',
        'plus'        => '<path d="M12 5v14M5 12h14"/>',
        'edit'        => '<path d="M4 20h4l10.5-10.5a2.1 2.1 0 0 0-3-3L5 17z"/><path d="m14 6 3 3"/>',
        'trash'       => '<path d="M4 7h16M9 7V4.5h6V7M6 7l1 13h10l1-13"/>',
        'info'        => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5.5M12 8v.01"/>',
        'alert'       => '<path d="M12 3 2.5 20h19z"/><path d="M12 9v4.5M12 17v.01"/>',
        'check'       => '<path d="m4.5 12.5 5 5 10-11"/>',
        'file'        => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/>',
        'calendar'    => '<rect x="3.5" y="5" width="17" height="16" rx="2.2"/><path d="M3.5 10h17M8 3v4M16 3v4"/>',
        'database'    => '<ellipse cx="12" cy="6" rx="8" ry="3.2"/><path d="M4 6v6c0 1.8 3.6 3.2 8 3.2s8-1.4 8-3.2V6"/><path d="M4 12v6c0 1.8 3.6 3.2 8 3.2s8-1.4 8-3.2v-6"/>',
        'play'        => '<path d="M7 4.5 19 12 7 19.5z"/>',
        'settings'    => '<circle cx="12" cy="12" r="3.2"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.6V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.6 1.7 1.7 0 0 0-1.9.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.9 1.7 1.7 0 0 0-1.6-1H3a2 2 0 1 1 0-4h.1A1.7 1.7 0 0 0 4.7 8.6a1.7 1.7 0 0 0-.3-1.9l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.9.3H9a1.7 1.7 0 0 0 1-1.6V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.6 1.7 1.7 0 0 0 1.9-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.9V10a1.7 1.7 0 0 0 1.6 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1Z"/>',
        'grid'        => '<rect x="3.5" y="3.5" width="7" height="7" rx="1.4"/><rect x="13.5" y="3.5" width="7" height="7" rx="1.4"/><rect x="3.5" y="13.5" width="7" height="7" rx="1.4"/><rect x="13.5" y="13.5" width="7" height="7" rx="1.4"/>',
        'dot'         => '<circle cx="12" cy="12" r="3"/>',
        'arrow-right' => '<path d="M4 12h16"/><path d="m14 6 6 6-6 6"/>',
        'arrow-up'    => '<path d="M12 20V4"/><path d="m6 10 6-6 6 6"/>',
        'arrow-down'  => '<path d="M12 4v16"/><path d="m18 14-6 6-6-6"/>',
        'link'        => '<path d="M10.5 13.5a4 4 0 0 0 5.7 0l2.6-2.6a4 4 0 0 0-5.7-5.7L11.7 6.6"/><path d="M13.5 10.5a4 4 0 0 0-5.7 0l-2.6 2.6a4 4 0 0 0 5.7 5.7l1.4-1.4"/>',
        'upload'      => '<path d="M12 16V4"/><path d="m7.5 8.5 4.5-4.5 4.5 4.5"/><path d="M4 20h16"/>',
        'image'       => '<rect x="3" y="4.5" width="18" height="15" rx="2.2"/><circle cx="8.5" cy="10" r="1.6"/><path d="m4 17 5-4.5 4 3.5 3-2.5 4 3.5"/>',
        'palette'     => '<path d="M12 3a9 9 0 1 0 0 18c1.2 0 2-.9 2-2 0-.6-.2-1-.6-1.4-.4-.4-.6-.8-.6-1.4 0-1.1.9-2 2-2H17a4 4 0 0 0 4-4c0-4-4-7.2-9-7.2Z"/><circle cx="8" cy="10" r="1.2" fill="currentColor" stroke="none"/><circle cx="12" cy="7.5" r="1.2" fill="currentColor" stroke="none"/><circle cx="15.5" cy="10" r="1.2" fill="currentColor" stroke="none"/>',
        'domain'      => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18"/><path d="M12 3c2.5 2.4 4 5.6 4 9s-1.5 6.6-4 9c-2.5-2.4-4-5.6-4-9s1.5-6.6 4-9Z"/>',
        'key'         => '<circle cx="8" cy="15" r="4.2"/><path d="m11 12 9-9M17 6l2.5 2.5M14.5 8.5 17 11"/>',
        'send'        => '<path d="m21 3-9.5 18-2.3-7.2L2 11.5z"/><path d="M21 3 9.2 13.8"/>',
        'star'        => '<path d="m12 3.5 2.7 5.6 6.1.9-4.4 4.3 1 6.1-5.4-2.9-5.4 2.9 1-6.1L3.2 10l6.1-.9z"/>',
        'book'        => '<path d="M4 4.5A2 2 0 0 1 6 2.5h14v19H6a2 2 0 0 1-2-2z"/><path d="M4 17.5h16"/>',
        'target'      => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.5" fill="currentColor" stroke="none"/>',
        'activity'    => '<path d="M3 12h4l2.5-7 4 14 2.5-7H21"/>',
        'server'      => '<rect x="3" y="4" width="18" height="7" rx="2"/><rect x="3" y="13" width="18" height="7" rx="2"/><path d="M7 7.5v.01M7 16.5v.01"/>',
        'map'         => '<path d="m9 4.5 6 2.5 6-2.5v15l-6 2.5-6-2.5-6 2.5v-15z"/><path d="M9 4.5v15M15 7v15"/>',
        'bag'         => '<path d="M5 8h14l1 13H4z"/><path d="M9 8V5.5a3 3 0 0 1 6 0V8"/>',
    ];

    $isi = $jalur[$nama] ?? $jalur['dot'];
@endphp

<svg
    xmlns="http://www.w3.org/2000/svg"
    width="{{ $ukuran }}"
    height="{{ $ukuran }}"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="{{ $tebal }}"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
    focusable="false"
>{{-- jangan di-escape: ini markup SVG yang kita kendalikan sendiri --}}{!! $isi !!}</svg>
