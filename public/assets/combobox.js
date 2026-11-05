/*
 * Combobox: select yang bisa dicari.
 *
 * Setiap <select class="js-combobox-asal"> dinaikkan menjadi combobox. Select
 * native tetap jadi sumber kebenaran: nilainya yang terkirim ke server, dan
 * tetap tampil bila JavaScript tidak jalan.
 *
 * Tidak ada dependensi. Dipakai ulang lewat window.SipedalCombobox.
 */
(function () {
    'use strict';

    var URUT = '';

    function norm(t) {
        return String(t || '')
            .toLowerCase()
            .replace(/[àáâãäå]/g, 'a').replace(/[èéêë]/g, 'e')
            .replace(/[ìíîï]/g, 'i').replace(/[òóôõö]/g, 'o')
            .replace(/[ùúûü]/g, 'u').replace(/[ç]/g, 'c');
    }

    function el(tag, cls) {
        var n = document.createElement(tag);
        if (cls) n.className = cls;
        return n;
    }

    function uniq() {
        return 'cb' + Math.random().toString(36).slice(2, 9);
    }

    function Combobox(select) {
        this.select = select;
        this.id = uniq();
        this.opsi = [];
        this.tampil = [];
        this.aktif = -1;
        this.terbuka = false;
        this.bangun();
        this.sinkronTrigger();
        this.ikat();
    }

    Combobox.prototype.bangun = function () {
        var s = this.select;

        // Pindahkan select ke tempat tersembunyi agar tetap terkirim.
        var wadah = el('div', 'combobox');
        s.parentNode.insertBefore(wadah, s);
        wadah.appendChild(s);
        s.classList.add('combobox__asal');

        var opsi = Array.prototype.slice.call(s.querySelectorAll('option'));
        this.opsi = opsi.map(function (o, i) {
            return {
                nilai: o.value,
                label: o.textContent.trim(),
                keterangan: o.getAttribute('data-keterangan') || '',
                asal: o
            };
        });

        // Pilihan kosong memakai nilai kosong, jadi yang pertama boleh dibuang.
        if (this.opsi.length && this.opsi[0].nilai === '' && this.opsi[0].asal.disabled) {
            this.opsi.shift();
        }

        this.kendali = el('button', 'combobox__kendali');
        this.kendali.type = 'button';
        this.kendali.setAttribute('aria-haspopup', 'listbox');
        this.kendali.setAttribute('aria-expanded', 'false');
        this.kendali.setAttribute('aria-controls', this.id);

        this.trigger = el('span', 'combobox__pemicu');
        this.kendali.appendChild(this.trigger);

        this.chev = el('span', 'combobox__panah');
        this.chev.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>';
        this.kendali.appendChild(this.chev);

        this.popup = el('div', 'combobox__popup');
        this.popup.id = this.id;
        this.popup.setAttribute('role', 'presentation');
        this.popup.hidden = true;

        this.cari = el('input', 'combobox__cari');
        this.cari.type = 'text';
        this.cari.setAttribute('role', 'combobox');
        this.cari.setAttribute('aria-expanded', 'false');
        this.cari.setAttribute('aria-autocomplete', 'list');
        this.cari.setAttribute('aria-controls', this.id + '-daftar');
        this.cari.setAttribute('aria-activedescendant', '');
        this.cari.placeholder = s.getAttribute('data-cari') || 'Cari…';
        this.cari.autocomplete = 'off';
        this.cari.spellcheck = false;

        this.daftar = el('ul', 'combobox__daftar');
        this.daftar.id = this.id + '-daftar';
        this.daftar.setAttribute('role', 'listbox');

        this.hitung = el('div', 'combobox__hitung');
        this.hitung.setAttribute('aria-live', 'polite');

        this.popup.appendChild(this.hitung);
        this.popup.appendChild(this.cari);
        this.popup.appendChild(this.daftar);

        wadah.appendChild(this.kendali);
        wadah.appendChild(this.popup);

        var wajib = s.hasAttribute('required');
        if (wajib) {
            this.kendali.setAttribute('aria-required', 'true');
            s.setAttribute('aria-hidden', 'true');
        }
        if (s.hasAttribute('aria-invalid')) {
            this.kendali.setAttribute('aria-invalid', 'true');
        }
        if (s.disabled) this.kendali.disabled = true;

        wadah._combobox = this;
        this.wadah = wadah;
    };

    Combobox.prototype.ikat = function () {
        var self = this;

        this.kendali.addEventListener('click', function () {
            self.terbuka ? self.tutup(true) : self.buka();
        });

        this.kendali.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                e.preventDefault();
                self.buka();
                self.gerak(e.key === 'ArrowDown' ? 1 : -1);
            } else if (e.key === ' ' || e.key === 'Enter') {
                e.preventDefault();
                self.buka();
            } else if (e.key.length === 1 && !e.ctrlKey && !e.metaKey) {
                self.buka();
                self.cari.value = e.key;
                self.saring();
            }
        });

        this.cari.addEventListener('input', function () {
            self.saring();
        });

        this.cari.addEventListener('keydown', function (e) {
            var n = self.tampil.length;
            if (e.key === 'ArrowDown') { e.preventDefault(); self.gerak(1); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); self.gerak(-1); }
            else if (e.key === 'Home') { e.preventDefault(); self.aktif = 0; self.tandai(); }
            else if (e.key === 'End') { e.preventDefault(); self.aktif = n - 1; self.tandai(); }
            else if (e.key === 'Enter') {
                e.preventDefault();
                if (self.aktif >= 0 && self.tampil[self.aktif]) self.pilih(self.tampil[self.aktif]);
            } else if (e.key === 'Escape') {
                e.preventDefault();
                self.tutup(true);
            } else if (e.key === 'Backspace' && self.cari.value === '') {
                self.pilih(null);
            }
        });

        this.daftar.addEventListener('click', function (e) {
            var li = e.target.closest('.combobox__opsi');
            if (!li) return;
            var o = self.opsi[Number(li.getAttribute('data-i'))];
            if (o) self.pilih(o);
        });

        document.addEventListener('click', function (e) {
            if (self.terbuka && !self.wadah.contains(e.target)) self.tutup(false);
        });

        this.select.addEventListener('change', function () {
            self.sinkronTrigger();
        });
    };

    Combobox.prototype.saring = function () {
        var q = norm(this.cari.value);
        this.tampil = this.opsi.filter(function (o) {
            if (q === '') return true;
            return norm(o.label).indexOf(q) > -1
                || norm(o.keterangan).indexOf(q) > -1
                || norm(o.nilai).indexOf(q) > -1;
        });
        this.aktif = this.tampil.length ? 0 : -1;
        this.gambarDaftar();
    };

    Combobox.prototype.gambarDaftar = function () {
        var self = this;
        this.daftar.innerHTML = '';

        if (!this.tampil.length) {
            var k = el('li', 'combobox__kosong');
            k.textContent = 'Tidak ada pilihan yang cocok';
            this.daftar.appendChild(k);
        } else {
            this.tampil.forEach(function (o, i) {
                var li = el('li', 'combobox__opsi');
                li.id = self.id + '-opsi-' + i;
                li.setAttribute('data-i', String(self.opsi.indexOf(o)));
                li.setAttribute('role', 'option');
                li.setAttribute('aria-selected', o.nilai === self.select.value ? 'true' : 'false');
                if (o.nilai === self.select.value) li.classList.add('is-terpilih');

                var t = el('span', 'combobox__label');
                t.textContent = o.label;
                li.appendChild(t);

                if (o.keterangan) {
                    var d = el('span', 'combobox__keterangan');
                    d.textContent = o.keterangan;
                    li.appendChild(d);
                }

                self.daftar.appendChild(li);
            });
        }

        var n = this.tampil.length;
        this.hitung.textContent = n + (n === 1 ? ' pilihan' : ' pilihan');
        this.hitung.hidden = n === 0;
        this.tandai();
    };

    Combobox.prototype.tandai = function () {
        var items = this.daftar.querySelectorAll('.combobox__opsi');
        Array.prototype.forEach.call(items, function (li, i) {
            li.classList.toggle('is-aktif', i === this.aktif);
        }, this);

        if (this.aktif < 0 || !items[this.aktif]) {
            this.cari.setAttribute('aria-activedescendant', '');
            return;
        }
        var id = items[this.aktif].id;
        this.cari.setAttribute('aria-activedescendant', id);
        items[this.aktif].scrollIntoView({ block: 'nearest' });
    };

    Combobox.prototype.gerak = function (arah) {
        var n = this.tampil.length;
        if (!n) return;
        this.aktif = this.aktif < 0 ? (arah > 0 ? 0 : n - 1) : (this.aktif + arah + n) % n;
        this.tandai();
    };

    Combobox.prototype.buka = function () {
        if (this.terbuka) return;
        this.terbuka = true;
        this.popup.hidden = false;
        this.kendali.setAttribute('aria-expanded', 'true');
        this.cari.setAttribute('aria-expanded', 'true');
        this.wadah.classList.add('is-terbuka');
        this.saring();
        this.cari.focus();
    };

    Combobox.prototype.tutup = function (fokus) {
        if (!this.terbuka) return;
        this.terbuka = false;
        this.popup.hidden = true;
        this.kendali.setAttribute('aria-expanded', 'false');
        this.cari.setAttribute('aria-expanded', 'false');
        this.wadah.classList.remove('is-terbuka');
        if (fokus) this.kendali.focus();
    };

    Combobox.prototype.pilih = function (o) {
        this.select.value = o ? o.nilai : '';
        this.select.dispatchEvent(new Event('change', { bubbles: true }));
        this.sinkronTrigger();
        this.tutup(true);
    };

    Combobox.prototype.sinkronTrigger = function () {
        var v = this.select.value;
        var ph = this.select.getAttribute('data-placeholder') || 'Pilih…';
        var o = this.opsi.filter(function (x) { return x.nilai === v; })[0];
        this.trigger.textContent = o ? o.label : ph;
        this.trigger.classList.toggle('is-kosong', !o);
    };

    function init(root) {
        (root || document).querySelectorAll('select.js-combobox-asal:not(.combobox__asal)')
            .forEach(function (s) { new Combobox(s); });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { init(document); });
    } else {
        init(document);
    }

    window.SipedalCombobox = { init: init };
})();
