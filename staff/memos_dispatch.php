<?php
try {
    // Load Sneat Header (Includes session initialization, $con context, and auth controls)
    require_once 'includes/header.php'; 
    if (!$con) throw new Exception('Database link verification dropped.');

    $dataset = getMemosDashboardDataset($con);
    if ($dataset['status'] === 'error') throw new Exception($dataset['message']);

    $memosList       = $dataset['memos_list'];
    $priorityOptions = ['Normal', 'Urgent', 'Critical'];
    $priorityBadges  = ['Normal' => 'secondary', 'Urgent' => 'warning', 'Critical' => 'danger'];
    
    $userRole = $_SESSION['role'] ?? '';
    $userId   = (int)($_SESSION['user_id'] ?? 0);

     


} catch (Throwable $e) {
    echo '<div class="alert alert-danger m-4">System Initializer Crash: ' . htmlspecialchars($e->getMessage()) . '</div>'; 
    exit;
} ?>
 

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Communications /</span> Executive Memorandum Desk</h4>

        <!-- ============================================================
             DYNAMIC FILTRATION TOOLBAR PANEL (SERVER-SIDE DISPATCHER)
             ============================================================ --> 
        <div class="card mb-4 shadow-sm border-0">
            <div class="card-body">
                <form id="serverMemoFilterForm" autocomplete="off">
                    <div class="row g-3">
                        
                        <!-- Global Live Text Search Field -->
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-bold text-uppercase text-heading">Keyword Search</label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="bx bx-search text-muted"></i></span>
                                <input type="text" name="search_keyword" id="search_keyword" class="form-control" placeholder="Search reference #, title, sender name..." />
                            </div>
                        </div>

                        <!-- NEW: Document Classification Filter Dropdown Selection Box -->
                        <div class="col-12 col-sm-6 col-md-2">
                            <label class="form-label small fw-bold text-uppercase text-heading">Classification Type</label>
                            <select name="filter_classification" id="filter_classification" class="form-select fw-medium">
                                <option value="">-- All Classifications --</option>
                                <option value="Memo">Memo</option>
                                <option value="Official Letter">Official Letter</option>
                                <option value="Circular">Circular</option>
                            </select>
                        </div>

                        <!-- Urgency Priority Level Selection Box -->
                        <div class="col-12 col-sm-6 col-md-2">
                            <label class="form-label small fw-bold text-uppercase text-heading">Urgency Priority</label>
                            <select name="filter_priority" id="filter_priority" class="form-select fw-medium">
                                <option value="">-- All Priorities --</option>
                                <?php foreach ($priorityOptions as $opt): ?>
                                    <option value="<?php echo $opt; ?>"><?php echo $opt; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Reset Action State Button -->
                        <div class="col-12 col-sm-12 col-md-2 d-grid align-self-end">
                            <button type="button" id="reset_memo_filters_btn" class="btn btn-outline-secondary d-flex align-items-center justify-content-center gap-1">
                                <i class="bx bx-refresh fs-4"></i> Reset Filters
                            </button>
                        </div>

                    </div>
                </form>
            </div>
        </div>

        <!-- ============================================================
             MAIN DATA TABLE LEDGER CARD LAYOUT
             ============================================================ -->
        <div class="card">
            <div class="card-header border-bottom d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0 fw-semibold text-primary">Official Memoranda Bulletin Board</h5>
                <?php if (in_array($userRole, ['Principal', 'Secretary'])): ?>
                    <button class="btn btn-primary btn-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#memoDispatchModal">
                        <i class="bx bx-edit-alt"></i> Draft & Publish Memo
                    </button>
                <?php endif; ?>
            </div>
            <div class="table-responsive text-nowrap card-body"> 
                <table class="table table-striped table-hover align-middle mb-0" id="DataTable">
                    <thead class="table-light text-uppercase font-size-sm">
                        <tr>
                            <th>S/N</th>
                            <th>Reference Code / Subject</th>
                            <th>Type</th>
                            <th>Letter Date</th>
                            <th>Published By</th>
                            <th>Priority</th>
                            <th>Read Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="memo_table_body" class="table-border-bottom-0">
                        <?php if (!empty($memosList)): ?>
                        <?php  $sn = 1; ?>
                            <?php foreach ($memosList as $row): ?>
                            <!-- Added classification dataset tags to support server-side real-time filter queries -->
                            <tr class="memo-row" 
                                data-priority="<?php echo $row['priority']; ?>" 
                                data-classification="<?php echo htmlspecialchars($row['document_classification'], ENT_QUOTES, 'UTF-8'); ?>">
                                <td><?php echo $sn++; ?></td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="fw-bold text-primary mb-0">#<?php echo htmlspecialchars($row['memo_ref']); ?></span>
                                        <small class="text-heading fw-semibold text-truncate" style="max-width: 260px;"><?php echo htmlspecialchars($row['title']); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <?php 
                                    // Assign custom colors based on Document Type classification
                                    $classType = $row['document_classification'] ?? 'Memo';
                                    $classBadge = 'secondary';
                                    $classIcon = 'bx-file';
                                    
                                    if ($classType === 'Memo') { $classBadge = 'primary'; $classIcon = 'bx-note'; }
                                    elseif ($classType === 'Official Letter') { $classBadge = 'info'; $classIcon = 'bx-envelope'; }
                                    elseif ($classType === 'Circular') { $classBadge = 'dark'; $classIcon = 'bx-broadcast'; }
                                    ?>
                                    <span class="badge bg-label-<?php echo $classBadge; ?> fw-semibold px-2 py-1">
                                        <i class="bx <?php echo $classIcon; ?> me-1"></i><?php echo htmlspecialchars($classType); ?>
                                    </span>
                                </td>
                                <td><span class="fw-medium text-heading"><i class="bx bx-calendar me-1"></i><?php echo date('d M, Y', strtotime($row['memo_date'])); ?></span></td>
                                <td><?php echo htmlspecialchars($row['sender_name']); ?></td>
                                <td>
                                    <span class="badge bg-label-<?php echo $priorityBadges[$row['priority']] ?? 'secondary'; ?> font-weight-bold">
                                        <?php echo $row['priority']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    $readString = $row['read_by_users'] ?? '';
                                    $hasBeenReadByMe = str_contains($readString, ",{$userId},");
                                    
                                    if (in_array($userRole, ['Principal', 'Secretary'])) {
                                        echo '<span class="badge bg-label-dark">Published Circular</span>';
                                    } else {
                                        echo $hasBeenReadByMe ? '<span class="badge bg-label-success">Opened</span>' : '<span class="badge bg-label-warning fw-bold animate-pulse">Unread</span>';
                                    }
                                    ?>
                                </td>
                                <td class="text-center">
                                    <div class="dropdown">
                                        <button type="button" class="btn btn-sm btn-icon dropdown-toggle hide-arrow p-0" data-bs-toggle="dropdown">
                                            <i class="bx bx-dots-vertical-rounded fs-5"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end"> 
                                           
                                            <!-- UPDATED: Standard POST AJAX PDF Trigger Link Option Row -->
                                            <a class="dropdown-item d-flex align-items-center text-success download-memo-pdf-btn" href="javascript:void(0);" data-entry="<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8'); ?>">
                                                <i class="bx bx-cloud-download me-2"></i> Download
                                            </a>

                                            
                                            <!-- FACTORED RBAC ADMINISTRATIVE EDIT AND AMENDMENT HOOK TRIPPERS -->
                                            <?php if (in_array($userRole, ['Principal', 'Secretary'])): ?>
                                                <a class="dropdown-item d-flex align-items-center text-primary edit-memo-btn" href="javascript:void(0);" data-entry="<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8'); ?>">
                                                    <i class="bx bx-edit-alt me-2"></i> Edit Metadata
                                                </a>
                                                <div class="dropdown-divider"></div>
                                                <a class="dropdown-item d-flex align-items-center text-danger delete-memo-btn" href="javascript:void(0);" data-id="<?php echo $row['id']; ?>">
                                                    <i class="bx bx-trash me-2"></i> Retract Bulletin
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
     MODAL LAYOUT: UNIFIED DRAFT & DISPATCH OFFICIAL CORRESPONDENCE (QUILL ALIGNED)
     ============================================================ --><!-- ============================================================
     MODAL LAYOUT: UNIFIED DRAFT & DISPATCH OFFICIAL CORRESPONDENCE (QUILL ALIGNED)
     ============================================================ -->
