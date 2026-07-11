<?php
try {
    // Auth check loop is executed natively within your header wrap file template
    require_once 'includes/header.php'; 
    if (!$con) throw new Exception('System framework link connection lost.');

    $profilePackage = getActiveUserProfileData($con);
    if ($profilePackage['status'] === 'error') throw new Exception($profilePackage['message']);

    $userProfile = $profilePackage['data'];

} catch (\Throwable $e) {
    echo '<div class="alert alert-danger m-4">Profile Engine Initialization Fault: ' . htmlspecialchars($e->getMessage()) . '</div>';
    exit;
} ?>

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Account /</span> Profile Settings Management</h4>

        <div class="row g-4">
            
            <!-- ============================================================
                 LEFT PANEL: ACCOUNT METADATA & BIOMETRICS CARD
                 ============================================================ -->
            <div class="col-12 col-md-7">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-header border-bottom py-3 d-flex align-items-center gap-2">
                        <i class="bx bx-user text-primary fs-4"></i>
                        <h5 class="card-title mb-0 fw-bold text-primary">Identity Profile Details</h5>
                    </div>
                    <form id="profileAccountUpdateForm" method="POST" autocomplete="off">
                        <div class="card-body pt-4">
                            
                            
                            <!-- ============================================================
                                 REDESIGNED IDENTITY PROFILE BADGE MODULE (WITH INTERACTIVE PREVIEW)
                                 ============================================================ -->
                            <div class="row g-3 mb-4 bg-light p-3 rounded border border-dashed mx-1 align-items-center">
                                <!-- Left Section: Smart Circular Interactive Avatar -->
                               <div class="col-12 col-sm-4 text-center text-sm-start border-end-sm pe-sm-4 mb-3 mb-sm-0">
                                    <div class="position-relative d-inline-block">
                                        <?php 
                                        $avatarFile = !empty($userProfile['user_pic']) ? $userProfile['user_pic'] : 'default_avatar.png';
                                        $avatarPath = 'https://localhost/' . $avatarFile;
                                        ?>
                                        <!-- Profile Picture Canvas Holder -->
                                        <img src="<?php echo $avatarPath; ?>" id="profile_avatar_preview" alt="User Avatar" class="rounded-circle border border-2 border-white shadow-sm" style="width: 100px; height: 100px; object-fit: cover;" />
                                        
                                        <!-- FIXED CRITICAL INJECTION: Completely hidden native file receiver input node block -->
                                        <input type="file" id="user_image_cropper_input" class="d-none" accept="image/png, image/jpeg" style="display: none !important;" />
                                        
                                        <!-- Floating Edit Icon Overlay Action Node Trigger Link — This matches label targeting ID perfectly -->
                                        <label w3-id="camera-overlay" for="user_image_cropper_input" class="position-absolute bottom-0 end-0 bg-primary text-white rounded-circle d-flex align-items-center justify-content-center shadow" style="width: 32px; height: 32px; cursor: pointer; border: 2px solid #fff; transition: all 0.2s ease;">
                                            <i class="bx bx-camera fs-5"></i>
                                        </label>
                                    </div>
                                    <span class="d-block font-size-xs text-muted mt-2 text-center text-sm-start ps-sm-2 fw-medium">Click camera to crop photo</span>
                                </div>


                                
                                <div class="col-12 col-sm-8 ps-sm-3">
                                    <div class="row g-2">
                                        <!-- Employee Registry Key Code -->
                                        <div class="col-12 mb-2">
                                            <span class="text-muted d-block font-size-xs text-uppercase fw-bold tracking-wider mb-1">
                                                <i class="bx bx-id-card me-1 text-secondary"></i>Employee Registry ID
                                            </span>
                                            <h5 class="text-primary fw-bold mb-0 ps-1" style="font-family: monospace; letter-spacing: 0.5px;">
                                                #<?php echo htmlspecialchars($userProfile['employee_id']); ?>
                                            </h5>
                                        </div>
                                        
                                        <!-- System Authorization Context Node Role -->
                                        <div class="col-12">
                                            <span class="text-muted d-block font-size-xs text-uppercase fw-bold tracking-wider mb-1">
                                                <i class="bx bx-shield-quarter me-1 text-secondary"></i>System Access Clearance
                                            </span>
                                            <div class="ps-1">
                                                <span class="badge bg-label-primary font-weight-bold text-uppercase px-3 py-1 mt-1 tracking-wider" style="font-size: 0.75rem;">
                                                    <i class="bx bx-user-check me-1"></i><?php echo htmlspecialchars($userProfile['role_name']); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>


                            <div class="row g-3 mb-3">
                                <div class="col-12">
                                    <label for="profile_name" class="form-label small fw-semibold text-heading">Full Personnel Name</label>
                                    <input type="text" id="profile_name" name="name" class="form-control" value="<?php echo htmlspecialchars($userProfile['name']); ?>" required />
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-12">
                                    <label for="profile_email" class="form-label small fw-semibold text-heading">Official Email Address Location</label>
                                    <input type="email" id="profile_email" name="email" class="form-control" value="<?php echo htmlspecialchars($userProfile['email']); ?>" required />
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-12 col-sm-6">
                                    <label for="profile_phone" class="form-label small fw-semibold text-heading">Contact Phone Number</label>
                                    <input type="text" id="profile_phone" name="phone_number" class="form-control" value="<?php echo htmlspecialchars($userProfile['phone_number']); ?>" required />
                                </div>
                                <div class="col-12 col-sm-6">
                                    <label for="profile_dept" class="form-label small fw-semibold text-heading">Assigned Department Branch</label>
                                    <input type="text" id="profile_dept" class="form-control bg-light" value="<?php echo htmlspecialchars($userProfile['department']); ?>" readonly disabled />
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="profile_desig" class="form-label small fw-semibold text-heading">Official Rank / Designation Designation</label>
                                    <input type="text" id="profile_desig" class="form-control bg-light" value="<?php echo htmlspecialchars($userProfile['designation']); ?>" readonly disabled />
                                    <small class="text-muted font-size-xs mt-1 d-block">Department and Rank parameters are restricted and can only be altered through an HR administrator.</small>
                                </div>
                            </div>

                        </div>
                        <div class="card-footer border-top bg-light text-end py-3">
                            <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold">
                                <i class="bx bx-save me-1"></i> Commit Core Updates
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ============================================================
                 RIGHT PANEL: SECURITY OVERRIDES / PASSWORD RESET CARD
                 ============================================================ -->
            <div class="col-12 col-md-5">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-header border-bottom py-3 d-flex align-items-center gap-2">
                        <i class="bx bx-shield-quarter text-warning fs-4"></i>
                        <h5 class="card-title mb-0 fw-bold text-dark">Security Credential Reset</h5>
                    </div>
                    <form id="profileSecurityUpdateForm" method="POST" autocomplete="off">
                        <div class="card-body pt-4">
                            
                            <div class="row g-3 mb-3">
                                <div class="col-12">
                                    <label for="current_password" class="form-label small fw-semibold text-heading">Active Current Password</label>
                                    <div class="input-group input-group-merge">
                                        <input type="password" id="current_password" name="current_password" class="form-control" placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" required />
                                        <span class="input-group-text cursor-pointer toggle-password-visibility"><i class="bx bx-hide text-muted"></i></span>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-12">
                                    <label for="new_password" class="form-label small fw-semibold text-heading">New Target Password Override Key</label>
                                    <div class="input-group input-group-merge">
                                        <input type="password" id="new_password" name="new_password" class="form-control" placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" required />
                                        <span class="input-group-text cursor-pointer toggle-password-visibility"><i class="bx bx-hide text-muted"></i></span>
                                    </div>
                                    <small class="text-muted font-size-xs mt-1 d-block">Security parameters lock passwords to at least 6 alphanumeric characters.</small>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="confirm_password" class="form-label small fw-semibold text-heading">Confirm New Target Password Key</label>
                                    <div class="input-group input-group-merge">
                                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" required />
                                        <span class="input-group-text cursor-pointer toggle-password-visibility"><i class="bx bx-hide text-muted"></i></span>
                                    </div>
                                </div>
                            </div>

                        </div>
                        <div class="card-footer border-top bg-light text-end py-3">
                            <button type="submit" class="btn btn-warning btn-sm text-dark px-4 fw-bold">
                                <i class="bx bx-key me-1"></i> Override Password Key
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<section>
    <!-- ============================================================
     MODAL LAYOUT: LIVE PROFILE IMAGE CROPPER TERMINAL
     ============================================================ -->
