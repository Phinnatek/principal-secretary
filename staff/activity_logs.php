<?php
try {
    // Auth check loop is executed natively within your header wrap file template
    require_once 'includes/header.php'; 
    if (!$con) throw new Exception('System framework link connection lost.');

    $dataset = getActivityLogsDashboardDataset($con);
    if ($dataset['status'] === 'error') {
        echo '<div class="alert alert-danger m-4">Access Denied: ' . htmlspecialchars($dataset['message']) . '</div>';
        exit;
    }

    $logsList        = $dataset['logs_list'];
    $distinctModules = $dataset['distinct_modules'];
    $actionTypes     = $dataset['action_types'];
    
    $actionColorsMap = [
        'INSERT_VISITOR'        => 'success', 'UPDATE_VISITOR'        => 'primary', 'CHECKOUT_VISITOR'      => 'info',
        'INSERT_CORRESPONDENCE' => 'success', 'UPDATE_CORRESPONDENCE' => 'primary', 'DELETE_CORRESPONDENCE' => 'danger',
        'READ_RECEIPT'          => 'secondary', 'UPDATE_INFO'          => 'warning', 'OVERRIDE_PASSWORD'     => 'dark'
    ];

} catch (Throwable $e) {
    echo '<div class="alert alert-danger m-4">Audit Engine Initializer Fault: ' . htmlspecialchars($e->getMessage()) . '</div>'; 
    exit;
} ?>

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Security /</span> System Audit Trails Ledger</h4>

        <!-- ============================================================
             DYNAMIC ADVANCED AUDIT TRAIL FILTRATION TOOLBAR PANEL
             ============================================================ -->
        <div class="card mb-4 shadow-sm border-0">
            <div class="card-body">
                <form id="serverLogFilterForm" autocomplete="off">
                    <div class="row g-3">
                        <!-- Global Live Search Key Input Block -->
                        <div class="col-12 col-md-3">
                            <label class="form-label small fw-bold text-uppercase text-heading">Keyword Interrogation</label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="bx bx-search text-muted"></i></span>
                                <input type="text" name="search_keyword" id="search_keyword" class="form-control" placeholder="Search narrative, user name, ID..." />
                            </div>
                        </div>

                        <!-- Filter Category Modules Drop-Down Box -->
                        <div class="col-12 col-sm-6 col-md-2">
                            <label class="form-label small fw-bold text-uppercase text-heading">System Component</label>
                            <select name="filter_module" id="filter_module" class="form-select fw-medium">
                                <option value="">-- All Sections --</option>
                                <?php foreach ($distinctModules as $mod): ?>
                                    <option value="<?php echo $mod; ?>"><?php echo $mod; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Date Chronology From Boundary Selector -->
                        <div class="col-12 col-sm-6 col-md-2">
                            <label class="form-label small fw-bold text-uppercase text-heading">Timeline From</label>
                            <input type="date" name="filter_from_date" id="filter_from_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" />
                        </div>

                        <!-- Date Chronology To Boundary Selector -->
                        <div class="col-12 col-sm-6 col-md-2">
                            <label class="form-label small fw-bold text-uppercase text-heading">Timeline To</label>
                            <input type="date" name="filter_to_date" id="filter_to_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" />
                        </div>
                        
                        <!-- Reset Action State Button Node -->
                        <div class="col-12 col-md-3 d-grid align-self-end">
                            <button type="button" id="reset_log_filters_btn" class="btn btn-outline-secondary d-flex align-items-center justify-content-center gap-1">
                                <i class="bx bx-refresh fs-4"></i> Reset Audit Filters
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- ============================================================
             MAIN ACTIVITY AUDIT TRAIL LOG RECORD DATA CARD CARD
             ============================================================ -->
        <div class="card">
            <div class="card-header border-bottom py-3 d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0 fw-bold text-primary"><i class="bx bx-shield-quarter me-2 fs-4"></i>Chronological Security Audit Timeline</h5>
                <span class="badge bg-label-secondary small fw-bold" id="logs_count_badge">Today's Total Entries: <?php echo count($logsList); ?></span>
            </div>
            
            <div class="table-responsive text-nowrap card-body">
                <table class="table table-striped table-hover align-middle mb-0" id="DataTable">
                    <thead class="table-light text-uppercase font-size-sm">
                        <tr>
                            <th style="width: 50px;">S/N</th>
                            <th style="width: 160px;">Timestamp Clock</th>
                            <th>Executing Officer Node</th>
                            <th>Module Component</th>
                            <th>Action Type Trigger</th>
                            <th>Audit Trail Security Narrative Description</th>
                        </tr>
                    </thead>
                    <tbody id="logs_table_body" class="table-border-bottom-0">
                        <?php if (empty($logsList)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bx bx-receipt d-block fs-1 mb-2"></i> No system footprint operations tracked matching active query timeline parameters.
                            </td>
                        </tr>
                        <?php else: $sn = 1; ?>
                            <?php foreach ($logsList as $row): 
                                $badgeColor = $actionColorsMap[$row['action_type']] ?? 'secondary'; ?>
                            <tr>
                                <td><?php echo $sn++; ?></td>
                                <td><span class="fw-medium text-heading"><i class="bx bx-time me-1"></i><?php echo date('d M, Y - h:i A', strtotime($row['created_at'])); ?></span></td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="fw-bold mb-0 text-dark small"><?php echo htmlspecialchars($row['operator_name'] ?? 'System Framework'); ?></span>
                                        <small class="text-muted font-size-xs">ID: #<?php echo htmlspecialchars($row['employee_id'] ?? '0000'); ?></small>
                                    </div>
                                </td>
                                <td><span class="badge bg-label-primary font-weight-bold px-2 py-1"><?php echo htmlspecialchars($row['module']); ?></span></td>
                                <td><span class="badge bg-label-<?php echo $badgeColor; ?> font-weight-bold font-size-xs"><?php echo $row['action_type']; ?></span></td>
                                <td><div class="text-wrap text-heading small" style="max-width: 450px; line-height: 1.45;"><?php echo htmlspecialchars($row['narrative']); ?></div></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>

<!-- ============================================================
     REAL-TIME INTERACTIVE AUDIT TRAIL INTERACTIONS PIPELINE
     ============================================================ -->
<script>
function renderActivityLogsTableUI(logsList) {
    let htmlOutput = '';
    
    if (!logsList || logsList.length === 0) {
        htmlOutput = `
        <tr>
            <td colspan="6" class="text-center py-5 text-muted">
                <i class="bx bx-receipt d-block fs-1 mb-2"></i> No system footprint operations tracked matching active query parameters.
            </td>
        </tr>`;
        $('#logs_count_badge').text('Total Entries Found: 0');
    } else {
        let sn = 1;
        const actionColorsMap = {
            'INSERT_VISITOR': 'success', 'UPDATE_VISITOR': 'primary', 'CHECKOUT_VISITOR': 'info',
            'INSERT_CORRESPONDENCE': 'success', 'UPDATE_CORRESPONDENCE': 'primary', 'DELETE_CORRESPONDENCE': 'danger',
            'READ_RECEIPT': 'secondary', 'UPDATE_INFO': 'warning', 'OVERRIDE_PASSWORD': 'dark'
        };

        $.each(logsList, function(index, row) {
            const badgeColor = actionColorsMap[row.action_type] || 'secondary';
            const rawDate = new Date(row.created_at);
            const humanizedDate = rawDate.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) + ' - ' + rawDate.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
            
            const operatorName = row.operator_name ? row.operator_name : 'System Framework';
            const empId = row.employee_id ? row.employee_id : '0000';

            htmlOutput += `
            <tr>
                <td>${sn++}</td>
                <td><span class="fw-medium text-heading"><i class="bx bx-time me-1"></i>${humanizedDate}</span></td>
                <td>
                    <div class="d-flex flex-column">
                        <span class="fw-bold mb-0 text-dark small">${operatorName}</span>
                        <small class="text-muted font-size-xs">ID: #${empId}</small>
                    </div>
                </td>
                <td><span class="badge bg-label-primary font-weight-bold px-2 py-1">${row.module}</span></td>
                <td><span class="badge bg-label-${badgeColor} font-weight-bold font-size-xs">${row.action_type}</span></td>
                <td><div class="text-wrap text-heading small" style="max-width: 450px; line-height: 1.45;">${row.narrative}</div></td>
            </tr>`;
        });
        $('#logs_count_badge').text(`Total Entries Found: ${logsList.length}`);
    }
    $('#logs_table_body').html(htmlOutput);
}

