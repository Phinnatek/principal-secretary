<?php
try {
    require_once 'includes/header.php';  

    Auth::checkAccess(['Principal', 'Secretary']);
    if (!$con) throw new Exception('Database link reference dropped.');

    $dataset = getVisitorsDashboardDataset($con);
    if ($dataset['status'] === 'error') throw new Exception($dataset['message']);

    $visitorsList  = $dataset['visitors_list'];
    $statusOptions = $dataset['status_options'];
    
    $statusBadges = [
        'Inside Office'    => 'primary',
        'Completed'        => 'success',
        'Banned / Flagged' => 'danger'
    ];

} catch (Throwable $e) {
    echo '<div class="alert alert-danger m-4">System Framework Failure: ' . htmlspecialchars($e->getMessage()) . '</div>'; 
    exit;
} ?>

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Office Desk /</span> Daily Visitor Logbook</h4>

        <!-- ============================================================
             DYNAMIC FILTRATION TOOLBAR PANEL
             ============================================================ -->
        <div class="card mb-4 shadow-sm border-0">
            <div class="card-body">
                <form id="serverVisitorFilterForm" autocomplete="off">
                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label class="form-label small fw-bold text-uppercase text-heading">Search Logbook</label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="bx bx-search text-muted"></i></span>
                                <input type="text" name="search_keyword" id="search_keyword" class="form-control" placeholder="Search name, purpose, company..." />
                            </div>
                        </div>

                        <div class="col-12 col-sm-4 col-md-3">
                            <label class="form-label small fw-bold text-uppercase text-heading">From Date</label>
                            <input type="date" name="filter_from_date" id="filter_from_date" class="form-control" />
                        </div>

                        <div class="col-12 col-sm-4 col-md-3">
                            <label class="form-label small fw-bold text-uppercase text-heading">To Date</label>
                            <input type="date" name="filter_to_date" id="filter_to_date" class="form-control" />
                        </div>
                        
                        <div class="col-12 col-sm-4 col-md-2 d-grid align-self-end">
                            <button type="button" id="reset_visitor_filters_btn" class="btn btn-outline-secondary d-flex align-items-center justify-content-center gap-1">
                                <i class="bx bx-refresh fs-4"></i> Clear
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- ============================================================
             MAIN DATA TABLE CARD
             ============================================================ -->
        <div class="card">
            <div class="card-header border-bottom d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0 fw-semibold text-primary">Today's Office Visitor Log</h5>
                <button class="btn btn-primary btn-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#visitorEntryModal">
                    <i class="bx bx-plus"></i> New Guest Entry
                </button>
            </div>
            
            <div class="table-responsive text-nowrap card-body">
                <table class="table table-striped table-hover align-middle mb-0" id="DataTable">
                    <thead class="table-light text-uppercase font-size-sm">
                        <tr>
                            <th>S/N</th>
                            <th>Guest Identity</th>
                            <th>Affiliation</th>
                            <th>Target Agenda / Purpose</th>
                            <th>Time In</th>
                            <th>Time Out</th>
                            <th>Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="visitor_table_body" class="table-border-bottom-0">
                        <?php if (!empty($visitorsList)): ?>
                        
                        <?php $sn = 1; ?>
                            <?php foreach ($visitorsList as $row): ?>
                            <tr>
                                <td><?php echo $sn++; ?></td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="fw-bold text-heading mb-0"><?php echo htmlspecialchars($row['guest_name']); ?></span>
                                        <small class="text-muted small"><?php echo htmlspecialchars($row['phone_number']); ?></small>
                                    </div>
                                </td>
                                <td class="fw-medium"><?php echo htmlspecialchars($row['organization'] ?: 'Independent'); ?></td>
                                <td><div class="text-truncate" style="max-width: 250px;" title="<?php echo htmlspecialchars($row['purpose']); ?>"><?php echo htmlspecialchars($row['purpose']); ?></div></td>
                                <td><span class="badge bg-label-secondary"><i class="bx bx-time me-1"></i><?php echo date('h:i A', strtotime($row['entry_time'])); ?></span></td>
                                <td>
                                    <?php echo $row['exit_time'] ? '<span class="badge bg-label-dark"><i class="bx bx-log-out me-1"></i>'.date('h:i A', strtotime($row['exit_time'])).'</span>' : '<span class="text-muted small fw-light">Still Inside</span>'; ?>
                                </td>
                                <td>
                                    <span class="badge bg-label-<?php echo $statusBadges[$row['status']] ?? 'secondary'; ?> font-weight-bold">
                                        <?php echo $row['status']; ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="dropdown">
                                        <button type="button" class="btn btn-sm btn-icon dropdown-toggle hide-arrow p-0" data-bs-toggle="dropdown">
                                            <i class="bx bx-dots-vertical-rounded fs-5"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end">
                                            <a class="dropdown-item d-flex align-items-center text-primary edit-visitor-btn" href="javascript:void(0);" data-entry="<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8'); ?>">
                                                <i class="bx bx-edit-alt me-2"></i> Edit Parameters
                                            </a>
                                            <?php if ($row['status'] === 'Inside Office'): ?>
                                                <a class="dropdown-item d-flex align-items-center text-success checkout-visitor-btn" href="javascript:void(0);" data-id="<?php echo $row['id']; ?>">
                                                    <i class="bx bx-log-out-circle me-2"></i> Log Exit Timestamp
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     MODAL LAYOUT: RECORD / AMEND VISITOR ENTRY
     ============================================================ -->
