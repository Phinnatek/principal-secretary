<?php
try {
    // Load Sneat Header configuration assets (Sets up $con context automatically)
    require_once 'includes/header.php'; 
    if (!$con) throw new Exception('Database link verification dropped.');

    // Fire unified query engine function loop
    $dataset = getAppointmentsDashboardDataset($con);
    if ($dataset['status'] === 'error') throw new Exception($dataset['message']);

    // Map local arrays to the returned dataset keys cleanly
    $appointmentsList       = $dataset['appointments_list'];
    $appointmentTypeOptions = $dataset['appointment_type_options'];
    $visitorTypeOptions     = $dataset['visitor_type_options'];
    $statusOptions          = $dataset['status_options'];
    $statusBadges           = $dataset['status_badges'];

} catch (Throwable $e) {
    error_log('Appointments Workspace Root Initialization Crash: ' . $e->getMessage());
    echo '<div class="alert alert-danger m-4">System Framework Failure: ' . htmlspecialchars($e->getMessage()) . '</div>'; 
    exit;
} ?>
 

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
         <!-- ============================================================
             DASHBOARD ANALYTICS RECAP CARDS
             ============================================================ -->
        <div class="row g-4 mb-4">
            <!-- Total Scheduled Card -->
            <div class="col-sm-6 col-xl-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div class="content-left">
                                <span class="text-heading text-muted small text-uppercase">Total Logs</span>
                                <div class="d-flex align-items-center my-1">
                                    <h4 class="mb-0 me-2 fw-bold"><?php echo count($appointmentsList); ?></h4>
                                </div>
                                <p class="mb-0 text-muted small">System wide tracked slots</p>
                            </div>
                            <div class="avatar bg-label-primary p-2 rounded">
                                <i class="bx bx-calendar bx-sm"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dynamic Counters derived from our loop processing -->
            <?php
            $pendingCount = 0; $approvedCount = 0;
            foreach ($appointmentsList as $appt) {
                if ($appt['status'] === 'Pending') $pendingCount++;
                if ($appt['status'] === 'Approved') $approvedCount++;
            }
            ?>

            <!-- Pending Review Card -->
            <div class="col-sm-6 col-xl-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div class="content-left">
                                <span class="text-heading text-muted small text-uppercase">Pending Review</span>
                                <div class="d-flex align-items-center my-1">
                                    <h4 class="mb-0 me-2 text-warning fw-bold"><?php echo $pendingCount; ?></h4>
                                </div>
                                <p class="mb-0 text-muted small">Awaiting office response</p>
                            </div>
                            <div class="avatar bg-label-warning p-2 rounded">
                                <i class="bx bx-time-five bx-sm"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Approved Bookings Card -->
            <div class="col-sm-6 col-xl-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div class="content-left">
                                <span class="text-heading text-muted small text-uppercase">Approved Slots</span>
                                <div class="d-flex align-items-center my-1">
                                    <h4 class="mb-0 me-2 text-success fw-bold"><?php echo $approvedCount; ?></h4>
                                </div>
                                <p class="mb-0 text-muted small">Confirmed calendar allocations</p>
                            </div>
                            <div class="avatar bg-label-success p-2 rounded">
                                <i class="bx bx-check-shield bx-sm"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

                <!-- ============================================================
             DYNAMIC FILTRATION TOOLBAR PANEL (SERVER-SIDE DISPATCHER)
             ============================================================ -->
        <div class="card mb-4 shadow-sm border-0">
            <div class="card-body">
                <form id="serverFiltrationForm" autocomplete="off">
                    <div class="row g-3">
                        
                        <!-- Global Live Text Search -->
                        <div class="col-12 col-md-3">
                            <label class="form-label small fw-bold text-uppercase text-heading">Keyword Search</label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="bx bx-search text-muted"></i></span>
                                <input type="text" name="search_keyword" id="search_keyword" class="form-control filter-input" placeholder="Search visitor, agenda, log..." />
                            </div>
                        </div>

                        <!-- From Range Boundary -->
                        <div class="col-12 col-sm-6 col-md-2">
                            <label class="form-label small fw-bold text-uppercase text-heading">From Date</label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="bx bx-calendar-alt text-muted"></i></span>
                                <input type="date" name="filter_from_date" id="filter_from_date" class="form-control filter-input" />
                            </div>
                        </div>

                        <!-- To Range Boundary -->
                        <div class="col-12 col-sm-6 col-md-2">
                            <label class="form-label small fw-bold text-uppercase text-heading">To Date</label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="bx bx-calendar-check text-muted"></i></span>
                                <input type="date" name="filter_to_date" id="filter_to_date" class="form-control filter-input" />
                            </div>
                        </div>
                        
                        <!-- Status Classification Selector -->
                        <div class="col-12 col-sm-6 col-md-3">
                            <label class="form-label small fw-bold text-uppercase text-heading">Operational Status</label>
                            <select name="filter_status" id="filter_status" class="form-select fw-medium filter-input">
                                <option value="">-- All Status Profiles --</option>
                                <?php foreach ($statusOptions as $status): ?>
                                    <option value="<?php echo $status; ?>"><?php echo $status; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Reset Triggers -->
                        <div class="col-12 col-sm-6 col-md-2 d-grid align-self-end">
                            <button type="button" id="reset_filters_btn" class="btn btn-outline-secondary d-flex align-items-center justify-content-center gap-1">
                                <i class="bx bx-refresh fs-4"></i> Clear All
                            </button>
                        </div>

                    </div>
                </form>
            </div>
        </div>


        <!-- ============================================================
             MAIN MANAGED APPOINTMENT REGISTRY DATA TABLE CARD
             ============================================================ -->
        <div class="card">
            <div class="card-header border-bottom d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0 fw-semibold text-primary">Principal's Daily Schedule Logs</h5>
                <button class="btn btn-primary btn-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#addAppointmentModal">
                    <i class="bx bx-plus"></i> Add Appointment
                </button>
            </div>
            
            <div class="table-responsive text-nowrap card-body">
                  <!-- =========================================================================
     MINIMALIST INSTITUTIONAL SCHEDULING DISPATCH MATRIX
     ========================================================================= -->
