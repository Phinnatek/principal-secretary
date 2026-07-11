<?php
try {
    // 1. Load Sneat Header (Includes Auth Session Initialization & $con Context)
    require_once 'includes/header.php'; 
    if (!$con) {
        throw new Exception('Database link verification dropped.');
    }

    $userRole = $_SESSION['role'] ?? '';
    $userId   = $_SESSION['user_id'] ?? 0;
 
    // 2. Extract Document Dataset Packages Envelope Contracts Safely
    $datasetResponse = getDocumentsDashboardDataset($con);
    if ($datasetResponse['status'] === 'error') {
        throw new Exception("Dataset Retrieval Error: " . $datasetResponse['message']);
    }
    
    // Unpack array data stream for view tables loops
    $documentsList = $datasetResponse['documents_list'] ?? [];

    // 3. Static Status Array Options & Contextual Sneat Badge Colors Mapping
    $docStatusOptions = ['Pending Review', 'Signed / Approved', 'Dispatched', 'Archived'];

    $statusBadges = [
        'Pending Review'    => 'warning',
        'Signed / Approved' => 'success',
        'Dispatched'        => 'info',
        'Archived'          => 'secondary'
    ];
    
    // 4. Load Master Institutional Index Code Catalog Mapping Array Matrix
    $masterFileIndex = getInstitutionalFileIndexMap();

} catch (Throwable $e) {
    error_log('Document UI Module Crash: ' . $e->getMessage());
    echo '<div class="alert alert-danger m-4 shadow-sm fw-semibold"><i class="bx bx-error-circle me-1"></i>System Framework Failure: ' . htmlspecialchars($e->getMessage()) . '</div>'; 
    exit;
} ?>

 
<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Office /</span> Document Tracking Registry</h4>

        <!-- Main Managed Registry Card -->
                 <!-- ============================================================
             DYNAMIC FILTRATION TOOLBAR PANEL (DOCUMENT TRACKING VECTOR)
             ============================================================ -->
        <div class="card mb-4 shadow-sm border-0">
            <div class="card-body">
                <form id="serverDocumentFilterForm" autocomplete="off">
                    <div class="row g-3">
                        
                        <!-- Global Live Text Search Search Field -->
                        <div class="col-12 col-md-3">
                            <label class="form-label small fw-bold text-uppercase text-heading">Keyword Search</label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="bx bx-search text-muted"></i></span>
                                <input type="text" name="search_keyword" id="search_keyword" class="form-control" placeholder="Search tracking #, title, sender..." />
                            </div>
                        </div>

                        <!-- From Range Boundary Date Field -->
                        <div class="col-12 col-sm-6 col-md-2">
                            <label class="form-label small fw-bold text-uppercase text-heading">From Date</label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="bx bx-calendar-alt text-muted"></i></span>
                                <input type="date" name="filter_from_date" id="filter_from_date" class="form-control" />
                            </div>
                        </div>

                        <!-- To Range Boundary Date Field -->
                        <div class="col-12 col-sm-6 col-md-2">
                            <label class="form-label small fw-bold text-uppercase text-heading">To Date</label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="bx bx-calendar-check text-muted"></i></span>
                                <input type="date" name="filter_to_date" id="filter_to_date" class="form-control" />
                            </div>
                        </div>
                        
                        <!-- Status Classification Selector Selection Box -->
                        <div class="col-12 col-sm-6 col-md-3">
                            <label class="form-label small fw-bold text-uppercase text-heading">Registry Status</label>
                            <select name="filter_status" id="filter_status" class="form-select fw-medium">
                                <option value="">-- All Status Profiles --</option>
                                <?php foreach ($docStatusOptions as $status): ?>
                                    <option value="<?php echo $status; ?>"><?php echo $status; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Reset Action State Buttons -->
                        <div class="col-12 col-sm-6 col-md-2 d-grid align-self-end">
                            <button type="button" id="reset_doc_filters_btn" class="btn btn-outline-secondary d-flex align-items-center justify-content-center gap-1">
                                <i class="bx bx-refresh fs-4"></i> Clear All
                            </button>
                        </div>

                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header border-bottom d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0 fw-semibold text-primary">Inward & Outward Document Ledger</h5>
                <!-- ============================================================
     REGISTRY TRIGGER: BROADCAST BUTTON DESK FOR DOCK DIALOGS
     ============================================================ -->
<button type="button" 
        class="btn btn-primary btn-sm fw-bold d-flex align-items-center gap-1 shadow-sm px-3 py-2" 
        data-bs-toggle="modal" 
        data-bs-target="#documentSubmissionModal">
    <i class="bx bx-plus-circle fs-5"></i> 
    <span>Log New Document Entry</span>
</button>
 
            </div>
            
            <div class="table-responsive text-nowrap card-body">
               <!-- =========================================================================
     INSTITUTIONAL REGISTRY MASTER LEDGER DATA REPRESENTATION MATRIX
     ========================================================================= -->