<div class="modal fade" id="memoDispatchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold text-primary" id="modalCenterTitle">Publish Official Correspondence</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="memoSubmissionForm" method="POST" autocomplete="off">
                <div class="modal-body">
                    <!-- Hidden operational index vectors -->
                    <input type="hidden" id="memo_record_id" name="id" value="" />
                    <!-- Hidden text elements to capture rich text strings for POST operations -->
                    <input type="hidden" id="content_payload" name="content" value="" />
                    <input type="hidden" id="recipient_address_payload" name="recipient_address" value="" />
                    <input type="hidden" id="subscription_payload" name="subscription" value="" />

                    <!-- Row 1: Document Subject Heading -->
                    <div class="row g-3 mb-3">
                        <div class="col-12">
                            <label for="title" class="form-label small fw-semibold text-heading">Document Subject / Title Heading</label>
                            <input type="text" id="title" name="title" class="form-control" placeholder="e.g., Notice of Urgent Board Appraisal Meeting" required />
                        </div>
                    </div>

                    <!-- NEW ROW IA: DYNAMIC DEPARTMENTS OFFICE ORIGIN LABEL LINE -->
                    <div class="row g-3 mb-3">
                        <div class="col-12">
                            <label for="office_name" class="form-label small fw-semibold text-heading">Office Name (Originating Department Label Header)</label>
                            <input type="text" id="office_name" name="office_name" class="form-control" value="Office of the Principal" placeholder="e.g., Office of the Principal" required />
                        </div>
                    </div>

                    <!-- Row 2: Document Date, Classification Type & Urgency Priority -->
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-4">
                            <label for="memo_date" class="form-label small fw-semibold text-heading">Official Document Date</label>
                            <input type="date" id="memo_date" name="memo_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required />
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="document_classification" class="form-label small fw-semibold text-heading">Document Classification</label>
                            <select id="document_classification" name="document_classification" class="form-select text-primary fw-bold" required>
                                <option value="Memo" selected>📝 Memo</option>
                                <option value="Official Letter">✉️ Official Letter</option>
                                <option value="Circular">📢 Circular</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="priority" class="form-label small fw-semibold text-heading">Urgency Priority Level</label>
                            <select id="priority" name="priority" class="form-select" required>
                                <?php foreach($priorityOptions as $opt): ?>
                                    <option value="<?php echo $opt; ?>"><?php echo $opt; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                     
                    <!-- Row 3: Recipient Designation Address Area (Quill Enabled) -->
                    <div class="row g-3 mb-3">
                        <div class="col-12">
                            <label class="form-label small fw-semibold text-heading">Recipient Designation Address</label>
                            <div id="quill_recipient_address_wrapper" style="height: 100px; border-bottom-left-radius: 0.375rem; border-bottom-right-radius: 0.375rem;"></div>
                        </div>
                    </div>

                    <!-- Row 4: Detailed Document Content Body (Quill Enabled) -->
                    <div class="row g-3 mb-3">
                        <div class="col-12">
                            <label class="form-label small fw-semibold text-heading">Detailed Document Body Content</label>
                            <div id="quill_editor_wrapper" style="height: 220px; border-bottom-left-radius: 0.375rem; border-bottom-right-radius: 0.375rem;"></div>
                        </div>
                    </div>

                    <!-- Row 5: Subscription Endorsements & Routing (Quill Enabled) -->
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-semibold text-heading">Subscription Endorsements & Routing</label>
                            <div id="quill_subscription_wrapper" style="height: 150px; border-bottom-left-radius: 0.375rem; border-bottom-right-radius: 0.375rem;"></div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Discard</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3"><i class="bx bx-broadcast me-1"></i> Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

 
