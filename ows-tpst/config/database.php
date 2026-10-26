<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'ows_tpst';

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

// Set timezone ke WIB
date_default_timezone_set('Asia/Jakarta');
?>