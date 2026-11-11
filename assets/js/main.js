/* =========================================================
   LENSA UYUB Studio - interaksi halaman
   Fitur: filter + cari + urutkan katalog, tambah ke daftar
   pesanan tanpa reload, modal detail paket, toast, konfirmasi
   hapus, validasi ringan checkout, tombol kembali ke atas.
   ========================================================= */
(() => {
  'use strict';

  const rupiah = (n) => 'Rp ' + Number(n).toLocaleString('id-ID');

  /* ---------- Toast ---------- */
  function toastHost() {
    let host = document.getElementById('toast-host');
    if (!host) {
      host = document.createElement('div');
      host.id = 'toast-host';
      host.setAttribute('aria-live', 'polite');
      document.body.appendChild(host);
    }
    return host;
  }

  function showToast(message, opts = {}) {
    const { type = 'success', linkText, linkHref, duration = 3500 } = opts;
    const el = document.createElement('div');
    el.className = 'app-toast app-toast-' + type;

    const text = document.createElement('span');
    text.textContent = message;
    el.appendChild(text);

    if (linkText && linkHref) {
      const a = document.createElement('a');
      a.href = linkHref;
      a.textContent = linkText;
      el.appendChild(a);
    }

    toastHost().appendChild(el);
    requestAnimationFrame(() => el.classList.add('show'));
    setTimeout(() => {
      el.classList.remove('show');
      setTimeout(() => el.remove(), 250);
    }, duration);
  }

  /* ---------- Badge jumlah pesanan di header ---------- */
  function updateCartBadge(count) {
    const badge = document.getElementById('cart-count');
    if (!badge) return;
    badge.textContent = count;
    const btn = badge.closest('a');
    if (btn) {
      btn.classList.remove('cart-bump');
      void btn.offsetWidth; // restart animasi
      btn.classList.add('cart-bump');
    }
  }

  /* ---------- Katalog: filter, cari, urutkan ---------- */
  function initCatalog() {
    const grid = document.getElementById('catalog-grid');
    if (!grid) return;

    const items = Array.from(grid.querySelectorAll('.catalog-item'));
    const filterBtns = document.querySelectorAll('.filter-btn');
    const search = document.getElementById('search-input');
    const sortSel = document.getElementById('sort-select');
    const countEl = document.getElementById('result-count');
    const emptyEl = document.getElementById('empty-state');
    const resetBtn = document.getElementById('reset-filter');

    const state = { filter: 'all', q: '', sort: 'default' };

    function apply() {
      const q = state.q.trim().toLowerCase();
      const ordered = items.slice();

      if (state.sort === 'price-asc')
        ordered.sort((a, b) => a.dataset.price - b.dataset.price);
      else if (state.sort === 'price-desc')
        ordered.sort((a, b) => b.dataset.price - a.dataset.price);
      else if (state.sort === 'name')
        ordered.sort((a, b) =>
          a.dataset.name.localeCompare(b.dataset.name, 'id'),
        );
      else ordered.sort((a, b) => a.dataset.id - b.dataset.id);

      let visible = 0;
      ordered.forEach((el) => {
        grid.appendChild(el);
        const okCat =
          state.filter === 'all' || el.dataset.category === state.filter;
        const okQ = !q || el.dataset.search.includes(q);
        const show = okCat && okQ;
        el.classList.toggle('d-none', !show);
        if (show) {
          visible++;
          el.classList.remove('pop-in');
          void el.offsetWidth;
          el.classList.add('pop-in');
        }
      });

      if (countEl) {
        countEl.textContent =
          visible + ' dari ' + items.length + ' paket ditampilkan';
      }
      if (emptyEl) emptyEl.classList.toggle('d-none', visible !== 0);
    }

    filterBtns.forEach((btn) => {
      btn.addEventListener('click', () => {
        filterBtns.forEach((b) => b.classList.remove('active'));
        btn.classList.add('active');
        state.filter = btn.dataset.filter;
        apply();
      });
    });

    if (search) {
      let timer;
      search.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(() => {
          state.q = search.value;
          apply();
        }, 150);
      });
      // Tekan "/" untuk langsung fokus ke kolom cari
      document.addEventListener('keydown', (e) => {
        const typing = /input|textarea|select/i.test(
          document.activeElement.tagName,
        );
        if (e.key === '/' && !typing) {
          e.preventDefault();
          search.focus();
        }
      });
    }

    if (sortSel) {
      sortSel.addEventListener('change', () => {
        state.sort = sortSel.value;
        apply();
      });
    }

    if (resetBtn) {
      resetBtn.addEventListener('click', () => {
        state.filter = 'all';
        state.q = '';
        state.sort = 'default';
        if (search) search.value = '';
        if (sortSel) sortSel.value = 'default';
        filterBtns.forEach((b) =>
          b.classList.toggle('active', b.dataset.filter === 'all'),
        );
        apply();
      });
    }

    apply();
  }

  /* ---------- Tambah ke daftar pesanan tanpa reload ---------- */
  function initAddToCart() {
    document.addEventListener('click', async (e) => {
      const btn = e.target.closest('.js-add-cart');
      if (!btn) return;
      e.preventDefault();

      const id = btn.dataset.id;
      if (!id || btn.classList.contains('is-loading')) return;

      const original = btn.innerHTML;
      btn.classList.add('is-loading');
      btn.innerHTML =
        '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>Menambahkan';

      try {
        const res = await fetch(
          'cart.php?add=' + encodeURIComponent(id) + '&ajax=1',
          {
            headers: { 'X-Requested-With': 'fetch' },
          },
        );
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const data = await res.json();
        if (!data.success) throw new Error(data.message || 'gagal');

        updateCartBadge(data.count);
        showToast(data.name + ' masuk ke daftar pesanan', {
          linkText: 'Lihat pesanan',
          linkHref: 'cart.php',
        });
        btn.innerHTML = 'Sudah ditambahkan';
        btn.classList.add('btn-added');
        setTimeout(() => {
          btn.innerHTML = original;
          btn.classList.remove('btn-added', 'is-loading');
        }, 1500);
      } catch (err) {
        // Cadangan: pakai cara lama (pindah halaman)
        window.location.href = btn.getAttribute('href') || 'cart.php?add=' + id;
      }
    });
  }

  /* ---------- Modal detail paket ---------- */
  function initDetailModal() {
    const modalEl = document.getElementById('detailModal');
    if (!modalEl || typeof bootstrap === 'undefined') return;
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);

    const $ = (sel) => modalEl.querySelector(sel);

    document.addEventListener('click', (e) => {
      const trigger = e.target.closest('.js-detail');
      if (!trigger) return;
      const card = trigger.closest('.catalog-item');
      if (!card) return;
      e.preventDefault();

      const d = card.dataset;
      $('#detail-title').textContent = d.name;
      $('#detail-umkm').textContent = d.umkm;
      $('#detail-desc').textContent = d.desc;
      $('#detail-price').textContent = rupiah(d.price);

      const badge = $('#detail-badge');
      badge.textContent = d.category === 'jasa' ? 'Jasa foto' : 'Produk fisik';
      badge.className =
        'badge px-2 py-1 ' +
        (d.category === 'jasa' ? 'badge-jasa' : 'badge-barang');

      const img = $('#detail-img');
      const ph = $('#detail-img-ph');
      if (d.img) {
        img.src = d.img;
        img.alt = d.name;
        img.classList.remove('d-none');
        ph.classList.add('d-none');
      } else {
        img.classList.add('d-none');
        ph.classList.remove('d-none');
      }

      const add = $('#detail-add');
      add.dataset.id = d.id;
      add.href = 'cart.php?add=' + d.id;

      modal.show();
    });
  }

  /* ---------- Hero: pintasan kebutuhan & strip film ---------- */
  function initHero() {
    const chips = document.querySelectorAll('.chip[data-q]');
    const search = document.getElementById('search-input');

    if (chips.length && search) {
      chips.forEach((chip) => {
        chip.addEventListener('click', () => {
          const wasActive = chip.classList.contains('active');
          chips.forEach((c) => c.classList.remove('active'));

          const allBtn = document.querySelector(
            '.filter-btn[data-filter="all"]',
          );
          if (allBtn) allBtn.click();

          if (wasActive) {
            search.value = '';
          } else {
            chip.classList.add('active');
            search.value = chip.dataset.q;
          }
          search.dispatchEvent(new Event('input', { bubbles: true }));

          const target = document.getElementById('catalog');
          if (target)
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
      });

      // Mengetik manual di kolom cari melepas pilihan pintasan
      search.addEventListener('input', () => {
        chips.forEach((c) => {
          if (c.classList.contains('active') && c.dataset.q !== search.value) {
            c.classList.remove('active');
          }
        });
      });
    }

    // Klik frame film membuka detail paket yang sama
    document.addEventListener('click', (e) => {
      const frame = e.target.closest('[data-open-id]');
      if (!frame) return;
      const trigger = document.querySelector(
        '.catalog-item[data-id="' + frame.dataset.openId + '"] .js-detail',
      );
      if (trigger) trigger.click();
    });
  }

  /* ---------- Konfirmasi sebelum hapus ---------- */
  function initConfirmLinks() {
    document.addEventListener('click', (e) => {
      const link = e.target.closest('a[data-confirm]');
      if (link && !window.confirm(link.dataset.confirm)) {
        e.preventDefault();
      }
    });
  }

  /* ---------- Checkout: validasi ringan ---------- */
  function initCheckout() {
    const phone = document.querySelector('input[name="customer_phone"]');
    if (phone) {
      phone.setAttribute('inputmode', 'numeric');
      phone.addEventListener('input', () => {
        phone.value = phone.value.replace(/[^0-9]/g, '').slice(0, 15);
      });
    }

    const date = document.querySelector('input[name="booking_date"]');
    if (date) {
      date.min = new Date().toISOString().slice(0, 10);
    }

    // Kartu opsi pengiriman ikut ter-highlight saat dipilih
    const radios = document.querySelectorAll('.shipping-radio');
    const sync = () => {
      radios.forEach((r) => {
        const label = r.closest('label');
        if (label) label.classList.toggle('option-selected', r.checked);
      });
    };
    radios.forEach((r) => r.addEventListener('change', sync));
    sync();

    // Cegah klik ganda pada tombol kirim
    const form = document.querySelector('form[action="invoice.php"]');
    if (form) {
      form.addEventListener('submit', () => {
        const submit = form.querySelector('button[type="submit"]');
        if (submit) {
          submit.disabled = true;
          submit.textContent = 'Memproses pesanan...';
        }
      });
    }
  }

  /* ---------- Tombol kembali ke atas ---------- */
  function initBackToTop() {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.id = 'back-to-top';
    btn.setAttribute('aria-label', 'Kembali ke atas');
    btn.textContent = '↑';
    document.body.appendChild(btn);

    window.addEventListener(
      'scroll',
      () => {
        btn.classList.toggle('show', window.scrollY > 400);
      },
      { passive: true },
    );
    btn.addEventListener('click', () =>
      window.scrollTo({ top: 0, behavior: 'smooth' }),
    );
  }

  document.addEventListener('DOMContentLoaded', () => {
    initCatalog();
    initHero();
    initAddToCart();
    initDetailModal();
    initConfirmLinks();
    initCheckout();
    initBackToTop();
  });
})();
