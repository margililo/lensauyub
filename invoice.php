<?php
$pageTitle = "Invoice & Payment Gateway - LENSA UYUB Studio";
include 'koneksi.php';

// ---------------------------------------------------------
// DAFTAR METODE PEMBAYARAN (dipakai untuk tampilan & validasi)
// ---------------------------------------------------------
$vaBanks = [
    'BCA'     => ['logo' => 'bca',     'va' => '8801 0812 9876 5432'],
    'Mandiri' => ['logo' => 'mandiri', 'va' => '8860 0812 9876 5432'],
    'BRI'     => ['logo' => 'bri',     'va' => '8881 0812 9876 5432'],
    'BNI'     => ['logo' => 'bni',     'va' => '8808 0812 9876 5432'],
];
$wallets = [
    'GoPay'     => 'gopay',
    'DANA'      => 'dana',
    'ShopeePay' => 'shopeepay',
    'OVO'       => 'ovo',
];

$allowedMethods = ['QRIS Nasional'];
foreach ($vaBanks as $bank => $info) { $allowedMethods[] = 'Transfer Virtual Account ' . $bank; }
foreach ($wallets as $wallet => $logo) { $allowedMethods[] = 'E-Wallet ' . $wallet; }

// Cari file logo dari teks metode pembayaran yang tersimpan di database
function paymentLogo($method) {
    $m = strtolower((string)$method);
    foreach (['qris', 'bca', 'mandiri', 'bri', 'bni', 'gopay', 'dana', 'shopeepay', 'ovo'] as $key) {
        if (strpos($m, $key) !== false) {
            return 'assets/img/pay/' . $key . '.svg';
        }
    }
    return null;
}

// 1. PROSES SAAT FORM CHECKOUT DIKIRIM (Membuat Invoice Baru di Database)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['customer_name'])) {
    if (empty($_SESSION['cart'])) {
        header("Location: index.php");
        exit;
    }

    $customerName    = mysqli_real_escape_string($conn, $_POST['customer_name']);
    $customerPhone   = mysqli_real_escape_string($conn, $_POST['customer_phone']);
    $bookingDate     = mysqli_real_escape_string($conn, $_POST['booking_date']);
    $shippingAddress = mysqli_real_escape_string($conn, $_POST['shipping_address']);
    $shippingCost    = (int)$_POST['shipping_cost'];
    $shippingCourier = ($shippingCost > 0) ? 'Kurir Ekspres Studio' : 'Ambil di Studio / File Digital';

    // Hitung ulang total harga produk dari database
    $subtotalProduk = 0;
    $cartData = [];
    foreach ($_SESSION['cart'] as $productId => $qty) {
        $q = mysqli_query($conn, "SELECT id, price FROM products WHERE id = " . (int)$productId);
        if ($p = mysqli_fetch_assoc($q)) {
            $subtotalProduk += ($p['price'] * $qty);
            $cartData[] = [
                'product_id' => $p['id'],
                'qty'        => $qty,
                'price'      => $p['price']
            ];
        }
    }

    $totalAmount   = $subtotalProduk + $shippingCost;
    $invoiceNumber = "INV-" . date('Ymd') . "-" . rand(100, 999);

    // Simpan ke tabel orders (Status awal: UNPAID)
    $queryOrder = "INSERT INTO orders 
        (invoice_number, customer_name, customer_phone, booking_date, shipping_address, shipping_courier, shipping_cost, total_amount, payment_status) 
        VALUES 
        ('$invoiceNumber', '$customerName', '$customerPhone', '$bookingDate', '$shippingAddress', '$shippingCourier', $shippingCost, $totalAmount, 'UNPAID')";
    
    if (mysqli_query($conn, $queryOrder)) {
        $orderId = mysqli_insert_id($conn);

        // Simpan rincian paket ke tabel order_items
        foreach ($cartData as $item) {
            $pid   = $item['product_id'];
            $qty   = $item['qty'];
            $price = $item['price'];
            mysqli_query($conn, "INSERT INTO order_items (order_id, product_id, qty, price) VALUES ($orderId, $pid, $qty, $price)");
        }

        // Kosongkan keranjang belanja setelah invoice terbit
        unset($_SESSION['cart']);

        // Arahkan ke halaman invoice dengan nomor invoice unik
        header("Location: invoice.php?inv=" . $invoiceNumber);
        exit;
    }
}

