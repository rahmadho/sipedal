{{--
    Komponen DataTable Server-side Terpadu.
    Mendukung AJAX search, sort, pagination, per-page setting, dan filter kustom
    tanpa reload halaman penuh.
--}}
@props([
    'id' => null,
    'action' => null,
    'cari' => '',
    'cariPlaceholder' => 'Cari data...',
    'perHalaman' => 25,
    'perHalamanOpsi' => [10, 25, 50, 100],
    'halaman' => 1,
    'total' => 0,
    'sort' => '',
    'arah' => 'desc',
    'withSearch' => true,
    'withPerPage' => true,
    'withReset' => true,
])

@php
    $dtId = $id ?: 'dt-' . \Illuminate\Support\Str::random(8);
    $actionUrl = $action ?: request()->url();
@endphp

<div class="datatable-root" id="{{ $dtId }}">
    {{-- Slot Opsional: Tab Pengalih --}}
    @if (isset($tabs))
        <div class="datatable-tabs" style="padding:0 18px 12px;border-bottom:1px solid var(--border);display:flex;gap:8px">
            {{ $tabs }}
        </div>
    @endif

    {{-- Toolbar Filter & Kontrol DataTable --}}
    <form
        class="toolbar datatable-form"
        method="GET"
        action="{{ $actionUrl }}"
        style="padding:12px 18px;background:var(--gray-50);border-bottom:1px solid var(--border);display:flex;flex-wrap:wrap;align-items:flex-end;gap:10px"
    >
        <input type="hidden" name="sort" class="dt-input-sort" value="{{ $sort }}">
        <input type="hidden" name="arah" class="dt-input-arah" value="{{ $arah }}">
        <input type="hidden" name="halaman" class="dt-input-halaman" value="{{ $halaman }}">

        {{-- Input Pencarian --}}
        @if ($withSearch)
            <div class="field field--cari" style="flex:1 1 200px;min-width:180px;max-width:320px;display:flex;flex-direction:column;gap:4px">
                <label style="font-size:11px;font-weight:600;color:var(--text-soft);text-transform:uppercase;letter-spacing:.3px">Pencarian</label>
                <div class="input-wrap" style="position:relative">
                    <span class="input-ikon" style="position:absolute;left:9px;top:50%;transform:translateY(-50%);color:var(--text-mute);pointer-events:none">
                        <x-ikon nama="search" :ukuran="13" />
                    </span>
                    <input
                        class="input input-sm dt-input-cari"
                        type="search"
                        name="cari"
                        value="{{ $cari }}"
                        placeholder="{{ $cariPlaceholder }}"
                        style="width:100%;height:32px;padding-left:28px"
                        autocomplete="off"
                    >
                </div>
            </div>
        @endif

        {{-- Slot Kustom: Filter Tambahan --}}
        @if (isset($filter))
            {{ $filter }}
        @endif

        {{-- Pengaturan Jumlah Baris Per Halaman --}}
        @if ($withPerPage)
            <div class="field field--sm" style="width:auto;min-width:115px;display:flex;flex-direction:column;gap:4px">
                <label style="font-size:11px;font-weight:600;color:var(--text-soft);text-transform:uppercase;letter-spacing:.3px">Per Halaman</label>
                <select class="input input-sm dt-select-per-halaman" name="per_halaman" style="height:32px">
                    @foreach ($perHalamanOpsi as $opsi)
                        <option value="{{ $opsi }}" @selected((int)$perHalaman === (int)$opsi)>
                            {{ $opsi }} baris
                        </option>
                    @endforeach
                </select>
            </div>
        @endif

        {{-- Aksi Toolbar (Reset & Tombol Tambahan) --}}
        <div class="toolbar__aksi" style="margin-left:auto;display:flex;align-items:center;gap:6px">
            @if (isset($aksi))
                {{ $aksi }}
            @endif

            @if ($withReset)
                <button type="button" class="btn btn-ghost btn-sm dt-btn-reset" style="height:32px" title="Reset semua filter">
                    Reset
                </button>
            @endif
        </div>
    </form>

    {{-- Kontainer Tabel --}}
    <div class="datatable-table-wrap" style="position:relative;transition:opacity .15s ease">
        {{ $slot }}
    </div>

    {{-- Kontainer Paginasi (Info di kiri, Paginator di kanan) --}}
    <div class="card__foot datatable-pagination-wrap" style="padding:10px 18px">
        @if (isset($paginasi))
            {{ $paginasi }}
        @else
            <x-paginasi :halaman="$halaman" :per-halaman="$perHalaman" :total="$total" />
        @endif
    </div>
</div>