<div class="modal fade" id="avatarCropperModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-bottom bg-dark text-white">
                <h5 class="modal-title fw-bold text-white"><i class="bx bx-crop me-2 fs-4"></i>Scale & Position Account Avatar</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center bg-light">
                <div class="img-container mx-auto" style="max-height: 400px; max-width: 100%;">
                    <!-- Target image reference element for Cropper.js workspace initialization -->
                    <img id="cropper_canvas_image" src="" style="max-width: 100%; display: block;" />
                </div>
            </div>
            <div class="modal-footer border-top bg-light justify-content-between">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="execute_avatar_crop_btn" class="btn btn-dark btn-sm px-4 fw-bold">
                    <i class="bx bx-check-circle me-1"></i> Lock & Apply Crop
                </button>
            </div>
        </div>
    </div>
</div>

</section>

<?php include_once 'includes/footer.php'; ?>
 
<script> 
    let activeCropperInstance = null;

    // =========================================================================
    // STEP 1: INTERCEPT FILE SELECTION AND LOAD INSIDE THE CROPPING CANVAS
    // =========================================================================
    $('#user_image_cropper_input').on('change', function(e) {
        const fileList = e.target.files;
        if (fileList && fileList.length > 0) {
            const uploadedFile = fileList[0];
            const fileReader = new FileReader();

            fileReader.onload = function(event) {
                // Initialize the Cropper preview image element
                const cropperImageNode = document.getElementById('cropper_canvas_image');
                cropperImageNode.src = event.target.result;

                // Launch modal canvas panel
                $('#avatarCropperModal').modal('show');
            };
            fileReader.readAsDataURL(uploadedFile);
        }
    });

    // Initialize Cropper workspace options object arrays when modal finishes transitioning
    $('#avatarCropperModal').on('shown.bs.modal', function() {
        const cropperImageTarget = document.getElementById('cropper_canvas_image');
        
        activeCropperInstance = new Cropper(cropperImageTarget, {
            aspectRatio: 1, // Enforce square aspect ratio profiles for avatars
            viewMode: 1,
            dragMode: 'move',
            background: true,
            responsive: true,
            checkOrientation: true
        });
    }).on('hidden.bs.modal', function() {
        // Dismantle instance completely to free up local memory arrays allocation nodes
        if (activeCropperInstance) {
            activeCropperInstance.destroy();
            activeCropperInstance = null;
        }
        // Empty text values selector elements to allow re-selection
        $('#user_image_cropper_input').val('');
    });

    // =========================================================================
    // STEP 2: CAPTURE CANVAS BYTES AND TRANSLATE TO BASE64 DATA URL STRING
    // =========================================================================
    // =========================================================================
    // STEP 2: CAPTURE CANVAS BYTES AND INSTANTLY POST TO ISOLATED BACKEND
    // =========================================================================
    $('#execute_avatar_crop_btn').on('click', function(e) {
        e.preventDefault();
        if (!activeCropperInstance) return;

        // Generate canvas object locked to square dimensions profile parameters
        const croppedCanvasNode = activeCropperInstance.getCroppedCanvas({
            width: 200,
            height: 200,
            imageSmoothingEnabled: true,
            imageSmoothingQuality: 'high'
        });

        // Translate binary pixels matrix into a standard non-scattered text string data token
        const generatedBase64DataUrlString = croppedCanvasNode.toDataURL('image/jpeg', 0.90);

        // Hide cropping workspace modal console panel
        $('#avatarCropperModal').modal('hide');

        Swal.fire({
            title: 'Uploading Profile Photo...',
            text: 'Mirroring cropped avatar data bytes streams onto system server.',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        // Fire transaction post stream directly up to the isolated backend endpoint
        $.ajax({
            url: server_controller,
            type: 'POST',
            data: {
                avatar_base64: generatedBase64DataUrlString,
                update_profile_avatar: 'true' // Flags the targeted Branch R handler logic
            },
            dataType: 'json'
        }).then(res => {
            if (res.status === 'success') {
                // Repaint the local avatar preview container instantly inside the badge wrapper
                $('#profile_avatar_preview').attr('src', generatedBase64DataUrlString);
                
                Swal.fire({
                    title: 'Photo Synchronized! 📸',
                    text: res.message,
                    icon: 'success',
                    confirmButtonColor: '#696cff'
                }).then(() => {
                    window.location.reload(); // Hard reload screen to clear background session caches
                });
            } else {
                throw new Error(res.message);
            }
        }).catch(err => {
            Swal.fire('Upload Failed', err.message || 'Connection lost during data stream transit.', 'error');
        });
    });

 
$(document).ready(function() {
    
    // Toggle obscure passwords character strings inputs visibility natively
    $(document).on('click', '.toggle-password-visibility', function() {
        const targetInputElement = $(this).closest('.input-group-merge').find('input');
        const eyeIcon = $(this).find('i');
        
        if (targetInputElement.attr('type') === 'password') {
            targetInputElement.attr('type', 'text');
            eyeIcon.removeClass('bx-hide').addClass('bx-show text-primary');
        } else {
            targetInputElement.attr('type', 'password');
            eyeIcon.removeClass('bx-show text-primary').addClass('bx-hide text-muted');
        }
    });

    // =========================================================================
    // SUBMIT 1: BIOMETRICS ACCOUNT CREDENTIALS UPDATE LINK (`update_profile_info=true`)
    // =========================================================================
    $('#profileAccountUpdateForm').on('submit', function(e) {
        e.preventDefault();
        const activeForm = this;

        Swal.fire({
            title: 'Commit Profile Modifications?',
            text: 'Are you sure you want to alter your account directory information properties?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#696cff',
            cancelButtonColor: '#8592a3',
            confirmButtonText: 'Yes, save modifications',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return $.ajax({
                    url: server_controller,
                    type: 'POST',
                    data: $(activeForm).serialize() + '&update_profile_info=true',
                    dataType: 'json'
                }).then(res => {
                    if (res.status !== 'success') throw new Error(res.message);
                    return res;
                }).catch(err => { Swal.showValidationMessage(`Operation Fault: ${err.message}`); });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then(result => {
            if (result.isConfirmed && result.value) {
                Swal.fire({
                    title: 'Identity Altered!',
                    text: result.value.message,
                    icon: 'success',
                    confirmButtonColor: '#696cff'
                });
            }
        });
    });

    // =========================================================================
    // SUBMIT 2: SECURITY KEY CREDENTIALS OVERRIDE LINK (`update_profile_security=true`)
    // =========================================================================
    $('#profileSecurityUpdateForm').on('submit', function(e) {
        e.preventDefault();
        const activeForm = this;

        const newKey = $('#new_password').val();
        const confKey = $('#confirm_password').val();

        if (newKey.length < 6) {
            Swal.fire('Security Barrier Code', 'New password keys must contain at least 6 alphanumeric characters.', 'warning');
            return;
        }
        if (newKey !== confKey) {
            Swal.fire('Mismatched Identifiers', 'Your password confirmation entry string does not match.', 'error');
            return;
        }

        Swal.fire({
            title: 'Override Cryptographic Password Key?',
            text: 'This action will instantly decouple your old login security footprint vectors.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ffab00',
            cancelButtonColor: '#8592a3',
            confirmButtonText: 'Yes, override key parameters',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return $.ajax({
                    url: server_controller,
                    type: 'POST',
                    data: $(activeForm).serialize() + '&update_profile_security=true',
                    dataType: 'json'
                }).then(res => {
                    if (res.status !== 'success') throw new Error(res.message);
                    return res;
                }).catch(err => { Swal.showValidationMessage(`Security Fault: ${err.message}`); });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then(result => {
            if (result.isConfirmed && result.value) {
                // FIXED DOM ELEMENTS SELECTOR RESET
                activeForm.reset(); 
                $('.toggle-password-visibility i').removeClass('bx-show text-primary').addClass('bx-hide text-muted');
                
                Swal.fire({
                    title: 'Overridden!',
                    text: result.value.message,
                    icon: 'success',
                    confirmButtonColor: '#696cff'
                });
            }
        });
    });
});
</script>

<style>
.border-dashed { 
    border-style: dashed !important; 
}
.font-size-xs { 
    font-size: 0.75rem !important; 
}
</style>
