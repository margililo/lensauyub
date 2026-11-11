<?php
$pageTitle = "Katalog Paket Foto & Cetak - LENSA UYUB Studio";
include 'koneksi.php';
include 'includes/header.php';

// Ambil data produk/jasa dari database MySQL
$result = mysqli_query($conn, "SELECT * FROM products ORDER BY id ASC");
$products = [];
while ($row = mysqli_fetch_assoc($result)) {
    $products[] = $row;
}

// Cari file foto di assets/img/ (tetap jalan walau nama file berekstensi ganda .jpg.jpg)
function getProductImage($filename) {
    if (empty($filename)) {
        return null;
    }
    $dir = __DIR__ . '/assets/img/';
    if (file_exists($dir . $filename)) {
        return 'assets/img/' . rawurlencode($filename);
    }
    if (file_exists($dir . $filename . '.jpg')) {
        return 'assets/img/' . rawurlencode($filename . '.jpg');
    }
    return null;
}

foreach ($products as $i => $p) {
    $products[$i]['img_path'] = getProductImage($p['image'] ?? null);
}

// Pintasan kebutuhan pelanggan (kata kunci harus ada di nama/deskripsi paket)
$needs = [
    'Foto produk'       => 'katalog',
    'Wisuda & keluarga' => 'wisuda',
    'Acara & lamaran'   => 'event',
    'Video promosi'     => 'video',
    'Cetak kanvas'      => 'kanvas',
    'Album foto'        => 'album',
];
?>

