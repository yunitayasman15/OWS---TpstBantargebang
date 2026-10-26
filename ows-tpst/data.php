<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
include 'config/database.php';

// Set page variables
$page_title = 'Data OWS - TPST Bantargebang';
$page_heading = 'Data OWS';
$page_subheading = 'Seluruh Data Timbangan';

// Filter variables
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build WHERE clause
$where_clauses = [];
$params = [];
$types = "";

if (!empty($search)) {
    $where_clauses[] = "(nomor_pintu LIKE ? OR nomor_plat LIKE ? OR keterangan LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

if (!empty($date_from)) {
    $where_clauses[] = "DATE(tanggal_jam) >= ?";
    $params[] = $date_from;
    $types .= "s";
}

if (!empty($date_to)) {
    $where_clauses[] = "DATE(tanggal_jam) <= ?";
    $params[] = $date_to;
    $types .= "s";
}

$where_sql = "";
if (!empty($where_clauses)) {
    $where_sql = "WHERE " . implode(" AND ", $where_clauses);
}

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Count total data with filters
$count_sql = "SELECT COUNT(*) as total FROM ows_data $where_sql";
$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$totalData = $count_stmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalData / $limit);

// Get data with filters and pagination
$sql = "SELECT * FROM ows_data $where_sql ORDER BY tanggal_jam DESC LIMIT $offset, $limit";
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$data = $stmt->get_result();

// Include header
include 'includes/header.php';
?>

