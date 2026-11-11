<?php
$pageTitle = "Formulir Checkout - LENSA UYUB Studio";
include 'koneksi.php';

// Jika keranjang kosong, kembalikan ke halaman cart
if (empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit;
}

$subtotalProduk = 0;
foreach ($_SESSION['cart'] as $productId => $qty) {
    $q = mysqli_query($conn, "SELECT price FROM products WHERE id = $productId");
    if ($p = mysqli_fetch_assoc($q)) {
        $subtotalProduk += ($p['price'] * $qty);
    }
}
$defaultOngkir = 15000;
$totalTagihan = $subtotalProduk + $defaultOngkir;

include 'includes/header.php';
?>

<main class="container my-4">
    <div class="mb-4">
        <a href="cart.php" class="text-decoration-none text-success small fw-medium">&larr; Kembali ke Daftar Pesanan</a>
        <h4 class="fw-bold mb-0 mt-1">📝 Formulir Checkout &amp; Penjadwalan Sesi</h4>
    </div>

    <form action="invoice.php" method="POST">
        <div class="row g-4">
            <!-- KOLOM KIRI: DATA KLIEN & PENGIRIMAN -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3">1. Informasi Klien &amp; Jadwal Sesi</h6>
                        
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Nama Lengkap Klien</label>
                            <input type="text" name="customer_name" class="form-control" placeholder="Nama Anda / Perwakilan UMKM" value="Aldian Yudhanto" required>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Nomor WhatsApp</label>
                                <input type="text" name="customer_phone" class="form-control" placeholder="0812xxxx" value="081212505074" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Rencana Tanggal Sesi Pemotretan</label>
                                <input type="date" name="booking_date" class="form-control" value="<?= date('Y-m-d', strtotime('+3 days')) ?>" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-semibold">Alamat Pengiriman Hasil Cetak / Catatan Lokasi Studio</label>
                            <textarea name="shipping_address" class="form-control" rows="2" required>Perumahan Mangun Jaya Lestari 2, Tambun Selatan</textarea>
                        </div>

                        <h6 class="fw-bold mb-3">2. Opsi Pengiriman Produk Fisik / Album</h6>
                        <div class="d-flex flex-column gap-2">
                            <label class="border rounded-3 p-3 d-flex justify-content-between align-items-center bg-light">
                                <div>
                                    <input type="radio" name="shipping_cost" value="15000" class="form-check-input me-2 shipping-radio" checked>
                                    <span class="fw-medium small">Kurir Ekspres (Pengiriman Album / Cetak Kanvas)</span>
                                </div>
                                <span class="fw-bold small text-success">Rp 15.000</span>
                            </label>

                            <label class="border rounded-3 p-3 d-flex justify-content-between align-items-center">
                                <div>
                                    <input type="radio" name="shipping_cost" value="0" class="form-check-input me-2 shipping-radio">
                                    <span class="fw-medium small">Ambil Langsung di Studio / Khusus Layanan File Digital</span>
                                </div>
                                <span class="fw-bold small text-success">Rp 0</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KOLOM KANAN: RINGKASAN TAGIHAN -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3">Ringkasan Biaya</h6>

                        <div class="d-flex justify-content-between small text-muted mb-2">
                            <span>Subtotal Paket Jasa &amp; Produk</span>
                            <span>Rp <?= number_format($subtotalProduk, 0, ',', '.') ?></span>
                        </div>
                        <div class="d-flex justify-content-between small text-muted mb-3">
                            <span>Biaya Kirim / Penanganan</span>
                            <span id="ongkir-display">Rp <?= number_format($defaultOngkir, 0, ',', '.') ?></span>
                        </div>

                        <div class="d-flex justify-content-between align-items-center border-top pt-3 mb-4">
                            <span class="fw-bold">Total Tagihan</span>
                            <span class="fs-5 fw-bold text-success" id="total-display" data-subtotal="<?= $subtotalProduk ?>">
                                Rp <?= number_format($totalTagihan, 0, ',', '.') ?>
                            </span>
                        </div>

                        <div class="alert alert-light border small text-muted mb-3 py-2">
                            🔒 Pembayaran dienkripsi aman dan terhubung dengan sistem <strong>Payment Gateway</strong>.
                        </div>

                        <button type="submit" class="btn btn-success w-100 py-2 fw-semibold">
                            💳 Buat Pesanan &amp; Lanjut ke Pembayaran
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</main>

<script>
// Kalkulasi dinamis ongkir pada halaman checkout
document.querySelectorAll('.shipping-radio').forEach(radio => {
    radio.addEventListener('change', function() {
        const ongkir = parseInt(this.value);
        const subtotal = parseInt(document.getElementById('total-display').getAttribute('data-subtotal'));
        const total = subtotal + ongkir;

        document.getElementById('ongkir-display').textContent = 'Rp ' + ongkir.toLocaleString('id-ID');
        document.getElementById('total-display').textContent = 'Rp ' + total.toLocaleString('id-ID');
    });
});
</script>

<?php include 'includes/footer.php'; ?>