// 2. PROSES SAAT TOMBOL KONFIRMASI BAYAR DI PAYMENT GATEWAY DIKLIK (Ubah UNPAID -> PAID)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_invoice'])) {
    $invNumber     = mysqli_real_escape_string($conn, $_POST['invoice_number']);
    $rawMethod     = $_POST['payment_method'] ?? '';

    // Hanya terima metode yang ada di daftar (cegah nilai sembarang dari form)
    if (in_array($rawMethod, $allowedMethods, true)) {
        $paymentMethod = mysqli_real_escape_string($conn, $rawMethod);
        mysqli_query($conn, "UPDATE orders SET payment_status = 'PAID', payment_method = '$paymentMethod' WHERE invoice_number = '$invNumber'");
    }
    
    header("Location: invoice.php?inv=" . $invNumber);
    exit;
}

// 3. AMBIL DATA INVOICE UNTUK DITAMPILKAN DI HALAMAN
$invParam = isset($_GET['inv']) ? mysqli_real_escape_string($conn, $_GET['inv']) : '';

if ($invParam !== '') {
    $orderQuery = mysqli_query($conn, "SELECT * FROM orders WHERE invoice_number = '$invParam'");
} else {
    // Jika dibuka tanpa parameter, tampilkan transaksi terakhir
    $orderQuery = mysqli_query($conn, "SELECT * FROM orders ORDER BY id DESC LIMIT 1");
}

$order = mysqli_fetch_assoc($orderQuery);

include 'includes/header.php';
?>