<style>
    input[type="date"]::-webkit-calendar-picker-indicator {
        display: none;
        -webkit-appearance: none;
    }
    
    .input-group-icon {
        display: flex;
        align-items: center;
        border: 1px solid #ddd;
        border-radius: 10px;
        background: white;
        overflow: hidden;
    }
    
    .input-group-icon .input-icon {
        padding: 0 12px;
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        height: 42px;
        cursor: pointer;
    }
    
    .input-group-icon .input-icon i {
        font-size: 18px;
    }
    
    .input-group-icon input {
        border: none;
        padding: 10px 12px;
        flex: 1;
        outline: none;
    }
    
    .badge-grup {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        display: inline-block;
        white-space: nowrap;
    }
    
    .badge-grup-1 { background: #dc3545; color: white; }
    .badge-grup-2 { background: #fd7e14; color: white; }
    .badge-grup-3 { background: #ffc107; color: #333; }
    .badge-grup-4 { background: #198754; color: white; }
    
    .badge-pintu {
        background: #e8f4fd;
        color: #0d6efd;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 500;
        display: inline-block;
        white-space: nowrap;
    }
    
    /* Keterangan cell styling */
    .keterangan-cell {
        max-width: 150px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        font-size: 12px;
    }
    
    .keterangan-cell small {
        display: block;
    }
    
    .table-container {
        overflow-x: auto;
    }
    
    .data-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
        font-size: 12px;
    }
    
    .data-table th {
        background: #f8fafc;
        padding: 10px 8px;
        font-size: 12px;
        font-weight: 600;
        color: #1a1a2e;
        border-bottom: 2px solid #eef2f6;
        text-align: left;
        white-space: nowrap;
    }
    
    .data-table td {
        padding: 10px 8px;
        font-size: 12px;
        border-bottom: 1px solid #eef2f6;
        vertical-align: middle;
        word-break: break-word;
    }
    
    .data-table tr:hover td {
        background: #fafbfd;
    }
    
    /* Lebar kolom tetap (fixed) */
    .data-table th:nth-child(1) { width: 5%; }  /* No */
    .data-table th:nth-child(2) { width: 14%; } /* Tanggal & Jam */
    .data-table th:nth-child(3) { width: 12%; } /* Nomor Pintu */
    .data-table th:nth-child(4) { width: 8%; }  /* Grup */
    .data-table th:nth-child(5) { width: 15%; } /* Nomor Plat */
    .data-table th:nth-child(6) { width: 8%; }  /* Berat Masuk */
    .data-table th:nth-child(7) { width: 20%; } /* Keterangan */
    .data-table th:nth-child(8) { width: 18%; } /* Aksi */
    
    .btn-edit {
        background: #ffc107;
        color: #333;
        border: none;
        padding: 4px 8px;
        border-radius: 6px;
        font-size: 11px;
        text-decoration: none;
        display: inline-block;
        margin-right: 4px;
    }
    
    .btn-edit:hover {
        background: #e0a800;
        color: #333;
    }
    
    .btn-delete {
        background: #dc3545;
        color: white;
        border: none;
        padding: 4px 8px;
        border-radius: 6px;
        font-size: 11px;
        text-decoration: none;
        display: inline-block;
    }
    
    .btn-delete:hover {
        background: #c82333;
        color: white;
    }
    
    .text-muted {
        color: #6c757d;
    }
    
    /* Tanggal & Jam styling */
    .tanggal-cell {
        line-height: 1.3;
    }
    .tanggal-cell strong {
        font-size: 11px;
    }
    .tanggal-cell small {
        font-size: 10px;
    }
    
    /* Responsive */
    @media (max-width: 1200px) {
        .data-table th:nth-child(7) { width: 15%; }
        .data-table th:nth-child(8) { width: 20%; }
    }
    
    @media (max-width: 992px) {
        .table-container {
            overflow-x: auto;
        }
        .data-table {
            min-width: 900px;
        }
    }
</style>

<div class="card">
    <div class="card-header">
        <i class="bi bi-table"></i> Seluruh Data Timbangan
    </div>
    <div class="card-body">
        <form method="GET" class="mb-4">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label"><i class="bi bi-search"></i> Pencarian</label>
                    <input type="text" name="search" class="form-control" 
                           placeholder="Cari Nomor Pintu, Plat, atau Keterangan..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label"><i class="bi bi-calendar-start"></i> Tanggal Dari</label>
                    <div class="input-group-icon">
                        <div class="input-icon" onclick="document.getElementById('date_from').showPicker()">
                            <i class="bi bi-calendar3"></i>
                        </div>
                        <input type="date" name="date_from" id="date_from" 
                               value="<?php echo htmlspecialchars($date_from); ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><i class="bi bi-calendar-end"></i> Tanggal Sampai</label>
                    <div class="input-group-icon">
                        <div class="input-icon" onclick="document.getElementById('date_to').showPicker()">
                            <i class="bi bi-calendar3"></i>
                        </div>
                        <input type="date" name="date_to" id="date_to" 
                               value="<?php echo htmlspecialchars($date_to); ?>">
                    </div>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100" style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); border: none;">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                </div>
            </div>
            <?php if(!empty($search) || !empty($date_from) || !empty($date_to)): ?>
            <div class="mt-3">
                <a href="data.php" class="btn btn-sm btn-secondary">
                    <i class="bi bi-arrow-repeat"></i> Reset Filter
                </a>
            </div>
            <?php endif; ?>
        </form>
        
        <div class="alert alert-info py-2 mb-3" style="background: #e8f4fd; border: none;">
            <i class="bi bi-info-circle"></i> Menampilkan <?php echo $data->num_rows; ?> dari <?php echo $totalData; ?> data
            <?php if(!empty($search)): ?>
                | Pencarian: <strong><?php echo htmlspecialchars($search); ?></strong>
            <?php endif; ?>
            <?php if(!empty($date_from)): ?>
                | Dari: <strong><?php echo date('d/m/Y', strtotime($date_from)); ?></strong>
            <?php endif; ?>
            <?php if(!empty($date_to)): ?>
                | Sampai: <strong><?php echo date('d/m/Y', strtotime($date_to)); ?></strong>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if($data && $data->num_rows > 0): ?>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal & Jam</th>
                        <th>Nomor Pintu</th>
                        <th>Grup</th>
                        <th>Nomor Plat</th>
                        <th>Berat Masuk</th>
                        <th>Keterangan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = $offset + 1;
                    while($row = $data->fetch_assoc()): 
                        // Format grup label
                        $grup_label = 'Grup ' . $row['grup'];
                        $grupClass = '';
                        if($row['grup'] == 1) $grupClass = 'badge-grup-1';
                        elseif($row['grup'] == 2) $grupClass = 'badge-grup-2';
                        elseif($row['grup'] == 3) $grupClass = 'badge-grup-3';
                        elseif($row['grup'] == 4) $grupClass = 'badge-grup-4';
                        
                        // Format keterangan
                        $keterangan = !empty($row['keterangan']) ? htmlspecialchars($row['keterangan']) : '';
                    ?>
                    <tr>
                        <td style="text-align: center;"><strong><?php echo $no++; ?></strong></td>
                        <td class="tanggal-cell">
                            <strong><?php echo date('d/m/Y', strtotime($row['tanggal_jam'])); ?></strong>
                            <br><small class="text-muted"><?php echo date('H:i:s', strtotime($row['tanggal_jam'])); ?></small>
                        </td>
                        <td>
                            <span class="badge-pintu">
                                <i class="bi bi-door-closed"></i> <?php echo htmlspecialchars($row['nomor_pintu']); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge-grup <?php echo $grupClass; ?>">
                                <i class="bi bi-grid"></i> <?php echo $grup_label; ?>
                            </span>
                        </td>
                        <td>
                            <i class="bi bi-truck text-muted me-1"></i>
                            <?php echo htmlspecialchars($row['nomor_plat']); ?>
                        </td>
                        <td>
                            <strong><?php echo number_format($row['tonase'], 0, ',', '.'); ?></strong>
<small class="text-muted">ton</small>
                        </td>
                        <td class="keterangan-cell">
                            <?php if(!empty($keterangan)): ?>
                                <small><i class="bi bi-chat-text"></i> <?php echo $keterangan; ?></small>
                            <?php else: ?>
                                <small class="text-muted"><i class="bi bi-dash-circle"></i> -</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="edit.php?id=<?php echo $row['id']; ?>" class="btn-edit">
                                <i class="bi bi-pencil"></i> Edit
                            </a>
                            <?php if($_SESSION['role'] == 'admin'): ?>
                            <a href="hapus.php?id=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('Yakin hapus data ini?')">
                                <i class="bi bi-trash"></i> Hapus
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <?php if($totalPages > 1): ?>
        <nav class="p-3">
            <ul class="pagination justify-content-center mb-0">
                <?php if($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>">
                        <i class="bi bi-chevron-left"></i> Sebelumnya
                    </a>
                </li>
                <?php endif; ?>
                
                <?php 
                $start_page = max(1, $page - 2);
                $end_page = min($totalPages, $page + 2);
                for($i = $start_page; $i <= $end_page; $i++): 
                ?>
                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>">
                        <?php echo $i; ?>
                    </a>
                </li>
                <?php endfor; ?>
                
                <?php if($page < $totalPages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>">
                        Selanjutnya <i class="bi bi-chevron-right"></i>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
        
        <?php else: ?>
        <div class="text-center p-5">
            <i class="bi bi-inbox" style="font-size: 48px; opacity: 0.5;"></i>
            <p class="mt-3 text-muted">Belum ada data</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
    const dateFrom = document.getElementById('date_from');
    const dateTo = document.getElementById('date_to');
    
    if(dateFrom && dateTo) {
        dateFrom.addEventListener('change', function() {
            if (dateTo.value && dateTo.value < this.value) {
                dateTo.value = this.value;
            }
            if (this.value) {
                dateTo.min = this.value;
            }
        });
        
        dateTo.addEventListener('change', function() {
            if (dateFrom.value && this.value < dateFrom.value) {
                this.value = dateFrom.value;
            }
            if (this.value) {
                dateFrom.max = this.value;
            }
        });
    }
</script>

<?php include 'includes/footer.php'; ?>