<section>
    <!-- ============================================================
     MODAL LAYOUT: LIVE CORRESPONDENCE PRINT PREVIEW & EXPORT TERMINAL
     ============================================================ -->
<div class="modal fade" id="memoPreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document" style="width:95%;">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-bottom bg-primary text-white">
                <h5 class="modal-title fw-bold text-white"><i class="bx bx-show-alt me-2 fs-4"></i>Institutional Document Portfolio Preview</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <!-- Live Canvas View Container Body -->
            <div class="modal-body p-4 bg-secondary bg-opacity-10" style="max-height: 500px; overflow-y: auto;">
                <div class="card shadow-sm border-0 mx-auto bg-white p-2" style="max-width: 1000px;">
                    <div id="preview_canvas_holder">
                        <!-- Reusable HTML letterhead layout builds inject here dynamically -->
                    </div>
                </div>
            </div>
            
            <!-- Dynamic Export Action Triggers Footer Panel -->
            <div class="modal-footer border-top bg-light justify-content-between">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close Preview</button>
                <div class="d-flex gap-2"> 
                    <button type="button" id="preview_generate_pdf_btn" class="btn btn-primary btn-sm px-3">
                        <i class="bx bx-cloud-download me-1"></i> Print
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

</section>
<?php include_once 'includes/footer.php'; ?>
 

    <link rel="stylesheet" href="../assets/css/quill.snow.css">
      <script src="../assets/js/quill.js"></script>

<script>

 


/**
 * Synchronously compiles and re-renders the complete Sneat Admin Memorandum Data Table body
 * using raw database result arrays without triggering heavy page document postbacks.
 *
 * @param {Array} memosList Fresh array payload containing row structures from the controller
 */