<style>
    /* Pilihan metode pembayaran dengan logo */
    .pay-group-title { font-size: .78rem; font-weight: 600; color: #6b7a73; margin-bottom: .4rem; }
    .pay-tiles { display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: .5rem; }
    .pay-tile { position: relative; margin: 0; cursor: pointer; }
    .pay-input { position: absolute; opacity: 0; pointer-events: none; }
    .pay-tile-body {
        display: flex; flex-direction: column; align-items: center; gap: .35rem;
        border: 2px solid #e1e7e4; border-radius: .75rem; padding: .6rem .5rem;
        background: #fff; transition: border-color .15s, box-shadow .15s, background-color .15s;
    }
    .pay-tile:hover .pay-tile-body { border-color: #9ec5fe; }
    .pay-tile-body img { width: 96px; height: auto; display: block; }
    .pay-tile-body span { font-size: .75rem; color: #3b4a43; font-weight: 600; }
    .pay-input:checked + .pay-tile-body { border-color: #0d6efd; background: #f1f6ff; box-shadow: 0 0 0 3px rgba(13, 110, 253, .12); }
    .pay-input:focus-visible + .pay-tile-body { outline: 3px solid #9ec5fe; outline-offset: 2px; }
    .pay-logo-inline { height: 26px; width: auto; vertical-align: middle; border-radius: 4px; }
    @media print { .no-print { display: none !important; } }
</style>

<main class="container my-4">
    <?php if (!$order): ?>
        <div class="text-center py-5">
            <h5 class="fw-bold">Belum Ada Data Transaksi</h5>
            <p class="text-muted small">Silakan pilih paket fotografi terlebih dahulu di katalog.</p>
            <a href="index.php" class="btn btn-success btn-sm px-4">Ke Katalog Paket</a>
        </div>
    <?php else: 
        // Ambil rincian item pesanan dari tabel order_items
        $orderId = $order['id'];
        $itemsQuery = mysqli_query($conn, "
            SELECT oi.*, p.name, p.type 
            FROM order_items oi 
            JOIN products p ON oi.product_id = p.id 
            WHERE oi.order_id = $orderId
        ");
    ?>
        <div class="row justify-content-center">
            <div class="col-lg-7">
                
                <!-- BANNER STATUS INVOICE TOKO DIGITAL -->
                <div class="card border-0 shadow-sm rounded-3 mb-3">
                    <div class="card-body p-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <span class="text-muted small d-block">Nomor Invoice</span>
                            <h5 class="fw-bold mb-0"><?= htmlspecialchars($order['invoice_number']) ?></h5>
                        </div>
                        <?php if ($order['payment_status'] === 'UNPAID'): ?>
                            <span class="badge bg-warning text-dark px-3 py-2 fs-6">
                                ⏳ STATUS: UNPAID (MENUNGGU PEMBAYARAN)
                            </span>
                        <?php else: ?>
                            <span class="badge bg-success text-white px-3 py-2 fs-6">
                                ✅ STATUS: PAID (LUNAS)
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($order['payment_status'] === 'UNPAID'): ?>
                    <!-- ============================================== -->
                    <!-- TAMPILAN 1: MODAL PAYMENT GATEWAY (SAAT UNPAID) -->
                    <!-- ============================================== -->
                    <div class="card border-primary border-2 shadow rounded-3 overflow-hidden mb-4">
                        <div class="bg-primary text-white p-3 d-flex justify-content-between align-items-center">
                            <span class="fw-bold">🔒 Payment Gateway (Terenkripsi SSL)</span>
                            <span class="badge bg-light text-primary">LensaPay Sandbox</span>
                        </div>

                        <div class="card-body p-4">
                            <div class="mb-3 pb-3 border-bottom d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="text-muted small d-block">Total Pembayaran</span>
                                    <h4 class="fw-bold text-primary mb-0">Rp <?= number_format($order['total_amount'], 0, ',', '.') ?></h4>
                                </div>
                                <div class="text-end small text-muted">
                                    <div>Klien: <strong><?= htmlspecialchars($order['customer_name']) ?></strong></div>
                                    <div>Jadwal Sesi: <?= htmlspecialchars($order['booking_date']) ?></div>
                                </div>
                            </div>

                            <form action="invoice.php" method="POST">
                                <input type="hidden" name="pay_invoice" value="1">
                                <input type="hidden" name="invoice_number" value="<?= htmlspecialchars($order['invoice_number']) ?>">

                                <h6 class="fw-bold small mb-3">Pilih Metode Pembayaran Elektronik:</h6>

                                <!-- GRUP 1: QRIS -->
                                <div class="mb-3">
                                    <div class="pay-group-title">QRIS (scan dari semua e-wallet &amp; mobile banking)</div>
                                    <div class="pay-tiles">
                                        <label class="pay-tile">
                                            <input type="radio" name="payment_method" value="QRIS Nasional"
                                                   class="pay-input" data-target="info-qris" checked>
                                            <div class="pay-tile-body">
                                                <img src="assets/img/pay/qris.svg" alt="QRIS">
                                                <span>QRIS</span>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <!-- GRUP 2: TRANSFER BANK (VIRTUAL ACCOUNT) -->
                                <div class="mb-3">
                                    <div class="pay-group-title">Transfer bank (Virtual Account)</div>
                                    <div class="pay-tiles">
                                        <?php foreach ($vaBanks as $bank => $info): ?>
                                            <label class="pay-tile">
                                                <input type="radio" name="payment_method"
                                                       value="Transfer Virtual Account <?= $bank ?>"
                                                       class="pay-input" data-target="info-va"
                                                       data-bank="<?= $bank ?>" data-va="<?= $info['va'] ?>">
                                                <div class="pay-tile-body">
                                                    <img src="assets/img/pay/<?= $info['logo'] ?>.svg" alt="<?= $bank ?>">
                                                    <span><?= $bank ?></span>
                                                </div>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <!-- GRUP 3: E-WALLET -->
                                <div class="mb-3">
                                    <div class="pay-group-title">E-Wallet instan</div>
                                    <div class="pay-tiles">
                                        <?php foreach ($wallets as $wallet => $logo): ?>
                                            <label class="pay-tile">
                                                <input type="radio" name="payment_method"
                                                       value="E-Wallet <?= $wallet ?>"
                                                       class="pay-input" data-target="info-ewallet"
                                                       data-wallet="<?= $wallet ?>">
                                                <div class="pay-tile-body">
                                                    <img src="assets/img/pay/<?= $logo ?>.svg" alt="<?= $wallet ?>">
                                                    <span><?= $wallet ?></span>
                                                </div>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <!-- PANEL INFO SESUAI METODE YANG DIPILIH -->
                                <div class="mb-4">
                                    <div id="info-qris" class="method-detail p-3 bg-white border rounded text-center">
                                        <div class="small fw-semibold text-dark mb-2">Kode QRIS LENSA UYUB Studio</div>
                                        <div id="qris-box" class="d-inline-block p-2 bg-white border rounded"></div>
                                        <div class="fw-bold text-primary mt-2">Rp <?= number_format($order['total_amount'], 0, ',', '.') ?></div>
                                        <div class="text-muted" style="font-size: 0.72rem;">
                                            NMID: ID20261108UMKM001 &bull; <?= htmlspecialchars($order['invoice_number']) ?>
                                        </div>
                                        <div class="d-flex justify-content-center gap-2 mt-3 flex-wrap">
                                            <button type="button" id="qris-download" class="btn btn-outline-primary btn-sm">Unduh QR (PNG)</button>
                                            <button type="button" id="qris-copy" class="btn btn-outline-secondary btn-sm">Salin isi QR</button>
                                        </div>
                                        <div class="text-muted mt-2" style="font-size: 0.7rem;">
                                            QR sandbox untuk simulasi. Bukan QRIS resmi, jadi tidak bisa dipindai aplikasi bank atau e-wallet.
                                        </div>
                                    </div>

                                    <div id="info-va" class="method-detail p-3 bg-white border rounded d-none">
                                        <div class="small text-muted">Nomor Virtual Account <span id="va-bank">BCA</span>:</div>
                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                            <div id="va-number" class="fw-bold font-monospace text-primary fs-5"></div>
                                            <button type="button" id="va-copy" class="btn btn-outline-primary btn-sm py-0">Salin</button>
                                        </div>
                                    </div>

                                    <div id="info-ewallet" class="method-detail p-3 bg-white border rounded d-none">
                                        <div class="small text-muted">
                                            Notifikasi pembayaran <strong id="wallet-name">GoPay</strong> dikirim ke nomor klien:
                                            <strong><?= htmlspecialchars($order['customer_phone']) ?></strong>
                                        </div>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-success w-100 py-2 fw-bold">
                                    ✅ Konfirmasi Pembayaran Sekarang
                                </button>
                            </form>
                        </div>
                    </div>

                <?php else: ?>
                    <!-- ============================================== -->
                    <!-- TAMPILAN 2: BUKTI INVOICE LUNAS (SAAT PAID)     -->
                    <!-- ============================================== -->
                    <?php $paidLogo = paymentLogo($order['payment_method']); ?>
                    <div class="card border-success border-2 shadow-sm rounded-3 mb-4">
                        <div class="card-body p-4">
                            <div class="text-center mb-4 pb-3 border-bottom">
                                <div class="fs-1 mb-1">🎉</div>
                                <h5 class="fw-bold text-success mb-1">Pembayaran Lunas &amp; Jadwal Terkonfirmasi!</h5>
                                <p class="text-muted small mb-0">
                                    Dibayar melalui
                                    <?php if ($paidLogo): ?>
                                        <img src="<?= $paidLogo ?>" alt="" class="pay-logo-inline mx-1">
                                    <?php endif; ?>
                                    <strong><?= htmlspecialchars($order['payment_method']) ?></strong>
                                    pada <?= htmlspecialchars($order['created_at']) ?>
                                </p>
                            </div>

                            <div class="row g-3 small mb-4">
                                <div class="col-sm-6">
                                    <div class="text-muted">Nama Klien:</div>
                                    <div class="fw-bold"><?= htmlspecialchars($order['customer_name']) ?> (<?= htmlspecialchars($order['customer_phone']) ?>)</div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="text-muted">Tanggal Sesi Foto:</div>
                                    <div class="fw-bold text-success"><?= htmlspecialchars($order['booking_date']) ?></div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="text-muted">Opsi Logistik / Pengiriman:</div>
                                    <div class="fw-semibold"><?= htmlspecialchars($order['shipping_courier']) ?></div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="text-muted">Alamat Klien / Pengiriman Cetak:</div>
                                    <div class="fw-semibold"><?= htmlspecialchars($order['shipping_address']) ?></div>
                                </div>
                            </div>

                            <h6 class="fw-bold small mb-2">Rincian Paket Jasa &amp; Produk:</h6>
                            <div class="table-responsive mb-3">
                                <table class="table table-sm table-bordered small mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Nama Paket / Produk</th>
                                            <th class="text-center">Jumlah</th>
                                            <th class="text-end">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($item = mysqli_fetch_assoc($itemsQuery)): ?>
                                            <tr>
                                                <td>
                                                    <?= htmlspecialchars($item['name']) ?>
                                                    <span class="badge <?= $item['type'] === 'jasa' ? 'badge-jasa' : 'badge-barang' ?> ms-1">
                                                        <?= strtoupper($item['type']) ?>
                                                    </span>
                                                </td>
                                                <td class="text-center"><?= $item['qty'] ?></td>
                                                <td class="text-end">Rp <?= number_format($item['price'] * $item['qty'], 0, ',', '.') ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                        <tr>
                                            <td colspan="2" class="text-end text-muted">Biaya Pengiriman / Penanganan</td>
                                            <td class="text-end">Rp <?= number_format($order['shipping_cost'], 0, ',', '.') ?></td>
                                        </tr>
                                        <tr class="table-success fw-bold">
                                            <td colspan="2" class="text-end">TOTAL LUNAS (PAID)</td>
                                            <td class="text-end">Rp <?= number_format($order['total_amount'], 0, ',', '.') ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex gap-2 justify-content-center mt-4 no-print">
                                <button onclick="window.print()" class="btn btn-outline-dark btn-sm px-3">
                                    🖨️ Cetak Bukti Invoice (PDF)
                                </button>
                                <a href="index.php" class="btn btn-success btn-sm px-4">
                                    + Buat Pesanan Baru
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    <?php endif; ?>
</main>

<?php if ($order && $order['payment_status'] === 'UNPAID'): ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
// QR dibuat otomatis untuk setiap invoice (nomor & nominal ikut masuk ke isi QR)
(function () {
    const box = document.getElementById('qris-box');
    if (!box || typeof QRCode === 'undefined') return;

    const invoice = <?= json_encode($order['invoice_number']) ?>;
    const amount  = <?= (int)$order['total_amount'] ?>;
    const payload = ['LENSAPAY-SANDBOX', invoice, amount, 'ID20261108UMKM001'].join('|');

    new QRCode(box, { text: payload, width: 200, height: 200, correctLevel: QRCode.CorrectLevel.M });

    // Unduh sebagai PNG (QR + nomor invoice + nominal)
    document.getElementById('qris-download').addEventListener('click', () => {
        const src = box.querySelector('canvas');
        if (!src) return;
        const pad = 30, w = src.width + pad * 2, h = src.height + pad * 2 + 70;
        const c = document.createElement('canvas');
        c.width = w; c.height = h;
        const g = c.getContext('2d');
        g.fillStyle = '#fff'; g.fillRect(0, 0, w, h);
        g.drawImage(src, pad, pad);
        g.fillStyle = '#111'; g.textAlign = 'center';
        g.font = 'bold 16px Arial';
        g.fillText('LENSA UYUB Studio', w / 2, src.height + pad + 28);
        g.font = '14px Arial';
        g.fillText(invoice + '  |  Rp ' + amount.toLocaleString('id-ID'), w / 2, src.height + pad + 52);

        const a = document.createElement('a');
        a.href = c.toDataURL('image/png');
        a.download = 'QRIS-' + invoice + '.png';
        a.click();
    });

    const copyBtn = document.getElementById('qris-copy');
    copyBtn.addEventListener('click', async () => {
        try { await navigator.clipboard.writeText(payload); copyBtn.textContent = 'Tersalin'; }
        catch (e) { copyBtn.textContent = 'Gagal menyalin'; }
        setTimeout(() => (copyBtn.textContent = 'Salin isi QR'), 1500);
    });
})();
</script>
<?php endif; ?>

<script>
// Interaksi pilihan metode pembayaran: tampilkan panel info sesuai pilihan
(function () {
    const radios = document.querySelectorAll('.pay-input');
    if (!radios.length) return;

    function showInfo(radio) {
        document.querySelectorAll('.method-detail').forEach(d => d.classList.add('d-none'));
        const panel = document.getElementById(radio.dataset.target);
        if (panel) panel.classList.remove('d-none');

        if (radio.dataset.va) {
            document.getElementById('va-bank').textContent = radio.dataset.bank;
            document.getElementById('va-number').textContent = radio.dataset.va;
        }
        if (radio.dataset.wallet) {
            document.getElementById('wallet-name').textContent = radio.dataset.wallet;
        }
    }

    radios.forEach(r => r.addEventListener('change', () => showInfo(r)));
    const checked = document.querySelector('.pay-input:checked');
    if (checked) showInfo(checked);

    // Tombol salin nomor Virtual Account
    const copyBtn = document.getElementById('va-copy');
    if (copyBtn) {
        copyBtn.addEventListener('click', async () => {
            const number = document.getElementById('va-number').textContent.replace(/\s/g, '');
            try {
                await navigator.clipboard.writeText(number);
                copyBtn.textContent = 'Tersalin';
            } catch (e) {
                copyBtn.textContent = 'Gagal menyalin';
            }
            setTimeout(() => (copyBtn.textContent = 'Salin'), 1500);
        });
    }
})();
</script>

<?php include 'includes/footer.php'; ?>