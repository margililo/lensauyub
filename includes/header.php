<?php
// Hitung total item di dalam daftar pesanan (Session Cart)
$totalCartItems = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $qty) {
        $totalCartItems += $qty;
    }
}

// Halaman aktif, dipakai untuk menandai menu dan menampilkan langkah pemesanan
$currentPage = basename($_SERVER['SCRIPT_NAME'], '.php');
$orderSteps = [
    'index'    => 'Pilih paket',
    'cart'     => 'Daftar pesanan',
    'checkout' => 'Checkout',
    'invoice'  => 'Pembayaran',
];
$stepKeys   = array_keys($orderSteps);
$currentIdx = array_search($currentPage, $stepKeys, true);
$showSteps  = ($currentIdx !== false && $currentPage !== 'index');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0B4F42">
    <title><?= $pageTitle ?? 'LENSA UYUB Studio - Booking Jasa Foto & Cetak' ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,700&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <header class="site-header">
        <div class="container">
            <div class="bar">
                <a class="brand" href="index.php">
                    <span class="brand-mark" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="8.5" fill="none" stroke="#fff" stroke-width="2"/><circle cx="12" cy="12" r="3" fill="#FFC93C"/></svg>
                    </span>
                    <span>LENSA UYUB <small>Studio</small></span>
                </a>

                <nav class="nav-links" aria-label="Menu utama">
                    <a href="index.php" class="nav-link-pill <?= $currentPage === 'index' ? 'active' : '' ?>">Katalog</a>
                    <a href="riwayat.php" class="nav-link-pill <?= $currentPage === 'riwayat' ? 'active' : '' ?>">Riwayat</a>
                    <a href="cart.php" class="cart-pill">
                        Daftar Pesanan <span class="count" id="cart-count"><?= $totalCartItems ?></span>
                    </a>
                </nav>
            </div>

            <?php if ($showSteps): ?>
                <ol class="stepper" aria-label="Langkah pemesanan">
                    <?php foreach ($orderSteps as $key => $label):
                        $idx = array_search($key, $stepKeys, true);
                        $state = $idx < $currentIdx ? 'done' : ($idx === $currentIdx ? 'current' : '');
                    ?>
                        <li class="<?= $state ?>" <?= $state === 'current' ? 'aria-current="step"' : '' ?>>
                            <span class="dot"><?= $state === 'done' ? '✓' : ($idx + 1) ?></span>
                            <?php if ($state === 'done' && $key !== 'invoice'): ?>
                                <a href="<?= $key ?>.php"><?= $label ?></a>
                            <?php else: ?>
                                <span><?= $label ?></span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </div>
    </header>