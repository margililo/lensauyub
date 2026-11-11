<?php
$pageTitle = "Daftar Pesanan - LensaUMKM Studio";
include 'koneksi.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// 1. Tambah ke keranjang (mendukung mode AJAX untuk tombol di katalog)
if (isset($_GET['add'])) {
    $productId = (int)$_GET['add'];
    $isAjax = isset($_GET['ajax']);

    $stmt = mysqli_prepare($conn, "SELECT name FROM products WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $productId);
    mysqli_stmt_execute($stmt);
    $found = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($found) {
        $_SESSION['cart'][$productId] = ($_SESSION['cart'][$productId] ?? 0) + 1;
    }

    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => (bool)$found,
            'name'    => $found['name'] ?? '',
            'count'   => array_sum($_SESSION['cart']),
            'message' => $found ? 'ok' : 'Paket tidak ditemukan',
        ]);
        exit;
    }

    header("Location: cart.php");
    exit;
}

// 2. Tambah / kurangi jumlah
if (isset($_GET['inc'])) {
    $productId = (int)$_GET['inc'];
    if (isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId]++;
    }
    header("Location: cart.php");
    exit;
}

if (isset($_GET['dec'])) {
    $productId = (int)$_GET['dec'];
    if (isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId]--;
        if ($_SESSION['cart'][$productId] <= 0) {
            unset($_SESSION['cart'][$productId]);
        }
    }
    header("Location: cart.php");
    exit;
}

// 3. Hapus satu item / kosongkan semua
if (isset($_GET['remove'])) {
    unset($_SESSION['cart'][(int)$_GET['remove']]);
    header("Location: cart.php");
    exit;
}

if (isset($_GET['clear'])) {
    $_SESSION['cart'] = [];
    header("Location: cart.php");
    exit;
}

include 'includes/header.php';
?>

<main class="container my-4">
    <div class="mb-4">
        <a href="index.php" class="text-decoration-none text-success small fw-medium">&larr; Kembali ke Katalog Paket</a>
        <div class="d-flex justify-content-between align-items-center mt-1">
            <h4 class="fw-bold mb-0">📋 Daftar Pesanan / Paket Pilihan</h4>
            <?php if (!empty($_SESSION['cart'])): ?>
                <a href="cart.php?clear=1" class="text-danger text-decoration-none small"
                   data-confirm="Kosongkan semua paket di daftar pesanan?">Kosongkan semua</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-4">
        <!-- KOLOM KIRI: DAFTAR ITEM -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-0">
                    <?php
                    $grandTotal = 0;

                    if (!empty($_SESSION['cart'])):
                        foreach ($_SESSION['cart'] as $productId => $qty):
                            $stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE id = ?");
                            mysqli_stmt_bind_param($stmt, "i", $productId);
                            mysqli_stmt_execute($stmt);
                            $product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
                            if (!$product) {
                                continue;
                            }
                            $subtotal = $product['price'] * $qty;
                            $grandTotal += $subtotal;
                    ?>
                        <div class="p-3 border-bottom">
                            <div class="row align-items-center g-3">
                                <div class="col-md-5">
                                    <span class="badge <?= $product['type'] === 'jasa' ? 'badge-jasa' : 'badge-barang' ?> mb-1">
                                        <?= strtoupper($product['type']) ?>
                                    </span>
                                    <h6 class="fw-bold mb-1"><?= htmlspecialchars($product['name']) ?></h6>
                                    <p class="text-muted small mb-0">
                                        <?= htmlspecialchars($product['umkm_name']) ?> &bull; Rp <?= number_format($product['price'], 0, ',', '.') ?>
                                    </p>
                                </div>

                                <div class="col-6 col-md-4">
                                    <span class="small text-muted d-block mb-1">Jumlah Sesi / Unit</span>
                                    <div class="qty-control">
                                        <a href="cart.php?dec=<?= (int)$productId ?>" aria-label="Kurangi jumlah">&minus;</a>
                                        <span><?= (int)$qty ?></span>
                                        <a href="cart.php?inc=<?= (int)$productId ?>" aria-label="Tambah jumlah">+</a>
                                    </div>
                                </div>

                                <div class="col-6 col-md-3 text-end">
                                    <div class="fw-bold text-success mb-1">
                                        Rp <?= number_format($subtotal, 0, ',', '.') ?>
                                    </div>
                                    <a href="cart.php?remove=<?= (int)$productId ?>" class="text-danger text-decoration-none small"
                                       data-confirm="Hapus <?= htmlspecialchars($product['name'], ENT_QUOTES) ?> dari daftar pesanan?">
                                        Hapus
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php
                        endforeach;
                    else:
                    ?>
                        <div class="text-center py-5">
                            <p class="text-muted mb-3">Belum ada paket yang dipilih. Pilih paket dari katalog untuk memulai.</p>
                            <a href="index.php" class="btn btn-success btn-sm px-4">Lihat Katalog Paket</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- KOLOM KANAN: RINGKASAN -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3">Ringkasan Pemesanan</h6>

                    <div class="d-flex justify-content-between text-muted small mb-2">
                        <span>Total Jenis Paket</span>
                        <span><?= count($_SESSION['cart']) ?> Jenis</span>
                    </div>
                    <div class="d-flex justify-content-between text-muted small mb-2">
                        <span>Total Sesi / Unit</span>
                        <span><?= array_sum($_SESSION['cart']) ?></span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <span class="fw-medium">Total Biaya</span>
                        <span class="fs-5 fw-bold text-dark">Rp <?= number_format($grandTotal, 0, ',', '.') ?></span>
                    </div>

                    <?php if (!empty($_SESSION['cart'])): ?>
                        <a href="checkout.php" class="btn btn-success w-100 py-2 fw-semibold">
                            Lanjut ke Checkout &rarr;
                        </a>
                    <?php else: ?>
                        <button class="btn btn-secondary w-100 py-2 fw-semibold" disabled>
                            Daftar Pesanan Kosong
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>