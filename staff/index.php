<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
try {
    require_once 'includes/header.php'; // Includes session checks, auth, and $con context
    if (!$con) throw new Exception('Database connection link missing.');

    $metricsPackage = getUnifiedDashboardMetrics($con);
    if ($metricsPackage['status'] === 'error') throw new Exception($metricsPackage['message']);

    $appts    = $metricsPackage['appointments'];
    $docs     = $metricsPackage['documents'];
    $guests   = $metricsPackage['visitors'];
    $staffQty = $metricsPackage['staff_count'];
    $logs     = $metricsPackage['activity_logs'];

    // Dynamic greeting based on time parameters
    $hour = date('H');
    $greeting = ($hour < 12) ? 'Good Morning' : (($hour < 17) ? 'Good Afternoon' : 'Good Evening');

} catch (\Throwable $e) {
    echo '<div class="alert alert-danger m-4">Dashboard Engine Fault: ' . htmlspecialchars($e->getMessage()) . '</div>';
    exit;
} ?>

<!-- Content wrapper -->
<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
        
        <!-- ============================================================
             ROW 1: WELCOME BANNER & STATS RECAP CARDS
             ============================================================ -->
        <div class="row g-4 mb-4">
            <!-- Greeting Hero Panel Card -->
            <div class="col-lg-8">
                <div class="card bg-label-primary h-100">
                    <div class="card-body d-flex align-items-center justify-content-between p-4">
                        <div class="content-left">
                            <h4 class="card-title text-primary fw-bold mb-1"><?php echo $greeting; ?>, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Officer'); ?>! 🎉</h4>
                            <p class="mb-3 text-heading small">Here is your executive workflow summary for today, <strong><?php echo date('d M, Y'); ?></strong>.</p>
                            <span class="badge bg-primary text-uppercase font-weight-bold tracking-wider">Access Node: <?php echo htmlspecialchars($_SESSION['role']); ?></span>
                        </div>
                        <div class="content-right d-none d-sm-block text-center me-3">
                            <i class="bx bx-laptop text-primary" style="font-size: 5.5rem; opacity: 0.85;"></i>
                        </div>
                    </div>
                </div>
            </div>
                        <!-- ============================================================
                 DASHBOARD METRIC WIDGET: INTERACTIVE REAL-TIME CLOUD SYNC
                 ============================================================ -->
            <div class="col-12 col-md-4 mb-4 d-none">
                <div class="card h-100 shadow-sm border-0 bg-label-primary">
                    <div class="card-body d-flex flex-column justify-content-between p-4">
                        <div class="d-flex align-items-start justify-content-between mb-2">
                            <div class="avatar flex-shrink-0 bg-white rounded p-2">
                                <i class="bx bx-cloud-upload text-primary fs-3"></i>
                            </div>
                            <?php
                            // Compute all outstanding unsynced database log entries dynamically
                            $syncTables = ['users', 'visitors_log', 'memos', 'appointments', 'activity_logs', 'documents', 'deletions_log'];
                            $totalPendingRows = 0;
                            foreach ($syncTables as $table) {
                                $totalPendingRows += (int)$con->query("SELECT COUNT(*) FROM `$table` WHERE `sync_status` = 'Pending'")->fetchColumn();
                            }
                            
                            // Check for outstanding unsynced local file assets as well
                            $pendingFilesCount = (int)$con->query("SELECT 
                                (SELECT COUNT(*) FROM `users` WHERE `file_sync_status` = 'Pending') + 
                                (SELECT COUNT(*) FROM `documents` WHERE `file_sync_status` = 'Pending') AS total")->fetchColumn();
                            
                            $grandTotalSyncQueue = $totalPendingRows + $pendingFilesCount;
                            ?>
                            <span class="badge <?php echo $grandTotalSyncQueue > 0 ? 'bg-warning' : 'bg-success'; ?> fw-bold text-uppercase small">
                                <?php echo $grandTotalSyncQueue > 0 ? 'Out of Sync' : 'Synced'; ?>
                            </span>
                        </div>
                        
                        <div class="mt-2">
                            <span class="d-block text-muted small text-uppercase fw-semibold mb-1">Cloud Synchronization Queue</span>
                            <h3 class="card-title mb-1 fw-bold text-dark <?php echo $grandTotalSyncQueue > 0 ? 'animate-pulse' : ''; ?>">
                                <?php echo $grandTotalSyncQueue; ?> <span class="fs-6 fw-normal text-muted">changes pending</span>
                            </h3>
                            <p class="mb-0 font-size-xs text-muted" style="line-height: 1.35;">
                                Mirrors local data entries, hard deletions, and cropped avatars safely over an encrypted SSL connection tunnel.
                            </p>
                        </div>

                        <!-- Action Button Execution Interface Link -->
                        <div class="d-grid mt-4">
                            <button type="button" id="dashboard_cloud_sync_btn" class="btn btn-primary btn-sm fw-bold shadow-sm py-2" <?php echo $grandTotalSyncQueue === 0 ? 'disabled' : ''; ?>>
                                <i class="bx bx-sync me-1 fs-5"></i> Execute Cloud Handshake
                            </button>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Active Security Node Counter Widget -->
            <div class="col-lg-4 col-md-12">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div class="content-left">
                                <span class="text-muted text-uppercase small fw-semibold">Active Campus Faculty</span>
                                <h2 class="mb-2 fw-bold mt-1 text-heading"><?php echo $staffQty; ?></h2>
                                <p class="mb-0 text-success small fw-medium"><i class="bx bx-check-circle me-1"></i>Verified Identity Nodes</p>
                            </div>
                            <div class="avatar bg-label-success p-2 rounded">
                                <i class="bx bx-group fs-3"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================================
             ROW 2: UNIFIED METRIC LOGISTIC COUNTERS GRID
             ============================================================ -->
        <div class="row g-4 mb-4">
            <!-- Today's Total Appointments -->
            <div class="col-6 col-md-3">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="avatar bg-label-primary p-2 rounded-circle"><i class="bx bx-calendar fs-4"></i></span>
                            <span class="badge bg-label-primary small fw-bold">Today</span>
                        </div>
                        <h4 class="mb-0 fw-bold text-heading"><?php echo (int)($appts['total_today'] ?? 0); ?></h4>
                        <small class="text-muted small text-uppercase">Total Bookings</small>
                    </div>
                </div>
            </div>

            <!-- Pending Review Appointments -->
            <div class="col-6 col-md-3">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="avatar bg-label-warning p-2 rounded-circle"><i class="bx bx-time-five fs-4"></i></span>
                            <span class="badge bg-label-warning small fw-bold">Queue</span>
                        </div>
                        <h4 class="mb-0 fw-bold text-warning"><?php echo (int)($appts['pending_today'] ?? 0); ?></h4>
                        <small class="text-muted small text-uppercase">Awaiting Calls</small>
                    </div>
                </div>
            </div>

            <!-- Documents Registry Totals -->
            <div class="col-6 col-md-3">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="avatar bg-label-info p-2 rounded-circle"><i class="bx bx-file-find fs-4"></i></span>
                            <span class="badge bg-label-info small fw-bold">Ledger</span>
                        </div>
                        <h4 class="mb-0 fw-bold text-info"><?php echo (int)($docs['total_docs'] ?? 0); ?></h4>
                        <small class="text-muted small text-uppercase">Tracked Files</small>
                    </div>
                </div>
            </div>

            <!-- Visitors Inside Office -->
            <div class="col-6 col-md-3">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="avatar bg-label-danger p-2 rounded-circle"><i class="bx bx-user-voice fs-4"></i></span>
                            <span class="badge bg-label-danger small fw-bold">Live</span>
                        </div>
                        <h4 class="mb-0 fw-bold text-danger"><?php echo (int)($guests['active_inside'] ?? 0); ?></h4>
                        <small class="text-muted small text-uppercase">Guests Inside</small>
                    </div>
                </div>
            </div>
        </div>

                <!-- ============================================================
             ROW 3: COMPREHENSIVE INTERACTIVE SPLIT BLOCKS (TIMELINES & DIRECTORIES)
             ============================================================ -->
        <div class="row g-4">
            <!-- Left Split: Real-Time Calendar Pipeline Stream Queue -->
            <div class="col-md-12 col-lg-7">
                <div class="card h-100 shadow-sm">
                    <div class="card-header border-bottom d-flex align-items-center justify-content-between py-3">
                        <h5 class="card-title mb-0 fw-bold text-primary">
                            <i class="bx bx-list-ol me-2 fs-4"></i>Principal's Active Operations Queue
                        </h5>
                        <a href="appointments.php" class="btn btn-xs btn-outline-primary fw-semibold px-2 py-1">View Full Logbook</a>
                    </div>
                    <div class="table-responsive text-nowrap card-body">
                        <table class="table table-hover table-striped mb-0 align-middle">
                            <thead class="table-light">
                                <tr class="text-uppercase font-size-sm">
                                    <th>S/N</th>
                                    <th>Visitor Profile Identity</th>
                                    <th>Classification Type</th>
                                    <th>Status Badges</th>
                                </tr>
                            </thead>
                            <tbody class="table-border-bottom-0">
                                <?php
                                $sr=1;
                                // Fetch localized today's appointments layout array data directly [27-Jun-2026]
                                $baseApptQuery = "SELECT a.*, t.type_name as appt_type_label FROM appointments a 
                                                  LEFT JOIN appointment_types t ON a.appointment_type_id = t.id
                                                  WHERE a.appointment_date = CURDATE() ORDER BY a.start_time ASC LIMIT 4";
                                if ($_SESSION['role'] === 'Staff') { 
                                    $baseApptQuery .= " AND a.scheduled_by = $userId"; 
                                }
                                $todayRowsList = $con->query($baseApptQuery)->fetchAll(PDO::FETCH_ASSOC);

                                if (empty($todayRowsList)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted small">
                                        <i class="bx bx-calendar-x d-block fs-2 mb-2 text-secondary"></i>
                                        No upcoming appointment profiles allocated for this active workspace shift timeline.
                                    </td>
                                </tr>
                                <?php else: 
                                $statusColorsMap = ['Pending' => 'warning', 'Approved' => 'success', 'Rejected' => 'danger', 'Completed' => 'info'];
                                foreach ($todayRowsList as $row): 
                                    $badgeStyle = $statusColorsMap[$row['status']] ?? 'secondary'; ?>
                                <tr>
                                <td><?php echo $sr++ ; ?></td>

                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="fw-bold text-heading mb-0 small"><?php echo htmlspecialchars($row['visitor_name']); ?></span>
                                            <small class="text-muted font-size-xs text-truncate" style="max-width: 180px;"><?php echo htmlspecialchars($row['purpose']); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-label-primary px-2 py-1 font-size-xs">
                                            <i class="bx bx-bookmark-alt-minus me-1"></i><?php echo htmlspecialchars($row['appt_type_label'] ?? 'General'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-label-<?php echo $badgeStyle; ?> font-weight-bold px-2 py-1 font-size-xs"><?php echo $row['status']; ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right Split: Security Tracking Timeline Audit Footprints -->
            <div class="col-md-12 col-lg-5">
                <div class="card h-100 shadow-sm">
                    <div class="card-header border-bottom py-3">
                        <h5 class="card-title mb-0 fw-bold text-dark">
                            <i class="bx bx-shield-quarter me-2 fs-4 text-secondary"></i>Security Tracking Footprints
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <ul class="timeline timeline-advance mb-0" style="padding-left: 0;">
                            <?php if (empty($logs)): ?>
                            <div class="text-center py-5 text-muted small">
                                <i class="bx bx-receipt d-block fs-3 mb-1"></i> No audit history footprints detected inside database registers volume.
                            </div>
                            <?php else: foreach ($logs as $logRow): ?>
                            <li class="timeline-item timeline-item-transparent ps-4 border-left-dashed mb-3 position-relative" style="list-style: none;">
                                <span class="timeline-point timeline-point-primary position-absolute start-0 top-0 mt-1" style="height: 10px; width: 10px; border-radius: 50%; background: #696cff; display: inline-block;"></span>
                                <div class="timeline-event p-0">
                                    <div class="timeline-header d-flex justify-content-between align-items-center mb-1">
                                        <h6 class="mb-0 fw-bold text-heading small"><?php echo htmlspecialchars($logRow['module'] . ' Module &middot; ' . $logRow['action_type']); ?></h6>
                                        <small class="text-muted font-size-xs"><?php echo date('h:i A', strtotime($logRow['created_at'])); ?></small>
                                    </div>
                                    <p class="mb-0 text-muted font-size-xs text-wrap" style="line-height: 1.4;"><?php echo htmlspecialchars($logRow['narrative']); ?></p>
                                </div>
                            </li>
                            <?php endforeach; endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

    </div> <!-- / Container Wrap -->
</div> <!-- / Content Wrapper Block Wrap -->

<!-- Dynamic Custom Inline Styles for Timeline Trackers Rendering -->
<style>
.border-left-dashed {
    border-left: 2px dashed #e4e6e8 !important;
    padding-bottom: 1rem;
}
.border-left-dashed:last-child {
    border-left: 2px solid transparent !important;
}
.font-size-xs {
    font-size: 0.75rem !important;
}
.tracking-wider {
    letter-spacing: 0.05em;
}
</style>

<?php 
// Include standard closing tags layouts and default core scripts tags references
include_once 'includes/footer.php'; 
?>
<script>
        // =========================================================================
    // DASHBOARD EXECUTOR: REAL-TIME BIDIRECTIONAL SYNC ENGINE HANDSHAKE
    // =========================================================================
    $(document).on('click', '#dashboard_cloud_sync_btn', function(e) {
        e.preventDefault();
        const syncButtonNode = $(this);

        Swal.fire({
            title: 'Initialize Bidirectional Sync?',
            text: "This opens an unblocked REST data chunk stream to upload your pending changes and overwrite your local database with the cloud master backup file.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#696cff',
            cancelButtonColor: '#8592a3',
            confirmButtonText: '<i class="bx bx-cloud-upload me-1"></i> Start Handshake',
            cancelButtonText: 'Abort',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                // Return standard POST payload calling the centralized controller script
                return $.ajax({
                    url: server_controller,
                    type: 'POST',
                    data: { execute_database_cloud_sync: 'true' },
                    dataType: 'json'
                }).then(res => {
                    if (res.status !== 'success') throw new Error(res.message);
                    return res;
                }).catch(err => { 
                    Swal.showValidationMessage(`Sync Interruption: ${err.message || 'Connection timeout.'}`); 
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                Swal.fire({
                    title: 'Database Synchronized! ☁️',
                    text: result.value.message,
                    icon: 'success',
                    confirmButtonColor: '#696cff'
                }).then(() => {
                    // Force a clean page reload to refresh all dashboard counter numbers instantly
                    window.location.reload();
                });
            }
        });
    });

</script>