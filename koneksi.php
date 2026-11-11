<?php
session_start();

$host = "127.0.0.1";
$port = 3306; // Port di Xampp Yud
$user = "root";
$pass = "";
$db   = "db_mitra_umkm";

$conn = mysqli_connect($host, $user, $pass, $db, $port);

if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
?>