{{-- Skrip Pengendali DataTable (Vanilla JS) --}}
<script>
(function() {
    const root = document.getElementById(@js($dtId));
    if (!root || root._dtInitialized) return;
    root._dtInitialized = true;

    const form = root.querySelector('.datatable-form');
    const tableWrap = root.querySelector('.datatable-table-wrap');
    const paginasiWrap = root.querySelector('.datatable-pagination-wrap');
    const inputSort = form.querySelector('.dt-input-sort');
    const inputArah = form.querySelector('.dt-input-arah');
    const inputHalaman = form.querySelector('.dt-input-halaman');
    const inputCari = form.querySelector('.dt-input-cari');
    const selectPerHalaman = form.querySelector('.dt-select-per-halaman');
    const btnReset = form.querySelector('.dt-btn-reset');

    let debounceTimer = null;
    let abortController = null;

    async function muatData(resetHalaman = false) {
        if (resetHalaman && inputHalaman) {
            inputHalaman.value = '1';
        }

        const formData = new FormData(form);
        const params = new URLSearchParams();
        for (const [key, val] of formData.entries()) {
            if (val !== '' && val !== null) {
                params.set(key, val);
            }
        }

        if (abortController) {
            abortController.abort();
        }
        abortController = new AbortController();

        tableWrap.style.opacity = '0.45';
        tableWrap.style.pointerEvents = 'none';

        const url = form.action + '?' + params.toString();

        try {
            const resp = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                signal: abortController.signal
            });

            if (!resp.ok) throw new Error('HTTP ' + resp.status);
            const data = await resp.json();

            if (data.html_tabel) {
                tableWrap.innerHTML = data.html_tabel;
            }
            if (data.html_paginasi) {
                paginasiWrap.innerHTML = data.html_paginasi;
            }

            if (inputSort && data.sort !== undefined) inputSort.value = data.sort;
            if (inputArah && data.arah !== undefined) inputArah.value = data.arah;
            if (inputHalaman && data.halaman !== undefined) inputHalaman.value = data.halaman;

            window.history.pushState(null, '', url);

            // Emit event kustom agar pemanggil bisa merefresh listener bila perlu
            root.dispatchEvent(new CustomEvent('datatable:loaded', { detail: data }));
        } catch (err) {
            if (err.name !== 'AbortError') {
                console.error('Gagal memuat datatable:', err);
            }
        } finally {
            tableWrap.style.opacity = '1';
            tableWrap.style.pointerEvents = 'auto';
        }
    }

    // 1. Debounce pencarian
    if (inputCari) {
        inputCari.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => muatData(true), 300);
        });
    }

    // 2. Per-halaman & semua select / input perubahan di dalam form
    form.addEventListener('change', function(e) {
        if (e.target.matches('select, input[type="radio"], input[type="checkbox"], input[type="date"]')) {
            muatData(true);
        }
    });

    // 3. Form submit intervensi
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        muatData(true);
    });

    // 4. Pengurutan kolom (sortable headers)
    tableWrap.addEventListener('click', function(e) {
        const th = e.target.closest('.th-sortable');
        if (!th) return;
        const kolom = th.getAttribute('data-sort');
        if (!kolom) return;

        if (inputSort.value === kolom) {
            inputArah.value = (inputArah.value === 'asc') ? 'desc' : 'asc';
        } else {
            inputSort.value = kolom;
            inputArah.value = (kolom === 'pagu' || kolom === 'kd_rup') ? 'desc' : 'asc';
        }
        muatData(false);
    });

    // 5. Paginasi klik delegasi
    paginasiWrap.addEventListener('click', function(e) {
        const btn = e.target.closest('.paginasi__btn');
        if (!btn || btn.getAttribute('aria-disabled') === 'true') return;
        e.preventDefault();

        const href = btn.getAttribute('href');
        if (!href) return;
        try {
            const u = new URL(href, window.location.origin);
            const hal = u.searchParams.get('halaman') || '1';
            if (inputHalaman) inputHalaman.value = hal;
            muatData(false);
        } catch (_) {}
    });

    // 6. Tombol Reset
    if (btnReset) {
        btnReset.addEventListener('click', function(e) {
            e.preventDefault();
            form.querySelectorAll('input[type="search"]').forEach(i => i.value = '');
            form.querySelectorAll('select').forEach(s => s.selectedIndex = 0);
            if (inputSort) inputSort.value = 'kd_rup';
            if (inputArah) inputArah.value = 'desc';
            muatData(true);
        });
    }

    // 7. Tab delegasi jika ada tab di root
    root.addEventListener('click', function(e) {
        const tabBtn = e.target.closest('.dt-tab-btn');
        if (!tabBtn) return;
        e.preventDefault();

        const tabVal = tabBtn.getAttribute('data-tab');
        if (!tabVal) return;

        // Update hidden tab input
        let tabInput = form.querySelector('input[name="tab"]');
        if (!tabInput) {
            tabInput = document.createElement('input');
            tabInput.type = 'hidden';
            tabInput.name = 'tab';
            form.appendChild(tabInput);
        }
        tabInput.value = tabVal;

        // Atur tampilan aktif tombol tab
        root.querySelectorAll('.dt-tab-btn').forEach(b => {
            const isActive = b === tabBtn;
            b.classList.toggle('btn-primary', isActive);
            b.classList.toggle('btn-ghost', !isActive);
            const badge = b.querySelector('.badge');
            if (badge) {
                badge.className = isActive ? 'badge badge--brand' : 'badge badge--netral';
            }
        });

        // Tampilkan/sembunyikan elemen khusus tab bila ada
        root.querySelectorAll('[data-tab-target]').forEach(el => {
            const target = el.getAttribute('data-tab-target');
            el.style.display = (target === tabVal) ? 'contents' : 'none';
        });

        muatData(true);
    });

    // 8. Popstate browser navigasi
    window.addEventListener('popstate', function() {
        const urlParams = new URLSearchParams(window.location.search);
        for (const [key, val] of urlParams.entries()) {
            const input = form.querySelector(`[name="${key}"]`);
            if (input) input.value = val;
        }
        muatData(false);
    });
})();
</script>
