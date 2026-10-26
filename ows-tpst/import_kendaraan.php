<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}
include 'config/database.php';

$page_title = 'Import Kendaraan - OWS TPST Bantargebang';
$page_heading = 'Import Master Kendaraan';
$page_subheading = 'Kelola data master relasi No. Pintu dan No. Plat';

$success = '';
$error = '';
$importCount = 0;
$duplicateCount = 0;

// ========== PROSES HAPUS ==========
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_id'])) {
    $delete_id = (int)$_POST['delete_id'];
    $stmt = $conn->prepare("DELETE FROM master_kendaraan WHERE id = ?");
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
    $no_plat = strtoupper(trim($_POST['no_plat']));
    $no_pintu = strtoupper(trim($_POST['no_pintu']));
    
    if (empty($no_plat) || empty($no_pintu)) {
        $error = "No. Plat dan No. Pintu harus diisi!";
    } else {
        // Cek apakah no_pintu sudah ada (kecuali dirinya sendiri)
        $check = $conn->prepare("SELECT id FROM master_kendaraan WHERE no_pintu = ? AND id != ?");
        $check->bind_param("si", $no_pintu, $edit_id);
        $check->execute();
        $check->store_result();
        
        if ($check->num_rows > 0) {
            $error = "No. Pintu sudah ada!";
        } else {
            $stmt = $conn->prepare("UPDATE master_kendaraan SET no_plat = ?, no_pintu = ? WHERE id = ?");
            $stmt->bind_param("ssi", $no_plat, $no_pintu, $edit_id);
            if ($stmt->execute()) {
                $success = "Data berhasil diupdate!";
            } else {
                $error = "Gagal mengupdate data!";
            }
        }
    }
}

// Hapus semua data
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_all'])) {
    $conn->query("TRUNCATE TABLE master_kendaraan");
    $success = "Semua data master telah dihapus!";
}

// Tambah satu data
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_single'])) {
    $no_plat = strtoupper(trim($_POST['no_plat']));
    $no_pintu = strtoupper(trim($_POST['no_pintu']));
    
    if (empty($no_plat) || empty($no_pintu)) {
        $error = "No. Plat dan No. Pintu harus diisi!";
    } else {
        $check = $conn->prepare("SELECT id FROM master_kendaraan WHERE no_pintu = ?");
        $check->bind_param("s", $no_pintu);
        $check->execute();
        $check->store_result();
        
        if ($check->num_rows == 0) {
            $stmt = $conn->prepare("INSERT INTO master_kendaraan (no_plat, no_pintu) VALUES (?, ?)");
            $stmt->bind_param("ss", $no_plat, $no_pintu);
            if ($stmt->execute()) {
                $success = "Berhasil menambahkan data!";
            } else {
                $error = "Gagal menambahkan data!";
            }
        } else {
            $error = "No. Pintu sudah ada!";
        }
    }
}

// Import manual copy-paste
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['import_manual'])) {
    $manual_data = trim($_POST['manual_data']);
    $lines = explode("\n", $manual_data);
    
    foreach($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        
        $parts = preg_split('/[\t,]+/', $line, 2);
        if (count($parts) >= 2) {
            $no_plat = strtoupper(trim($parts[0]));
            $no_pintu = strtoupper(trim($parts[1]));
            
            if (!empty($no_plat) && !empty($no_pintu)) {
                $check = $conn->prepare("SELECT id FROM master_kendaraan WHERE no_pintu = ?");
                $check->bind_param("s", $no_pintu);
                $check->execute();
                $check->store_result();
                
                if ($check->num_rows == 0) {
                    $stmt = $conn->prepare("INSERT INTO master_kendaraan (no_plat, no_pintu) VALUES (?, ?)");
                    $stmt->bind_param("ss", $no_plat, $no_pintu);
                    if ($stmt->execute()) $importCount++;
                } else {
                    $duplicateCount++;
                }
            }
        }
    }
    
    if ($importCount > 0) {
        $success = "Berhasil mengimport $importCount data! (Duplicate: $duplicateCount)";
    } else {
        $error = "Tidak ada data yang diimport.";
    }
}