function renderMemosTableUI(memosList) {
    let htmlOutput = '';
    
    // ADJUSTED: Changed colspan to 8 to perfectly balance our updated layout matrix columns
    if (!memosList || memosList.length === 0) {
        htmlOutput = `
        <tr>
            <td colspan="8" class="text-center py-5 text-muted">
                <i class="bx bx-envelope d-block fs-1 mb-2"></i> No official correspondence documents discovered inside your mailbox account directory.
            </td>
        </tr>`;
    } else {
        let sn = 1;
        // Sneat urgency priority status color configurations maps
        const priorityBadgesMap = { 'Normal': 'secondary', 'Urgent': 'warning', 'Critical': 'danger' };
        
        // Safely extract active session role injected globally via PHP layout engines
        const activeUserRole = "<?php echo $_SESSION['role'] ?? ''; ?>";
        const activeUserId = ",<?php echo $_SESSION['user_id'] ?? 0; ?>,";

        $.each(memosList, function(index, row) {
            const badgeColor = priorityBadgesMap[row.priority] || 'secondary';
            
            // Format dynamic document creation dates safely using standard locales formatting
            const rawDate = new Date(row.memo_date);
            const humanizedDate = rawDate.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
            
            // FACTORED: Generate unique classification badge metrics for UI formatting consistency
            const docClass = row.document_classification || 'Memo';
            let classBadge = 'secondary';
            let classIcon = 'bx-file';
            
            if (docClass === 'Memo') { 
                classBadge = 'primary'; 
                classIcon = 'bx-note'; 
            } else if (docClass === 'Official Letter') { 
                classBadge = 'info'; 
                classIcon = 'bx-envelope'; 
            } else if (docClass === 'Circular') { 
                classBadge = 'dark'; 
                classIcon = 'bx-broadcast'; 
            }
            
            const classificationBadgeMarkup = `
                <span class="badge bg-label-${classBadge} fw-semibold px-2 py-1">
                    <i class="bx ${classIcon} me-1"></i>${docClass}
                </span>`;
            
            // Escape complete inline HTML row elements payload safely for data transmission
            const escapedJSON = JSON.stringify(row).replace(/"/g, '&quot;');
            
            // Evaluates user reading footprint tracking lists matching strings context boundaries
            const readString = row.read_by_users || '';
            const isReadByMe = readString.includes(activeUserId);
            
            let statusBadgeMarkup = '';
            let administrativeActionsGroup = ''; // String container for administrative context rows

            if (activeUserRole === 'Principal' || activeUserRole === 'Secretary') {
                statusBadgeMarkup = `<span class="badge bg-label-dark">Published Circular</span>`;
                
                // Factored inside the dynamic JS layout string output engine natively for administrative actors
                administrativeActionsGroup = `
                    <a class="dropdown-item d-flex align-items-center text-primary edit-memo-btn" href="javascript:void(0);" data-entry="${escapedJSON}">
                        <i class="bx bx-edit-alt me-2"></i> Edit 
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item d-flex align-items-center text-danger delete-memo-btn" href="javascript:void(0);" data-id="${row.id}">
                        <i class="bx bx-trash me-2"></i> Retract Bulletin
                    </a>`;
            } else {
                statusBadgeMarkup = isReadByMe ? `<span class="badge bg-label-success">Opened</span>` : `<span class="badge bg-label-warning fw-bold animate-pulse">Unread</span>`;
            }

            htmlOutput += `
            <tr class="memo-row" data-priority="${row.priority}" data-classification="${docClass}">
                <td>${sn++}</td>
                <td>
                    <div class="d-flex flex-column">
                        <span class="fw-bold text-primary mb-0">#${row.memo_ref}</span>
                        <small class="text-heading fw-semibold text-truncate" style="max-width: 260px;">${row.title}</small>
                    </div>
                </td>
                <td>${classificationBadgeMarkup}</td>
                <td><span class="fw-medium text-heading"><i class="bx bx-calendar me-1"></i>${humanizedDate}</span></td>
                <td>${row.sender_name}</td>
                <td><span class="badge bg-label-${badgeColor} font-weight-bold">${row.priority}</span></td>
                <td>${statusBadgeMarkup}</td>
                <td class="text-center">
                    <div class="dropdown">
                        <button type="button" class="btn btn-sm btn-icon dropdown-toggle hide-arrow p-0" data-bs-toggle="dropdown">
                            <i class="bx bx-dots-vertical-rounded fs-5"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end"> 
                        <!-- UPDATED: Standard POST AJAX PDF Trigger Link Option Row -->
                            <a class="dropdown-item d-flex align-items-center text-success download-memo-pdf-btn" href="javascript:void(0);" data-entry="${escapedJSON}">
                                <i class="bx bx-cloud-download me-2"></i> Download 
                            </a>
                            ${administrativeActionsGroup}
                        </div>
                    </div>
                </td>
            </tr>`;
        });
    }
    // Inject generated clean rows instantly into your HTML table placeholder container
    $('#memo_table_body').html(htmlOutput);
}

/**
 * Formats a raw date object into an institutional ordinal date string 
 * e.g., 27th June, 2026
 *
 * @param {Date} dateObj Instantiated JavaScript Date object
 * @returns {string} Formatted string with ordinal suffixes
 */
function formatOrdinalDate(dateObj) {
    if (!(dateObj instanceof Date) || isNaN(dateObj)) return 'N/A';
    
    const day = dateObj.getDate();
    const month = dateObj.toLocaleDateString('en-GB', { month: 'long' });
    const year = dateObj.getFullYear();
    
    // Calculate strict arithmetic ordinal rules
    let suffix = 'th';
    if (day < 11 || day > 13) {
        switch (day % 10) {
            case 1:  suffix = 'st'; break;
            case 2:  suffix = 'nd'; break;
            case 3:  suffix = 'rd'; break;
        }
    }
    
    return `${day}${suffix} ${month}, ${year}`;
}

        // =========================================================================
    // REUSABLE MATRIX: INSTITUTIONAL S.D.A CLONED COMPILER STRINGS LAYOUT ENGINE
    // =========================================================================
    function compileInstitutionalDocumentHTML(rowData) {
        // Resolve absolute server host properties pointers to dynamically embed custom typeface asset
        const localFontBaseUrl = window.location.origin + '/principal/assets/fonts/mtn_regular.ttf';
        
        // Parse calendar dates natively using institutional ordinal rule functions
        const actionRawDate = new Date(rowData.memo_date);
        const humanizedDocDate = formatOrdinalDate(actionRawDate);
        
        const docClassificationPure = rowData.document_classification || 'Memo';
        const classificationLabel = docClassificationPure.toUpperCase();
        const targetOfficeLabel = rowData.office_name || 'Office of the Principal';

        // CLEANING TRICK: Automatically strip out accidental blank spaces or trailing rules from Quill contents
        let cleanedContentBody = (rowData.content || '').trim();
        
        // Remove trailing lines or duplicate break segments generated accidentally by empty paragraph inputs
        cleanedContentBody = cleanedContentBody.replace(/(<p><br><\/p>)+$/, '');
        cleanedContentBody = cleanedContentBody.replace(/<hr\s*\/?>/gi, ''); // Suppress orphan lines pushing layouts

        return `
        <!-- DYNAMIC FONTS STYLING BLOCK CONTAINER LAYER -->
        <style>
            @font-face {
                font-family: 'MTN Sans';
                src: url('${localFontBaseUrl}') format('truetype');
                font-weight: normal;
                font-style: normal;
            }
            .canvas-print-frame, 
            .canvas-print-frame p, 
            .canvas-print-frame div, 
            .canvas-print-frame table, 
            .canvas-print-frame td, 
            .canvas-print-frame span,
            .canvas-print-frame strong,
            .canvas-print-frame small { 
                font-family: 'MTN Sans', Arial, sans-serif !important; 
            }
            .quill-body-view p {
                margin-top: 0 !important;
                margin-bottom: 12px !important;
                line-height: 1.6 !important;
            }
            /* Reset absolute margins boundaries on signature blocks layout blocks */
            .signatory-footer-table {
                page-break-inside: avoid !important;
            }
        </style>

            <div id="pushed_print_canvas" class="canvas-print-frame" style="width: 100%; max-width: 1000px; padding: 10px 0; background: #ffffff; color: #111111; box-sizing: border-box; margin: 0 auto; text-align: left;">

            <!-- INSTITUTIONAL LETTERHEAD CLONE MATRIX (UNIFIED COMPONENT GRID) -->
            <table cellpadding="0" cellspacing="0" border="0" style="width: 100%; border-collapse: collapse; margin-bottom: 5px; table-layout: fixed;">
                
                <!-- ROW 1: PRIMARY COLLEGE NAME & OFFICE DIVISION HEADINGS -->
                <tr>
                    <td colspan="3" style="text-align: center; padding-bottom: 25px; width: 1000px;">
                        <div style="font-size: 18pt; font-weight: bold; color: #003399; font-family: 'Arial Black', Arial, sans-serif; letter-spacing: -0.5px; text-transform: uppercase; margin: 0; padding: 0; line-height: 1.2; text-align: center !important;">
                            Seventh-Day Adventist College of Education
                        </div>
                         <div style="font-size: 15pt; font-weight: bold; color: #990066; font-family: 'Georgia', serif; margin: 6px 0 0 0; font-style: italic; text-align: center !important;">
                        ${targetOfficeLabel}
                    </div>
                    </td>
                </tr>  
                
                <!-- ROW 2: TRI-COLUMN MASTER BUSINESS BRIEFINGS -->
                <tr>
                    <!-- Left Column: Your Ref. Block Spacer (Locked Width 350px) -->
                    <td style="width: 350px; text-align: left; vertical-align: top; font-size: 11.5pt; color: #000000; padding-top: 5px;">
                        <div style="font-style: italic; font-family: Arial, sans-serif;">Your Ref. No.: ............................</div> 
                    </td>
                    
                    <!-- Center Column: Emblem Logo Vector Placement Area (Locked Width 260px) -->
                    <td style="width: 260px; text-align: center; vertical-align: top; padding: 0 5px;">
                        <img src="../assets/img/avatars/logo.png" style="width: 110px; height: 110px; display: inline-block; border: 0;" onerror="this.style.display='none';" />
                        <div style="font-size: 9.5pt; font-weight: bold; margin-top: 6px; color: #000000; font-family: Arial, sans-serif;">(Founded: 1962)</div>
                    </td>
                    
                    <!-- Right Column: Institutional Postal Registry Information (Locked Width 390px) -->
                    <td style="width: 390px; text-align: left; vertical-align: top; font-size: 11pt; line-height: 1.4; padding-left: 35px; padding-top: 5px; color: #000000; font-family: Arial, sans-serif;">
                        <div>P. O. Box AS 18</div>
                        <div>Asokore-Koforidua</div>
                        <div style="margin-bottom: 6px;">Ghana West Africa</div>
                        <div style="margin-bottom: 2px;">Tel: ${rowData.sender_phone || '+233 24 000 0000'}</div>
                        <div>Email: <span style="color: #003399; text-decoration: none;">g.admin@sedacoe.edu.gh</span></div>
                    </td>
                </tr>
                
                <!-- ROW 3: HORIZONTALLY SYNCHRONIZED METADATA BLOCK (Our Ref Left / Date Right) -->
                <tr>
                    <!-- Far-Left Column: Aligned with 'Your Ref.' bounds -->
                    <td style="width: 350px; text-align: left; vertical-align: bottom; padding-top: 0px; font-size: 11.5pt; color: #000000;">
                        <span style="font-style: italic; text-decoration: underline; font-family: Arial, sans-serif; font-weight: bold;">Our Ref. No.: ${rowData.memo_ref}</span>
                    </td>
                    
                    <!-- Middle Column Spacer to preserve core width boundaries -->
                    <td style="width: 260px;"></td>
                    
                    <!-- Far-Right Column: Aligned vertically with right address blocks -->
                    <td style="width: 390px; text-align: left; padding-left: 35px; vertical-align: bottom; padding-top: 0; font-size: 11.5pt; color: #000000; font-weight: normal; font-family: Arial, sans-serif;">
                        ${humanizedDocDate}
                    </td>
                </tr>

                <!-- ROW 4: SEPARATOR BOUNDARY STROKE LINE -->
                <tr>
                    <td colspan="3" style="padding-top: 5px; padding-bottom: 5px; width: 1000px;">
                        <div style="border-top: 2px solid #000000; height: 1px; width: 100%;"></div>
                    </td>
                </tr>
            </table>
            
            <!-- CLASSIFICATION LABEL BANNER -->
            ${docClassificationPure !== 'Official Letter' ? `
                <div style="text-align: center; font-size: 15pt; font-weight: bold; letter-spacing: 1px; margin: 25px 0 20px 0; color: #111111; font-family: Arial, sans-serif;">
                    OFFICIAL ${classificationLabel}
                </div>
            ` : '<div style="margin-top: 30px;"></div>'}

            <!-- TARGET RECIPIENT DESIGNATION BOX -->
            <div style="margin-bottom: 25px; font-size: 11.5pt; line-height: 1.5; color: #111111; font-family: Arial, sans-serif;">
                ${docClassificationPure == 'Official Letter' ? '' : '<strong style="display:block; margin-bottom:4px; color:#333; text-transform:uppercase;">TO:</strong>'}
                ${rowData.recipient_address}
            </div>

            <!-- DOCUMENT TITLE HEADING -->
            <div style="font-size: 12.5pt; font-weight: bold; text-transform: uppercase;  padding-bottom: 0px; margin-bottom: 0px; color: #003399; font-family: Arial, sans-serif;">
                ${rowData.title}
            </div>

            <!-- CORE RICH WORKSPACE CONTENT DATA BODY OUTPUT (QUILL ENFORCED) -->
            <div class="quill-body-view" style="font-size: 11.5pt; text-align: justify; min-height: 120px; color: #111111; font-family: Arial, sans-serif;">
                ${cleanedContentBody}
            </div> 
            <!-- NEW DYNAMIC SUBSCRIPTION BLOCK LAYER (LEFT ALIGNED MARGIN BOUNDS) -->
            <div class="quill-body-view" style="font-size: 11.5pt; text-align: left; margin-top: 35px; min-height: 80px; color: #000000; page-break-inside: avoid !important;">
                ${rowData.subscription || ''}
            </div>
 
        </div>`;
    }


$(document).ready(function() {
    
    // =========================================================================
    // CLIENT-SIDE EXPORT ENGINE: PERFECT RENDERING REPLICATOR (PDF & WORD)
    // =========================================================================
    // Dynamically inject the free open-source html2pdf library script asset tag
    if (typeof html2pdf === 'undefined') {
        const scriptElement = document.createElement('script');
        console.log('html2pdf is not defined');
        document.head.appendChild(scriptElement);
    }
 
 
        // Global active runtime tracker object to hold the current row properties bundle
    let currentPreviewPayload = null;


    // =========================================================================
    // INITIAL BUTTON CLICK TRIGGER: POPULATE PREVIEW & LAUNCH MODAL WINDOW
    // =========================================================================
    $(document).on('click', '.download-memo-pdf-btn', function(e) {
        e.preventDefault();
        
        const rowData = $(this).data('entry');
        if (!rowData || !rowData.id) {
            Swal.fire('Error', 'Unable to resolve document reference metadata parameters.', 'error');
            return;
        }

        // Cache the active dataset payload globally into the tracking variable
        currentPreviewPayload = rowData;

        // Compile HTML strings instantly and inject inside preview container surface
        const generatedPreviewMarkup = compileInstitutionalDocumentHTML(rowData);
        $('#preview_canvas_holder').html(generatedPreviewMarkup);

        // Open structural preview terminal canvas model view panel
        $('#memoPreviewModal').modal('show');
    });


    // =========================================================================
    // EXPORT ACTION A: DIRECT NATIVE PRINT HANDSHAKE WITH CUSTOM MTN SANS FONT
    // =========================================================================
        // =========================================================================
    // EXPORT ACTION A: DIRECT NATIVE PRINT HANDSHAKE WITH BALANCED MARGINS
    // =========================================================================
    $(document).on('click', '#preview_generate_pdf_btn', function () {
        const printableElement = document.getElementById('pushed_print_canvas');

        if (!printableElement) {
            Swal.fire({ 
                title: 'Print Error', 
                text: 'The structural document preview canvas layer could not be isolated.', 
                icon: 'error',
                confirmButtonColor: '#696cff'
            });
            return;
        }

        const printWindow = window.open('', '_blank');
        if (!printWindow) {
            Swal.fire({ 
                title: 'Popup Blocked', 
                text: 'Please allow popups to initialize print tasks.', 
                icon: 'warning',
                confirmButtonColor: '#696cff'
            });
            return;
        }

        const localFontBaseUrl = window.location.origin + '/principal/assets/fonts/mtn_regular.ttf';

        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="utf-8">
                <title>Institutional Document Portfolio</title>
                <style>
                    @font-face {
                        font-family: 'MTN Sans';
                        src: url('${localFontBaseUrl}') format('truetype');
                    }
                    
                    /* FIXED: Balanced side margins and reduced top/bottom padding gaps */
                    @page {
                        size: A4 portrait;
                        margin: 10mm 15mm; 
                    }
                    
                    body {
                        margin: 0;
                        padding: 0;
                        background: #ffffff;
                        color: #111111;
                        font-family: 'MTN Sans', Arial, sans-serif !important;
                        -webkit-print-color-adjust: exact;
                        print-color-adjust: exact;
                    }
                    
                    #pushed_print_canvas {
                        width: 100%;
                        max-width: 1000px;
                        margin: 0 auto;
                        padding: 0 !important; /* Strips double padding issues */
                        box-sizing: border-box;
                    }
                    
                    table {
                        width: 100%;
                        border-collapse: collapse;
                    }
                    
                    img {
                        max-width: 100%;
                        height: auto;
                    }
                    
                    #pushed_print_canvas * {
                        font-family: 'MTN Sans', Arial, sans-serif !important;
                    }
                </style>
            </head>
            <body>
                <!-- Inject perfect outer HTML structure directly -->
                <div id="pushed_print_canvas">
                    ${printableElement.innerHTML}
                </div>
            </body>
            </html>
        `);

        printWindow.document.close();
        printWindow.focus();

        setTimeout(function() {
            printWindow.print();
            printWindow.close();
            
            Swal.fire({
                title: 'Print Task Dispatched',
                text: 'The document portfolio has been formatted cleanly and sent to your printer hub.',
                icon: 'success',
                confirmButtonColor: '#696cff'
            });
        }, 600);
    });
 

    // =========================================================================
    // UI RESET HOOK: WIPE MEMORY REFERENCES WHEN PREVIEW PANEL CLOSES DOWN
    // =========================================================================
    $('#memoPreviewModal').on('hidden.bs.modal', function () {
        // Clear DOM structure inside preview canvas surface container area to prevent stale cache displays
        $('#preview_canvas_holder').empty();
        
        // Kill global tracker variable object reference instance parameter pointer
        currentPreviewPayload = null;
    });

    
    // 1. Initialize Quill Body Content Editor
const quillWorkspaceEditor = new Quill('#quill_editor_wrapper', {
    modules: { toolbar: [['bold', 'italic', 'underline'], [{ 'list': 'ordered'}, { 'list': 'bullet' }], [{ 'color': [] }, { 'background': [] }], ['clean']] },
    theme: 'snow'
});

// 2. Initialize Quill Recipient Address Editor
const quillRecipientAddressEditor = new Quill('#quill_recipient_address_wrapper', {
    modules: { toolbar: [['bold', 'italic', 'underline'], [{ 'color': [] }], ['clean']] },
    placeholder: 'e.g., The Dean,\nFaculty of Computer Science & Engineering',
    theme: 'snow'
});

// 3. NEW: Initialize Third Standalone Quill Instance for Subscription Desks
const quillSubscriptionEditor = new Quill('#quill_subscription_wrapper', {
    modules: { toolbar: [['bold', 'italic', 'underline'], [{ 'list': 'ordered'}, { 'list': 'bullet' }], [{ 'color': [] }], ['clean']] },
    placeholder: 'Yours faithfully,\n\n[Underline Name Here]\nPrincipal\n\nCC: Vice Chancellor\nHead of Audit',
    theme: 'snow'
});


 
$(document).ready(function() {
    
    // =========================================================================
    // MODAL SUBMISSION HANDLER SYNC: COMMIT THREE DYNAMIC EDITORS FOR POST
    // =========================================================================
    $('#memoSubmissionForm').on('submit', function(e) {
        e.preventDefault();
        const activeForm = this;

        // Extract raw HTML from all three editor instances
        const bodyRawHtmlContent = quillWorkspaceEditor.root.innerHTML.trim();
        const addressRawHtmlContent = quillRecipientAddressEditor.root.innerHTML.trim();
        const subscriptionRawHtmlContent = quillSubscriptionEditor.root.innerHTML.trim();
        
        // Validate fields against empty strings to block garbage inputs
        if (quillWorkspaceEditor.getText().trim().length === 0) {
            Swal.fire('Empty Context', 'Please provide document body contents instructions.', 'warning');
            return;
        }
        if (quillSubscriptionEditor.getText().trim().length === 0) {
            Swal.fire('Empty Subscription', 'Please complete the subscription valediction block text lines.', 'warning');
            return;
        }

        // Bind data parameters into their respective hidden layout payload vector slots
        $('#content_payload').val(bodyRawHtmlContent);
        $('#recipient_address_payload').val(addressRawHtmlContent);
        $('#subscription_payload').val(subscriptionRawHtmlContent);

        Swal.fire({
            title: 'Broadcast Correspondence?',
            text: "This notice will immediately publish across all desktop view registries logs.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#696cff',
            preConfirm: () => {
                return $.ajax({
                    url: server_controller,
                    type: 'POST',
                    data: $(activeForm).serialize() + '&save_memo=true',
                    dataType: 'json'
                }).then(res => {
                    if (res.status !== 'success') throw new Error(res.message);
                    return res;
                }).catch(err => { Swal.showValidationMessage(`Error: ${err.message}`); });
            }
        }).then(result => {
            if (result.isConfirmed && result.value) {
                $('#memoDispatchModal').modal('hide');
                
                // Clear inputs and reset ALL THREE Quill panels cleanly
                activeForm.reset();
                quillWorkspaceEditor.setContents([]); 
                quillRecipientAddressEditor.setContents([]); 
                quillSubscriptionEditor.setContents([]); 
                
                renderMemosTableUI(result.value.data);
                Swal.fire('Broadcasted!', result.value.message, 'success');
            }
        });
    });

    // =========================================================================
    // EDIT DATA TRIPPERS: EXTRACT RECORD AND FILL ALL METADATA FIELDS
    // =========================================================================
    $(document).on('click', '.edit-memo-btn', function(e) {
        e.preventDefault();
        const data = $(this).data('entry');
        
        $('#memoDispatchModal #modalCenterTitle').text('Amend Published Correspondence Metadata');
        $('#memoDispatchModal button[type="submit"]').html('<i class="bx bx-check-double me-1"></i> Save Modified Bulletin');
        
        $('#memoDispatchModal #memo_record_id').val(data.id);
        $('#memoDispatchModal #title').val(data.title);
        $('#memoDispatchModal #document_classification').val(data.document_classification);
        $('#memoDispatchModal #priority').val(data.priority);
        $('#memoDispatchModal #memo_date').val(data.memo_date);
            // Inside the edit-memo-btn listener
        $('#memoDispatchModal #office_name').val(data.office_name); 
        
        // Populate all three standalone Quill workspace interfaces safely
        quillRecipientAddressEditor.clipboard.dangerouslyPasteHTML(data.recipient_address || '');
        quillWorkspaceEditor.clipboard.dangerouslyPasteHTML(data.content || '');
        quillSubscriptionEditor.clipboard.dangerouslyPasteHTML(data.subscription || ''); // NEW INJECTION HOOK
        
        $('#memoDispatchModal').modal('show');
    });

    // =========================================================================
    // DMISSAL RECOVERY HOOKS: RESTORE PRISTINE STATES NATIVELY
    // =========================================================================
    $('#memoDispatchModal').on('hidden.bs.modal', function () {
        this.querySelector('form').reset();
        
        // Wipe clean tracking content buffers arrays across all instances
        quillWorkspaceEditor.setContents([]); 
        quillRecipientAddressEditor.setContents([]); 
        quillSubscriptionEditor.setContents([]); 
        
        $('#memoDispatchModal #memo_record_id').val('');
        $('#memoDispatchModal #modalCenterTitle').text('Publish Office Memo');
        $('#memoDispatchModal button[type="submit"]').html('<i class="bx bx-broadcast me-1"></i> Save');
        
        $('#memoDispatchModal #office_name').val('Office of the Principal');

        
        const todayStr = new Date().toISOString().split('T');
        $('#memoDispatchModal #memo_date').val(todayStr);
    });
});

 

    function in_array(needle, haystack) { return haystack.indexOf(needle) !== -1; }

         // =========================================================================
    // RETRACTION OPERATION: OBLITERATE BULLETIN RECORD LOGS (`delete_memo=true`)
    // =========================================================================
    $(document).on('click', '.delete-memo-btn', function(e) {
        e.preventDefault();
        const rowId = $(this).data('id');

        Swal.fire({
            title: 'Retract Circular Notice?',
            text: "Are you sure you want to completely erase this memo bulletin from the database logs?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ff3e1d',
            cancelButtonColor: '#8592a3',
            confirmButtonText: 'Yes, retract record',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return $.ajax({
                    url: server_controller,
                    type: 'POST',
                    data: { id: rowId, delete_memo: 'true' },
                    dataType: 'json'
                }).then(res => {
                    if (res.status !== 'success') throw new Error(res.message);
                    return res;
                }).catch(err => { Swal.showValidationMessage(`Error: ${err.message}`); });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then(result => {
            if (result.isConfirmed && result.value) {
                renderMemosTableUI(result.value.data);
                Swal.fire('Erased!', 'Correspondence successfully retracted from system registries.', 'success');
            }
        });
    });

    // =========================================================================
    // TOOLBAR ADVANCED SERVER-SIDE DATA FILTERING LINK (`fetch_filtered_memos=true`)
    // =========================================================================
    let searchTimer = null;
    function executeAdvancedMemoFiltration() {
        const payload = $('#serverMemoFilterForm').serialize() + '&fetch_filtered_memos=true';
        $('#memo_table_body').html('<tr><td colspan="7" class="text-center py-5 text-primary"><div class="spinner-border spinner-border-sm me-2" role="status"></div>Syncing administrative mailboxes...</td></tr>');
        
        $.ajax({
            url: server_controller,
            type: 'POST',
            data: payload,
            dataType: 'json'
        }).then(res => { if (res.status === 'success') renderMemosTableUI(res.data); });
    }

    $('#filter_priority').on('change', function() { executeAdvancedMemoFiltration(); });
    $('#search_keyword').on('keyup', function() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(executeAdvancedMemoFiltration, 300);
    });

    $('#reset_memo_filters_btn').on('click', function() {
        // FIXED DOM ELEMENTS SELECTOR RESET
        $('#serverMemoFilterForm')[0].reset();
        executeAdvancedMemoFiltration();
    });
 
        // Listen for dropdown changes, including the new dynamic classification type selector
    $('#filter_classification, #filter_priority').on('change', function() { 
        executeAdvancedMemoFiltration(); 
    });

});
</script>
