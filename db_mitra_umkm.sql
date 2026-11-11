-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 10, 2026 at 12:22 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_mitra_umkm`
--

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_phone` varchar(30) NOT NULL,
  `booking_date` date DEFAULT NULL,
  `shipping_address` text NOT NULL,
  `shipping_courier` varchar(50) NOT NULL,
  `shipping_cost` int(11) NOT NULL,
  `total_amount` int(11) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_status` enum('UNPAID','PAID') DEFAULT 'UNPAID',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `invoice_number`, `customer_name`, `customer_phone`, `booking_date`, `shipping_address`, `shipping_courier`, `shipping_cost`, `total_amount`, `payment_method`, `payment_status`, `created_at`) VALUES
(1, 'INV-20261108-322', 'Aldian Yudhanto', '081212505074', '2026-11-11', 'Perumahan Mangun Jaya Lestari 2, Tambun Selatan', 'Kurir Ekspres Studio', 15000, 1165000, 'Transfer Virtual Account BCA', 'PAID', '2026-11-08 06:07:54'),
(2, 'INV-20261108-905', 'Aldian Yudhanto', '081212505074', '2026-11-11', 'Perumahan Mangun Jaya Lestari 2, Tambun Selatan', 'Kurir Ekspres Studio', 15000, 265000, 'QRIS Nasional', 'PAID', '2026-11-08 06:12:40'),
(3, 'INV-20261110-220', 'Aldian Yudhanto', '081212505074', '2026-11-13', 'Perumahan Mangun Jaya Lestari 2, Tambun Selatan', 'Kurir Ekspres Studio', 15000, 165000, 'QRIS Nasional', 'PAID', '2026-11-10 10:44:49'),
(4, 'INV-20261110-228', 'Aldian Yudhanto', '081212505074', '2026-11-13', 'Perumahan Mangun Jaya Lestari 2, Tambun Selatan', 'Kurir Ekspres Studio', 15000, 765000, 'QRIS Nasional', 'PAID', '2026-11-10 10:50:49'),
(5, 'INV-20261110-929', 'Aldian Yudhanto', '081212505074', '2026-11-13', 'Perumahan Mangun Jaya Lestari 2, Tambun Selatan', 'Kurir Ekspres Studio', 15000, 765000, NULL, 'UNPAID', '2026-11-10 10:53:37'),
(6, 'INV-20261110-128', 'Aldian Yudhanto', '081212505074', '2026-11-13', 'Perumahan Mangun Jaya Lestari 2, Tambun Selatan', 'Kurir Ekspres Studio', 15000, 665000, 'QRIS Nasional', 'PAID', '2026-11-10 10:59:04');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `qty` int(11) NOT NULL,
  `price` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `qty`, `price`) VALUES
(1, 1, 1, 1, 150000),
(2, 1, 2, 1, 250000),
(3, 1, 3, 1, 750000),
(4, 2, 2, 1, 250000),
(5, 3, 1, 1, 150000),
(6, 4, 3, 1, 750000),
(7, 5, 3, 1, 750000),
(8, 6, 2, 2, 250000),
(9, 6, 1, 1, 150000);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `umkm_name` varchar(100) NOT NULL,
  `name` varchar(150) NOT NULL,
  `type` enum('barang','jasa') NOT NULL,
  `price` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `umkm_name`, `name`, `type`, `price`, `description`, `image`) VALUES
(1, 'LENSA UYUB Studio', 'Paket Foto Katalog Produk UMKM (10 Foto)', 'jasa', 150000, 'Pemotretan produk di studio dengan 2 konsep latar + edit warna profesional.', 'Foto_Katalog_Produk_UMKM.jpg'),
(2, 'LENSA UYUB Studio', 'Paket Foto Wisuda & Keluarga Studio', 'jasa', 250000, 'Sesi foto 60 menit di studio (maks. 5 orang) + semua file foto resolusi tinggi.', 'Foto_Paket_Wisuda.jpg'),
(3, 'LENSA UYUB Studio', 'Paket Dokumentasi Event / Lamaran (3 Jam)', 'jasa', 750000, 'Liputan fotografer ke lokasi acara selama 3 jam + Google Drive akses cepat.', 'Foto_Dokumentasi_Event.jpg'),
(4, 'LENSA UYUB Studio', 'Add-On: Jasa Edit Video Reels / TikTok Produk', 'jasa', 120000, 'Tambahan video promosi vertikal durasi 30-60 detik siap unggah.', 'Jasa_Edit.jpg'),
(5, 'LENSA UYUB Print', 'Cetak Foto Kanvas + Bingkai Kayu 16R', 'barang', 125000, 'Cetakan kanvas premium ukuran 40x50 cm lengkap dengan bingkai kayu minimalis.', 'Foto_Cetak_foto.jpg'),
(6, 'LENSA UYUB Print', 'Album Kolase Photobook Hardcover (20 Hal)', 'barang', 300000, 'Album foto eksklusif anti-air dengan desain tata letak modern.', 'Foto_Album_Kolase.jpg');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
