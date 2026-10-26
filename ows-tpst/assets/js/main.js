/**
 * OWS TPST Bantargebang
 * Main JavaScript File
 */

// ========== DOM Ready ==========
document.addEventListener('DOMContentLoaded', function() {
    
    // ========== SIDEBAR TOGGLE ==========
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.querySelector('.sidebar');
    
    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Untuk desktop: toggle class collapsed
            if (window.innerWidth >= 769) {
                sidebar.classList.toggle('collapsed');
                
                // Simpan state ke localStorage
                const isCollapsed = sidebar.classList.contains('collapsed');
                localStorage.setItem('sidebarCollapsed', isCollapsed);
            } 
            // Untuk mobile: toggle show class
            else {
                sidebar.classList.toggle('show');
                
                // Buat overlay jika belum ada
                let overlay = document.querySelector('.sidebar-overlay');
                if (!overlay && sidebar.classList.contains('show')) {
                    overlay = document.createElement('div');
                    overlay.className = 'sidebar-overlay';
                    overlay.style.cssText = `
                        position: fixed;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                        background: rgba(0,0,0,0.5);
                        z-index: 1040;
                    `;
                    overlay.addEventListener('click', function() {
                        sidebar.classList.remove('show');
                        overlay.remove();
                    });
                    document.body.appendChild(overlay);
                } else if (overlay && !sidebar.classList.contains('show')) {
                    overlay.remove();
                }
            }
        });
        
        // Load saved sidebar state
        const savedState = localStorage.getItem('sidebarCollapsed');
        if (savedState === 'true' && window.innerWidth >= 769) {
            sidebar.classList.add('collapsed');
        }
    }
    
    // ========== AUTO HIDE ALERT ==========
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.remove();
            }, 500);
        }, 3000);
    });
    
    // ========== CONFIRM DELETE ==========
    const deleteButtons = document.querySelectorAll('.btn-delete, .delete-confirm');
    deleteButtons.forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            const message = btn.getAttribute('data-confirm') || 'Apakah Anda yakin ingin menghapus data ini?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
    
    // ========== FORM VALIDATION ==========
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
    
    // ========== SEARCH/FILTER TABLE ==========
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const searchText = this.value.toLowerCase();
            const table = document.querySelector('.table-responsive table');
            if (table) {
                const rows = table.querySelectorAll('tbody tr');
                rows.forEach(function(row) {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(searchText) ? '' : 'none';
                });
            }
        });
    }
    
    // ========== DATE/TIME FORMATTER ==========
    window.formatDateTime = function(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleString('id-ID', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    };
    
    // ========== NUMBER FORMATTER ==========
    window.formatNumber = function(number, decimals = 2) {
        if (isNaN(number)) return '0';
        return parseFloat(number).toLocaleString('id-ID', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        });
    };
    
    // ========== TOAST NOTIFICATION ==========
    window.showToast = function(message, type = 'success') {
        let toastContainer = document.querySelector('.toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            toastContainer.style.zIndex = '1100';
            document.body.appendChild(toastContainer);
        }
        
        const toastId = 'toast-' + Date.now();
        const bgClass = type === 'success' ? 'bg-success' : (type === 'error' ? 'bg-danger' : 'bg-warning');
        
        const toastHTML = `
            <div id="${toastId}" class="toast align-items-center text-white ${bgClass} border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;
        
        toastContainer.insertAdjacentHTML('beforeend', toastHTML);
        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement, { delay: 3000 });
        toast.show();
        
        toastElement.addEventListener('hidden.bs.toast', function() {
            toastElement.remove();
        });
    };
    
    // ========== LOADING SPINNER ==========
    window.showLoading = function(show) {
        let spinner = document.querySelector('.spinner-overlay');
        if (show) {
            if (!spinner) {
                spinner = document.createElement('div');
                spinner.className = 'spinner-overlay';
                spinner.innerHTML = '<div class="spinner-border text-light" role="status"><span class="visually-hidden">Loading...</span></div>';
                document.body.appendChild(spinner);
            }
            spinner.style.display = 'flex';
        } else if (spinner) {
            spinner.style.display = 'none';
        }
    };
    
    // ========== AUTO REFRESH DATA (OPTIONAL) ==========
    const autoRefresh = document.getElementById('autoRefresh');
    let refreshInterval = null;
    
    if (autoRefresh && autoRefresh.checked) {
        refreshInterval = setInterval(function() {
            if (document.hasFocus()) {
                location.reload();
            }
        }, 30000); // Refresh setiap 30 detik
    }
    
    if (autoRefresh) {
        autoRefresh.addEventListener('change', function() {
            if (this.checked) {
                refreshInterval = setInterval(function() {
                    if (document.hasFocus()) {
                        location.reload();
                    }
                }, 30000);
            } else if (refreshInterval) {
                clearInterval(refreshInterval);
                refreshInterval = null;
            }
        });
    }
    
    // ========== EXPORT TABLE TO CSV ==========
    window.exportToCSV = function(tableId, filename = 'export.csv') {
        const table = document.getElementById(tableId);
        if (!table) return;
        
        let csv = [];
        const rows = table.querySelectorAll('tr');
        
        rows.forEach(function(row) {
            const rowData = [];
            const cols = row.querySelectorAll('td, th');
            cols.forEach(function(col) {
                let text = col.textContent.trim();
                text = text.replace(/,/g, ';'); // Ganti koma dengan titik koma
                rowData.push(text);
            });
            csv.push(rowData.join(','));
        });
        
        const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = filename;
        link.click();
        URL.revokeObjectURL(link.href);
        
        showToast('Data berhasil diexport!', 'success');
    };
    
    // ========== PRINT TABLE ==========
    window.printTable = function(tableId, title = 'Data OWS') {
        const table = document.getElementById(tableId);
        if (!table) return;
        
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html>
            <head>
                <title>${title}</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                <style>
                    body { padding: 20px; }
                    table { width: 100%; border-collapse: collapse; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background: #2c3e50; color: white; }
                    @media print {
                        button { display: none; }
                    }
                </style>
            </head>
            <body>
                <h3>${title}</h3>
                <p>Tanggal Cetak: ${new Date().toLocaleString('id-ID')}</p>
                <table class="table table-bordered">
                    ${table.outerHTML}
                </table>
                <button onclick="window.print()">Cetak</button>
            </body>
            </html>
        `);
        printWindow.document.close();
    };
    
    // ========== RESIZE HANDLER ==========
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            if (window.innerWidth >= 769) {
                const sidebarMobile = document.querySelector('.sidebar');
                if (sidebarMobile && sidebarMobile.classList.contains('show')) {
                    sidebarMobile.classList.remove('show');
                    const overlay = document.querySelector('.sidebar-overlay');
                    if (overlay) overlay.remove();
                }
            }
        }, 250);
    });
    
    // ========== TOOLTIP INITIALIZATION ==========
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.forEach(function(tooltipTriggerEl) {
        new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // ========== POPOVER INITIALIZATION ==========
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.forEach(function(popoverTriggerEl) {
        new bootstrap.Popover(popoverTriggerEl);
    });
    
    // ========== KEYBOARD SHORTCUTS ==========
    document.addEventListener('keydown', function(e) {
        // Ctrl + S untuk save form
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            const form = document.querySelector('form');
            if (form) {
                form.dispatchEvent(new Event('submit'));
            }
        }
        
        // Alt + D untuk ke dashboard
        if (e.altKey && e.key === 'd') {
            window.location.href = 'index.php';
        }
        
        // Alt + I untuk input
        if (e.altKey && e.key === 'i') {
            window.location.href = 'input.php';
        }
        
        // Alt + L untuk lihat data
        if (e.altKey && e.key === 'l') {
            window.location.href = 'data.php';
        }
    });
    
    // ========== CHART UPDATE HELPER ==========
    window.updateChart = function(chartInstance, newLabels, newData) {
        if (chartInstance) {
            chartInstance.data.labels = newLabels;
            chartInstance.data.datasets[0].data = newData;
            chartInstance.update();
        }
    };
    
    console.log('OWS TPST Bantargebang - JavaScript Loaded');
});

// ========== SERVICE WORKER FOR OFFLINE (Optional) ==========
if ('serviceWorker' in navigator && location.protocol === 'https:') {
    navigator.serviceWorker.register('/sw.js').catch(function(err) {
        console.log('ServiceWorker registration failed: ', err);
    });
}