<main class="container pb-4">

    <!-- HERO -->
    <section class="hero">
        <h1>Satu pesanan untuk foto, video, dan cetak.</h1>
        <p class="lead-text">
            Pilih paket, tentukan jadwal sesi, lalu bayar online. Cocok untuk katalog produk UMKM, wisuda, dan dokumentasi acara.
        </p>

        <div class="chip-label">Mulai dari kebutuhanmu:</div>
        <div class="chip-row">
            <?php foreach ($needs as $label => $keyword): ?>
                <button type="button" class="chip" data-q="<?= htmlspecialchars($keyword) ?>"><?= htmlspecialchars($label) ?></button>
            <?php endforeach; ?>
        </div>

        <?php $frames = array_filter($products, fn($p) => !empty($p['img_path'])); ?>
        <?php if ($frames): ?>
            <div class="filmstrip" aria-label="Contoh hasil foto. Klik untuk melihat detail paket.">
                <div class="film-track">
                    <?php foreach ($frames as $p): ?>
                        <button type="button" class="film-frame" data-open-id="<?= (int)$p['id'] ?>">
                            <img src="<?= $p['img_path'] ?>" alt="<?= htmlspecialchars($p['name']) ?>" loading="lazy">
                            <span class="film-label"><?= htmlspecialchars($p['name']) ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </section>

    <!-- FILTER KATEGORI, PENCARIAN & URUTKAN -->
    <section class="catalog-toolbar" id="catalog">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex flex-wrap gap-2" id="filter-container">
                <button class="filter-btn active" data-filter="all">Semua layanan</button>
                <button class="filter-btn" data-filter="jasa">Paket jasa foto</button>
                <button class="filter-btn" data-filter="barang">Produk cetak</button>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <input type="text" id="search-input" class="form-control form-control-sm" style="min-width: 220px;"
                       placeholder="Cari paket atau produk (tekan /)" autocomplete="off">
                <select id="sort-select" class="form-select form-select-sm" aria-label="Urutkan paket">
                    <option value="default">Urutan awal</option>
                    <option value="price-asc">Harga terendah</option>
                    <option value="price-desc">Harga tertinggi</option>
                    <option value="name">Nama A-Z</option>
                </select>
            </div>
        </div>
    </section>
    <p id="result-count" aria-live="polite"></p>

    <!-- GRID KATALOG DINAMIS DARI DATABASE -->
    <section class="row g-3" id="catalog-grid">
        <?php if ($products): ?>
            <?php foreach ($products as $row): ?>
                <?php
                $imgPath = $row['img_path'];
                $searchText = strtolower($row['name'] . ' ' . $row['umkm_name'] . ' ' . $row['description']);
                $isJasa = $row['type'] === 'jasa';
                ?>
                <div class="col-12 col-sm-6 col-lg-4 col-xl-3 catalog-item"
                     data-id="<?= (int)$row['id'] ?>"
                     data-category="<?= htmlspecialchars($row['type']) ?>"
                     data-name="<?= htmlspecialchars($row['name']) ?>"
                     data-umkm="<?= htmlspecialchars($row['umkm_name']) ?>"
                     data-desc="<?= htmlspecialchars($row['description']) ?>"
                     data-price="<?= (int)$row['price'] ?>"
                     data-img="<?= $imgPath ? htmlspecialchars($imgPath) : '' ?>"
                     data-search="<?= htmlspecialchars($searchText) ?>">
                    <article class="product-card">

                        <!-- FOTO PRODUK (klik untuk lihat detail) -->
                        <div class="product-media">
                            <?php if ($imgPath): ?>
                                <img src="<?= $imgPath ?>" alt="<?= htmlspecialchars($row['name']) ?>"
                                     class="product-img js-detail" loading="lazy">
                            <?php else: ?>
                                <div class="product-img-placeholder js-detail">
                                    <span><?= $isJasa ? 'Paket studio' : 'Produk cetak' ?></span>
                                </div>
                            <?php endif; ?>
                            <span class="badge kind-tag <?= $isJasa ? 'badge-jasa' : 'badge-barang' ?>">
                                <?= $isJasa ? 'Jasa foto' : 'Produk fisik' ?>
                            </span>
                            <span class="price-chip">Rp <?= number_format($row['price'], 0, ',', '.') ?></span>
                        </div>

                        <div class="product-body">
                            <h6 class="fw-bold mb-1 product-title"><?= htmlspecialchars($row['name']) ?></h6>
                            <p class="text-muted small mb-2"><?= htmlspecialchars($row['umkm_name']) ?></p>
                            <p class="text-secondary small mb-3" style="font-size: 0.84rem;"><?= htmlspecialchars($row['description']) ?></p>

                            <div class="mt-auto d-flex gap-2">
                                <button type="button" class="btn btn-outline-secondary btn-sm js-detail">Detail</button>
                                <a href="cart.php?add=<?= (int)$row['id'] ?>"
                                   data-id="<?= (int)$row['id'] ?>"
                                   class="btn btn-success btn-sm flex-grow-1 js-add-cart">
                                    Tambah ke pesanan
                                </a>
                            </div>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <p class="text-muted">Belum ada paket layanan di database.</p>
            </div>
        <?php endif; ?>
    </section>

    <!-- KONDISI KOSONG SAAT FILTER/PENCARIAN TIDAK ADA HASIL -->
    <div id="empty-state" class="text-center py-5 d-none">
        <p class="text-muted mb-3">Tidak ada paket yang cocok dengan filter atau kata kunci ini.</p>
        <button type="button" id="reset-filter" class="btn btn-outline-success btn-sm px-4">Tampilkan semua paket</button>
    </div>

</main>

<!-- MODAL DETAIL PAKET -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detail-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0">
            <div class="modal-body p-0">
                <div class="row g-0">
                    <div class="col-md-6">
                        <img id="detail-img" src="" alt="" class="d-none w-100 h-100" style="object-fit: cover; min-height: 300px;">
                        <div id="detail-img-ph" class="product-img-placeholder h-100" style="min-height: 300px;">
                            <span>Foto belum tersedia</span>
                        </div>
                    </div>
                    <div class="col-md-6 p-4 d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span id="detail-badge" class="badge px-2 py-1"></span>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                        </div>
                        <h5 id="detail-title" class="fw-bold mb-1"></h5>
                        <p id="detail-umkm" class="text-muted small mb-3"></p>
                        <p id="detail-desc" class="text-secondary mb-3"></p>
                        <div class="mt-auto">
                            <p id="detail-price" class="fs-4 fw-bold text-success mb-3"></p>
                            <a id="detail-add" href="cart.php" data-id="" class="btn btn-success w-100 js-add-cart">
                                Tambah ke pesanan
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>