<div class="modal fade" id="visitorEntryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold text-primary" id="modalCenterTitle">Log New Visitor Entry</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="visitorSubmissionForm" method="POST" autocomplete="off">
                <div class="modal-body">
                    <!-- Hidden operational row identifier element -->
                    <input type="hidden" id="visitor_record_id" name="id" value="" />

                    <div class="row g-3 mb-3">
                        <div class="col-12">
                            <label for="guest_name" class="form-label small fw-semibold text-heading">Visitor / Delegate Name</label>
                            <input type="text" id="guest_name" name="guest_name" class="form-control" placeholder="e.g., Hon. Dr. Justice Osei" required />
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-sm-6">
                            <label for="phone_number" class="form-label small fw-semibold text-heading">Contact Phone Number</label>
                            <input type="text" id="phone_number" name="phone_number" class="form-control" placeholder="e.g., +233 50 000 1122" required />
                        </div>
                        <div class="col-12 col-sm-6">
                            <label for="organization" class="form-label small fw-semibold text-heading">Affiliation / Organization</label>
                            <input type="text" id="organization" name="organization" class="form-control" placeholder="e.g., Ministry of Education / SRC" />
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <label for="purpose" class="form-label small fw-semibold text-heading">Explicit Agenda / Purpose of Visit</label>
                            <textarea id="purpose" name="purpose" class="form-control" rows="3" placeholder="State reason criteria for administrative clearance evaluation..." required></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3">Commit Entry Log</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php 
// Include Sneat Admin footer closing layout templates
include_once 'includes/footer.php'; 
?>

<!-- ============================================================
     REAL-TIME INTERACTIVE VISITOR OPERATIONS & AJAX PIPELINE
     ============================================================ -->
<script>

/**
 * Re-renders the complete Sneat Admin Visitor Data Table body dynamically
 * using raw database result array payloads from the controller.
 *
 * @param {Array} visitorsList Updated list object package array from the server
 */