<table class="table table-striped table-hover align-middle mb-0" id="DataTable">
    <thead class="table-light text-uppercase font-size-sm">
        <tr>
            <th style="width: 50px;">S/N</th>
            <th>Tracking ID / Title</th> 
            <th>Sender Agency</th>
            <th>Recipient Desk</th>
            <th>Date Metrics</th>
            <th>Status</th>
            <th>Logged By</th>
            <th class="text-center" style="width: 80px;">Actions</th>
        </tr>
    </thead>
    <tbody id="document_table_body" class="table-border-bottom-0">
        <?php if (!empty($documentsList)): $sn = 1;
            foreach ($documentsList as $row): ?>
            <!-- Factored dynamic row identifiers to match asynchronous frontend filtering parameters -->
            <tr class="document-row" data-date="<?php echo $row['received_dispatched_date']; ?>" data-status="<?php echo htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8'); ?>">
                <td><?php echo $sn++; ?></td>
                
                <!-- COLUMN 2: Tracking Number, Category Index Name and Subject Matter Title -->
                <td>
                    <div class="d-flex flex-column">
                        <span class="fw-bold text-primary mb-0" style="font-family: monospace; letter-spacing: 0.5px;">
                            <?php echo htmlspecialchars($row['document_number']); ?>
                        </span>
                        <small class="text-muted font-size-xs fw-semibold mb-1">
                            📂 Index: <?php echo htmlspecialchars($row['file_index_category_name'] ?? 'General Document'); ?>
                        </small>
                        <small class="text-heading fw-bold text-truncate" style="max-width: 260px;" title="<?php echo htmlspecialchars($row['title']); ?>">
                            <?php echo htmlspecialchars($row['title']); ?>
                        </small>
                    </div>
                </td> 
                
                
                <td>
                    <div class="d-flex flex-column">
                        <span class="fw-medium text-dark small"><?php echo htmlspecialchars($row['sender_name']); ?></span>
                        <?php if (!empty($row['sender_phone'])): ?>
                            <small class="text-muted font-size-xs mt-1">
                                <i class="bx bx-phone me-1 font-size-xs"></i><?php echo htmlspecialchars($row['sender_phone']); ?>
                            </small>
                        <?php endif; ?>
                    </div>
                </td>
                
                <!-- COLUMN 4: Target Recipient Person / Desk Officer Designation (WORKFLOW CONDITIONAL) -->
                <td>
                    <?php if ($row['status'] === 'Pending Review'): ?>
                        <!-- Document is still locked inside central registry review cycle -->
                        <span class="badge bg-label-warning text-uppercase small fw-bold font-size-xs tracking-wider">
                            <i class="bx bx-pause-circle me-1"></i>Under Review
                        </span>
                        <small class="text-muted d-block mt-1 font-size-xs italic">Not Issued From Registry</small>
                    <?php else: ?>
                        <!-- Document has passed review boundaries, expose clear recipient routing desk -->
                        <span class="text-dark fw-bold small">
                            <i class="bx bx-user-voice text-secondary me-1 text-none"></i>
                            <?php echo !empty($row['receiver_name']) ? htmlspecialchars($row['receiver_name']) : 'General Registry Desk'; ?>
                        </span>
                    <?php endif; ?>
                </td>
                
                <!-- COLUMN 5: Double Calendar Timestamps Matrix (Registry Date vs Actual Delivery Date) -->
                <td>
                    <div class="d-flex flex-column">
                        <small class="text-heading font-size-xs fw-semibold" title="Registry Action Date">
                            <i class="bx bx-export me-1 text-primary font-size-xs"></i>Reg: <?php echo date('d M, Y', strtotime($row['received_dispatched_date'])); ?>
                        </small>
                        
                        <?php if ($row['status'] === 'Pending Review'): ?>
                            <!-- Suppress arrival calendar indicators until document is released out -->
                            <small class="text-muted font-size-xs mt-1 italic">
                                <i class="bx bx-time-five me-1 font-size-xs"></i>Awaiting Dispatch
                            </small>
                        <?php elseif (!empty($row['received_date'])): ?>
                            <small class="text-success font-size-xs mt-1" title="Physical Arrival Date">
                                <i class="bx bx-import me-1 text-success font-size-xs"></i>Rec: <?php echo date('d M, Y', strtotime($row['received_date'])); ?>
                            </small>
                        <?php else: ?>
                            <small class="text-muted font-size-xs mt-1 italic"><i class="bx bx-minus-circle me-1 font-size-xs"></i>No Arrival Date</small>
                        <?php endif; ?>
                    </div>
                </td>
                
                <!-- COLUMN 6: Core Verification Status Badge Indicator -->
                <td>
                    <span class="badge bg-label-<?php echo $statusBadges[$row['status']] ?? 'secondary'; ?> font-weight-bold px-2 py-1">
                        <?php echo $row['status']; ?>
                    </span>
                </td>
                
                
                <td>
                    <small class="text-muted fw-semibold"><?php echo htmlspecialchars($row['recorder_name'] ?? 'System Process'); ?></small>
                </td>
                
                <!-- COLUMN 8: Actions Dropdown Control Bar Options -->
                <td class="text-center">
                    <div class="dropdown">
                        <button type="button" class="btn btn-sm btn-icon dropdown-toggle hide-arrow p-0" data-bs-toggle="dropdown">
                            <i class="bx bx-dots-vertical-rounded fs-5"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <!-- NEW ACTION ELEMENT: INJECTED HIGHEST PRIORITY VIEW MORE OVERLAY LINK -->
                            <a class="dropdown-item d-flex align-items-center text-secondary view-document-details-btn mb-1 fw-bold" href="javascript:void(0);" data-entry="<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8'); ?>">
                                <i class="bx bx-show me-2 fs-5 text-secondary"></i> View Full Details
                            </a>

                            <div class="dropdown-divider"></div>

                            <!-- METADATA MODIFICATION LINK TRIGGER -->
                            <a class="dropdown-item d-flex align-items-center text-primary edit-document-btn" href="javascript:void(0);" data-entry="<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8'); ?>">
                                <i class="bx bx-edit-alt me-2"></i> Edit Metadata
                            </a>
                            
                            <!-- CONDITIONAL MANAGEMENT SIGN-OFF CLEARANCE LINKS -->
                            <?php if (in_array($userRole, ['Principal', 'Secretary']) && $row['status'] === 'Pending Review'): ?>
                                <a class="dropdown-item d-flex align-items-center text-success change-doc-status-btn" href="javascript:void(0);" data-id="<?php echo $row['id']; ?>" data-status="Signed / Approved">
                                    <i class="bx bx-check-shield me-2"></i> Approve & Sign
                                </a>
                            <?php endif; ?>
                            
                            <div class="dropdown-divider"></div>
                            
                            <!-- ATTACHMENT ACCESS TRACK CHANNELS -->
                                                        <!-- ATTACHMENT ACCESS TRACK CHANNELS -->
                            <?php if (!empty($row['file_path'])): ?>
                                <!-- Standard Online Preview Link -->
                                <a class="dropdown-item d-flex align-items-center text-info mb-1" href="<?php echo htmlspecialchars($row['file_path']); ?>" target="_blank">
                                    <i class="bx bx-show-alt me-2"></i> View Scanned File
                                </a>
                                <!-- Native Download Forcing Link -->
                                <a class="dropdown-item d-flex align-items-center text-success mb-1" href="<?php echo htmlspecialchars($row['file_path']); ?>" download="Doc_<?php echo htmlspecialchars($row['document_number']); ?>">
                                    <i class="bx bx-cloud-download me-2"></i> Download Attachment
                                </a>
                            <?php else: ?>
                                <a class="dropdown-item d-flex align-items-center text-muted disabled mb-1" href="javascript:void(0);">
                                    <i class="bx bx-hide me-2"></i> No Scan Attached
                                </a>
                            <?php endif; ?>
                            
                            <!-- SECURE DIRECT DELETION ACTION INTERFACE DROPDOWN LINK -->
                            <?php if (in_array($userRole, ['Principal', 'Secretary'])): ?>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item d-flex align-items-center text-danger delete-document-btn" href="javascript:void(0);" data-id="<?php echo $row['id']; ?>" data-number="<?php echo htmlspecialchars($row['document_number']); ?>">
                                    <i class="bx bx-trash me-2"></i> Wipe Ledger Record
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
 
 
 
 <!-- =========================================================================
     MODAL LAYOUT: REDESIGNED TYPOGRAPHIC TIMELINE DOSSIER VIEW
     ========================================================================= -->
