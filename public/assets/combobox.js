/*
 * Combobox: select yang bisa dicari dengan dukungan single dan multi-select.
 *
 * Setiap <select class="js-combobox-asal"> dinaikkan menjadi combobox. Select
 * native tetap jadi sumber kebenaran: nilainya yang terkirim ke server, dan
 * tetap tampil bila JavaScript tidak jalan.
 *
 * Tidak ada dependensi. Dipakai ulang lewat window.SipedalCombobox.
 */
(function () {
    'use strict';

    function norm(t) {
        return String(t || '')
            .toLowerCase()
            .replace(/[àáâãäå]/g, 'a').replace(/[èéêë]/g, 'e')
            .replace(/[ìíîï]/g, 'i').replace(/[òóôõö]/g, 'o')
            .replace(/[ùúûü]/g, 'u').replace(/[ç]/g, 'c');
    }

    function escapeHtml(t) {
        var div = document.createElement('div');
        div.textContent = t || '';
        return div.innerHTML;
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
        this.multi = select.multiple || select.hasAttribute('data-multi');
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
        if (this.multi) wadah.classList.add('combobox--multi');
        s.parentNode.insertBefore(wadah, s);
        wadah.appendChild(s);
        s.classList.add('combobox__asal');

        var opsi = Array.prototype.slice.call(s.querySelectorAll('option'));
        this.opsi = opsi.map(function (o) {
            return {
                nilai: o.value,
                label: o.textContent.trim(),
                keterangan: o.getAttribute('data-keterangan') || '',
                asal: o
            };
        });

        // Pilihan kosong nilai "" untuk single select di posisi pertama
        if (!this.multi && this.opsi.length && this.opsi[0].nilai === '' && this.opsi[0].asal.disabled) {
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
        if (this.multi) this.daftar.setAttribute('aria-multiselectable', 'true');

        this.hitung = el('div', 'combobox__hitung');
        this.hitung.setAttribute('aria-live', 'polite');

        this.popup.appendChild(this.cari);

        // Toolbar aksi cepat untuk multi-select (Pilih Semua / Kosongkan)
        if (this.multi) {
            this.toolbar = el('div', 'combobox__multi-toolbar');
            this.btnSemua = el('button', 'combobox__multi-btn');
            this.btnSemua.type = 'button';
            this.btnSemua.textContent = 'Pilih Semua';

            this.btnReset = el('button', 'combobox__multi-btn');
            this.btnReset.type = 'button';
            this.btnReset.textContent = 'Kosongkan';

            this.toolbar.appendChild(this.btnSemua);
            this.toolbar.appendChild(this.btnReset);
            this.popup.appendChild(this.toolbar);
        }

        this.popup.appendChild(this.hitung);
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
                if (!self.multi) self.pilih(null);
            }
        });

        this.daftar.addEventListener('click', function (e) {
            var li = e.target.closest('.combobox__opsi');
            if (!li) return;
            var o = self.opsi[Number(li.getAttribute('data-i'))];
            if (o) self.pilih(o);
        });

        if (this.multi && this.toolbar) {
            this.btnSemua.addEventListener('click', function (e) {
                e.stopPropagation();
                self.pilihSemua();
            });
            this.btnReset.addEventListener('click', function (e) {
                e.stopPropagation();
                self.kosongkan();
            });
        }

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
                if (self.multi) li.classList.add('combobox__opsi-multi');
                li.id = self.id + '-opsi-' + i;
                li.setAttribute('data-i', String(self.opsi.indexOf(o)));
                li.setAttribute('role', 'option');

                var isSelected = self.multi ? o.asal.selected : (o.nilai === self.select.value);
                li.setAttribute('aria-selected', isSelected ? 'true' : 'false');
                if (isSelected) li.classList.add('is-terpilih');

                // Checkbox visual untuk multi-select
                if (self.multi) {
                    var chk = el('span', 'combobox__checkbox');
                    chk.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>';
                    li.appendChild(chk);
                }

                var konten = el('div', 'combobox__konten');
                var t = el('span', 'combobox__label');
                t.textContent = o.label;
                konten.appendChild(t);

                if (o.keterangan) {
                    var d = el('span', 'combobox__keterangan');
                    d.textContent = o.keterangan;
                    konten.appendChild(d);
                }

                li.appendChild(konten);
                self.daftar.appendChild(li);
            });
        }

        var n = this.tampil.length;
        this.hitung.textContent = n + ' pilihan';
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
        if (this.multi) {
            if (!o) return;
            o.asal.selected = !o.asal.selected;
            this.gambarDaftar();
            this.sinkronTrigger();
            this.select.dispatchEvent(new Event('change', { bubbles: true }));
        } else {
            this.select.value = o ? o.nilai : '';
            this.select.dispatchEvent(new Event('change', { bubbles: true }));
            this.sinkronTrigger();
            this.tutup(true);
        }
    };

    Combobox.prototype.pilihSemua = function () {
        var target = this.tampil.length ? this.tampil : this.opsi;
        target.forEach(function (o) {
            if (o.nilai !== '') o.asal.selected = true;
        });
        this.gambarDaftar();
        this.sinkronTrigger();
        this.select.dispatchEvent(new Event('change', { bubbles: true }));
    };

    Combobox.prototype.kosongkan = function () {
        this.opsi.forEach(function (o) {
            o.asal.selected = false;
        });
        this.gambarDaftar();
        this.sinkronTrigger();
        this.select.dispatchEvent(new Event('change', { bubbles: true }));
    };

    Combobox.prototype.sinkronTrigger = function () {
        var ph = this.select.getAttribute('data-placeholder') || 'Pilih…';

        if (this.multi) {
            var terpilih = this.opsi.filter(function (x) {
                return x.asal.selected && x.nilai !== '';
            });

            if (terpilih.length === 0) {
                this.trigger.innerHTML = escapeHtml(ph);
                this.trigger.classList.add('is-kosong');
            } else if (terpilih.length === 1) {
                this.trigger.innerHTML = escapeHtml(terpilih[0].label);
                this.trigger.classList.remove('is-kosong');
            } else {
                this.trigger.innerHTML = escapeHtml(terpilih[0].label)
                    + ' <span class="combobox__multi-badge">+' + (terpilih.length - 1) + '</span>';
                this.trigger.classList.remove('is-kosong');
            }
        } else {
            var v = this.select.value;
            var o = this.opsi.filter(function (x) { return x.nilai === v; })[0];
            this.trigger.textContent = o ? o.label : ph;
            this.trigger.classList.toggle('is-kosong', !o);
        }
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