function renderVisitorsTableUI(visitorsList) {
    let htmlOutput = '';
    
    if (!visitorsList || visitorsList.length === 0) {
        htmlOutput = `
        <tr>
            <td colspan="8" class="text-center py-5 text-muted">
                <i class="bx bx-user-voice d-block fs-1 mb-2"></i> No active visitors found matching parameters.
            </td>
        </tr>`;
    } else {
        let sn = 1;
        const badgesMap = { 'Inside Office': 'primary', 'Completed': 'success', 'Banned / Flagged': 'danger' };

        $.each(visitorsList, function(index, row) {
            const badgeColor = badgesMap[row.status] || 'secondary';
            const escapedJSON = JSON.stringify(row).replace(/"/g, '&quot;');
            
            // Format dynamic clock 24h strings to humanized 12h slots safely
            const formatClock = (timeStr) => {
                if (!timeStr) return '';
                const parts = timeStr.split(':');
                const h = parseInt(parts[0], 10);
                const m = parts[1];
                return `${h % 12 || 12}:${m} ${h >= 12 ? 'PM' : 'AM'}`;
            };

            htmlOutput += `
            <tr>
                <td>${sn++}</td>
                <td>
                    <div class="d-flex flex-column">
                        <span class="fw-bold text-heading mb-0">${row.guest_name}</span>
                        <small class="text-muted small">${row.phone_number}</small>
                    </div>
                </td>
                <td class="fw-medium">${row.organization || 'Independent'}</td>
                <td><div class="text-truncate" style="max-width: 250px;" title="${row.purpose}">${row.purpose}</div></td>
                <td><span class="badge bg-label-secondary"><i class="bx bx-time me-1"></i>${formatClock(row.entry_time)}</span></td>
                <td>
                    ${row.exit_time ? `<span class="badge bg-label-dark"><i class="bx bx-log-out me-1"></i>${formatClock(row.exit_time)}</span>` : `<span class="text-muted small fw-light">Still Inside</span>`}
                </td>
                <td><span class="badge bg-label-${badgeColor} font-weight-bold">${row.status}</span></td>
                <td class="text-center">
                    <div class="dropdown">
                        <button type="button" class="btn btn-sm btn-icon dropdown-toggle hide-arrow p-0" data-bs-toggle="dropdown">
                            <i class="bx bx-dots-vertical-rounded fs-5"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a class="dropdown-item d-flex align-items-center text-primary edit-visitor-btn" href="javascript:void(0);" data-entry="${escapedJSON}">
                                <i class="bx bx-edit-alt me-2"></i> Edit Parameters
                            </a>
                            ${row.status === 'Inside Office' ? `
                                <a class="dropdown-item d-flex align-items-center text-success checkout-visitor-btn" href="javascript:void(0);" data-id="${row.id}">
                                    <i class="bx bx-log-out-circle me-2"></i> Log Exit Timestamp
                                </a>
                            ` : ''}
                        </div>
                    </div>
                </td>
            </tr>`;
        });
    }
    $('#visitor_table_body').html(htmlOutput);
}

$(document).ready(function() {
    
    // =========================================================================
    // FORM SUBMISSION: DUAL ADD/UPDATE VISITOR DETAILS (`save_visitor=true`)
    // =========================================================================
    $('#visitorSubmissionForm').on('submit', function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Commit Visitor Entry Log?',
            text: "Are you sure you want to capture this guest record properties?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#696cff',
            cancelButtonColor: '#8592a3',
            confirmButtonText: 'Yes, save entry log',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return $.ajax({
                    url: server_controller,
                    type: 'POST',
                    data: $('#visitorSubmissionForm').serialize() + '&save_visitor=true',
                    dataType: 'json'
                }).then(res => {
                    if (res.status !== 'success') throw new Error(res.message);
                    return res;
                }).catch(err => { Swal.showValidationMessage(`Error: ${err.message}`); });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then(result => {
            if (result.isConfirmed && result.value) {
                $('#visitorEntryModal').modal('hide');
                $('#visitorSubmissionForm')[0].reset();
                renderVisitorsTableUI(result.value.data);
                Swal.fire('Saved!', result.value.message, 'success');
            }
        });
    });

        // =========================================================================
    // UI ACTIONS DYNAMIC MAPPING: POPULATE FIELDS FOR AMENDMENT
    // =========================================================================
    $(document).on('click', '.edit-visitor-btn', function(e) {
        e.preventDefault();
        const data = $(this).data('entry');
        
        $('#visitorEntryModal #modalCenterTitle').text('Amend Visitor Log Parameters');
        $('#visitorEntryModal button[type="submit"]').text('Update Entry Log');
        
        $('#visitorEntryModal #visitor_record_id').val(data.id);
        $('#visitorEntryModal #guest_name').val(data.guest_name);
        $('#visitorEntryModal #phone_number').val(data.phone_number);
        $('#visitorEntryModal #organization').val(data.organization);
        $('#visitorEntryModal #purpose').val(data.purpose);
        
        $('#visitorEntryModal').modal('show');
    });

    // =========================================================================
    // CHECKOUT TIMESTAMP EMISSION: MUTATE STATE TERMINAL (`checkout_visitor=true`)
    // =========================================================================
    $(document).on('click', '.checkout-visitor-btn', function(e) {
        e.preventDefault();
        const rowId = $(this).data('id');
        
        Swal.fire({
            title: 'Log Guest Departure?',
            text: "This action records the current server timestamp as the visitor's exit execution window boundary.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#71dd37',
            cancelButtonColor: '#8592a3',
            confirmButtonText: 'Yes, log exit timestamp',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return $.ajax({
                    url: server_controller,
                    type: 'POST',
                    data: { id: rowId, checkout_visitor: 'true' },
                    dataType: 'json'
                }).then(res => {
                    if (res.status !== 'success') throw new Error(res.message);
                    return res;
                }).catch(err => { Swal.showValidationMessage(`Error: ${err.message}`); });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then(result => {
            if (result.isConfirmed && result.value) {
                renderVisitorsTableUI(result.value.data);
                Swal.fire('Checked Out!', 'Departure timestamp logged successfully.', 'success');
            }
        });
    });

    // =========================================================================
    // ADVANCED SERVER-SIDE DATA QUERY FILTRATION LINK (`fetch_filtered_visitors=true`)
    // =========================================================================
    let visitorTimer = null;
    function executeAdvancedVisitorFiltration() {
        const payload = $('#serverVisitorFilterForm').serialize() + '&fetch_filtered_visitors=true';
        
        $('#visitor_table_body').html(`
            <tr>
                <td colspan="8" class="text-center py-5 text-primary">
                    <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                    Interrogating logbooks...
                </td>
            </tr>
        `);
        
        $.ajax({
            url: server_controller,
            type: 'POST',
            data: payload,
            dataType: 'json'
        }).then(res => { 
            if (res.status === 'success') {
                renderVisitorsTableUI(res.data); 
            } else {
                throw new Error(res.message || 'Ledger transaction extraction fault.');
            }
        }).catch(err => {
            $('#visitor_table_body').html(`<tr><td colspan="8" class="text-center py-5 text-danger fw-medium"><i class="bx bx-error-circle d-block fs-3 mb-2"></i>Fetch Fault: ${err.message}</td></tr>`);
        });
    }

    $('#filter_from_date, #filter_to_date').on('change', function() { 
        executeAdvancedVisitorFiltration(); 
    });
    
    $('#search_keyword').on('keyup', function() {
        clearTimeout(visitorTimer);
        visitorTimer = setTimeout(executeAdvancedVisitorFiltration, 300);
    });

    $('#reset_visitor_filters_btn').on('click', function() {
        $('#serverVisitorFilterForm').reset();
        executeAdvancedVisitorFiltration();
    });
 

// TO THIS:
$('#visitorEntryModal').on('hidden.bs.modal', function () {
    $('#visitorSubmissionForm')[0].reset(); // FIXED INDEX
    $('#visitorEntryModal #visitor_record_id').val('');
    $('#visitorEntryModal #modalCenterTitle').text('Log New Visitor Entry');
    $('#visitorEntryModal button[type="submit"]').text('Commit Entry Log');
});

});
</script>