<div class="modal fade" id="documentDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 8px;">
            
            <!-- Minimalist Sleek Top Bar -->
            <div class="modal-header border-bottom bg-light px-4 py-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="bx bx-file text-primary fs-4"></i>
                    <h5 class="modal-title fw-bold text-dark mb-0" style="letter-spacing: -0.3px;">Document Dossier Review</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4 bg-white">
                <!-- BLOCK 1: MASTER METADATA BANNER -->
                <div class="d-flex align-items-baseline justify-content-between mb-4 pb-2 border-bottom">
                    <div>
                        <span class="text-muted text-uppercase tracking-wider font-monospace d-block" style="font-size: 0.65rem;">System Reference ID</span>
                        <h4 id="view_document_number" class="text-dark font-monospace fw-bold mt-1 mb-0">#SDACOE-00-0000</h4>
                    </div>
                    <div>
                        <span id="view_status_badge" class="badge bg-label-primary text-uppercase font-weight-bold tracking-wider" style="font-size: 0.7rem; padding: 6px 12px;">Pending Review</span>
                    </div>
                </div>

                <!-- BLOCK 2: THE SUBJECT MATTER DISPATCH -->
                <div class="mb-4">
                    <span class="text-muted text-uppercase tracking-wider font-monospace d-block mb-1" style="font-size: 0.65rem;">Subject / Title Matter</span>
                    <p id="view_title" class="text-heading fw-semibold m-0 text-wrap lh-base" style="font-size: 1.05rem; letter-spacing: -0.1px;">Notice text placeholder line container string</p>
                </div>

                <!-- BLOCK 3: FILE STORAGE INDEX CONTAINER -->
                <div class="mb-4 p-3 bg-light rounded border border-dashed">
                    <span class="text-muted text-uppercase tracking-wider font-monospace d-block mb-1" style="font-size: 0.65rem;">Archive Index Classification Location</span>
                    <div class="d-flex align-items-center gap-2 mt-1">
                        <i class="bx bx-folder-open text-warning fs-4"></i>
                        <span id="view_file_index" class="text-dark fw-bold small text-wrap">General Document</span>
                    </div>
                </div>

                <!-- BLOCK 4: THE LIVE ROUTING TIMELINE GRAPH FLOW -->
                <div class="mt-4 pt-2">
                    <span class="text-muted text-uppercase tracking-wider font-monospace d-block mb-3" style="font-size: 0.65rem;">Document Routing Trail Milestones</span>
                    
                    <div class="position-relative ps-4 border-start border-2 ms-2" style="border-color: #e4e6eb !important;">
                        
                        <!-- STEP A: ORIGINATION STEP -->
                        <div class="mb-4 position-relative">
                            <!-- Bullet Indicator Node -->
                            <div class="position-absolute bg-primary rounded-circle border border-white" style="width: 12px; height: 12px; left: -23px; top: 4px; border-width: 2px !important;"></div>
                            
                            <span class="text-muted font-monospace text-uppercase d-block" style="font-size: 0.65rem;">01 // Originating Sender Agency</span>
                            <h6 id="view_sender_name" class="text-dark fw-bold mb-1 small mt-1">Regional Ministry Office</h6>
                            <small id="view_sender_phone" class="text-muted font-monospace d-flex align-items-center gap-1" style="font-size: 0.7rem;">
                                <i class="bx bx-phone font-size-xs"></i>+233 24 000 0000
                            </small>
                        </div>

                        <!-- STEP B: DESTINATION STEP -->
                        <div class="position-relative">
                            <!-- Bullet Indicator Node -->
                            <div class="position-absolute bg-success rounded-circle border border-white" style="width: 12px; height: 12px; left: -23px; top: 4px; border-width: 2px !important;"></div>
                            
                            <span class="text-muted font-monospace text-uppercase d-block" style="font-size: 0.65rem;">02 // Released Destination Recipient Desk</span>
                            <h6 id="view_receiver_name" class="text-dark fw-bold mb-1 small mt-1">Principal Desk</h6>
                            <small id="view_arrival_date" class="text-muted font-monospace d-flex align-items-center gap-1" style="font-size: 0.7rem;">
                                <i class="bx bx-calendar-check font-size-xs"></i>Rec: Awaiting Clearance
                            </small>
                        </div>

                    </div>
                </div>

                <!-- BLOCK 5: HARD AUDIT TRAIL FOOTER LOG DATA -->
                <div class="row g-0 mt-4 pt-3 border-top font-monospace text-muted align-items-center" style="font-size: 0.7rem; letter-spacing: 0.2px;">
                    <div class="col-4">
                        Logged: <strong id="view_reg_date" class="text-dark fw-semibold">01 Jul, 2026</strong>
                    </div>
                    <div class="col-4 text-center border-start border-end">
                        By: <strong id="view_recorder_name" class="text-dark fw-semibold">Operator</strong>
                    </div>
                    <div class="col-4 text-end" id="view_sync_status_wrapper">
                        Cloud: <strong id="view_sync_status" class="text-success fw-bold">[SYNCED]</strong>
                    </div>
                </div>

            </div>

            <!-- Modal Action Footer Control Layer Toolbar -->
            <div class="modal-footer border-top bg-light p-3 d-flex align-items-center justify-content-between">
                <div id="view_attachment_action_wrapper" class="d-flex align-items-center gap-1">
                    <!-- Attachment button links dynamically stream via JS -->
                </div>
                <button type="button" class="btn btn-outline-secondary btn-sm fw-bold px-3 shadow-sm" data-bs-dismiss="modal">Close Dossier</button>
            </div>

        </div>
    </div>
</div>


<!-- =========================================================================
     MODAL LAYOUT: OFFICIAL REGISTRY SIGN-OFF & DISPATCH CONTROLLER
     ========================================================================= -->
