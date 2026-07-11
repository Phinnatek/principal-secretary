<?php
try {
    // 1. Load Sneat Header (Includes Auth Session Initialization & $con Context)
    require_once 'includes/header.php';  

    // Force RBAC Security Access Guard Protection
    Auth::checkAccess(['Principal', 'Secretary']);
    if (!$con) throw new Exception('Database link verification dropped.');

    // 2. Load Unified Dataset
    $dataset = getStaffDashboardDataset($con);
    if ($dataset['status'] === 'error') throw new Exception($dataset['message']);

    $staffList         = $dataset['staff_list'];
    $rolesList         = $dataset['roles_list'];
    $departmentOptions = $dataset['department_options'];
    $statusOptions     = $dataset['status_options'];

} catch (Throwable $e) {
    error_log('Staff Directory Workspace Root Initialization Crash: ' . $e->getMessage());
    echo '<div class="alert alert-danger m-4">System Framework Failure: ' . htmlspecialchars($e->getMessage()) . '</div>'; 
    exit;
} ?>

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Human Resources /</span> Campus Faculty & Staff Directory</h4>

        
        <!-- ============================================================
             MAIN STAFF DIRECTORY MANAGEMENT DATA TABLE CARD
             ============================================================ -->
        <div class="card">
            <div class="card-header border-bottom d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0 fw-semibold text-primary">Registered College Personnel Ledger</h5>
                <button class="d-none btn btn-primary btn-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#staffManagementModal">
                    <i class="bx bx-user-plus"></i> Register Staff
                </button>
            </div>
            
            <div class="table-responsive text-nowrap card-body">
                <table class="table table-striped table-hover align-middle mb-0" id="DataTable">
                    <thead class="table-light text-uppercase font-size-sm">
                        <tr>
                            <th>S/N</th>
                            <th>Staff Identity</th>
                            <th>Employee ID</th>
                            <th>Department</th>
                            <th>Designation / Rank</th>
                            <th>System Authority</th>
                            <th>Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                                        <tbody id="staff_table_body" class="table-border-bottom-0">
                        <?php if (empty($staffList)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bx bx-folder-open d-block fs-1 mb-2"></i> No personnel profiles configured inside the storage registry.
                            </td>
                        </tr>
                        <?php else: $sn = 1; ?>
                            <?php foreach ($staffList as $row): ?>
                            <tr>
                                <td><?php echo $sn++; ?></td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="fw-bold text-heading mb-0"><?php echo htmlspecialchars($row['name']); ?></span>
                                        <small class="text-muted small"><?php echo htmlspecialchars($row['email']); ?></small>
                                    </div>
                                </td>
                                <td><span class="fw-medium text-primary">#<?php echo htmlspecialchars($row['employee_id'] ?? 'N/A'); ?></span></td>
                                <td><?php echo htmlspecialchars($row['department'] ?? 'Unassigned'); ?></td>
                                <td><span class="text-heading fw-medium"><?php echo htmlspecialchars($row['designation'] ?? 'Staff Member'); ?></span></td>
                                <td><span class="badge bg-label-primary font-weight-bold"><?php echo $row['role_name']; ?></span></td>
                                <td>
                                    <span class="badge bg-label-<?php echo ($row['status'] === 'Active') ? 'success' : 'danger'; ?> font-weight-bold">
                                        <?php echo $row['status']; ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="dropdown">
                                        <button type="button" class="btn btn-sm btn-icon dropdown-toggle hide-arrow p-0" data-bs-toggle="dropdown">
                                            <i class="bx bx-dots-vertical-rounded fs-5"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end">
                                            <a class="dropdown-item d-flex align-items-center text-primary edit-staff-btn" href="javascript:void(0);" data-entry="<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8'); ?>">
                                                <i class="bx bx-edit-alt me-2"></i> Amend Profile
                                            </a>
                                            <div class="dropdown-divider"></div>
                                            <?php if ($row['status'] === 'Active'): ?>
                                                <a class="d-none dropdown-item d-flex align-items-center text-danger change-staff-status-btn" href="javascript:void(0);" data-id="<?php echo $row['id']; ?>" data-status="Inactive">
                                                    <i class="bx bx-user-x me-2"></i> Suspend Access
                                                </a>
                                            <?php else: ?>
                                                <a class="dropdown-item d-flex align-items-center text-success change-staff-status-btn" href="javascript:void(0);" data-id="<?php echo $row['id']; ?>" data-status="Active">
                                                    <i class="bx bx-user-check me-2"></i> Activate Access
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
     MODAL LAYOUT: REGISTER / AMEND STAFF MEMEBER (TRUE-BOOLEAN ALIGNED)
     ============================================================ -->
<div class="modal fade" id="staffManagementModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold text-primary" id="modalCenterTitle">Register Staff Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="staffSubmissionForm" method="POST" autocomplete="off">
                <div class="modal-body">
                    <!-- Hidden operational identifier vector element -->
                    <input type="hidden" id="staff_record_id" name="id" value="" />

                    <div class="row g-3 mb-3">
                        <div class="col-12">
                            <label for="name" class="form-label small fw-semibold text-heading">Full Legal Name</label>
                            <input type="text" id="name" name="name" class="form-control" placeholder="e.g., Prof. Emmanuel Ampofo" required />
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-sm-6">
                            <label for="email" class="form-label small fw-semibold text-heading">Official Email Address</label>
                            <input type="email" id="email" name="email" class="form-control" placeholder="e.g., e.ampofo@college.edu" required />
                        </div>
                        <div class="col-12 col-sm-6">
                            <label class="form-label small fw-semibold text-heading" id="pass_label">System Password Key</label>
                            <input type="password" id="password" name="password" class="form-control" placeholder="Minimum 6 characters" required />
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-sm-6">
                            <label for="employee_id" class="form-label small fw-semibold text-heading">College Employee ID</label>
                            <input type="text" id="employee_id" name="employee_id" class="form-control" placeholder="e.g., COL-2026-M41" required />
                        </div>
                        <div class="col-12 col-sm-6">
                            <label for="phone_number" class="form-label small fw-semibold text-heading">Contact Phone Number</label>
                            <input type="text" id="phone_number" name="phone_number" class="form-control" placeholder="e.g., +233 24 111 2222" />
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-sm-6">
                            <label for="department" class="form-label small fw-semibold text-heading">Academic Department</label>
                            <select id="department" name="department" class="form-select" required>
                                <option value="" disabled selected>-- Select Department --</option>
                                <?php foreach($departmentOptions as $dept): ?>
                                    <option value="<?php echo $dept; ?>"><?php echo $dept; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label for="designation" class="form-label small fw-semibold text-heading">Official Rank / Designation</label>
                            <input type="text" id="designation" name="designation" class="form-control" placeholder="e.g., Senior Lecturer, Head Clerk" required />
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-12">
                            <label for="role_id" class="form-label small fw-semibold text-heading">System Access Authority Privileges</label>
                            <select id="role_id" name="role_id" class="form-select" required>
                                <option value="" disabled selected>-- Assign RBAC Role --</option>
                                <?php foreach($rolesList as $role): ?>
                                    <option value="<?php echo $role['id']; ?>"><?php echo htmlspecialchars($role['role_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3">Save Profile</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php 
// Include Sneat closing tags layouts and default engine core scripts
include_once 'includes/footer.php'; 
?>

<!-- ============================================================
     REAL-TIME INTERACTIVE PERSONNEL OPERATIONAL ACTIONS & AJAX PIPELINE
     ============================================================ --> 
<script>

/**
 * Re-renders the complete Sneat Admin Staff Data Table body dynamically
 * using raw database result array payloads from the controller.
 *
 * @param {Array} staffList Updated list object package array from the server
 */
function renderStaffTableUI(staffList) {
    let htmlOutput = '';
    
    if (!staffList || staffList.length === 0) {
        htmlOutput = `
        <tr>
            <td colspan="8" class="text-center py-5 text-muted">
                <i class="bx bx-folder-open d-block fs-1 mb-2"></i> No active personnel metrics match your chosen filter query parameters.
            </td>
        </tr>`;
    } else {
        let sn = 1;
        $.each(staffList, function(index, row) {
            const statusBadge = (row.status === 'Active') ? 'success' : 'danger';
            const escapedJSON = JSON.stringify(row).replace(/"/g, '&quot;');
            
            htmlOutput += `
            <tr>
                <td>${sn++}</td>
                <td>
                    <div class="d-flex flex-column">
                        <span class="fw-bold text-heading mb-0">${row.name}</span>
                        <small class="text-muted small">${row.email}</small>
                    </div>
                </td>
                <td><span class="fw-medium text-primary">#${row.employee_id || 'N/A'}</span></td>
                <td>${row.department || 'Unassigned'}</td>
                <td><span class="text-heading fw-medium">${row.designation || 'Staff'}</span></td>
                <td><span class="badge bg-label-primary font-weight-bold">${row.role_name}</span></td>
                <td><span class="badge bg-label-${statusBadge} font-weight-bold">${row.status}</span></td>
                <td class="text-center">
                    <div class="dropdown">
                        <button type="button" class="btn btn-sm btn-icon dropdown-toggle hide-arrow p-0" data-bs-toggle="dropdown">
                            <i class="bx bx-dots-vertical-rounded fs-5"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a class="dropdown-item d-flex align-items-center text-primary edit-staff-btn" href="javascript:void(0);" data-entry="${escapedJSON}">
                                <i class="bx bx-edit-alt me-2"></i> Amend Profile
                            </a>
                            <div class="dropdown-divider"></div>
                            ${row.status === 'Active' ? `
                                <a class="dropdown-item d-flex align-items-center text-danger change-staff-status-btn" href="javascript:void(0);" data-id="${row.id}" data-status="Inactive">
                                    <i class="bx bx-user-x me-2"></i> Suspend Access
                                </a>
                            ` : `
                                <a class="dropdown-item d-flex align-items-center text-success change-staff-status-btn" href="javascript:void(0);" data-id="${row.id}" data-status="Active">
                                    <i class="bx bx-user-check me-2"></i> Activate Access
                                </a>
                            `}
                        </div>
                    </div>
                </td>
            </tr>`;
        });
    }
    $('#staff_table_body').html(htmlOutput);
}

$(document).ready(function() {
    
    // =========================================================================
    // MODAL SUBMISSION: SAVE / UPDATE STAFF MEMBER (`save_staff=true`)
    // =========================================================================
    $('#staffSubmissionForm').on('submit', function(e) {
        e.preventDefault();
        
        Swal.fire({
            title: 'Commit Personnel Profile Changes?',
            text: "Are you sure you want to write these parameter properties into the core security database index?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#696cff',
            cancelButtonColor: '#8592a3',
            confirmButtonText: 'Yes, save identity profile',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return $.ajax({
                    url: server_controller,
                    type: 'POST',
                    data: $('#staffSubmissionForm').serialize() + '&save_staff=true',
                    dataType: 'json'
                }).then(res => {
                    if (res.status !== 'success') throw new Error(res.message);
                    return res;
                }).catch(err => { Swal.showValidationMessage(`Operation Blocked: ${err.message}`); });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then(result => {
            if (result.isConfirmed && result.value) {
                $('#staffManagementModal').modal('hide');
                $('#staffSubmissionForm')[0].reset();
                
                renderStaffTableUI(result.value.data);
                Swal.fire('Saved!', result.value.message, 'success');
            }
        });
    });

    // =========================================================================
    // DYNAMIC POPULATION ACTION: PRE-FILL FORM VALUES FOR UPDATE
    // =========================================================================
    $(document).on('click', '.edit-staff-btn', function(e) {
        e.preventDefault();
        const data = $(this).data('entry');
        
        $('#staffManagementModal #modalCenterTitle').text('Amend Personnel Profile Parameters');
        $('#staffManagementModal button[type="submit"]').text('Update Identity Profile');
        
        // Target structural hidden input vector mapping
        $('#staffManagementModal #staff_record_id').val(data.id);
        $('#staffManagementModal #name').val(data.name);
        $('#staffManagementModal #email').val(data.email);
        $('#staffManagementModal #employee_id').val(data.employee_id);
        $('#staffManagementModal #phone_number').val(data.phone_number);
        $('#staffManagementModal #department').val(data.department);
        $('#staffManagementModal #designation').val(data.designation);
        $('#staffManagementModal #role_id').val(data.role_id);
        
        // Make password non-compulsory during updating profiles
        $('#staffManagementModal #password').removeAttr('required');
        // Clear old password data keys to protect security bounds
        $('#staffManagementModal #password').val('');
        $('#staffManagementModal #pass_label').html('Password Overwrite Key <span class="text-muted fw-normal">(Leave blank to retain)</span>');
        
        $('#staffManagementModal').modal('show');
    });

    // =========================================================================
    // ACCOUNT ACCESS LOCK MUTATION DISPATCHER (`update_staff_status=true`)
    // =========================================================================
    $(document).on('click', '.change-staff-status-btn', function(e) {
        e.preventDefault();
        const rowId = $(this).data('id');
        const targetState = $(this).data('status');
        const alertIcon = (targetState === 'Active') ? 'info' : 'warning';
        const contextColor = (targetState === 'Active') ? '#71dd37' : '#ff3e1d';

        Swal.fire({
            title: 'Authorize System Access Shift?',
            html: `Are you sure you want to change this profile authorization status to <strong>${targetState}</strong>?`,
            icon: alertIcon,
            showCancelButton: true,
            confirmButtonColor: contextColor,
            cancelButtonColor: '#8592a3',
            confirmButtonText: 'Yes, modify permission',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return $.ajax({
                    url: server_controller,
                    type: 'POST',
                    data: { id: rowId, status: targetState, update_staff_status: 'true' },
                    dataType: 'json'
                }).then(res => {
                    if (res.status !== 'success') throw new Error(res.message);
                    return res;
                }).catch(err => { Swal.showValidationMessage(`Error: ${err.message}`); });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then(result => {
            if (result.isConfirmed && result.value) {
                renderStaffTableUI(result.value.data);
                Swal.fire('Updated!', 'Personnel entry access privileges shifted successfully.', 'success');
            }
        });
    });

   
        // =========================================================================
    // SERVER SIDE RETRIEVAL TOOLBAR DISPATCHER (`fetch_filtered_staff=true`)
    // =========================================================================
    let staffFilterTimer = null;
    function executeAdvancedStaffFiltration() {
        const queryData = $('#serverStaffFilterForm').serialize() + '&fetch_filtered_staff=true';

        $('#staff_table_body').html(`
            <tr>
                <td colspan="8" class="text-center py-5 text-primary">
                    <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                    Interrogating credential records database index...
                </td>
            </tr>
        `);

        $.ajax({
            url: server_controller,
            type: 'POST',
            data: queryData,
            dataType: 'json'
        }).then(response => {
            if (response.status === 'success') {
                renderStaffTableUI(response.data); 
            } else { 
                throw new Error(response.message); 
            }
        }).catch(error => {
            $('#staff_table_body').html(`
                <tr>
                    <td colspan="8" class="text-center py-5 text-danger fw-medium">
                        <i class="bx bx-error-circle d-block fs-3 mb-2"></i>
                        Fetch Error: ${error.message}
                    </td>
                </tr>
            `);
        });
    }

    // Bind execution hooks on selection parameters alteration shifts
    $('#filter_department, #filter_role').on('change', function() { 
        executeAdvancedStaffFiltration(); 
    });
    
    // Alphanumeric keyboard trigger loop
    $('#search_keyword').on('keyup', function() {
        clearTimeout(staffFilterTimer);
        staffFilterTimer = setTimeout(executeAdvancedStaffFiltration, 300);
    });

    // Clear filter toolbar input values wrapper
    $('#reset_staff_filters_btn').on('click', function() {
        $('#serverStaffFilterForm')[0].reset();
        executeAdvancedStaffFiltration();
    });

    // Restore pristine validation attributes inside modal fields upon dismissal
    $('#staffManagementModal').on('hidden.bs.modal', function () {
        $('#staffSubmissionForm')[0].reset();
        $('#staffManagementModal #staff_record_id').val('');
        $('#staffManagementModal #modalCenterTitle').text('Register Staff Profile');
        $('#staffManagementModal button[type="submit"]').text('Save Profile');
        $('#staffManagementModal #password').attr('required', 'required');
        $('#staffManagementModal #pass_label').text('System Password Key');
    });
});
</script>