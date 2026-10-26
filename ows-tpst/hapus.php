<?php
define('ACCESS_ALLOWED', true);
require_once 'config/auth.php';
requireAdmin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if($id > 0) {
    // Ambil data untuk log
    $stmt = $conn->prepare("SELECT * FROM ows_data WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    
    if($data) {
        $delete = $conn->prepare("DELETE FROM ows_data WHERE id = ?");
        $delete->bind_param("i", $id);
        if($delete->execute()) {
            addLog($conn, $_SESSION['user_id'], $_SESSION['username'], 'hapus', "Menghapus data ID $id: {$data['nomor_plat']} - {$data['tonase']} ton");
        }
    }
}

header('Location: data.php');
exit();
?>