<div class="modal fade" id="documentSignOffModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-md" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 8px;">
            <div class="modal-header border-bottom bg-success text-white">
                <h5 class="modal-title fw-bold text-white"><i class="bx bx-check-shield me-2 fs-4"></i>Registry Clearance & Sign-Off Authorization</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form id="documentSignOffForm" method="POST" autocomplete="off">
                <div class="modal-body p-4">
                    <!-- Hidden operational index pointers -->
                    <input type="hidden" id="signoff_doc_id" name="id" value="" />
                    <input type="hidden" name="update_document_status" value="true" />

                    <!-- TARGET NOTIFICATION ROW BANNER -->
                    <div class="alert alert-neutral p-3 border mb-3 rounded d-flex align-items-center gap-2 small fw-semibold text-dark bg-light">
                        <i class="bx bx-file text-success fs-4"></i>
                        <span>Filing Inward Document Subject: <strong id="signoff_target_title" class="text-primary text-truncate d-inline-block" style="max-width: 250px;">Subject Line</strong></span>
                    </div>

                    <!-- TRANSFERRED: INTERACTIVE SEARCHABLE INSTITUTIONAL FILE INDEX DROPDOWN SELECTOR -->
                    <div class="row g-3 mb-3">
                        <div class="col-12 text-start">
                            <label for="tracking_number" class="form-label small fw-bold text-uppercase text-heading text-success">Assign Institutional File Index Category</label>
                            <div class="position-relative">
                                <select id="tracking_number" name="tracking_number" class="form-select text-primary fw-bold choices-file-index-select" required>
                                    <option value="" disabled selected>-- Type to Search Index Group --</option>
                                    <?php 
                                    $masterFileIndex = getInstitutionalFileIndexMap();
                                    foreach ($masterFileIndex as $letterGroup => $codes): 
                                    ?>
                                        <optgroup label="📂 Section Category Group [<?php echo $letterGroup; ?>]">
                                            <?php foreach ($codes as $indexNo => $description): ?>
                                                <option value="<?php echo $indexNo; ?>">
                                                    Code: <?php echo $indexNo; ?> - <?php echo $description; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <small class="text-muted font-size-xs d-block mt-1">Assigning a file index generates the unique institutional tracking ID auto-stamped on this file.</small>
                        </div>
                    </div>

                    <!-- Row 2: Receiver Name -->
                    <div class="row g-3 mb-3">
                        <div class="col-12">
                            <label for="signoff_receiver_name" class="form-label small fw-bold text-uppercase text-heading">Receiver Name / Target Desk Recipient</label>
                            <input type="text" id="signoff_receiver_name" name="receiver_name" class="form-control" placeholder="e.g., Vice Chancellor Desk / Registrar" required />
                        </div>
                    </div>

                    <!-- Row 3: Received Date Selector -->
                    <div class="row g-3 mb-3">
                        <div class="col-12">
                            <label for="signoff_received_date" class="form-label small fw-bold text-uppercase text-heading">Actual Release / Received Calendar Date</label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="bx bx-calendar text-muted"></i></span>
                                <input type="date" id="signoff_received_date" name="received_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required />
                            </div>
                        </div>
                    </div>

                    <!-- Row 4: Next Status Transition Target -->
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="signoff_status_select" class="form-label small fw-bold text-uppercase text-heading">Next Workflow Status Target</label>
                            <select id="signoff_status_select" name="status" class="form-select text-success fw-bold" required>
                                <option value="Signed / Approved" selected>🟢 Signed / Approved (Internal Clearance)</option>
                                <option value="Dispatched">🔵 Dispatched (Outbound Mailing)</option>
                                <option value="Archived">⚫ Archived (Closed File Registry)</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer border-top bg-light py-3">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Abort</button>
                    <button type="submit" class="btn btn-success btn-sm px-4 fw-bold shadow-sm">
                        <i class="bx bx-check-double me-1 fs-5"></i> Commit Sign-Off & File
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- =========================================================================
     MODAL LAYOUT: INSTITUTIONAL REGISTRY DOCUMENT TYPE-TO-SEARCH DISPATCH TERMINAL
     ========================================================================= -->
<div class="modal fade" id="documentSubmissionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-bottom bg-primary text-white">
                <h5 class="modal-title fw-bold text-white" id="documentModalTitle">
                    <i class="bx bx-cabinet me-2 fs-4"></i>Log Official Registry Document Record
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
             <form id="documentSubmissionForm" method="POST" autocomplete="off" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <!-- Hidden operational database primary key reference pointer -->
                    <input type="hidden" id="doc_id" name="id" value="" />
                    
                    <!-- ROW 1: MASTER DOCUMENT SUBJECT TITLE -->
                    <div class="row g-3 mb-3">
                        <div class="col-12">
                            <label for="title" class="form-label small fw-bold text-uppercase text-heading">Document Title / Subject Matter</label>
                            <input type="text" id="title" name="title" class="form-control" placeholder="e.g., Notice of Infrastructure Audit Request Memo" required />
                        </div>
                    </div>

                    <!-- RE-INJECTED ROW 2: DYNAMIC DOCUMENT TYPE SELECT DROPDOWN -->
                    <div class="row g-3 mb-3">
                        <div class="col-12">
                            <label for="doc_type" class="form-label small fw-bold text-uppercase text-heading">Document Classification Vector</label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="bx bx-purchase-tag-alt text-muted"></i></span>
                                <select id="doc_type" name="doc_type" class="form-select fw-semibold" required>
                                    <option value="" disabled selected>-- Select Classification Vector Type --</option>
                                    <?php 
                                    // Utilizes standard array values options initialized inside your page header
                                    if (!empty($docStatusOptions) || isset($docStatusOptions)):
                                        // Standard fallback array loop in case $docTypes was unassigned
                                        $formDocTypes = isset($docTypes) ? $docTypes : ['Incoming Letter', 'Outgoing Letter', 'Internal Memo', 'Payment Vouchers'];
                                        foreach ($formDocTypes as $typeOption): 
                                    ?>
                                        <option value="<?php echo htmlspecialchars($typeOption); ?>">
                                            <?php echo htmlspecialchars($typeOption); ?>
                                        </option>
                                    <?php 
                                        endforeach; 
                                    endif; 
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
 
                    <!-- ROW 3: DETACHED SENDER BIOMETRICS & CONTACT METADATA GRID -->
                    <div class="row g-3 mb-3">
                        <!-- Sender Name / Originating Source Field -->
                        <div class="col-12 col-sm-6">
                            <label for="sender_name" class="form-label small fw-bold text-uppercase text-heading">Sender Name / Originating Agency</label>
                            <input type="text" id="sender_name" name="sender_name" class="form-control" placeholder="e.g., Regional Ministry Office / GTEC Desk" required />
                        </div>
                        
                        <!-- Sender Telephone Number Contact Channel Field -->
                        <div class="col-12 col-sm-6">
                            <label for="sender_phone" class="form-label small fw-bold text-uppercase text-heading">Sender Telephone Number</label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="bx bx-phone text-muted"></i></span>
                                <input type="text" id="sender_phone" name="sender_phone" class="form-control" placeholder="e.g., +233 24 000 0000" />
                            </div>
                        </div>
                    </div>

                    <!-- ROW 4: ACTION TIMELINE CALENDAR SELECTOR -->
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <label for="received_dispatched_date" class="form-label small fw-bold text-uppercase text-heading">Registry Action Calendar Date</label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="bx bx-calendar text-muted"></i></span>
                                <input type="date" id="received_dispatched_date" name="received_dispatched_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required />
                            </div>
                        </div>
                    </div>

                    <!-- ROW 5: OPTIONAL SCAN FILE MEDIA ATTACHMENT DRAG BLOCK -->
                    <div class="row">
                        <div class="col-12">
                            <div class="bg-light p-3 rounded border border-dashed text-center position-relative">
                                <label for="document_file" class="form-label small fw-bold text-uppercase text-heading d-block mb-2">
                                    <i class="bx bx-upload text-secondary fs-3 d-block mb-1"></i>
                                    Scan / Softcopy Document Attachment <span class="text-muted fw-normal">(Optional)</span>
                                </label>
                                <input type="file" id="document_file" name="document_file" class="form-control mx-auto" accept=".pdf,.jpg,.jpeg,.png" style="max-width: 400px;" />
                                <span class="d-block font-size-xs text-muted mt-2">Accepted formats: High-resolution PDF document scans, JPG, or PNG files under 5MB max.</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- ACTION CAPTURE FOOTER TOOLBAR BUTTON PANELS -->
                <div class="modal-footer border-top bg-light py-3">
                    <button type="button" class="btn btn-outline-secondary btn-sm fw-semibold" data-bs-dismiss="modal">Cancel Execution</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold shadow-sm">
                        <i class="bx bx-save me-1"></i> Commit Ledger Record
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
.border-dashed { 
    border-style: dashed !important; 
    border-color: #d9dee3 !important;
}
.font-size-xs { 
    font-size: 0.725rem !important; 
}
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


