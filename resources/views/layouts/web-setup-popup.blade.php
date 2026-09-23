@php
    $systemVersionSetting = $settings['system_version'] ?? '';
    $webSetupSetting = $settings['web_setup'] ?? '';
    $webUrlSetting = $settings['web_url'] ?? '';

    $showSetupPopup = false;
    if ($systemVersionSetting === '2.14.0') {
        if ($webSetupSetting === '' || ($webSetupSetting == '1' && empty($webUrlSetting))) {
            $showSetupPopup = true;
        }
    }
@endphp

@if($showSetupPopup)
<div id="web-setup-overlay" style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); z-index: 9999999; display: flex; align-items: center; justify-content: center; font-family: 'Outfit', sans-serif;">
    <div class="setup-modal-card" style="background: #ffffff; border-radius: 20px; width: 90%; max-width: 500px; padding: 40px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); border: 1px solid rgba(255, 255, 255, 0.8); text-align: center; transform: scale(0.9); opacity: 0; transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);">
        
        <!-- Step 1: Web Setup Choice -->
        <div id="step-choice">
            <div style="background: rgba(var(--bs-primary-rgb), 0.1); width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px;">
                <i class="ph ph-globe" style="font-size: 40px; color: var(--bs-primary);"></i>
            </div>
            <h4 style="font-weight: 700; color: #0f172a; margin-bottom: 12px; font-size: 24px;">Web Setup Configuration</h4>
            <p style="color: #64748b; font-size: 15px; line-height: 1.6; margin-bottom: 30px;">
                To enable all system features in this version (2.14.0), please configure your web setup status. Do you have a web portal configured for eClassify?
            </p>
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <button type="button" id="btn-setup-yes" class="btn btn-primary" style="padding: 12px 24px; font-weight: 600; border-radius: 12px; font-size: 15px; display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <i class="ph ph-check-circle"></i> Yes, I have a web setup
                </button>
                <button type="button" id="btn-setup-no" class="btn btn-outline-secondary" style="padding: 12px 24px; font-weight: 600; border-radius: 12px; font-size: 15px; color: #475569; border-color: #cbd5e1;">
                    No, I only use mobile apps
                </button>
            </div>
        </div>

        <!-- Step 2: Enter Web URL -->
        <div id="step-url" style="display: none;">
            <div style="background: rgba(var(--bs-primary-rgb), 0.1); width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px;">
                <i class="ph ph-link" style="font-size: 40px; color: var(--bs-primary);"></i>
            </div>
            <h4 style="font-weight: 700; color: #0f172a; margin-bottom: 12px; font-size: 24px;">Configure Web URL</h4>
            <p style="color: #64748b; font-size: 15px; line-height: 1.6; margin-bottom: 24px;">
                Please enter the URL of your eClassify web portal. This is required for deep-linking and web integration.
            </p>
            <form id="web-setup-form" onsubmit="return false;">
                <div style="margin-bottom: 24px; text-align: left;">
                    <label for="popup_web_url" style="font-weight: 600; font-size: 14px; color: #334155; margin-bottom: 8px; display: block;">Web Portal URL</label>
                    <input type="url" id="popup_web_url" class="form-control" placeholder="https://yourwebsite.com" style="padding: 12px 16px; border-radius: 10px; border: 1px solid #cbd5e1; font-size: 15px;" required>
                    <div id="url-error-msg" style="color: #ef4444; font-size: 13px; margin-top: 6px; display: none;">Please enter a valid URL starting with http:// or https://</div>
                </div>
                <div style="display: flex; gap: 12px;">
                    <button type="button" id="btn-url-back" class="btn btn-light" style="flex: 1; padding: 12px; font-weight: 600; border-radius: 12px; font-size: 15px; background: #f1f5f9; color: #475569;">
                        Back
                    </button>
                    <button type="submit" id="btn-url-submit" class="btn btn-primary" style="flex: 2; padding: 12px; font-weight: 600; border-radius: 12px; font-size: 15px; display: flex; align-items: center; justify-content: center; gap: 8px;">
                        Save & Continue
                    </button>
                </div>
            </form>
        </div>

        <!-- Step 3: Confirm No Web Setup -->
        <div id="step-confirm-no" style="display: none;">
            <div style="background: rgba(245, 158, 11, 0.1); width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px;">
                <i class="ph ph-warning" style="font-size: 40px; color: #f59e0b;"></i>
            </div>
            <h4 style="font-weight: 700; color: #0f172a; margin-bottom: 12px; font-size: 24px;">Are you sure?</h4>
            <p style="color: #64748b; font-size: 15px; line-height: 1.6; margin-bottom: 30px;">
                By confirming you do not have a web setup, some web features and redirects will be disabled. You can change this setting later in Web Settings.
            </p>
            <div style="display: flex; gap: 12px;">
                <button type="button" id="btn-confirm-back" class="btn btn-light" style="flex: 1; padding: 12px; font-weight: 600; border-radius: 12px; font-size: 15px; background: #f1f5f9; color: #475569;">
                    Cancel
                </button>
                <button type="button" id="btn-confirm-submit" class="btn btn-primary" style="flex: 2; padding: 12px; font-weight: 600; border-radius: 12px; font-size: 15px;">
                    Confirm
                </button>
            </div>
        </div>

        <!-- Loading / Progress State -->
        <div id="popup-loading" style="display: none; padding: 30px 0;">
            <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                <span class="visually-hidden">Loading...</span>
            </div>
            <h5 style="margin-top: 20px; font-weight: 600; color: #334155;">Saving settings...</h5>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Simple animation to show the card
        setTimeout(function() {
            $('.setup-modal-card').css({
                'transform': 'scale(1)',
                'opacity': '1'
            });
        }, 100);

        // Transition from choice to url input
        $('#btn-setup-yes').on('click', function() {
            $('#step-choice').fadeOut(200, function() {
                $('#step-url').fadeIn(200);
            });
        });

        // Go back to step choice from url input
        $('#btn-url-back').on('click', function() {
            $('#step-url').fadeOut(200, function() {
                $('#step-choice').fadeIn(200);
            });
        });

        // Transition from choice to confirm no
        $('#btn-setup-no').on('click', function() {
            $('#step-choice').fadeOut(200, function() {
                $('#step-confirm-no').fadeIn(200);
            });
        });

        // Go back to choice from confirm no
        $('#btn-confirm-back').on('click', function() {
            $('#step-confirm-no').fadeOut(200, function() {
                $('#step-choice').fadeIn(200);
            });
        });

        // Ajax submission function
        function submitWebSettings(webSetup, webUrl) {
            // Hide other steps, show loading
            $('#step-url').hide();
            $('#step-confirm-no').hide();
            $('#popup-loading').show();

            $.ajax({
                url: "{{ route('settings.store') }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    web_setup: webSetup,
                    web_url: webUrl
                },
                success: function(response) {
                    // Reload page on success
                    window.location.reload();
                },
                error: function(xhr) {
                    $('#popup-loading').hide();
                    // Go back to correct step on error and alert user
                    if (webSetup == 1) {
                        $('#step-url').show();
                    } else {
                        $('#step-confirm-no').show();
                    }
                    
                    if (typeof Toastify === "function") {
                        Toastify({
                            text: xhr.responseJSON?.message || "Something went wrong. Please try again.",
                            duration: 3000,
                            close: true,
                            gravity: "top",
                            position: "right",
                            backgroundColor: "#ef4444",
                        }).showToast();
                    } else {
                        alert(xhr.responseJSON?.message || "Something went wrong. Please try again.");
                    }
                }
            });
        }

        // Handle Confirm Yes URL Submit
        $('#web-setup-form').on('submit', function(e) {
            e.preventDefault();
            const webUrl = $('#popup_web_url').val().trim();
            const urlPattern = /^https?:\/\/.+/i;

            if (!urlPattern.test(webUrl)) {
                $('#url-error-msg').show();
                $('#popup_web_url').addClass('is-invalid');
                return false;
            }

            $('#url-error-msg').hide();
            $('#popup_web_url').removeClass('is-invalid');

            submitWebSettings(1, webUrl);
        });

        // Handle Confirm No Submit
        $('#btn-confirm-submit').on('click', function() {
            submitWebSettings(0, '');
        });
    });
</script>
@endif
