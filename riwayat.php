<?php
// Tampilkan error PHP selama masa pengembangan (hapus 2 baris ini kalau sudah rampung)
ini_set('display_errors', 1);
error_reporting(E_ALL);

$pageTitle = "Riwayat Transaksi - LENSA UYUB Studio";
include 'koneksi.php';
include 'includes/header.php';

// Pencarian opsional: nomor invoice, nama, atau nomor WhatsApp
$keyword = trim($_GET['q'] ?? '');
$where = '';
if ($keyword !== '') {
    $safe = mysqli_real_escape_string($conn, $keyword);
    $where = "WHERE invoice_number LIKE '%$safe%'
              OR customer_name LIKE '%$safe%'
              OR customer_phone LIKE '%$safe%'";
}

$orders = mysqli_query($conn, "SELECT * FROM orders $where ORDER BY created_at DESC");
if (!$orders) {
    die('<div class="container my-4 alert alert-danger">Query gagal: ' . htmlspecialchars(mysqli_error($conn)) . '</div>');
}
$totalOrders = mysqli_num_rows($orders);
?>

<main class="container my-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <a href="index.php" class="text-decoration-none text-success small fw-medium">&larr; Kembali ke Katalog Paket</a>
            <h4 class="fw-bold mb-0 mt-1">🧾 Riwayat Transaksi</h4>
            <div class="text-muted small"><?= $totalOrders ?> transaksi</div>
        </div>
        <form method="GET" class="d-flex gap-2">
            <input type="text" name="q" class="form-control form-control-sm"
                   placeholder="Cari invoice / nama / WhatsApp"
                   value="<?= htmlspecialchars($keyword) ?>">
            <button class="btn btn-success btn-sm px-3">Cari</button>
            <?php if ($keyword !== ''): ?>
                <a href="riwayat.php" class="btn btn-outline-secondary btn-sm">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if ($totalOrders > 0): ?>
        <?php while ($order = mysqli_fetch_assoc($orders)): ?>
            <?php
            $orderId = (int)$order['id'];
            $items = mysqli_query($conn, "SELECT oi.qty, oi.price, p.name
                FROM order_items oi
                LEFT JOIN products p ON p.id = oi.product_id
                WHERE oi.order_id = $orderId");
            $isPaid = $order['payment_status'] === 'PAID';
            ?>
            <div class="card border-0 shadow-sm rounded-3 mb-3">
                <div class="card-body p-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                        <div>
                            <h6 class="fw-bold mb-1"><?= htmlspecialchars($order['invoice_number']) ?></h6>
                            <div class="text-muted small">
                                Dibuat: <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?>
                            </div>
                        </div>
                        <span class="badge <?= $isPaid ? 'bg-success' : 'bg-warning text-dark' ?> px-3 py-2">
                            <?= $isPaid ? 'LUNAS' : 'BELUM BAYAR' ?>
                        </span>
                    </div>

                    <div class="row g-3 small mb-3">
                        <div class="col-md-4">
                            <div class="text-muted">Klien</div>
                            <div class="fw-semibold"><?= htmlspecialchars($order['customer_name']) ?></div>
                            <div><?= htmlspecialchars($order['customer_phone']) ?></div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted">Jadwal Sesi</div>
                            <div class="fw-semibold">
                                <?= $order['booking_date'] ? date('d M Y', strtotime($order['booking_date'])) : '-' ?>
                            </div>
                            <div><?= htmlspecialchars($order['shipping_courier']) ?></div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted">Metode Pembayaran</div>
                            <div class="fw-semibold"><?= htmlspecialchars($order['payment_method'] ?? '-') ?></div>
                        </div>
                    </div>

                    <button class="btn btn-outline-success btn-sm mb-2" type="button"
                            data-bs-toggle="collapse" data-bs-target="#detail-<?= $orderId ?>">
                        Lihat rincian paket
                    </button>

                    <div class="collapse" id="detail-<?= $orderId ?>">
                        <div class="border rounded-3">
                            <?php if ($items): while ($item = mysqli_fetch_assoc($items)): ?>
                                <div class="d-flex justify-content-between small p-2 border-bottom">
                                    <span>
                                        <?= htmlspecialchars($item['name'] ?? '(paket dihapus)') ?>
                                        <span class="text-muted">&times; <?= (int)$item['qty'] ?></span>
                                    </span>
                                    <span>Rp <?= number_format($item['price'] * $item['qty'], 0, ',', '.') ?></span>
                                </div>
                            <?php endwhile; endif; ?>
                            <div class="d-flex justify-content-between small text-muted p-2 border-bottom">
                                <span>Biaya Kirim</span>
                                <span>Rp <?= number_format($order['shipping_cost'], 0, ',', '.') ?></span>
                            </div>
                            <div class="d-flex justify-content-between fw-bold p-2">
                                <span>Total</span>
                                <span class="text-success">Rp <?= number_format($order['total_amount'], 0, ',', '.') ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="text-center py-5">
            <p class="text-muted mb-3">
                <?= $keyword !== '' ? 'Tidak ada transaksi yang cocok dengan pencarian.' : 'Belum ada riwayat transaksi.' ?>
            </p>
            <a href="index.php" class="btn btn-success btn-sm px-4">Lihat Katalog Paket</a>
        </div>
    <?php endif; ?>
</main>

<?php include 'includes/footer.php'; ?>