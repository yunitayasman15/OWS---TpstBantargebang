<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}
include 'config/database.php';

$page_title = 'Import OWS - TPST Bantargebang';
$page_heading = 'Import Data OWS';
$page_subheading = 'Kelola data OWS (Tanggal, Pintu, Plat, Berat Masuk, Keterangan)';

$success = '';
$error = '';
$importCount = 0;
$duplicateCount = 0;

// ========== PROSES HAPUS ==========
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_id'])) {
    $delete_id = (int)$_POST['delete_id'];
    $stmt = $conn->prepare("DELETE FROM ows_data WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        $success = "Data berhasil dihapus!";
    } else {
        $error = "Gagal menghapus data!";
    }
}

// ========== PROSES EDIT ==========
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_id'])) {
    $edit_id = (int)$_POST['edit_id'];
    $tanggal_jam = trim($_POST['tanggal_jam']);
    $nomor_plat = strtoupper(trim($_POST['nomor_plat']));
    $nomor_pintu = strtoupper(trim($_POST['nomor_pintu']));
    $tonase = floatval(str_replace(',', '.', $_POST['tonase']));
    $keterangan = trim($_POST['keterangan']);
    
    if (empty($tanggal_jam) || empty($nomor_plat) || empty($nomor_pintu) || $tonase <= 0) {
        $error = "Semua field wajib diisi dengan benar!";
    } else {
        $stmt = $conn->prepare("UPDATE ows_data SET tanggal_jam = ?, nomor_plat = ?, nomor_pintu = ?, tonase = ?, keterangan = ? WHERE id = ?");
        $stmt->bind_param("sssdsi", $tanggal_jam, $nomor_plat, $nomor_pintu, $tonase, $keterangan, $edit_id);
        if ($stmt->execute()) {
            $success = "Data berhasil diupdate!";
        } else {
            $error = "Gagal mengupdate data!";
        }
    }
}

// Hapus semua data
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_all'])) {
    $conn->query("TRUNCATE TABLE ows_data");
    $success = "Semua data OWS telah dihapus!";
}

// ========== HAPUS DATA BERDASARKAN BULAN ==========
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_by_month'])) {
    $bulan = (int)$_POST['bulan'];
    $tanggal_batas = date('Y-m-d H:i:s', strtotime("-$bulan months"));
    
    // Hitung berapa data yang akan dihapus
    $cek = $conn->prepare("SELECT COUNT(*) as total FROM ows_data WHERE tanggal_jam < ?");
    $cek->bind_param("s", $tanggal_batas);
    $cek->execute();
    $jumlah_akan_dihapus = $cek->get_result()->fetch_assoc()['total'];
    
    if ($jumlah_akan_dihapus > 0) {
        $stmt = $conn->prepare("DELETE FROM ows_data WHERE tanggal_jam < ?");
        $stmt->bind_param("s", $tanggal_batas);
        if ($stmt->execute()) {
            $success = "Berhasil menghapus <strong>$jumlah_akan_dihapus</strong> data yang lebih lama dari <strong>$bulan bulan</strong> (sebelum " . date('d/m/Y', strtotime($tanggal_batas)) . ").";
        } else {
            $error = "Gagal menghapus data: " . $conn->error;
        }
    } else {
        $error = "Tidak ada data yang lebih lama dari $bulan bulan.";
    }
}

// Tambah satu data
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_single'])) {
    $tanggal_jam = trim($_POST['tanggal_jam']);
    $nomor_plat = strtoupper(trim($_POST['nomor_plat']));
    $nomor_pintu = strtoupper(trim($_POST['nomor_pintu']));
    $tonase = floatval(str_replace(',', '.', $_POST['tonase']));
    $keterangan = trim($_POST['keterangan']);
    
    if (empty($tanggal_jam) || empty($nomor_plat) || empty($nomor_pintu) || $tonase <= 0) {
        $error = "Semua field wajib diisi dengan benar!";
    } else {
        $stmt = $conn->prepare("INSERT INTO ows_data (tanggal_jam, nomor_plat, nomor_pintu, tonase, keterangan) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssds", $tanggal_jam, $nomor_plat, $nomor_pintu, $tonase, $keterangan);
        if ($stmt->execute()) {
            $success = "Berhasil menambahkan data!";
        } else {
            $error = "Gagal menambahkan data: " . $stmt->error;
        }
    }
}