<table class="table table-striped table-hover align-middle mb-0" id="DataTable">
    <thead class="table-light text-uppercase font-size-sm">
        <tr>
            <th style="width: 50px;">S/N</th>
            <th>Visitor / Host Target</th>
            <th>Session Type</th>
            <th>Confirmed Date & Timeline</th>
            <th>Status</th>
            <th class="text-center" style="width: 80px;">Actions</th>
        </tr>
    </thead>
    <tbody id="appointment_table_body" class="table-border-bottom-0">
        <?php if (!empty($appointmentsList)): $sn = 1;
            foreach ($appointmentsList as $row): ?>
            <tr class="appointment-row" 
                data-date="<?php echo $row['appointment_date']; ?>" 
                data-type="<?php echo $row['appointment_type_id']; ?>"
                data-status="<?php echo htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8'); ?>">
                <td><?php echo $sn++; ?></td>
                
                <!-- VISITOR TARGET CELL: Combines Name, Phone, and Department Routing -->
                <td class="visitor-name-cell-wrapper">
                    <div class="d-flex flex-column">
                        <span class="fw-bold text-dark mb-0 visitor-name-cell"><?php echo htmlspecialchars($row['visitor_name']); ?></span>
                        <small class="text-muted font-monospace font-size-xs mt-1">
                            <i class="bx bx-phone me-1 font-size-xs"></i><?php echo htmlspecialchars($row['visitor_phone'] ?? 'NONE'); ?>
                        </small>
                        <small class="text-secondary fw-semibold font-size-xs mt-1">
                            <i class="bx bx-buildings me-1 font-size-xs"></i>Dept: <?php echo !empty($row['department']) ? htmlspecialchars($row['department']) : '<span class="text-muted italic">[ N/A - OUTSIDE_GUEST ]</span>'; ?>
                        </small>
                    </div>
                </td>
                
                <!-- SESSION TYPE CELL -->
                <td>
                    <span class="badge bg-label-primary fw-bold font-size-xs text-uppercase">
                        <i class="bx bx-bookmark-alt-minus me-1"></i><?php echo htmlspecialchars($row['appointment_type_label'] ?? 'General'); ?>
                    </span>
                </td>
                
                <!-- CONFIRMED TIMELINE CALENDAR DATE CELL -->
                <td>
                    <div class="d-flex flex-column">
                        <span class="fw-bold text-heading mb-0 small">
                            <i class="bx bx-calendar-event me-1 text-primary"></i><?php echo date('d M, Y', strtotime($row['appointment_date'])); ?>
                        </span>
                        <?php if ($row['status'] === 'Pending'): ?>
                            <small class="text-warning font-size-xs mt-1 italic">
                                <i class="bx bx-time-five me-1"></i>Awaiting Allocation Slot
                            </small>
                        <?php else: ?>
                            <small class="text-muted font-monospace font-size-xs mt-1">
                                <i class="bx bx-time me-1"></i><?php echo date('h:i A', strtotime($row['start_time'])); ?> - <?php echo date('h:i A', strtotime($row['end_time'])); ?>
                            </small>
                        <?php endif; ?>
                    </div>
                </td>
                
                <!-- WORKFLOW STATUS CELL -->
                <td>
                    <?php 
                    $badgeColor = 'secondary';
                    if ($row['status'] === 'Pending') $badgeColor = 'warning';
                    elseif ($row['status'] === 'Approved') $badgeColor = 'success';
                    elseif ($row['status'] === 'Completed') $badgeColor = 'info';
                    elseif ($row['status'] === 'Rejected') $badgeColor = 'danger';
                    ?>
                    <span class="badge bg-label-<?php echo $badgeColor; ?> font-weight-bold text-uppercase px-2 py-1">
                        <?php echo $row['status']; ?>
                    </span>
                </td>
                
                <!-- ACTION OPTIONS MENU DROPDOWN HUB -->
                <td class="text-center">
                    <div class="dropdown">
                        <button type="button" class="btn btn-sm btn-icon dropdown-toggle hide-arrow p-0" data-bs-toggle="dropdown">
                            <i class="bx bx-dots-vertical-rounded fs-5"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <!-- NEW OVERLAY TEXT TRACE TRIGGER LINK -->
                            <a class="dropdown-item d-flex align-items-center text-secondary view-appointment-details-btn mb-1 fw-bold" href="javascript:void(0);" data-entry="<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8'); ?>">
                                <i class="bx bx-show me-2 fs-5 text-secondary"></i> View Full Details
                            </a>
                            
                            <div class="dropdown-divider"></div>

                            <?php if ($row['status'] === 'Pending'): ?>
                                <a class="dropdown-item d-flex align-items-center text-primary edit-appointment-btn" href="javascript:void(0);" data-entry="<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8'); ?>">
                                    <i class="bx bx-edit-alt me-2"></i> Edit Record
                                </a>
                            <?php endif; ?>
                                 
                            <?php if (in_array($_SESSION['role'], ['Principal', 'Secretary']) && $row['status'] === 'Pending'): ?>
                                <a class="dropdown-item d-flex align-items-center text-success update-status-btn" href="javascript:void(0);" data-id="<?php echo $row['id']; ?>" data-status="Approved">
                                    <i class="bx bx-check-circle me-2"></i> Approve Request
                                </a>
                                <a class="dropdown-item d-flex align-items-center text-danger update-status-btn" href="javascript:void(0);" data-id="<?php echo $row['id']; ?>" data-status="Rejected">
                                    <i class="bx bx-x-circle me-2"></i> Reject Request
                                </a>
                            <?php endif; ?>
                            
                            <?php if (in_array($_SESSION['role'], ['Principal', 'Secretary']) && $row['status'] === 'Approved'): ?>
                                <a class="dropdown-item d-flex align-items-center text-info update-status-btn" href="javascript:void(0);" data-id="<?php echo $row['id']; ?>" data-status="Completed">
                                    <i class="bx bx-badge-check me-2"></i> Mark Completed
                                </a>
                            <?php endif; ?>
                            
                            <?php if (in_array($_SESSION['role'], ['Principal', 'Secretary'])): ?>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item d-flex align-items-center text-danger delete-appointment-btn" href="javascript:void(0);" data-id="<?php echo $row['id']; ?>" data-visitor="<?php echo htmlspecialchars($row['visitor_name'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <i class="bx bx-trash me-2"></i> Wipe Appointment
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
    </div> <!-- / Container Wrap -->
</div> <!-- / Content Wrapper Block Wrap -->


 <!-- ========================================================================= 
     SWISS-FINTECH ARCHITECTURE: PURE TYPOGRAPHIC APPOINTMENT REVIEW
     ========================================================================= --> 
<div class="modal fade" id="appointmentDetailsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-centered" role="document">
    <div class="modal-content shadow-none rounded-0 bg-white border-0 px-4 py-3" style="border-radius: 0px !important;">
      
      <div class="modal-header border-0 px-0 pb-4 d-flex align-items-baseline justify-content-between">
        <div class="d-flex align-items-baseline gap-3">
          <span class="text-dark fw-bold text-uppercase" style="font-size: 0.75rem; letter-spacing: 2px;">SCHEDULER.SYS</span>
          <span class="text-muted font-monospace" style="font-size: 0.65rem;">/ APPT_DOSSIER_VIEW</span>
        </div>
        <button type="button" class="btn p-0 border-0 text-muted font-monospace shadow-none" style="font-size: 0.65rem; letter-spacing: 1px;" data-bs-dismiss="modal">[ ESC ]</button>
      </div>

      <div class="modal-body p-0">
        <!-- HEADER IDENTITY LEVEL -->
        <div class="row g-0 mb-4 pb-3 border-bottom border-dark">
          <div class="col-7">
            <span class="text-muted d-block text-uppercase font-monospace mb-1" style="font-size: 0.6rem; letter-spacing: 1px;">Visitor Identity Record</span>
            <h4 id="view_visitor_name" class="text-dark fw-bold m-0 tracking-tight" style="font-size: 1.25rem;">Dr. Kwaku Mensah</h4>
          </div>
          <div class="col-5 text-end align-self-end">
            <span id="view_status_flag" class="text-dark font-monospace fw-bold text-uppercase d-inline-block" style="font-size: 0.65rem; letter-spacing: 1px;">// PENDING</span>
          </div>
        </div>

        <!-- MEETING AGENDA / PURPOSE BLOCK -->
        <div class="mb-4 pb-4 border-bottom border-light-subtle">
          <span class="text-muted d-block text-uppercase font-monospace mb-2" style="font-size: 0.6rem; letter-spacing: 1px;">Detailed Agenda Payload</span>
          <p id="view_purpose" class="text-dark fw-normal m-0 lh-base text-wrap" style="font-size: 0.95rem; letter-spacing: -0.1px;">Agenda details block representation</p>
        </div>

        <!-- STRUCTURAL TRAIL GRID ROWS -->
        <div class="row g-0 mb-4 pb-4 border-bottom border-light-subtle">
          <div class="col-6 pe-3">
            <div class="mb-3">
              <span class="text-muted d-block text-uppercase font-monospace mb-1" style="font-size: 0.6rem; letter-spacing: 1px;">Contact Metrics</span>
              <div id="view_visitor_phone" class="text-dark font-monospace small fw-bold">TEL // +233 24 000 0000</div>
            </div>
            <div>
              <span class="text-muted d-block text-uppercase font-monospace mb-1" style="font-size: 0.6rem; letter-spacing: 1px;">Visitor Profile Class</span>
              <div id="view_visitor_type" class="text-muted font-monospace small">Category // Representative</div>
            </div>
          </div>

          <div class="col-6 ps-3 border-start border-light-subtle">
            <div class="mb-3">
              <span class="text-muted d-block text-uppercase font-monospace mb-1" style="font-size: 0.6rem; letter-spacing: 1px;">Host Target Assignment</span>
              <div id="view_department" class="text-dark fw-bold small text-wrap">Finance Treasury Branch</div>
            </div>
            <div>
              <span class="text-muted d-block text-uppercase font-monospace mb-1" style="font-size: 0.6rem; letter-spacing: 1px;">Session Vector Class</span>
              <div id="view_session_type" class="text-primary font-monospace small fw-bold">ACADEMIC_BOARD</div>
            </div>
          </div>
        </div>

        <!-- GRAPH TIMELINE INTERACTION LEVEL FLOW -->
        <div class="mb-4 pb-4 border-bottom border-light-subtle">
            <span class="text-muted d-block text-uppercase font-monospace mb-2" style="font-size: 0.6rem; letter-spacing: 1px;">Confirmed Timeline Metrics</span>
            <div class="d-flex align-items-center justify-content-between font-monospace text-dark py-1" style="font-size: 0.85rem;">
                <div><i class="bx bx-calendar me-1"></i>DATE: <strong id="view_meeting_date">01 JUL 2026</strong></div>
                <div class="border-start ps-3"><i class="bx bx-time me-1"></i>INTERVAL: <strong id="view_time_window">09:00 AM - 09:30 AM</strong></div>
            </div>
        </div>

        <!-- HARD AUDIT SYSTEM TRACKS LOG INFO -->
        <div class="row g-0 font-monospace text-muted align-items-center" style="font-size: 0.65rem; letter-spacing: 0.5px;">
          <div class="col-6">RECORDED_BY: <strong id="view_scheduler_name" class="text-dark fw-bold">SECRETARY_DESK</strong></div>
          <div class="col-6 text-end">CLOUD_MIRROR: <strong id="view_sync_status" class="text-dark fw-bold">[SYNCED]</strong></div>
        </div>

      </div>

      <div class="modal-footer border-0 px-0 pt-4 mt-3 border-top border-dark d-flex justify-content-end">
        <button type="button" class="btn btn-link p-0 text-dark fw-bold font-monospace text-uppercase shadow-none text-decoration-none" style="font-size: 0.65rem; letter-spacing: 1px;" data-bs-dismiss="modal">[ DISMISS ]</button>
      </div>

    </div>
  </div>
</div>

 <!-- =========================================================================
     MODAL LAYOUT: OFFICIAL APPOINTMENT SCHEDULING & APPROVAL CONTROLLER
     ========================================================================= -->
<div class="modal fade" id="appointmentApproveModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-md" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 8px;">
            <div class="modal-header border-bottom bg-success text-white">
                <h5 class="modal-title fw-bold text-white"><i class="bx bx-check-circle me-2 fs-4"></i>Schedule & Approve Meeting Slot</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form id="appointmentApproveForm" method="POST" autocomplete="off">
                <div class="modal-body p-4">
                    <!-- Hidden operational row table index identifier pointers -->
                    <input type="hidden" id="approve_appt_id" name="id" value="" />
                    <input type="hidden" name="approve_appointment_request" value="true" />

                    <!-- VISITOR NAME RUNTIME NOTIFICATION BANNER -->
                    <div class="alert alert-neutral p-3 border mb-3 rounded d-flex align-items-center gap-2 small fw-semibold text-dark bg-light">
                        <i class="bx bx-user text-success fs-4"></i>
                        <span>Approving Booking For: <strong id="approve_target_visitor" class="text-primary">Visitor Name</strong></span>
                    </div>

                    <!-- Row 1: Allocation Calendar Date Selector -->
                    <div class="row g-3 mb-3">
                        <div class="col-12">
                            <label for="approve_meeting_date" class="form-label small fw-bold text-uppercase text-heading text-success">Confirmed Meeting Date</label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="bx bx-calendar text-muted"></i></span>
                                <input type="date" id="approve_meeting_date" name="appointment_date" class="form-control fw-bold" value="<?php echo date('Y-m-d'); ?>" min="<?php echo date('Y-m-d'); ?>" required />
                            </div>
                            <small class="text-muted font-size-xs d-block mt-1">Select the official calendar date the Principal is free to host this meeting slot session.</small>
                        </div>
                    </div>

                    <!-- Row 2: Two-Column Precision Timeline Clocks Selector Matrix -->
                    <div class="row g-3">
                        <!-- Meeting Start Time Parameters -->
                        <div class="col-12 col-sm-6">
                            <label for="approve_start_time" class="form-label small fw-bold text-uppercase text-heading">Meeting Start Time</label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="bx bx-time-five text-muted"></i></span>
                                <input type="time" id="approve_start_time" name="start_time" class="form-control" value="09:00" required />
                            </div>
                        </div>
                        
                        <!-- Meeting Completion End Time Parameters -->
                        <div class="col-12 col-sm-6">
                            <label for="approve_end_time" class="form-label small fw-bold text-uppercase text-heading">Expected End Time</label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="bx bx-time text-muted"></i></span>
                                <input type="time" id="approve_end_time" name="end_time" class="form-control" value="09:30" required />
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer border-top bg-light py-3">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Abort</button>
                    <button type="submit" class="btn btn-success btn-sm px-4 fw-bold shadow-sm">
                        <i class="bx bx-badge-check me-1 fs-5"></i> Commit Meeting Schedule
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- =========================================================================
     MODAL LAYOUT: SEARCHABLE APPOINTMENT MANAGEMENT TERMINAL
     ========================================================================= -->
<div class="modal fade" id="addAppointmentModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 8px;">
            <div class="modal-header border-bottom bg-primary text-white">
                <h5 class="modal-title fw-bold text-white" id="modalCenterTitle">
                    <i class="bx bx-calendar-plus me-2 fs-4"></i>Log Appointment Booking Slot
                </h5>
                <button type="button" class="btn btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form id="appointmentSubmissionForm" method="POST" autocomplete="off">
                <div class="modal-body p-4">
                    <!-- Hidden Entry Key Identifier Primary Database Reference Key Pointer -->
                    <input type="hidden" id="id" name="id" value="" />

                    <!-- ROW 1: TWO-COLUMN VISITOR IDENTITY BIOMETRICS -->
                    <div class="row g-3 mb-3">
                        <!-- Visitor Full Name Text Field -->
                        <div class="col-12 col-sm-6">
                            <label for="visitor_name" class="form-label text-heading small fw-bold text-uppercase">Visitor Full Name</label>
                            <input type="text" id="visitor_name" name="visitor_name" class="form-control" placeholder="e.g. Dr. Kwaku Mensah" required />
                        </div>
                        
                        <!-- Visitor Contact Telephone Number Channel Field -->
                        <div class="col-12 col-sm-6">
                            <label for="visitor_phone" class="form-label text-heading small fw-bold text-uppercase">Visitor Telephone Number</label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="bx bx-phone text-muted"></i></span>
                                <input type="text" id="visitor_phone" name="visitor_phone" class="form-control" placeholder="e.g. +233 24 000 0000" required />
                            </div>
                        </div>
                    </div>

                    <!-- ROW 2: TWO-COLUMN RELATIONAL WORKFLOW CONFIGURATION CHOICE MATRICES -->
                    <div class="row g-3 mb-3">
                        <!-- Operational Profile Group Dropdown -->
                        <div class="col-12 col-sm-6">
                            <label for="visitor_type" class="form-label text-heading small fw-bold text-uppercase">Visitor Operational Profile</label>
                            <select id="visitor_type" name="visitor_type" class="form-select" required>
                                <option value="" disabled selected>-- Select Profile --</option>
                                <?php foreach($visitorTypeOptions as $opt): ?>
                                    <option value="<?php echo $opt; ?>"><?php echo $opt; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Session Relation Type Core Trigger Field Selector -->
                        <div class="col-12 col-sm-6">
                            <label for="appointment_type_id" class="form-label text-heading small fw-bold text-uppercase">Appointment Session Type</label>
                            <select id="appointment_type_id" name="appointment_type_id" class="form-select fw-semibold text-primary" required>
                                <option value="" disabled selected>-- Select Type Vector --</option>
                                <?php foreach($appointmentTypeOptions as $typeOpt): ?>
                                    <option value="<?php echo $typeOpt['id']; ?>"><?php echo htmlspecialchars($typeOpt['type_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- ROW 3: REUSABLE LOOP SELECTION FUNCTION FOR DEPARTMENTS (HIDDEN CONDITION CONTAINER) -->
                    <div class="row g-3 mb-3" id="department_form_row">
                        <div class="col-12 text-start">
                            <label for="department" class="form-label text-heading small fw-bold text-uppercase text-heading">Target Host Department Branch</label>
                            <div class="position-relative">
                                <select id="department" name="department" class="form-select text-dark fw-bold choices-department-select" required>
                                    <option value="" disabled selected>-- Type or Search Department Branch --</option>
                                    <?php 
                                    $departmentsList = getCollegeDepartmentsMap();
                                    foreach ($departmentsList as $valueKey => $displayLabel): 
                                    ?>
                                        <option value="<?php echo $valueKey; ?>"><?php echo $displayLabel; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- ROW 4: TARGET SYSTEM CALENDAR DATE BOOKING TIMELINE -->
                    <div class="row g-3 mb-3">
                        <div class="col-12">
                            <label for="appointment_date" class="form-label text-heading small fw-bold text-uppercase">Target Booking Calendar Date</label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="bx bx-calendar text-muted"></i></span>
                                <input type="date" id="appointment_date" name="appointment_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" min="<?php echo date('Y-m-d'); ?>" required />
                            </div>
                        </div>
                    </div> 

                    <!-- ROW 5: DETAILED TARGET PURPOSE AGENDA PARAGRAPH -->
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="purpose" class="form-label text-heading small fw-bold text-uppercase">Detailed Target Agenda / Purpose</label>
                            <textarea id="purpose" name="purpose" class="form-control" rows="3" placeholder="Provide description criteria detailing transaction metrics for principal appraisal..." required></textarea>
                        </div>
                    </div>
                </div>
                
                <!-- FOOTER INTERFACE BUTTON CONTROL DESK BAR -->
                <div class="modal-footer border-top bg-light py-3">
                    <button type="button" class="btn btn-outline-secondary btn-sm fw-semibold" data-bs-dismiss="modal">Cancel Execution</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold shadow-sm">
                        <i class="bx bx-calendar-check me-1 fs-5"></i> Save Booking Entry
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- =========================================================================
     SNEAT LAYOUT UI INTERACTION THEME STYLING SKIN FOR CHOICES.JS
     ========================================================================= -->
<style>
.choices__inner {
    background-color: #ffffff !important;
    border: 1px solid #d9dee3 !important;
    border-radius: 0.375rem !important;
    padding: 7px 14px 6px !important;
    min-height: 40px !important;
    font-size: 0.9375rem !important;
    text-align: left !important;
}
.choices__focus .choices__inner {
    border-color: #696cff !important;
    box-shadow: 0 0 0.25rem 0.05rem rgba(105, 108, 255, 0.25) !important;
}
.choices__list--dropdown {
    border: 1px solid #d9dee3 !important;
    border-radius: 0.375rem !important;
    box-shadow: 0 0.25rem 1rem rgba(161, 172, 184, 0.45) !important;
    text-align: left !important;
}
</style>


<script>

$(document).ready(function() {
  
      // =========================================================================
    // MODAL POPUP ACTION: RENDER MINIMALIST TYPOGRAPHIC APPOINTMENT DOSSIER
    // =========================================================================
    $(document).on('click', '.view-appointment-details-btn', function(e) {
        e.preventDefault();
        const row = $(this).data('entry');

        // Humanize dates for typographic display mapping
        const dateObj = new Date(row.appointment_date);
        const humanizedDate = dateObj.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }).toUpperCase();
        
        // 1. POPULATE UPPER STAT LAYER PARAMETERS
        $('#view_visitor_name').text(row.visitor_name.toUpperCase());
        $('#view_status_flag').text(`// ${row.status.toUpperCase()}`);
        $('#view_purpose').text(row.purpose);

        // 2. POPULATE STRUCTURAL ROUTING MATRICES
        $('#view_visitor_phone').text(`TEL // ${row.visitor_phone ? row.visitor_phone : 'NONE'}`);
        $('#view_visitor_type').text(`CATEGORY // ${row.visitor_type.toUpperCase()}`);
        
        const cleanDeptStr = row.department ? row.department.replace(/ /g, '_').toUpperCase() : 'N_A__OUTSIDE_GUEST';
        $('#view_department').text(cleanDeptStr);
        
        const cleanLabelStr = row.appointment_type_label ? row.appointment_type_label.replace(/ /g, '_').toUpperCase() : 'GENERAL_SESSION';
        $('#view_session_type').text(cleanLabelStr);

        // 3. POPULATE PRECISION WORKFLOW TIMELINE LOGS
        $('#view_meeting_date').text(humanizedDate);
        if (row.status === 'Pending') {
            $('#view_time_window').html('<span style="color: #ffab00;">AWAITING_ALLOCATION_SLOT</span>');
        } else {
            // Function blocks to extract clean custom AM/PM values out of the 24h timestamps strings safely
            const formatTimeStr = (tStr) => {
                if(!tStr) return '--:--';
                const parts = tStr.split(':');
                const hrs = parseInt(parts[0], 10);
                const ampm = hrs >= 12 ? 'PM' : 'AM';
                const displayHrs = hrs % 12 || 12;
                return `${displayHrs.toString().padStart(2, '0')}:${parts[1]} ${ampm}`;
            };
            $('#view_time_window').text(`${formatTimeStr(row.start_time)} - ${formatTimeStr(row.end_time)}`);
        }

        // 4. POPULATE TRACKING AUDIT FOOTPRINT REGISTERS
        $('#view_scheduler_name').text(row.scheduler_name ? row.scheduler_name.replace(/ /g, '_').toUpperCase() : 'SYSTEM_SYNC_NODE');
        $('#view_sync_status').text(`[${row.sync_status.toUpperCase()}]`);

        // Show our clean editorial modal layer canvas overlay framework
        $('#appointmentDetailsModal').modal('show');
    });


     // =========================================================================
    // SERVER SIDE TRANSACTION DISPATCH HOOK (DYNAMIC ASYNC FETCHING)
    // =========================================================================
    function executeAdvancedRegistryFiltration() {
        // Collect entire serialization data map and append your required flag rule
        const payloadData = $('#serverFiltrationForm').serialize() + '&fetch_filtered_appointments=true';

        // Display a clean, non-obtrusive inline loader inside the table layout space
        $('#appointment_table_body').html(`
            <tr>
                <td colspan="9" class="text-center py-5 text-primary">
                    <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                    Syncing live database metrics...
                </td>
            </tr>
        `);

        $.ajax({
            url: server_controller,
            type: 'POST',
            data: payloadData,
            dataType: 'json'
        }).then(response => {
            if (response.status === 'success') {
                // Pass raw array data straight into your reusable UI template renderer function
                renderAppointmentsTableUI(response.data); 
            } else {
                throw new Error(response.message || 'Execution extraction error.');
            }
        }).catch(error => {
            console.error("Fetch Failure:", error);
            $('#appointment_table_body').html(`
                <tr>
                    <td colspan="9" class="text-center py-5 text-danger fw-medium">
                        <i class="bx bx-error-circle d-block fs-3 mb-2"></i>
                        Database Fetch Error: ${error.message || 'Unable to update registry lines.'}
                    </td>
                </tr>
            `);
        });
    }

    // Trigger filters on structural dropdown select or date input option shifts
    $('#filter_from_date, #filter_to_date, #filter_status').on('change', function() {
        executeAdvancedRegistryFiltration();
    });

    // Throttled keyboard input listener for live text field phrase searches
    let inputDebounceTimer = null;
    $('#search_keyword').on('keyup', function() {
        clearTimeout(inputDebounceTimer);
        inputDebounceTimer = setTimeout(executeAdvancedRegistryFiltration, 300); // 300ms threshold delay
    });

    // Handle pristine clear actions
    $('#reset_filters_btn').on('click', function() {
        $('#serverFiltrationForm')[0].reset();
        executeAdvancedRegistryFiltration(); // Fires empty state, defaulting back to Today
    });

    
    
    // Global cache tracking variable to capture Choices lifecycle instantiations
let choicesDepartmentInstance = null;

$(document).ready(function() {
    
    // 1. Instantiation: Trigger type-to-search interface over selector container nodes
    const deptSelectElement = document.querySelector('.choices-department-select');
    if (deptSelectElement) {
        choicesDepartmentInstance = new Choices(deptSelectElement, {
            searchEnabled: true,
            searchChoices: true,
            removeItemButton: false,
            shouldSort: false,
            itemSelectText: 'Select branch',
            placeholder: true,
            placeholderValue: '-- Type or Search Department Branch --',
            classNames: { containerOuter: ['choices', 'mb-0'] } // Resolves DOMTokenList spacing error bugs
        });
    }

 

        // =========================================================================
    // CORE WORKFLOW SHIELD ENGINE: HIDE DEPARTMENT ROW IF VISITOR IS A PARENT OR OUTSIDER
    // =========================================================================
    function evaluateVisitorProfileDepartmentVisibility() {
        // Extract the plain string value option chosen from your visitor profile select box
        const visitorProfileValue = $('#visitor_type').val();
        const targetDeptRow       = $('#department_form_row');
        const deptSelectInput     = $('#department');

        console.log('Active Selected Visitor Operational Profile: ', visitorProfileValue);

        // Check conditions: If Parent or Outsider string blocks match, slide up the department select row
        if (visitorProfileValue === 'Parent' || visitorProfileValue === 'External Guest') {
            // Animate block hiding path smoothly
            targetDeptRow.slideUp(180);
            
            // Strip structural input requirement parameters to bypass form blocking
            deptSelectInput.removeAttr('required');
            
            // Flush outstanding choices completely out of active background array memory
            if (choicesDepartmentInstance) {
                choicesDepartmentInstance.removeActiveItems();
                choicesDepartmentInstance.setChoiceByValue('');
            }
        } else {
            // Visitor is standard personnel/ministry rep, reveal department selection dropdown choices
            targetDeptRow.slideDown(180);
            deptSelectInput.attr('required', 'required');
        }
    }

    // Bind instantaneous user select input change hooks click listener tracking triggers
    $(document).on('change', '#visitor_type', function() {
        console.log('visitor_type dropdown value has mutated');
        evaluateVisitorProfileDepartmentVisibility();
    });

    // =========================================================================
    // EDIT TRANSITION HANDSHAKE: INJECT METADATA VALUES & FORCE EVALUATIONS LOOP
    // =========================================================================
        // Inside your dynamic '.edit-appointment-btn' click event listener:
    $(document).on('click', '.edit-appointment-btn', function(e) {
        e.preventDefault();
        const data = $(this).data('entry'); 
        
        $('#appointmentSubmissionForm #id').val(data.id);
        $('#appointmentSubmissionForm #visitor_name').val(data.visitor_name);
        $('#appointmentSubmissionForm #visitor_phone').val(data.visitor_phone);
        
        // Inject values straight into the corrected profile element container
        $('#appointmentSubmissionForm #visitor_type').val(data.visitor_type);
        
        $('#appointmentSubmissionForm #appointment_type_id').val(data.appointment_type_id);
        $('#appointmentSubmissionForm #appointment_date').val(data.appointment_date);
        $('#appointmentSubmissionForm #purpose').val(data.purpose);

        if (choicesDepartmentInstance && data.department) {
            choicesDepartmentInstance.setChoiceByValue(data.department.toString());
        }

        // FORCE CONDITIONAL EVALUATION IMMEDIATELY BASED ON THE INJECTED VISITOR VALUE PROFILE
        evaluateVisitorProfileDepartmentVisibility();

        $('#modalCenterTitle').html('<i class="bx bx-edit-alt me-2 fs-4"></i>Modify Appointment Booking Properties');
        $('#addAppointmentModal').modal('show');
    });

    // =========================================================================
    // MODAL DISMISSAL CLEANUPS: RESTORE PRISTINE FORM BOUNDARY CONFIGURATIONS
    // =========================================================================
    $('#addAppointmentModal').on('hidden.bs.modal', function () {
        // Reset underlying form element parameters nodes natively using direct index pointers
        $('#appointmentSubmissionForm')[0].reset();
        $('#appointmentSubmissionForm #id').val('');
        
        if (choicesDepartmentInstance) {
            choicesDepartmentInstance.removeActiveItems();
            choicesDepartmentInstance.setChoiceByValue('');
        }
        
        // Restore standard default visibility layout tracks properties on close dismiss actions
        $('#department_form_row').show();
        $('#department').attr('required', 'required');
        
        $('#modalCenterTitle').html('<i class="bx bx-calendar-plus me-2 fs-4"></i>Log Appointment Booking Slot');
        
        // Fallback calendar configuration date parameter synchronization
        const todayDateString = new Date().toISOString().split('T')[0];
        $('#appointmentSubmissionForm #appointment_date').val(todayDateString);
    });
});

   
   
       // =========================================================================
    // MODAL SUBMISSION: QUEUE LOG ENTRY FORM HANDLER (CLEAN INTEGRATION)
    // =========================================================================
    $('#appointmentSubmissionForm').on('submit', function(e) {
        e.preventDefault(); 

        Swal.fire({
            title: 'Commit Schedule Record Changes?',
            text: "Are you sure you want to log these metadata entries into the master system database register?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#696cff',
            cancelButtonColor: '#8592a3',
            confirmButtonText: 'Yes, save changes',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                // Automatically serialize form fields and append the save_appointment flag parameter
                return $.ajax({
                    url: server_controller,
                    type: 'POST',
                    data: $('#appointmentSubmissionForm').serialize() + '&save_appointment=true',
                    dataType: 'json'
                }).then(response => {
                    if (response.status !== 'success') throw new Error(response.message);
                    return response;
                }).catch(error => { 
                    Swal.showValidationMessage(`Transaction Failure: ${error.message || 'Connection timeout.'}`); 
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                // Dismiss the structural Bootstrap overlay modal container
                $('#addAppointmentModal').modal('hide');
                
                // Reset standard HTML input form fields using the native DOM node pointer
                $('#appointmentSubmissionForm')[0].reset();

                // FIXED Lifecycle Sync: Clear Choices search tracking states back to defaults
                if (choicesDepartmentInstance) {
                    choicesDepartmentInstance.removeActiveItems(); // Wipes previous selections
                    choicesDepartmentInstance.setChoiceByValue(''); // Restores clean blank baseline state
                }

                // Restore default visibilities for standard workflow department row states
                $('#department_form_row').show();
                $('#department').attr('required', 'required');

                // CALL THE REUSABLE FUNCTION TO UPDATE THE UI TIMELINE CARDS MATRIX INSTANTLY
                renderAppointmentsTableUI(result.value.data);

                // Dispatch structural confirmation success toast
                Swal.fire({
                    title: 'Saved! 🎉',
                    text: result.value.message,
                    icon: 'success',
                    confirmButtonColor: '#696cff'
                });
            }
        });
    });

    // =========================================================================
    // APPOINTMENTS WIPER: HARD DROP HANDLER OVER AJAX (`delete_appointment=true`)
    // =========================================================================
    $(document).on('click', '.delete-appointment-btn', function(e) {
        e.preventDefault();
        const appointmentId = $(this).data('id');
        const visitorNameStr = $(this).data('visitor');

        Swal.fire({
            title: 'Wipe Appointment Booking?',
            html: `Are you sure you want to permanently erase the schedule slot for <strong>${visitorNameStr}</strong>? <br><small class="text-danger fw-semibold">This will archive a JSON snapshot and log a hard deletion for cloud synchronization.</small>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ff3e1d', // Sneat Danger Red
            cancelButtonColor: '#8592a3',
            confirmButtonText: 'Yes, wipe record',
            cancelButtonText: 'Abort',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return $.ajax({
                    url: server_controller,
                    type: 'POST',
                    data: { id: appointmentId, delete_appointment: 'true' },
                    dataType: 'json'
                }).then(res => {
                    if (res.status !== 'success') throw new Error(res.message);
                    return res;
                }).catch(err => { 
                    Swal.showValidationMessage(`Operation Fault: ${err.message || 'Connection lost.'}`); 
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then(result => {
            if (result.isConfirmed && result.value) {
                // Call your frontend function to repaint the cards/table body row grid instantly
                renderAppointmentsTableUI(result.value.data);
                
                Swal.fire({
                    title: 'Wiped Clean! 🗑️',
                    text: 'The schedule record has been dropped locally, backed up into the JSON archive, and synced to the outbound cloud delete ledger.',
                    icon: 'success',
                    confirmButtonColor: '#696cff'
                });
            }
        });
    });


    // =========================================================================
    // APPOINTMENT APPROVAL INTERCEPT: CAPTURE AND LAUNCH SCHEDULING CONSOLE
    // =========================================================================
    $(document).on('click', '.update-status-btn[data-status="Approved"]', function(e) {
        e.preventDefault();
        
        const targetId = $(this).data('id');
        const parentRowNode = $(this).closest('tr');
        
        // Dynamic Extraction: Scrape the visitor's full name from your table cell text nodes elements
        // Update the jQuery selector path '.visitor-name-cell' to match your explicit text name location
        const visitorNameText = parentRowNode.find('.visitor-name-cell').text().trim() || 'Scheduled Guest';

        // Pre-populate input definitions layout properties states
        $('#approve_appt_id').val(targetId);
        $('#approve_target_visitor').text(visitorNameText);
        
        // Sync default calendar target path parameters natively back to current timeline values
        const currentTodayStr = new Date().toISOString().split('T')[0];
        $('#approve_meeting_date').val(currentTodayStr);
        $('#approve_meeting_date').attr('min', currentTodayStr); // Blocks retro-active allocation selections

        // Open the secondary scheduler confirmation window frame cleanly
        $('#appointmentApproveModal').modal('show');
    });

    // =========================================================================
    // SCHEDULER SUBMISSION ENGINE (`approve_appointment_request=true`)
    // =========================================================================
    $('#appointmentApproveForm').on('submit', function(e) {
        e.preventDefault();
        const executionForm = this;

        Swal.fire({
            title: 'Confirm Meeting Schedule?',
            text: "This authorizes the booking request, allocates the chosen slot parameters, and marks the appointment as Approved.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#71dd37', // Sneat Success Green Color Hex code
            cancelButtonColor: '#8592a3',
            confirmButtonText: 'Yes, Confirm Meeting Slot',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return $.ajax({
                    url: server_controller,
                    type: 'POST',
                    data: $(executionForm).serialize(), // Automatically packs (id, appointment_date, start_time, end_time)
                    dataType: 'json'
                }).then(res => {
                    if (res.status !== 'success') throw new Error(res.message);
                    return res;
                }).catch(err => { 
                    Swal.showValidationMessage(`Schedule Fault: ${err.message || 'Connection timeout.'}`); 
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then(result => {
            if (result.isConfirmed && result.value) {
                // Clear input tracking buffers and dismiss overlay boundaries frames
                $('#appointmentApproveModal').modal('hide');
                executionForm.reset();

                // Repaint administrative appointments tracking timeline cards instantly using returned updated dataset array
                renderAppointmentsTableUI(result.value.data);
                
                Swal.fire({
                    title: 'Schedule Confirmed! 📅',
                    text: 'The appointment has successfully shifted to an Approved meeting slot state.',
                    icon: 'success',
                    confirmButtonColor: '#696cff'
                });
            }
        });
    });



       // =========================================================================
    // DYNAMIC TRIGGER: ACTIONS DROP-DOWN STATE MODIFICATION HANDLER (EXCLUDES APPROVAL)
    // =========================================================================
    $(document).on('click', '.update-status-btn[data-status!="Approved"]', function(e) {
        e.preventDefault();
        
        const apptId = $(this).data('id');
        const targetStatus = $(this).data('status');
        
        // Define theme colors and descriptive labels context aware
        let contextColor = '#696cff'; // Fallback Sneat Primary Blue
        let dynamicPrompt = `Are you sure you want to change this record's state to <strong>${targetStatus}</strong>?`;
        
        if (targetStatus === 'Rejected') {
            contextColor = '#ff3e1d'; // Sneat Danger Crimson Red
            dynamicPrompt = "Are you sure you want to <strong>Decline this Meeting Request</strong>?";
        } else if (targetStatus === 'Completed') {
            contextColor = '#03c3ec'; // Sneat Info Sky Blue
            dynamicPrompt = "Do you want to mark this transaction session as <strong>Concluded & Concluded</strong>?";
        }

        Swal.fire({
            title: 'Modify Allocation Status?',
            html: dynamicPrompt,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: contextColor,
            cancelButtonColor: '#8592a3',
            confirmButtonText: `Yes, commit change`,
            cancelButtonText: 'Abort request',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return $.ajax({
                    url: server_controller,
                    type: 'POST',
                    data: {
                        id: apptId,
                        status: targetStatus,
                        update_appointment_status: 'true' // Explicitly maps to Branch X backend handler
                    },
                    dataType: 'json'
                }).then(response => {
                    if (response.status !== 'success') {
                        throw new Error(response.message || 'State validation loop failure.');
                    }
                    return response;
                }).catch(error => {
                    Swal.showValidationMessage(`Operation Rejected: ${error.message || 'Connection timeout.'}`);
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                // Call the frontend table re-renderer to paint fresh dataset row states instantly
                renderAppointmentsTableUI(result.value.data);
                
                Swal.fire({
                    title: 'Status Modified! 🟢',
                    text: 'The principal schedule registry timeline has updated successfully.',
                    icon: 'success',
                    confirmButtonColor: '#696cff'
                });
            }
        });
    });

});



/**
 * Asynchronously repaints the appointment list view elements cleanly 
 * following a database mutation response to preserve minimal look parameters.
 * 
 * @param {Array} appointmentsList Fresh array data packets list from server response
 */
function renderAppointmentsTableUI(appointmentsList) {
    let htmlOutput = '';
    
    if (!appointmentsList || appointmentsList.length === 0) {
        htmlOutput = `
        <tr>
            <td colspan="6" class="text-center py-5 text-muted font-monospace" style="font-size: 0.75rem;">
                <i class="bx bx-calendar-x d-block fs-1 mb-2"></i> [ NO_ACTIVE_APPOINTMENT_REQUEST_QUEUES_LOADED ]
            </td>
        </tr>`;
    } else {
        let sn = 1;
        const sessionRoleClearance = '<?php echo $_SESSION['role']; ?>';
        
                $.each(appointmentsList, function(index, row) {
            // FIXED: Migrated from PHP shorthand "elseif" to correct JavaScript "else if" syntax
            let badgeColor = 'secondary';
            if (row.status === 'Pending') {
                badgeColor = 'warning';
            } else if (row.status === 'Approved') {
                badgeColor = 'success';
            } else if (row.status === 'Completed') {
                badgeColor = 'info';
            } else if (row.status === 'Rejected') {
                badgeColor = 'danger';
            }

            // Humanize date properties configurations cleanly
            const dateObj = new Date(row.appointment_date);
            const humanizedDate = dateObj.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });

  

            // Format standard clock loops metrics contextually
            let timelineHTML = '';
            if (row.status === 'Pending') {
                timelineHTML = `<small class="text-warning font-size-xs mt-1 italic"><i class="bx bx-time-five me-1"></i>Awaiting Allocation Slot</small>`;
            } else {
                const formatTimeStr = (tStr) => {
                    if(!tStr) return '--:--';
                    const parts = tStr.split(':');
                    const hrs = parseInt(parts[0], 10);
                    const ampm = hrs >= 12 ? 'PM' : 'AM';
                    const displayHrs = hrs % 12 || 12;
                    return `${displayHrs.toString().padStart(2, '0')}:${parts[1]} ${ampm}`;
                };
                timelineHTML = `<small class="text-muted font-monospace font-size-xs mt-1"><i class="bx bx-time me-1"></i>${formatTimeStr(row.start_time)} - ${formatTimeStr(row.end_time)}</small>`;
            }

            // Clean department branch labels display maps logic parameters
            const deptText = row.department ? row.department : '<span class="text-muted italic">[ N/A - OUTSIDE_GUEST ]</span>';
            const labelText = row.appointment_type_label ? row.appointment_type_label : 'General';
            const phoneText = row.visitor_phone ? row.visitor_phone : 'NONE';

            // Compile dynamic management link items fields parameters definitions
            let editLinkHTML = '';
            if (row.status === 'Pending') {
                editLinkHTML = `
                <a class="dropdown-item d-flex align-items-center text-primary edit-appointment-btn" href="javascript:void(0);" data-entry="${JSON.stringify(row).replace(/"/g, '&quot;')}">
                    <i class="bx bx-edit-alt me-2"></i> Edit Record
                </a>`;
            }

            let receptionActionsHTML = '';
            if ((sessionRoleClearance === 'Principal' || sessionRoleClearance === 'Secretary') && row.status === 'Pending') {
                receptionActionsHTML = `
                <a class="dropdown-item d-flex align-items-center text-success update-status-btn" href="javascript:void(0);" data-id="${row.id}" data-status="Approved"><i class="bx bx-check-circle me-2"></i> Approve Request</a>
                <a class="dropdown-item d-flex align-items-center text-danger update-status-btn" href="javascript:void(0);" data-id="${row.id}" data-status="Rejected"><i class="bx bx-x-circle me-2"></i> Reject Request</a>`;
            }

            let activeSessionActionsHTML = '';
            if ((sessionRoleClearance === 'Principal' || sessionRoleClearance === 'Secretary') && row.status === 'Approved') {
                activeSessionActionsHTML = `
                <a class="dropdown-item d-flex align-items-center text-info update-status-btn" href="javascript:void(0);" data-id="${row.id}" data-status="Completed"><i class="bx bx-badge-check me-2"></i> Mark Completed</a>`;
            }

            let deletionActionLinkHTML = '';
            if (sessionRoleClearance === 'Principal' || sessionRoleClearance === 'Secretary') {
                deletionActionLinkHTML = `
                <div class="dropdown-divider"></div>
                <a class="dropdown-item d-flex align-items-center text-danger delete-appointment-btn" href="javascript:void(0);" data-id="${row.id}" data-visitor="${row.visitor_name.replace(/"/g, '&quot;')}">
                    <i class="bx bx-trash me-2"></i> Wipe Appointment
                </a>`;
            }

            const jsonStringEscaped = JSON.stringify(row).replace(/"/g, '&quot;');

            htmlOutput += `
            <tr class="appointment-row" data-date="${row.appointment_date}" data-type="${row.appointment_type_id}" data-status="${row.status}">
                <td>${sn++}</td>
                <td class="visitor-name-cell-wrapper">
                    <div class="d-flex flex-column">
                        <span class="fw-bold text-dark mb-0 visitor-name-cell">${row.visitor_name}</span>
                        <small class="text-muted font-monospace font-size-xs mt-1"><i class="bx bx-phone me-1 font-size-xs"></i>${phoneText}</small>
                        <small class="text-secondary fw-semibold font-size-xs mt-1"><i class="bx bx-buildings me-1 font-size-xs"></i>Dept: ${deptText}</small>
                    </div>
                </td>
                <td><span class="badge bg-label-primary fw-bold font-size-xs text-uppercase"><i class="bx bx-bookmark-alt-minus me-1"></i>${labelText}</span></td>
                <td>
                    <div class="d-flex flex-column">
                        <span class="fw-bold text-heading mb-0 small"><i class="bx bx-calendar-event me-1 text-primary"></i>${humanizedDate}</span>
                        ${timelineHTML}
                    </div>
                </td>
                <td><span class="badge bg-label-${badgeColor} font-weight-bold text-uppercase px-2 py-1">${row.status}</span></td>
                <td class="text-center">
                    <div class="dropdown">
                        <button type="button" class="btn btn-sm btn-icon dropdown-toggle hide-arrow p-0" data-bs-toggle="dropdown"><i class="bx bx-dots-vertical-rounded fs-5"></i></button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a class="dropdown-item d-flex align-items-center text-secondary view-appointment-details-btn mb-1 fw-bold" href="javascript:void(0);" data-entry="${jsonStringEscaped}"><i class="bx bx-show me-2 fs-5 text-secondary"></i> View Full Details</a>
                            <div class="dropdown-divider"></div>
                            ${editLinkHTML}
                            ${receptionActionsHTML}
                            ${activeSessionActionsHTML}
                            ${deletionActionLinkHTML}
                        </div>
                    </div>
                </td>
            </tr>`;
        });
    }
    $('#appointment_table_body').html(htmlOutput);
}

 


</script>

<?php 
// Include Sneat layout closing tags and default core scripts 
include_once 'includes/footer.php'; 
?>