// Import CSV
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['import_csv']) && isset($_FILES['file_csv'])) {
    $file = $_FILES['file_csv']['tmp_name'];
    
    if (($handle = fopen($file, "r")) !== false) {
        $rowCount = 0;
        
        while (($data = fgetcsv($handle, 10000, ",")) !== false) {
            $rowCount++;
            if ($rowCount == 1) continue;
            
            $no_plat = isset($data[3]) ? strtoupper(trim(str_replace('"', '', $data[3]))) : '';
            $no_pintu = isset($data[4]) ? strtoupper(trim(str_replace('"', '', $data[4]))) : '';
            
            if (empty($no_plat) && isset($data[0]) && strpos($data[0], 'B ') !== false) {
                $no_plat = strtoupper(trim(str_replace('"', '', $data[0])));
                $no_pintu = isset($data[1]) ? strtoupper(trim(str_replace('"', '', $data[1]))) : '';
            }
            
            if (!empty($no_plat) && !empty($no_pintu) && $no_plat != 'NO POLISI') {
                $check = $conn->prepare("SELECT id FROM master_kendaraan WHERE no_pintu = ?");
                $check->bind_param("s", $no_pintu);
                $check->execute();
                $check->store_result();
                
                if ($check->num_rows == 0) {
                    $stmt = $conn->prepare("INSERT INTO master_kendaraan (no_plat, no_pintu) VALUES (?, ?)");
                    $stmt->bind_param("ss", $no_plat, $no_pintu);
                    if ($stmt->execute()) $importCount++;
                } else {
                    $duplicateCount++;
                }
            }
        }
        fclose($handle);
        $success = "Berhasil mengimport $importCount data! (Duplicate: $duplicateCount)";
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
    $where = "WHERE no_pintu LIKE '%$search_filter%' OR no_plat LIKE '%$search_filter%'";
}

$totalData = $conn->query("SELECT COUNT(*) as total FROM master_kendaraan $where")->fetch_assoc()['total'];
$totalPages = ceil($totalData / $limit);
$sampleData = $conn->query("SELECT * FROM master_kendaraan $where ORDER BY no_pintu ASC LIMIT $offset, $limit");

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
</style>

<div class="card">
    <div class="card-header">
        <i class="bi bi-truck"></i> Import Master Kendaraan
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
            <form method="POST" onsubmit="return confirm('Yakin ingin menghapus SEMUA data master?')">
               
            </form>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-header">
                        <i class="bi bi-file-earmark-text"></i> Import CSV
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <input type="file" name="file_csv" class="form-control" accept=".csv" required>
                            </div>
                            <button type="submit" name="import_csv" class="btn btn-primary-custom w-100">
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
                                <input type="text" name="no_plat" class="form-control" placeholder="No. Plat (contoh: B 1234 ABC)" required>
                            </div>
                            <div class="mb-3">
                                <input type="text" name="no_pintu" class="form-control" placeholder="No. Pintu (contoh: SHI 57)" required>
                            </div>
                            <button type="submit" name="add_single" class="btn btn-primary-custom w-100">
                                <i class="bi bi-save"></i> Tambah Data
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card mb-3">
                    <div class="card-header">
                        <i class="bi bi-pencil-square"></i> Copy-Paste Data
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <textarea name="manual_data" class="form-control" rows="4" 
                                    placeholder="Format: NO_PLAT [TAB/SPASI] NO_PINTU&#10;&#10;Contoh:&#10;B 9734 BYW	SHI-LOTTEMART&#10;B 9048 BYU	SHI 57"></textarea>
                            </div>
                            <button type="submit" name="import_manual" class="btn btn-primary-custom">
                                <i class="bi bi-upload"></i> Import Data
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-funnel"></i> Filter & Data Master
                    </div>
                    <div class="card-body">
                        <!-- Filter Bar - Auto Filter -->
                        <div class="filter-bar">
                            <div class="row">
                                <div class="col-md-12">
                                    <label class="form-label"><i class="bi bi-search"></i> Cari No. Pintu / No. Plat (ketik otomatis terfilter)</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                                        <input type="text" id="searchInput" class="form-control" 
                                               placeholder="Ketik nomor pintu atau plat..." 
                                               value="<?php echo htmlspecialchars($search_filter); ?>"
                                               onkeyup="filterData()">
                                        <button class="btn btn-secondary" type="button" onclick="resetFilter()">
                                            <i class="bi bi-arrow-repeat"></i> Reset
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tabel Data Master -->
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th style="width: 5%">No</th>
                                        <th style="width: 35%">No. Pintu</th>
                                        <th style="width: 35%">No. Plat</th>
                                        <th style="width: 25%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if($sampleData && $sampleData->num_rows > 0): ?>
                                        <?php 
                                        $no = $offset + 1;
                                        while($row = $sampleData->fetch_assoc()): 
                                        ?>
                                        <tr>
                                            <td class="text-center"><?php echo $no++; ?></td>
                                            <td><strong><?php echo htmlspecialchars($row['no_pintu']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($row['no_plat']); ?></td>
                                            <td>
                                                <button type="button" class="btn btn-edit-sm btn-sm-custom" 
                                                        data-bs-toggle="modal" data-bs-target="#editModal"
                                                        data-id="<?php echo $row['id']; ?>"
                                                        data-pintu="<?php echo htmlspecialchars($row['no_pintu']); ?>"
                                                        data-plat="<?php echo htmlspecialchars($row['no_plat']); ?>">
                                                    <i class="bi bi-pencil"></i> Edit
                                                </button>
                                                <button type="button" class="btn btn-delete-sm btn-sm-custom" 
                                                        data-bs-toggle="modal" data-bs-target="#deleteModal"
                                                        data-id="<?php echo $row['id']; ?>"
                                                        data-pintu="<?php echo htmlspecialchars($row['no_pintu']); ?>">
                                                    <i class="bi bi-trash"></i> Hapus
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">Belum ada data</div>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                             </div
                        </div>
                        
                        <!-- Pagination dengan Tombol « dan » -->
                        <?php if($totalPages > 1): ?>
                        <nav>
                            <ul class="pagination">
                                <!-- Tombol First (<<) -->
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=1&search=<?php echo urlencode($search_filter); ?>">
                                        <i class="bi bi-chevron-double-left"></i>
                                    </a>
                                </li>
                                
                                <!-- Tombol Previous (<) -->
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search_filter); ?>">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>
                                
                                <?php
                                // Menentukan halaman yang ditampilkan (maksimal 5 halaman)
                                $start_page = max(1, $page - 2);
                                $end_page = min($totalPages, $page + 2);
                                
                                // Tambahkan ellipsis di awal jika perlu
                                if ($start_page > 1) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                                
                                // Looping halaman
                                for($i = $start_page; $i <= $end_page; $i++): 
                                ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search_filter); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                                <?php endfor; ?>
                                
                                <?php
                                // Tambahkan ellipsis di akhir jika perlu
                                if ($end_page < $totalPages) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                                ?>
                                
                                <!-- Tombol Next (>) -->
                                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search_filter); ?>">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                                
                                <!-- Tombol Last (>>) -->
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
                <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Edit Data Kendaraan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="edit_id" id="edit_id">
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-door-open"></i> No. Pintu</label>
                        <input type="text" name="no_pintu" id="edit_no_pintu" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-truck"></i> No. Plat</label>
                        <input type="text" name="no_plat" id="edit_no_plat" class="form-control" required>
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
        window.location.href = 'import_kendaraan.php';
    }
    
    // Submit dengan Enter
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
        const pintu = button.getAttribute('data-pintu');
        const plat = button.getAttribute('data-plat');
        
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_no_pintu').value = pintu;
        document.getElementById('edit_no_plat').value = plat;
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