// Import manual copy-paste (Format BARU: tanggal_jam|nomor_pintu|nomor_plat|tonase|keterangan)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['import_manual'])) {
    $manual_data = trim($_POST['manual_data']);
    $lines = explode("\n", $manual_data);
    
    foreach($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        
        // Support separator tab, koma, atau pipe (|)
        $parts = preg_split('/[\t,|]+/', $line, 5);
        if (count($parts) >= 4) {
            $tanggal_jam = trim($parts[0]);
            // PERUBAHAN: nomor_pintu di posisi ke-2, nomor_plat di posisi ke-3
            $nomor_pintu = strtoupper(trim($parts[1]));
            $nomor_plat = strtoupper(trim($parts[2]));
            $tonase = floatval(str_replace(',', '.', $parts[3]));
            $keterangan = isset($parts[4]) ? trim($parts[4]) : '';
            
            if (!empty($tanggal_jam) && !empty($nomor_plat) && !empty($nomor_pintu) && $tonase > 0) {
                $stmt = $conn->prepare("INSERT INTO ows_data (tanggal_jam, nomor_plat, nomor_pintu, tonase, keterangan) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssds", $tanggal_jam, $nomor_plat, $nomor_pintu, $tonase, $keterangan);
                if ($stmt->execute()) $importCount++;
            }
        }
    }
    
    if ($importCount > 0) {
        $success = "Berhasil mengimport $importCount data OWS!";
    } else {
        $error = "Tidak ada data yang diimport. Pastikan format: tanggal_jam [tab/koma] nomor_pintu [tab/koma] nomor_plat [tab/koma] berat_masuk [tab/koma] keterangan";
    }
}

// Import CSV
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['import_csv']) && isset($_FILES['file_csv'])) {
    $file = $_FILES['file_csv']['tmp_name'];
    
    if (($handle = fopen($file, "r")) !== false) {
        $rowCount = 0;
        
        while (($data = fgetcsv($handle, 10000, ",")) !== false) {
            $rowCount++;
            if ($rowCount == 1) continue; // Skip header
            
            // Mapping kolom CSV: tanggal_jam, nomor_plat, nomor_pintu, tonase, keterangan
            $tanggal_jam = isset($data[0]) ? trim($data[0]) : '';
            $nomor_plat = isset($data[1]) ? strtoupper(trim(str_replace('"', '', $data[1]))) : '';
            $nomor_pintu = isset($data[2]) ? strtoupper(trim(str_replace('"', '', $data[2]))) : '';
            $tonase = isset($data[3]) ? floatval(str_replace(',', '.', $data[3])) : 0;
            $keterangan = isset($data[4]) ? trim($data[4]) : '';
            
            if (!empty($tanggal_jam) && !empty($nomor_plat) && !empty($nomor_pintu) && $tonase > 0) {
                $stmt = $conn->prepare("INSERT INTO ows_data (tanggal_jam, nomor_plat, nomor_pintu, tonase, keterangan) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssds", $tanggal_jam, $nomor_plat, $nomor_pintu, $tonase, $keterangan);
                if ($stmt->execute()) $importCount++;
            }
        }
        fclose($handle);
        $success = "Berhasil mengimport $importCount data OWS!";
    } else {
        $error = "Gagal membuka file!";
    }
}

// Filter pencarian
$search_filter = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Query dengan filter
$where = "";
if (!empty($search_filter)) {
    $where = "WHERE nomor_pintu LIKE '%$search_filter%' OR nomor_plat LIKE '%$search_filter%' OR keterangan LIKE '%$search_filter%'";
}

$totalData = $conn->query("SELECT COUNT(*) as total FROM ows_data $where")->fetch_assoc()['total'];
$totalPages = ceil($totalData / $limit);
$sampleData = $conn->query("SELECT * FROM ows_data $where ORDER BY tanggal_jam DESC LIMIT $offset, $limit");

// Include header (yang berisi sidebar)
include 'includes/header.php';
?>