$(document).ready(function() {
    
    // =========================================================================
    // ADVANCED RE-QUERY FILTRATION LINK CONTROLLER (`fetch_filtered_logs=true`)
    // =========================================================================
    let logQueryTimer = null;

    function executeAdvancedLogFiltration() {
        const payloadData = $('#serverLogFilterForm').serialize() + '&fetch_filtered_logs=true';
        
        $('#logs_table_body').html(`
            <tr>
                <td colspan="6" class="text-center py-5 text-primary">
                    <div class="spinner-border spinner-border-sm me-2" role="status"></div> Interrogating security registers database volumes...
                </td>
            </tr>
        `);
        
        $.ajax({
            url: server_controller,
            type: 'POST',
            data: payloadData,
            dataType: 'json'
        }).then(res => {
            if (res.status === 'success') {
                renderActivityLogsTableUI(res.data);
            } else {
                throw new Error(res.message || 'Audit trail ledger footprint lookup fault.');
            }
        }).catch(err => {
            $('#logs_table_body').html(`<tr><td colspan="6" class="text-center py-5 text-danger fw-medium"><i class="bx bx-error-circle d-block fs-3 mb-2"></i>Fetch Audit Failure: ${err.message}</td></tr>`);
        });
    }

    // Trigger instant requery upon dropdown or date criteria mutations
    $('#filter_module, #filter_from_date, #filter_to_date').on('change', function() {
        executeAdvancedLogFiltration();
    });

    // Enforce 300ms input text debounce threshold on search keyword streams
    $('#search_keyword').on('keyup', function() {
        clearTimeout(logQueryTimer);
        logQueryTimer = setTimeout(executeAdvancedLogFiltration, 300);
    });

    // Reset workspace filtration form and restore pristine date boundaries natively
    $('#reset_log_filters_btn').on('click', function() {
        const activeFilterForm = document.getElementById('serverLogFilterForm');
        if (activeFilterForm) {
            activeFilterForm.reset(); // FIXED NATIVE DOM RESET ENGINE CALL
        }
        executeAdvancedLogFiltration();
    });
});
</script>

<style>
.font-size-xs { 
    font-size: 0.75rem !important; 
}
.text-wrap { 
    white-space: normal !important; 
}
</style>