border-radius: 0.375rem !important;box-shadow: 0 0.25rem 1rem rgba(161, 172, 184, 0.45) !important;text-align: left !important;}.choices__heading {font-weight: bold !important;color: #a1b0ca !important;font-size: 0.75rem !important;text-transform: uppercase !important;letter-spacing: 0.5px !important;padding: 8px 12px !important;}
</style>

<?php 
// Inject Sneat Admin closing wrappers and core script tags references
include_once 'includes/footer.php'; 
?> 

<script> 



    let docFilterTimer = null;

    // =========================================================================
    // SERVER SIDE REGISTRY DISPATCH HOOK (DYNAMIC ASYNC FILE ASSET FETCHING)
    // =========================================================================
    function executeAdvancedDocumentFiltration() {
        // Build payload string parameter mapping using standard true boolean flag rules
        const datasetPayload = $('#serverDocumentFilterForm').serialize() + '&fetch_filtered_documents=true';

        // Provide custom non-disruptive loading indicators across table cells row placeholders
        $('#document_table_body').html(`
            <tr>
                <td colspan="8" class="text-center py-5 text-primary">
                    <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                    Querying administrative file registers...
                </td>
            </tr>
        `);

        $.ajax({
            url: server_controller,
            type: 'POST',
            data: datasetPayload,
            dataType: 'json'
        }).then(response => {
            if (response.status === 'success') {
                // Return fresh raw data array slice directly into your reusable rendering function
                renderDocumentsTableUI(response.data); 
            } else {
                throw new Error(response.message || 'Ledger fetch execution fault.');
            }
        }).catch(error => {
            console.error("Document Registry Fetch Failure:", error);
            $('#document_table_body').html(`
                <tr>
                    <td colspan="8" class="text-center py-5 text-danger fw-medium">
                        <i class="bx bx-error-circle d-block fs-3 mb-2"></i>
                        Database Operation Error: ${error.message || 'Unable to load structural lines.'}
                    </td>
                </tr>
            `);
        });
    }

    // Bind event listeners to register option modifications inside form selection elements
    $('#filter_from_date, #filter_to_date, #filter_status').on('change', function() {
        executeAdvancedDocumentFiltration();
    });

    // Throttled alphanumeric keyboard execution listener for keyword inputs
    $('#search_keyword').on('keyup', function() {
        clearTimeout(docFilterTimer);
        docFilterTimer = setTimeout(executeAdvancedDocumentFiltration, 300); // 300ms window barrier
    });

    // Handle button action to clear out existing filtering metrics parameters
    $('#reset_doc_filters_btn').on('click', function() {
        $('#serverDocumentFilterForm')[0].reset();
        executeAdvancedDocumentFiltration(); // Fires empty layout parameters, falling back to full default
    });

  
  /**
 * Asynchronously repaints the document data row list UI elements cleanly 
 * following a database mutation response to preserve current workflow logic rules.
 * 
 * @param {Array} documentsList Fresh array data packets list from server response
 */
function renderDocumentsTableUI(documentsList) {
    let htmlOutput = '';
    
    if (!documentsList || documentsList.length === 0) {
        htmlOutput = `
        <tr>
            <td colspan="8" class="text-center py-5 text-muted">
                <i class="bx bx-receipt d-block fs-1 mb-2"></i> No official registry document entries discovery logged under this authorization account node.
            </td>
        </tr>`;
    } else {
        let sn = 1;
        const statusBadgesMap = {
            'Pending Review': 'warning',
            'Signed / Approved': 'success',
            'Dispatched': 'info',
            'Archived': 'secondary'
        };

        // Cache role clearance checks straight from background server global tags
        const sessionRoleClearance = '<?php echo $_SESSION['role']; ?>';

        $.each(documentsList, function(index, row) {
            const badgeColor = statusBadgesMap[row.status] || 'secondary';
            
            // Format timestamps into clean institutional visual notations
            const regDate = new Date(row.received_dispatched_date);
            const humanizedRegDate = regDate.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
            
            // 1. Process conditional workflow recipients routing text
            let recipientCellHTML = '';
            if (row.status === 'Pending Review') {
                recipientCellHTML = `
                    <span class="badge bg-label-warning text-uppercase small fw-bold font-size-xs tracking-wider"><i class="bx bx-pause-circle me-1"></i>Under Review</span>
                    <small class="text-muted d-block mt-1 font-size-xs italic">Not Issued From Registry</small>`;
            } else {
                const cleanRecName = row.receiver_name ? row.receiver_name : 'General Registry Desk';
                recipientCellHTML = `<span class="text-dark fw-bold small"><i class="bx bx-user-voice text-secondary me-1"></i>${cleanRecName}</span>`;
            }

            // 2. Process conditional multi-date timestamp logs
            let dateMetricsCellHTML = `<small class="text-heading font-size-xs fw-semibold"><i class="bx bx-export me-1 text-primary font-size-xs"></i>Reg: ${humanizedRegDate}</small>`;
            if (row.status === 'Pending Review') {
                dateMetricsCellHTML += `<small class="text-muted font-size-xs mt-1 italic"><i class="bx bx-time-five me-1 font-size-xs"></i>Awaiting Dispatch</small>`;
            } else if (row.received_date) {
                const recDate = new Date(row.received_date);
                const humanizedRecDate = recDate.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
                dateMetricsCellHTML += `<small class="text-success font-size-xs mt-1"><i class="bx bx-import me-1 text-success font-size-xs"></i>Rec: ${humanizedRecDate}</small>`;
            } else {
                dateMetricsCellHTML += `<small class="text-muted font-size-xs mt-1 italic"><i class="bx bx-minus-circle me-1 font-size-xs"></i>No Arrival Date</small>`;
            }

            // 3. Compile optional phone contacts text rows
            const phoneLineHTML = row.sender_phone ? `<small class="text-muted font-size-xs mt-1"><i class="bx bx-phone me-1 font-size-xs"></i>${row.sender_phone}</small>` : '';

            // 4. Compile dynamic action management menus indicators
            let managementActionLinkHTML = '';
            if ((sessionRoleClearance === 'Principal' || sessionRoleClearance === 'Secretary') && row.status === 'Pending Review') {
                managementActionLinkHTML = `
                    <a class="dropdown-item d-flex align-items-center text-success change-doc-status-btn" href="javascript:void(0);" data-id="${row.id}" data-status="Signed / Approved">
                        <i class="bx bx-check-shield me-2"></i> Approve & Sign
                    </a>`;
            }

            // 5. Compile binary storage files access options
            let attachmentsOptionsHTML = '';
            if (row.file_path && row.file_path.trim() !== '') {
                attachmentsOptionsHTML = `
                    <a class="dropdown-item d-flex align-items-center text-info mb-1" href="${row.file_path}" target="_blank"><i class="bx bx-show-alt me-2"></i> View Scanned File</a>
                    <a class="dropdown-item d-flex align-items-center text-success mb-1" href="${row.file_path}" download="Doc_${row.document_number}"><i class="bx bx-cloud-download me-2"></i> Download Attachment</a>`;
            } else {
                attachmentsOptionsHTML = `<a class="dropdown-item d-flex align-items-center text-muted disabled mb-1" href="javascript:void(0);"><i class="bx bx-hide me-2"></i> No Scan Attached</a>`;
            }

            // 6. Compile secure destructive record drops options links
            let wipeRecordLinkHTML = '';
            if (sessionRoleClearance === 'Principal' || sessionRoleClearance === 'Secretary') {
                wipeRecordLinkHTML = `
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item d-flex align-items-center text-danger delete-document-btn" href="javascript:void(0);" data-id="${row.id}" data-number="${row.document_number}">
                        <i class="bx bx-trash me-2"></i> Wipe Ledger Record
                    </a>`;
            }

            // 7. Inject everything into the row layout string block
            const jsonStringEscaped = JSON.stringify(row).replace(/"/g, '&quot;');

            htmlOutput += `
            <tr class="document-row" data-date="${row.received_dispatched_date}" data-status="${row.status}">
                <td>${sn++}</td>
                <td>
                    <div class="d-flex flex-column">
                        <span class="fw-bold text-primary mb-0" style="font-family: monospace; letter-spacing: 0.5px;">${row.document_number}</span>
                        <small class="text-muted font-size-xs fw-semibold mb-1">📂 Index: ${row.file_index_category_name ? row.file_index_category_name : 'General Document'}</small>
                        <small class="text-heading fw-bold text-truncate" style="max-width: 260px;" title="${row.title}">${row.title}</small>
                    </div>
                </td>
                <td>
                    <div class="d-flex flex-column">
                        <span class="fw-medium text-dark small">${row.sender_name}</span>
                        ${phoneLineHTML}
                    </div>
                </td>
                <td>${recipientCellHTML}</td>
                <td><div class="d-flex flex-column">${dateMetricsCellHTML}</div></td>
                <td><span class="badge bg-label-${badgeColor} font-weight-bold px-2 py-1">${row.status}</span></td>
                <td><small class="text-muted fw-semibold">${row.recorder_name ? row.recorder_name : 'System Process'}</small></td>
                <td class="text-center">
                    <div class="dropdown">
                        <button type="button" class="btn btn-sm btn-icon dropdown-toggle hide-arrow p-0" data-bs-toggle="dropdown"><i class="bx bx-dots-vertical-rounded fs-5"></i></button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a class="dropdown-item d-flex align-items-center text-secondary view-document-details-btn mb-1 fw-bold" href="javascript:void(0);" data-entry="${jsonStringEscaped}">
                                <i class="bx bx-show me-2 fs-5 text-secondary"></i> View Full Details
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item d-flex align-items-center text-primary edit-document-btn" href="javascript:void(0);" data-entry="${jsonStringEscaped}"><i class="bx bx-edit-alt me-2"></i> Edit Metadata</a>
                            ${managementActionLinkHTML}
                            <div class="dropdown-divider"></div>
                            ${attachmentsOptionsHTML}
                            ${wipeRecordLinkHTML}
                        </div>
                    </div>
                </td>
            </tr>`;
        });
    }
    $('#document_table_body').html(htmlOutput);
} 



$(document).ready(function() {
    
    
    
       // =========================================================================
        // INITIALIZE CHOICES.JS: TYPE-TO-SEARCH ARCHITECTURE FOR FILE INDEXING
        // =========================================================================
        const targetSelectElement = document.querySelector('.choices-file-index-select');
        
        if (targetSelectElement) {
            choicesFileIndexInstance = new Choices(targetSelectElement, {
                searchEnabled: true,          // Spawns type-to-search input panel inside dropdown
                searchChoices: true,
                removeItemButton: false,
                shouldSort: false,            // Prevents resetting your custom numeric dictionary sorting order
                itemSelectText: 'Click to select',
                placeholder: true,
                placeholderValue: '-- Type or Select Index Group --',
                classNames: {
                    // FIXED: Split multiple classes into an array of separate tokens 
                    // instead of a single spaced string to resolve DOMTokenList add error
                    containerOuter: ['choices', 'mb-0'] 
                }
            });
        }
        
        
    // =========================================================================
    // MODAL POPUP ACTION: RENDER REDESIGNED GRAPH TIMELINE LEDGER DOSSIER
    // =========================================================================
    $(document).on('click', '.view-document-details-btn', function(e) {
        e.preventDefault();
        const row = $(this).data('entry');

        // Humanize date properties configurations cleanly
        const regDateFormatted = new Date(row.received_dispatched_date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
        const receivedDateStr = row.received_date ? new Date(row.received_date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '';
        
        // Map status badges colors to native Sneat labels skins mapping
        const statusBadgesMap = {
            'Pending Review': 'warning', 'Signed / Approved': 'success', 'Dispatched': 'info', 'Archived': 'secondary'
        };
        const activeColorTheme = statusBadgesMap[row.status] || 'secondary';

        // 1. POPULATE UPPER CARD ELEMENTS METRICS
        $('#view_document_number').text(row.document_number);
        $('#view_status_badge').removeClass().addClass(`badge bg-label-${activeColorTheme} font-weight-bold text-uppercase`).text(row.status);
        $('#view_title').text(row.title);
        $('#view_file_index').text(row.file_index_category_name ? row.file_index_category_name : 'General Archive Document');

        // 2. POPULATE DETACHED TIMELINE COMPONENT FIELDS
        $('#view_sender_name').text(row.sender_name);
        $('#view_sender_phone').html(row.sender_phone ? `<i class="bx bx-phone me-1 font-size-sm"></i>${row.sender_phone}` : '<i class="bx bx-minus me-1"></i>No Telephone Logged');

        // WORKFLOW CONDITION ENGINE: If record is under review, completely hide destination receiver names text parameters
        if (row.status === 'Pending Review') {
            $('#view_receiver_name').html('<span class="badge bg-label-warning text-uppercase" style="font-size:0.65rem; letter-spacing:0.5px;"><i class="bx bx-pause-circle me-1"></i>Under Review</span>');
            $('#view_arrival_date').html('<i class="bx bx-time-five me-1 font-size-sm text-muted"></i>Awaiting Dispatch');
        } else {
            const receiverText = row.receiver_name ? row.receiver_name : 'General Registry Desk';
            $('#view_receiver_name').text(receiverText);
            $('#view_arrival_date').html(`<i class="bx bx-calendar-check me-1 font-size-sm text-success"></i>Rec: ${receivedDateStr}`);
        }

        // 3. POPULATE LEDGER FOOTER PROPERTY LOGLINES INDICATORS
        $('#view_reg_date').text(regDateFormatted);
        $('#view_recorder_name').text(row.recorder_name ? row.recorder_name : 'System Process Node');

        // Render clean synchronization metrics badge states on-the-fly
        if (row.sync_status === 'Synced') {
            $('#view_sync_status').removeClass().addClass('text-success fw-bold').text('[SYNCED]');
        } else {
            $('#view_sync_status').removeClass().addClass('text-warning fw-bold').text('[PENDING]');
        }

        // 4. GENERATE CLEAN ACTION BUTTON BUTTON OVERLAYS NATIVELY
        let attachmentButtonHTML = '';
        if (row.file_path && row.file_path.trim() !== '') {
            attachmentButtonHTML = `
                <a href="${row.file_path}" target="_blank" class="btn btn-primary btn-sm fw-bold">
                    <i class="bx bx-show me-1 font-size-sm"></i> Preview File
                </a>
                <a href="${row.file_path}" download="Doc_${row.document_number}" class="btn btn-outline-success btn-sm fw-bold">
                    <i class="bx bx-cloud-download me-1 font-size-sm"></i> Download File
                </a>`;
        } else {
            attachmentButtonHTML = '<span class="badge bg-label-secondary font-size-xs fw-normal px-3 py-2"><i class="bx bx-hide me-1"></i>No digital softcopy scanner link</span>';
        }
        $('#view_attachment_action_wrapper').html(attachmentButtonHTML);

        // Fire and render the redesigned modal
        $('#documentDetailsModal').modal('show');
    });


    // =========================================================================
    // SIGN-OFF EVENT: INTERCEPT ACTION BUTTON CLICK AND LAUNCH THE POPUP
    // =========================================================================
    // =========================================================================
    // APPROVAL TRIGGER INTERCEPTOR: POPULATE DETAILS TO SIGN-OFF POPUP
    // =========================================================================
    $(document).on('click', '.change-doc-status-btn', function(e) {
        e.preventDefault();
        
        const targetId = $(this).data('id');
        const documentRowContainer = $(this).closest('tr');
        
        // Grab document title straight from explorer node elements text tags
        const documentTitleText = documentRowContainer.find('small.text-heading.fw-bold').text().trim();

        // Preset inputs layout states
        $('#signoff_doc_id').val(targetId);
        $('#signoff_target_title').text(documentTitleText);
        $('#signoff_receiver_name').val('');
        
        if (choicesFileIndexInstance) {
            choicesFileIndexInstance.removeActiveItems(); // Flush historical category picks
            choicesFileIndexInstance.setChoiceByValue(''); // Reset to baseline placeholder
        }

        $('#documentSignOffModal').modal('show');
    });

    // Clear and restore choices states upon closing signoff popup windows
    $('#documentSignOffModal').on('hidden.bs.modal', function () {
        this.querySelector('form').reset();
        if (choicesFileIndexInstance) {
            choicesFileIndexInstance.removeActiveItems();
            choicesFileIndexInstance.setChoiceByValue('');
        }
    });


    // =========================================================================
    // SIGN-OFF TRANSACTION EXECUTION ENGINE (`update_document_status=true`)
    // =========================================================================
    $('#documentSignOffForm').on('submit', function(e) {
        e.preventDefault();
        const activeForm = this;

        Swal.fire({
            title: 'Commit Signature Clearance?',
            text: "Are you sure you want to authorize and release this document record from under review status?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#71dd37', // Sneat Success Green Color Hex
            cancelButtonColor: '#8592a3',
            confirmButtonText: 'Yes, Authorize Release',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return $.ajax({
                    url: server_controller,
                    type: 'POST',
                    data: $(activeForm).serialize(), // Automatically packs inputs (id, status, receiver_name, received_date)
                    dataType: 'json'
                }).then(res => {
                    if (res.status !== 'success') throw new Error(res.message);
                    return res;
                }).catch(err => { 
                    Swal.showValidationMessage(`Sign-Off Fault: ${err.message || 'Connection timeout.'}`); 
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then(result => {
            if (result.isConfirmed && result.value) {
                // Clear inputs and dismiss modal window frame targets
                $('#documentSignOffModal').modal('hide');
                activeForm.reset();

                // Repaint directory grid on screen instantly using fresh dataset returned array
                renderDocumentsTableUI(result.value.data);
                
                Swal.fire({
                    title: 'Authorized! 📜',
                    text: 'The document tracking file has been signed off and successfully released from registry review loops.',
                    icon: 'success',
                    confirmButtonColor: '#696cff'
                });
            }
        });
    });


    // =========================================================================
    // REGISTRY LEDGER HARD WIPER HANDLER (`delete_document=true`)
    // =========================================================================
    $(document).on('click', '.delete-document-btn', function(e) {
        e.preventDefault();
        
        // Extract parameters safely from the context button row
        const documentId     = $(this).data('id');
        const documentNumStr = $(this).data('number');

        Swal.fire({
            title: 'Wipe Ledger Record?',
            html: `Are you sure you want to permanently erase registry file <strong>#${documentNumStr}</strong>? <br><small class="text-danger fw-semibold">This will archive a JSON snapshot, erase the database record, and delete local disk softcopies.</small>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ff3e1d', // Sneat UI Crimson Red
            cancelButtonColor: '#8592a3',
            confirmButtonText: '<i class="bx bx-trash me-1"></i> Yes, wipe record',
            cancelButtonText: 'Abort',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return $.ajax({
                    url: server_controller,
                    type: 'POST',
                    data: { 
                        id: documentId, 
                        delete_document: 'true' // Flags the targeted Branch V backend catcher
                    },
                    dataType: 'json'
                }).then(res => {
                    if (res.status !== 'success') throw new Error(res.message);
                    return res;
                }).catch(err => { 
                    Swal.showValidationMessage(`${err.message || 'Connection lost.'}`); 
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then(result => {
            if (result.isConfirmed && result.value) {
                // Instantly repaint table row layout on canvas with updated backend array dataset
                renderDocumentsTableUI(result.value.data);
                
                Swal.fire({
                    title: 'Wiped Clean! 🗑️',
                    text: 'Record row dropped locally, archived as a compressed JSON payload, and added to the outbound sync queue.',
                    icon: 'success',
                    confirmButtonColor: '#696cff'
                });
            }
        });
    });


    // =========================================================================
    // FORM SUBMISSION: DUAL CONTROLLER WITH CLIENT-SIDE BASE64 FILE ENCODING
    // =========================================================================
    $('#documentSubmissionForm').on('submit', function(e) {
        e.preventDefault();
        
        const fileInput = $('#document_file')[0];
        const hasFile = (fileInput && fileInput.files && fileInput.files.length > 0);

        // Helper function that leverages Promise loops to process file conversion
        function processFileToBase64(file) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.readAsDataURL(file); // Converts binary stream directly to base64 DataURL
                reader.onload = () => resolve(reader.result);
                reader.onerror = error => reject(error);
            });
        }

        // Sequential compilation runner block
        async function compileAndSubmitPayload() {
            let base64String = '';
            let fileNameString = '';

            if (hasFile) {
                try {
                    const targetFile = fileInput.files[0];
                    fileNameString = targetFile.name;
                    base64String = await processFileToBase64(targetFile);
                } catch (fileErr) {
                    throw new Error(`File Processing Fault: ${fileErr.message}`);
                }
            }

            // Serialize standard fields and cleanly append our base64 parameters
            let serializedData = $('#documentSubmissionForm').serialize();
            serializedData += '&save_document=true';
            serializedData += '&file_base64=' + encodeURIComponent(base64String);
            serializedData += '&file_name=' + encodeURIComponent(fileNameString);

            return $.ajax({
                url: server_controller,
                type: 'POST',
                data: serializedData,
                dataType: 'json'
            }).then(res => {
                if (res.status !== 'success') throw new Error(res.message);
                return res;
            }).catch(err => { 
                throw new Error(err.message || 'Execution exception encountered.'); 
            });
        }

        // Invoke structured SweetAlert2 interface loop
        Swal.fire({
            title: 'Commit Document Ledger Entry?',
            text: "Are you sure you want to log these metadata entries into the system master index?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#696cff',
            cancelButtonColor: '#8592a3',
            confirmButtonText: 'Yes, save ledger record',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                // Call our asynchronous compilation thread function
                return compileAndSubmitPayload().catch(err => {
                    Swal.showValidationMessage(`${err.message}`);
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then(result => {
            if (result.isConfirmed && result.value) {
                // FIXED: Targets correct modal identifier name
                $('#documentSubmissionModal').modal('hide');
                
                // Clear text inputs, checkboxes, and file pointers instantly
                $('#documentSubmissionForm')[0].reset();
                
                // Trigger dynamic UI re-rendering matrix instantly using raw data arrays
                renderDocumentsTableUI(result.value.data);
                Swal.fire('Saved!', result.value.message, 'success');
            }
        });
    });


    // =========================================================================
    // TRIGGER EVENT: EXTRACT PROPERTIES & POPULATE FOR UPDATE ACTIONS
    // =========================================================================
    $(document).on('click', '.edit-document-btn', function(e) {
        e.preventDefault();
        const data = $(this).data('entry');
        
        // FIXED: Targets correct header titles and confirmation action buttons
        $('#documentModalTitle').html('<i class="bx bx-edit-alt me-2 fs-4"></i>Modify Document Metadata Entry');
        $('#documentSubmissionModal button[type="submit"]').html('<i class="bx bx-check-double me-1"></i> Update Changes'); 
        
            // A. Inside your dynamic table '.edit-document-btn' click pre-fill event listener:
    $('#documentSubmissionModal #doc_id').val(data.id);
    $('#documentSubmissionModal #title').val(data.title);
    $('#documentSubmissionModal #doc_type').val(data.doc_type); // NEW MAP HOOK
    $('#documentSubmissionModal #sender_name').val(data.sender_name);
    $('#documentSubmissionModal #sender_phone').val(data.sender_phone);
    $('#documentSubmissionModal #received_dispatched_date').val(data.received_dispatched_date);

  

         
        $('#documentSubmissionModal').modal('show');
    });
 

    // =========================================================================
    // UI CLEAN UP: RESTORE PRISTINE VALS UPON MODAL FORM DISMISSAL
    // =========================================================================
    $('#documentSubmissionModal').on('hidden.bs.modal', function () {
        $('#documentSubmissionForm')[0].reset();
        $('#documentSubmissionModal #doc_id').val(''); 
        $('#documentSubmissionModal #doc_type').val(''); // NEW RESET FLOOD
        
        
        $('#documentModalTitle').html('<i class="bx bx-cabinet me-2 fs-4"></i>Log Official Registry Document Record');
        $('#documentSubmissionModal button[type="submit"]').html('<i class="bx bx-save me-1"></i> Commit Ledger Record');
        
        // Restore current fallback operational date value
        const todayStr = new Date().toISOString().split('T')[0];
        $('#documentSubmissionModal #received_dispatched_date').val(todayStr);
    });
});

</script>