<style>
    .table-container { overflow-x: auto; }
    .data-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .data-table th { background: #f8fafc; padding: 12px 10px; position: sticky; top: 0; border-bottom: 2px solid #eef2f6; }
    .data-table td { padding: 10px 10px; border-bottom: 1px solid #eef2f6; vertical-align: middle; }
    .data-table tr:hover td { background: #fafbfd; }
    
    .btn-sm-custom { padding: 4px 10px; font-size: 11px; border-radius: 6px; }
    .btn-edit-sm { background: #ffc107; color: #333; border: none; }
    .btn-edit-sm:hover { background: #e0a800; color: #333; }
    .btn-delete-sm { background: #dc3545; color: white; border: none; }
    .btn-delete-sm:hover { background: #c82333; color: white; }
    
    .filter-bar { background: #f8f9fa; padding: 15px; border-radius: 12px; margin-bottom: 20px; }
    
    /* Pagination Styles */
    .pagination {
        margin-top: 20px;
        justify-content: center;
        flex-wrap: wrap;
    }
    .pagination .page-link {
        color: #1a1a2e;
        border-radius: 8px;
        margin: 0 3px;
        padding: 8px 14px;
        transition: all 0.2s;
    }
    .pagination .page-link:hover {
        background: #1a1a2e;
        color: white;
        transform: translateY(-2px);
    }
    .pagination .active .page-link {
        background: #1a1a2e;
        border-color: #1a1a2e;
        color: white;
    }
    .pagination .disabled .page-link {
        color: #adb5bd;
        cursor: not-allowed;
        transform: none;
    }
    .pagination .disabled .page-link:hover {
        background: transparent;
        color: #adb5bd;
    }
    
    .alert-info-custom { background: #e8f4fd; border: none; border-radius: 12px; }
    
    .tonase-badge {
        background: #e8f4fd;
        padding: 4px 10px;
        border-radius: 20px;
        font-weight: 600;
        color: #0d6efd;
    }
    
    .card-danger {
        border: 2px solid #dc3545;
    }
    .card-danger .card-header {
        background: linear-gradient(135deg, #dc3545 0%, #a71d2a 100%);
        color: white;
    }
</style>

<div class="card">
    <div class="card-header">
        <i class="bi bi-archive"></i> Import Data OWS
    </div>
    <div class="card-body">
        <?php if($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="alert alert-info-custom d-flex justify-content-between align-items-center">
            <span><i class="bi bi-database"></i> Total data: <strong><?php echo number_format($totalData); ?></strong> record</span>
            <form method="POST" onsubmit="return confirm('Yakin ingin menghapus SEMUA data OWS?')" class="d-inline">
                <button type="submit" name="delete_all" class="btn btn-danger btn-sm">
                    <i class="bi bi-trash3"></i> Hapus Semua
                </button>
            </form>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-header">
                        <i class="bi bi-filetype-csv"></i> Import CSV
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <input type="file" name="file_csv" class="form-control" accept=".csv" required>
                                <div class="form-text text-muted mt-1">
                                    Format CSV: tanggal_jam, nomor_plat, nomor_pintu, berat_masuk, keterangan
                                </div>
                            </div>
                            <button type="submit" name="import_csv" class="btn btn-primary w-100">
                                <i class="bi bi-upload"></i> Import CSV
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-header">
                        <i class="bi bi-plus-circle"></i> Tambah Manual
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-2">
                                <input type="datetime-local" name="tanggal_jam" class="form-control" required>
                            </div>
                            <div class="mb-2">
                                <input type="text" name="nomor_plat" class="form-control" placeholder="No. Plat (contoh: B 1234 ABC)" required>
                            </div>
                            <div class="mb-2">
                                <input type="text" name="nomor_pintu" class="form-control" placeholder="No. Pintu (contoh: 0997 S)" required>
                            </div>
                            <div class="mb-2">
                                <input type="number" step="0.01" name="tonase" class="form-control" placeholder="Berat Masuk (Ton)" required>
                            </div>
                            <div class="mb-3">
                                <input type="text" name="keterangan" class="form-control" placeholder="Keterangan (opsional)">
                            </div>
                            <button type="submit" name="add_single" class="btn btn-primary w-100">
                                <i class="bi bi-save"></i> Tambah Data
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- ========== FORM BARU: HAPUS DATA BERDASARKAN BULAN ========== -->
            <div class="col-12">
                <div class="card mb-3 card-danger">
                    <div class="card-header">
                        <i class="bi bi-calendar-x"></i> Hapus Data Berdasarkan Bulan
                    </div>
                    <div class="card-body">
                        <form method="POST" onsubmit="return confirm('Yakin ingin menghapus data yang lebih lama dari periode yang dipilih? Tindakan ini tidak bisa dibatalkan!')">
                            <div class="row align-items-end">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Hapus data yang lebih lama dari:</label>
                                    <select name="bulan" class="form-select" required>
                                        <option value="">-- Pilih Periode --</option>
                                        <option value="1">1 Bulan yang lalu</option>
                                        <option value="2">2 Bulan yang lalu</option>
                                        <option value="3">3 Bulan yang lalu</option>
                                        <option value="4">4 Bulan yang lalu</option>
                                        <option value="5">5 Bulan yang lalu</option>
                                        <option value="6">6 Bulan yang lalu</option>
                                        <option value="12">12 Bulan (1 Tahun) yang lalu</option>
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <div class="alert alert-warning mb-0 py-2">
                                        <small><i class="bi bi-exclamation-triangle"></i> <strong>Perhatian:</strong> Data yang dihapus tidak dapat dikembalikan. Pastikan Anda sudah membackup data.</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" name="delete_by_month" class="btn btn-danger w-100">
                                        <i class="bi bi-trash3"></i> Hapus Data
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <!-- ========== END FORM BARU ========== -->

            <div class="col-12">
                <div class="card mb-3">
                    <div class="card-header">
                        <i class="bi bi-pencil-square"></i> Copy-Paste Data
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <textarea name="manual_data" class="form-control" rows="4" 
                                    placeholder="Format: TANGGAL_JAM [TAB/KOMA] NO_PINTU [TAB/KOMA] NO_PLAT [TAB/KOMA] BERAT MASUK [TAB/KOMA] KETERANGAN&#10;&#10;Contoh:&#10;2024-04-24 10:30:00	0997 S	B 3278 TOQ	5.5	Normal&#10;2024-04-24 11:00:00	0225 U	B 6675 ROQ	7.2	Overload"></textarea>
                            </div>
                            <button type="submit" name="import_manual" class="btn btn-primary">
                                <i class="bi bi-upload"></i> Import Data
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-funnel"></i> Filter & Data OWS
                    </div>
                    <div class="card-body">
                        <!-- Filter Bar - Auto Filter -->
                        <div class="filter-bar">
                            <div class="row">
                                <div class="col-md-12">
                                    <label class="form-label"><i class="bi bi-search"></i> Cari No. Pintu / No. Plat / Keterangan (ketik otomatis terfilter)</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                                        <input type="text" id="searchInput" class="form-control" 
                                               placeholder="Ketik nomor pintu, plat, atau keterangan..." 
                                               value="<?php echo htmlspecialchars($search_filter); ?>"
                                               onkeyup="filterData()">
                                        <button class="btn btn-secondary" type="button" onclick="resetFilter()">
                                            <i class="bi bi-arrow-repeat"></i> Reset
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tabel Data OWS -->
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th style="width: 3%">No</th>
                                        <th style="width: 15%">Tanggal & Jam</th>
                                        <th style="width: 15%">No. Plat</th>
                                        <th style="width: 12%">No. Pintu</th>
                                        <th style="width: 8%">Berat Masuk</th>
                                        <th style="width: 20%">Keterangan</th>
                                        <th style="width: 12%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if($sampleData && $sampleData->num_rows > 0): ?>
                                        <?php 
                                        $no = $offset + 1;
                                        while($row = $sampleData->fetch_assoc()): 
                                            $tanggal_formatted = date('d/m/Y H:i', strtotime($row['tanggal_jam']));
                                        ?>
                                        <tr>
                                            <td class="text-center"><?php echo $no++; ?></td>
                                            <td><small><?php echo $tanggal_formatted; ?></small></td>
                                            <td><?php echo htmlspecialchars($row['nomor_plat']); ?></td>
                                            <td><strong><?php echo htmlspecialchars($row['nomor_pintu']); ?></strong></td>
                                            <td><span class="tonase-badge"><?php echo number_format($row['tonase'], 2); ?> ton</span></td>
                                            <td><?php echo htmlspecialchars($row['keterangan'] ?: '-'); ?></td>
                                            <td>
                                                <button type="button" class="btn btn-edit-sm btn-sm-custom" 
                                                        data-bs-toggle="modal" data-bs-target="#editModal"
                                                        data-id="<?php echo $row['id']; ?>"
                                                        data-tanggal="<?php echo $row['tanggal_jam']; ?>"
                                                        data-plat="<?php echo htmlspecialchars($row['nomor_plat']); ?>"
                                                        data-pintu="<?php echo htmlspecialchars($row['nomor_pintu']); ?>"
                                                        data-tonase="<?php echo $row['tonase']; ?>"
                                                        data-keterangan="<?php echo htmlspecialchars($row['keterangan']); ?>">
                                                    <i class="bi bi-pencil"></i> Edit
                                                </button>
                                                <button type="button" class="btn btn-delete-sm btn-sm-custom" 
                                                        data-bs-toggle="modal" data-bs-target="#deleteModal"
                                                        data-id="<?php echo $row['id']; ?>"
                                                        data-pintu="<?php echo htmlspecialchars($row['nomor_pintu']); ?>">
                                                    <i class="bi bi-trash"></i> Hapus
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted">Belum ada data OWS</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination dengan Tombol « dan » -->
                        <?php if($totalPages > 1): ?>
                        <nav>
                            <ul class="pagination">
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=1&search=<?php echo urlencode($search_filter); ?>">
                                        <i class="bi bi-chevron-double-left"></i>
                                    </a>
                                </li>
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search_filter); ?>">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>
                                
                                <?php
                                $start_page = max(1, $page - 2);
                                $end_page = min($totalPages, $page + 2);
                                
                                if ($start_page > 1) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                                
                                for($i = $start_page; $i <= $end_page; $i++): 
                                ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search_filter); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                                <?php endfor; ?>
                                
                                <?php
                                if ($end_page < $totalPages) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                                ?>
                                
                                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search_filter); ?>">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $totalPages; ?>&search=<?php echo urlencode($search_filter); ?>">
                                        <i class="bi bi-chevron-double-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); color: white;">
                <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Edit Data OWS</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="edit_id" id="edit_id">
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-calendar"></i> Tanggal & Jam</label>
                        <input type="datetime-local" name="tanggal_jam" id="edit_tanggal" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-truck"></i> No. Plat</label>
                        <input type="text" name="nomor_plat" id="edit_plat" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-door-open"></i> No. Pintu</label>
                        <input type="text" name="nomor_pintu" id="edit_pintu" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-weight"></i> Berat Masuk</label>
                        <input type="number" step="0.01" name="tonase" id="edit_tonase" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-chat-text"></i> Keterangan</label>
                        <input type="text" name="keterangan" id="edit_keterangan" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Hapus -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background: #dc3545; color: white;">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle"></i> Konfirmasi Hapus</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="delete_id" id="delete_id">
                    <p>Apakah Anda yakin ingin menghapus data berikut?</p>
                    <p><strong>No. Pintu:</strong> <span id="delete_no_pintu"></span></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Hapus</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Auto filter tanpa reload halaman (delay 0.5 detik)
    let typingTimer;
    const doneTypingInterval = 500;
    
    function filterData() {
        clearTimeout(typingTimer);
        typingTimer = setTimeout(function() {
            const search = document.getElementById('searchInput').value;
            const currentUrl = new URL(window.location.href);
            if (search) {
                currentUrl.searchParams.set('search', search);
            } else {
                currentUrl.searchParams.delete('search');
            }
            currentUrl.searchParams.set('page', 1);
            window.location.href = currentUrl.toString();
        }, doneTypingInterval);
    }
    
    function resetFilter() {
        window.location.href = 'import_ows.php';
    }
    
    document.getElementById('searchInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            clearTimeout(typingTimer);
            filterData();
        }
    });
    
    // Modal Edit - Isi data
    const editModal = document.getElementById('editModal');
    editModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const id = button.getAttribute('data-id');
        const tanggal = button.getAttribute('data-tanggal');
        const plat = button.getAttribute('data-plat');
        const pintu = button.getAttribute('data-pintu');
        const tonase = button.getAttribute('data-tonase');
        const keterangan = button.getAttribute('data-keterangan');
        
        document.getElementById('edit_id').value = id;
        // Format datetime-local (YYYY-MM-DDThh:mm)
        if (tanggal) {
            const formattedDate = tanggal.replace(' ', 'T').slice(0, 16);
            document.getElementById('edit_tanggal').value = formattedDate;
        }
        document.getElementById('edit_plat').value = plat;
        document.getElementById('edit_pintu').value = pintu;
        document.getElementById('edit_tonase').value = tonase;
        document.getElementById('edit_keterangan').value = keterangan || '';
    });
    
    // Modal Hapus - Isi data
    const deleteModal = document.getElementById('deleteModal');
    deleteModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const id = button.getAttribute('data-id');
        const pintu = button.getAttribute('data-pintu');
        
        document.getElementById('delete_id').value = id;
        document.getElementById('delete_no_pintu').textContent = pintu;
    });
</script>

<?php include 'includes/footer.php'; ?>