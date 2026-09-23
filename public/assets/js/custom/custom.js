// @ts-nocheck

const AVATAR_COLORS = ['#1abc9c','#2ecc71','#3498db','#9b59b6','#34495e','#16a085','#27ae60','#2980b9','#8e44ad','#2c3e50','#f1c40f','#e67e22','#e74c3c','#d35400','#c0392b'];

function generateInitialAvatar(name, size) {
    size = size || 40;
    
    if (!name || !name.trim()) {
        return '<div class="avatar-placeholder" style="width:' + size + 'px;height:' + size + 'px;"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40"><path d="M20,4.211a7.191,7.191,0,0,0-7,7.368,7.191,7.191,0,0,0,7,7.368,7.191,7.191,0,0,0,7-7.368,7.191,7.191,0,0,0-7-7.368ZM9,11.579C9,5.184,13.925,0,20,0S31,5.184,31,11.579,26.075,23.158,20,23.158,9,17.974,9,11.579Zm11,20a22.19,22.19,0,0,0-16.545,7.76,1.93,1.93,0,0,1-2.827.088,2.184,2.184,0,0,1-.083-2.976A26.1,26.1,0,0,1,20,27.368,26.1,26.1,0,0,1,39.455,36.45a2.184,2.184,0,0,1-.083,2.976,1.93,1.93,0,0,1-2.827-.088A22.19,22.19,0,0,0,20,31.579Z" fill="currentColor" fill-rule="evenodd"/></svg></div>';
    }
    var initial = name.charAt(0).toUpperCase();
    var sum = 0;
    for (var i = 0; i < name.length; i++) {
        sum += name.charCodeAt(i);
    }
    var color = AVATAR_COLORS[sum % AVATAR_COLORS.length];
    return '<div class="avatar-initial" style="width:' + size + 'px;height:' + size + 'px;background-color:' + color + ';">' + initial + '</div>';
}

function escapeHtml(text) {
    return String(text ?? '').replace(/[&<>"']/g, function (c) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
}

function generateInitialAvatarDataUri(name, size) {
    size = size || 40;
    
    var initial = (name && name.trim()) ? name.trim().charAt(0).toUpperCase() : '?';
    var sum = 0;
    for (var i = 0; i < (name || '').length; i++) sum += name.charCodeAt(i);
    var color = AVATAR_COLORS[sum % AVATAR_COLORS.length];
    
    var svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' + size + '" height="' + size + '" viewBox="0 0 ' + size + ' ' + size + '">' +
        '<rect width="100%" height="100%" fill="' + color + '"/>' +
        '<text x="50%" y="50%" fill="#fff" font-family="Arial,sans-serif" font-size="' + Math.round(size * 0.45) + '" text-anchor="middle" dominant-baseline="central">' + escapeHtml(initial) + '</text>' +
        '</svg>';
    return 'data:image/svg+xml,' + encodeURIComponent(svg);
}

function onErrorUserAvatar(e,size) {
    var img = e.target;
    if (!img.src || !img.getAttribute('src')) return;
    var name = img.getAttribute('data-name') || '';
    
    img.src = generateInitialAvatarDataUri(name,size);
}

$(document).ready(function () {

    /// START :: ACTIVE MENU CODE
    function setActiveMenu() {
        let pageUrl = window.location.href.split(/[?#]/)[0];
        // Strip trailing slash for consistent comparisons
        const normalise = function(url) { return url.replace(/\/$/, ''); };
        pageUrl = normalise(pageUrl);

        // Minimum length a link URL must have to be considered for prefix matching.
        // Prevents the app root (e.g. "http://localhost") from matching every page.
        const baseOrigin = window.location.origin;
        const minPrefixLen = baseOrigin.length + 2; // origin + '/' + at least 1 char

        $(".menu a").each(function () {
            const rawLink = this.href ? this.href.split(/[?#]/)[0] : '';
            const linkUrl  = normalise(rawLink);
            if (!linkUrl) return;

            // Exact match  –OR–  prefix match (child/detail pages).
            // Using linkUrl + '/' prevents /settings matching /settings-extra.
            const isMatch = (linkUrl === pageUrl) ||
                            (linkUrl.length >= minPrefixLen &&
                             pageUrl.startsWith(linkUrl + '/'));

            if (isMatch) {
                const $link = $(this);
                const $submenuItem = $link.closest('.submenu-item');

                if ($submenuItem.length) {
                    // ── Submenu item ──────────────────────────────────────
                    $submenuItem.addClass("active");

                    const $parentSidebarItem = $submenuItem.closest('.sidebar-item.has-sub');
                    if ($parentSidebarItem.length) {
                        $parentSidebarItem.addClass("active");

                        const $submenu = $parentSidebarItem.find('> .submenu');
                        if ($submenu.length) {
                            $submenu.css('display', '');
                            $submenu.addClass("active");
                            $parentSidebarItem.addClass("submenu-open");
                        }
                    }
                } else {
                    // ── Top-level sidebar item (Settings, Notification…) ──
                    const $parent = $link.parent();
                    $parent.addClass("active");
                    $parent.parent().addClass("active");
                    $parent.parent().prev().addClass("active");
                    $parent.parent().parent().addClass("active");
                    $parent.parent().parent().parent().addClass("active");

                    $parent.prevAll('.sidebar-new-title').first().addClass('active');
                }
            }

            // ── subURL fallback (<a id="subURL"> used by detail/edit views) ──
            let subURL = $("a#subURL").attr("href");
            if (subURL && subURL !== 'undefined') {
                subURL = normalise(subURL);
                const isSubMatch = (linkUrl === subURL) ||
                                   (linkUrl.length >= minPrefixLen &&
                                    subURL.startsWith(linkUrl + '/'));

                if (isSubMatch) {
                    const $link2 = $(this);
                    const $submenuItem2 = $link2.closest('.submenu-item');

                    if ($submenuItem2.length) {
                        $submenuItem2.addClass("active");
                        const $parentSidebarItem2 = $submenuItem2.closest('.sidebar-item.has-sub');
                        if ($parentSidebarItem2.length) {
                            $parentSidebarItem2.addClass("active");
                            const $submenu2 = $parentSidebarItem2.find('> .submenu');
                            if ($submenu2.length) {
                                $submenu2.css('display', '');
                                $submenu2.addClass("active");
                                $parentSidebarItem2.addClass("submenu-open");
                            }
                        }
                    } else {
                        const $parent2 = $link2.parent();
                        $parent2.addClass("active");
                        $parent2.parent().addClass("active");
                        $parent2.parent().prev().addClass("active");
                        $parent2.parent().parent().addClass("active");

                        $parent2.prevAll('.sidebar-new-title').first().addClass('active');
                    }
                }
            }
        });
    }

    // Function to ensure submenus with active items have the active class
    function ensureActiveSubmenus() {
        $('.sidebar-item.has-sub.active').each(function() {
            const $sidebarItem = $(this);
            const $submenu = $sidebarItem.find('.submenu');
            
            // If sidebar-item is active but submenu doesn't have active class
            // Check if submenu has active items
            if ($submenu.length && !$submenu.hasClass('active')) {
                const hasActiveItem = $submenu.find('.submenu-item.active').length > 0;
                if (hasActiveItem) {
                    // Remove any inline display styles
                    $submenu.css('display', '');
                    // Add active class
                    $submenu.addClass('active');
                    $sidebarItem.addClass('submenu-open');
                }
            }
        });
    }
    
    // Run active menu detection on document ready
    setActiveMenu();
    
    // Also run after a short delay to catch any dynamically loaded content
    setTimeout(function() {
        setActiveMenu();
        ensureActiveSubmenus();
    }, 100);
    
    // Run again after a longer delay to ensure everything is set
    setTimeout(function() {
        ensureActiveSubmenus();
    }, 500);
    /// END :: ACTIVE MENU CODE

    /// START :: SIDEBAR SMOOTH TRANSITIONS
    // ─── Step 1: Inject rotatable chevron arrows into every parent link ───────
    (function injectArrows() {
        document.querySelectorAll('.sidebar-item.has-sub > .sidebar-link').forEach(function(link) {
            if (link.querySelector('.sidebar-chevron')) return;
            var arrow = document.createElement('span');
            arrow.className = 'sidebar-chevron';
            arrow.setAttribute('aria-hidden', 'true');
            link.appendChild(arrow);
        });
    })();

    // ─── Step 2: Single smooth toggle — capture phase fires before mazer.js ──
    // Active menus CAN be closed by clicking their parent link again.
    (function initSmoothToggle() {
        var sidebarMenu = document.getElementById('sidebarMenu');
        if (!sidebarMenu) return;

        // Track menus the user has deliberately closed so the 500ms
        // ensureActiveSubmenus pass doesn't silently re-open them.
        var manuallyClosed = new WeakSet();

        sidebarMenu.addEventListener('click', function(e) {
            var link = e.target.closest('.sidebar-item.has-sub > .sidebar-link');
            if (!link) return;

            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();

            var sidebarItem = link.closest('.sidebar-item.has-sub');
            var $submenu    = $(sidebarItem).find('> .submenu');
            if (!$submenu.length) return;

            var isOpen = $submenu.hasClass('active');

            if (isOpen) {
                // ── CLOSE (always allowed, even for the active parent) ───────
                manuallyClosed.add(sidebarItem);
                $(sidebarItem).removeClass('submenu-open');
                $submenu.stop(true, false).slideUp(220, function() {
                    $submenu.removeClass('active').css('display', '');
                });
            } else {
                // ── OPEN ─────────────────────────────────────────────────────
                if (manuallyClosed.delete) manuallyClosed.delete(sidebarItem);
                $submenu.addClass('active').hide();
                $(sidebarItem).addClass('submenu-open');
                $submenu.stop(true, false).slideDown(220, function() {
                    $submenu.css('display', '');
                });
            }
        }, true);

        // Patch ensureActiveSubmenus to respect menus the user deliberately closed
        var _origEnsure = ensureActiveSubmenus;
        ensureActiveSubmenus = function() {
            $('.sidebar-item.has-sub.active').each(function() {
                if (manuallyClosed.has(this)) return; // respect user's choice
                var $sidebarItem = $(this);
                var $submenu = $sidebarItem.find('> .submenu');
                if ($submenu.length && !$submenu.hasClass('active')) {
                    var hasActiveItem = $submenu.find('.submenu-item.active').length > 0;
                    if (hasActiveItem) {
                        $submenu.css('display', '');
                        $submenu.addClass('active');
                        $sidebarItem.addClass('submenu-open');
                    }
                }
            });
        };
    })();
    /// END :: SIDEBAR SMOOTH TRANSITIONS
    // Only target <select> elements — `.select2` also matches select2's own
    // generated container spans, and re-initializing those creates a phantom
    // empty widget (breaks selects initialized earlier in page scripts).
    if ($('select.select2').length > 0) {
        $('select.select2').select2();
    }

    $('.select2-selection__clear').hide();

    FilePond.registerPlugin(FilePondPluginImagePreview, FilePondPluginFileValidateSize,
        FilePondPluginFileValidateType);

    if ($('.filepond').length > 0) {
        $('.filepond').filepond({
            credits: null,
            allowFileSizeValidation: "true",
            maxFileSize: '25MB',
            labelMaxFileSizeExceeded: 'File is too large',
            labelMaxFileSize: 'Maximum file size is {filesize}',
            allowFileTypeValidation: true,
            acceptedFileTypes: ['image/*'],
            labelFileTypeNotAllowed: 'File of invalid type',
            fileValidateTypeLabelExpectedTypes: 'Expects {allButLastType} or {lastType}',
            storeAsFile: true,
            allowPdfPreview: true,
            pdfPreviewHeight: 320,
            pdfComponentExtraParams: 'toolbar=0&navpanes=0&scrollbar=0&view=fitH',
            allowVideoPreview: true, // default true
            allowAudioPreview: true // default true
        });
    }

    //magnific popup
    $(document).on('click', '.image-popup-no-margins', function () {
        $(this).magnificPopup({
            type: 'image',
            closeOnContentClick: true,
            closeBtnInside: false,
            fixedContentPos: true,
            image: {
                verticalFit: true
            },
            zoom: {
                enabled: true,
                duration: 300 // don't forget to change the duration also in CSS
            },
            gallery: {
                enabled: true
            },
        }).magnificPopup('open');
        return false;
    });

    $('#table_list').on('load-success.bs.table', function () {
        if ($('.gallery').length > 0) {
            $('.gallery').each(function () { // the containers for all your galleries
                $(this).magnificPopup({
                    delegate: 'a', // the selector for gallery item
                    type: 'image',
                    gallery: {
                        enabled: true
                    }
                });
            });
        }
    })

    $(document).off('focusin');
});


/// START :: TinyMCE
document.addEventListener("DOMContentLoaded", () => {
    tinymce.init({
        selector: '#tinymce_editor',
        height: 400,
        menubar: true,
        plugins: [
            'advlist autolink lists link charmap print preview anchor',
            'searchreplace visualblocks code fullscreen',
            'insertdatetime table paste code help wordcount'
        ],

        toolbar: 'insert | undo redo |  formatselect | bold italic backcolor  | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | help',
        setup: function (editor) {
            editor.on("change keyup", function () {
                //tinyMCE.triggerSave(); // updates all instances
                editor.save(); // updates this instance's textarea
                $(editor.getElement()).trigger('change'); // for garlic to detect change
            });
        }
    });
});

$('body').append('<div id="loader-container"><div class="loader"></div></div>');
$(window).on('load', function () {
    $('#loader-container').fadeOut('slow');
});

setTimeout(function () {
    $(".error-msg").fadeOut(1500)
}, 5000);

document.addEventListener('touchstart', event => {
    if (event.cancelable) {
        event.preventDefault();
    }
});

document.addEventListener('touchmove', event => {
    if (event.cancelable) {
        event.preventDefault();
    }
});

document.addEventListener('touchcancel', event => {
    if (event.cancelable) {
        event.preventDefault();
    }
});

$('.status-switch').on('change', function () {
    if ($(this).is(":checked")) {
        $(this).siblings('input[type="hidden"]').val(1);
    } else {
        $(this).siblings('input[type="hidden"]').val(0);
    }
})

$('input[type="radio"][name="duration_type"]').on('click', function () {
    if ($(this).hasClass('edit_duration_type')) {
        if ($(this).is(':checked')) {
            if ($(this).val() == 'limited') {
                $('#edit_limitation_for_duration').show();
                $('#edit_durationLimit').attr("required", "true").val("");
            } else {
                // Unlimited
                $('#edit_limitation_for_duration').hide();
                $('#edit_durationLimit').removeAttr("required").val("");
            }
        }
    } else {
        if ($(this).is(':checked')) {
            if ($(this).val() == 'limited') {
                $('#limitation_for_duration').show();
                $('#durationLimit').attr("required", "true").val("");
            } else {
                // Unlimited
                $('#limitation_for_duration').hide();
                $('#durationLimit').removeAttr("required").val("");
            }
        }
    }
});

$('input[type="radio"][name="item_limit_type"]').on('click', function () {
    if ($(this).hasClass('edit_item_limit_type')) {
        if ($(this).is(':checked')) {
            if ($(this).val() == 'limited') {
                $('#edit_limitation_for_limit').show();
                $('#edit_ForLimit').attr("required", "true");
            } else {
                // Unlimited
                $('#edit_limitation_for_limit').hide();
                $('#edit_ForLimit').val('');
                $('#edit_ForLimit').removeAttr("required");
            }
        }
    } else {
        if ($(this).is(':checked')) {
            if ($(this).val() == 'limited') {
                $('#limitation_for_limit').show();
                $('#durationForLimit').attr("required", "true");
            } else {
                // Unlimited
                $('#limitation_for_limit').hide();
                $('#durationForLimit').removeAttr("required");
            }
        }
    }
});

$('#filter').change(function () {
    let selectedValue = $(this).val();
    // Hide all criteria elements initially
    $('#category_criteria, #price_criteria').hide();
    // Show the relevant criteria based on the selected option
    if (selectedValue === "category_criteria") {
        $('#category_criteria').show();
    } else if (selectedValue === "price_criteria") {
        $('#price_criteria').show();
    }
});

$('#edit_filter').change(function () {
    let selectedValue = $(this).val();
    $('#edit_min_price').val("");
    $('#edit_max_price').val("");
    // Hide all criteria elements initially
    $('#edit_category_criteria, #edit_price_criteria').hide();
    // Show the relevant criteria based on the selected option
    if (selectedValue === "category_criteria") {
        $('#edit_category_criteria').show();
    } else if (selectedValue === "price_criteria") {
        $('#edit_price_criteria').show();
    }
});

$("#include_image").change(function () {
    if (this.checked) {
        $('#show_image').show('fast');
        $('#file').attr('required', 'required');
    } else {
        $('#file').val('');
        $('#file').removeAttr('required');
        $('#show_image').hide('fast');
    }
});

function updateSelectedUsers() {
    let user_list = [];
    let data = $("#user_notification_list").bootstrapTable('getSelections');
    data.forEach(function (value) {
        if (value.id != "") {
            user_list.push(value.id);
        }
    });
    // safer to use val() instead of .text() for form fields
    $('textarea#user_id').val(user_list.join(','));
}

$('#user_notification_list').on('check.bs.table uncheck.bs.table check-all.bs.table uncheck-all.bs.table', function () {
    updateSelectedUsers();
});

$('#delete_multiple').on('click', function (e) {
    e.preventDefault();
    let table = $('#table_list');
    let selected = table.bootstrapTable('getSelections');
    let ids = "";

    $.each(selected, function (i, e) {
        ids += e.id + ",";
    });
    ids = ids.slice(0, -1);
    if (ids == "") {
        showErrorToast(trans('Please Select Notification First'));
    } else {
        showDeletePopupModal($(this).attr('href'), {
            data: {
                id: ids
            }, successCallBack: function () {
                $('#table_list').bootstrapTable('refresh');
            }
        })
    }
});


$(".checkbox-toggle-switch").on('change', function () {
    let inputValue = $(this).is(':checked') ? 1 : 0;
    $(this).siblings(".checkbox-toggle-switch-input").val(inputValue);
});

$('.toggle-button').on('click', function (e) {
    e.preventDefault();
    $(this).closest('.category-header').next('.subcategories').slideToggle();
});

let length = $('#sub_category_count').val();

for (let i = 1; i <= length; i++) {
    $('.child_category_list' + i).hide();
    $('#sub_category' + i).change(function () {
        $('#child_category' + i).prop("checked", $(this).is(":checked"));
    });

    $('#category_arrow' + i).on('click', function () {
        $('.child_category_list' + i).toggle();
    });
}

$('#type').on('change', function () {
    if ($.inArray($(this).val(), ['checkbox', 'radio', 'dropdown']) > -1) {
        $('#field-values-div').slideDown(500);
        $('.min-max-fields').slideUp(500);
    } else if ($.inArray($(this).val(), ['fileinput']) > -1) {
        $('.min-max-fields').slideUp(500);
    } else {
        $('#field-values-div').slideUp(500);
        $('.min-max-fields').slideDown(500);
    }
});

$('.image').on('change', function () {
    const allowedExtensions = /(\.jpg|\.jpeg|\.png|\.gif)$/i;
    const fileInput = this;
    const [file] = fileInput.files;
    if (!file) {
        return; // No file selected
    }

    if (!allowedExtensions.exec(file.name)) {
        $('.img_error').text('Invalid file type. Please choose an image file.');
        fileInput.value = '';
        return;
    }

    const maxFileSize = 2 * 1024 * 1024; // 5MB (adjust as needed)
    if (file.size > maxFileSize) {
        $('.img_error').text('File size exceeds the maximum allowed size (2MB).');
        fileInput.value = '';
    }
    if (file) {
        $(this).siblings('#initial-avatar-wrapper').hide();
        $(this).siblings('.preview-image').attr('src', URL.createObjectURL(file)).show();
    }
});

$('.img_input').on('click', function () {
    $(this).siblings('.image').click();
});

$(".toggle-password").on('click', function () {
    $(this).toggleClass("bi bi-eye bi-eye-slash");
    let input = $(this).parent().siblings("input");
    if (input.attr("type") == "password") {
        input.attr("type", "text");
    } else {
        input.attr("type", "password");
    }
});

$('#price,#discount_in_percentage').on('input', function () {
    let price = $('#price').val();
    let discount = $('#discount_in_percentage').val();
    let final_price = calculateDiscountedAmount(price, discount);
    $('#final_price').val(final_price);
})

$('#final_price').on('input', function () {
    let discountedPrice = $(this).val();
    let price = $('#price').val();
    let discount = calculateDiscount(price, discountedPrice);
    $('#discount_in_percentage').val(discount);
})


$('#edit_price,#edit_discount_in_percentage').on('input', function () {
    let price = $('#edit_price').val();
    let discount = $('#edit_discount_in_percentage').val();
    let final_price = calculateDiscountedAmount(price, discount);
    $('#edit_final_price').val(final_price);
})

$('#edit_final_price').on('input', function () {
    let discountedPrice = $(this).val();
    let price = $('#edit_price').val();
    let discount = calculateDiscount(price, discountedPrice);
    $('#edit_discount_in_percentage').val(discount);
})
$('#slug').bind('keyup blur', function () {
    $(this).val($(this).val().replace(/[^A-Za-z0-9-]/g, ''))
});

function toggleRejectedReasonVisibility() {
    var status = $('#status').val();
    var rejectedReasonContainer = $('#rejected_reason_container');
    if (status === 'soft rejected' || status === 'permanent rejected') {
        rejectedReasonContainer.show();
    } else {
        rejectedReasonContainer.hide();
    }
}

$('.editdata, #status').on('click change', function () {
    toggleRejectedReasonVisibility();
});

$(document).on('change', '.update-item-status', function () {
    let url = window.baseurl + "common/change-status";
    ajaxRequest('PUT', url, {
        id: $(this).attr('id'),
        table: "items",
        column: "deleted_at",
        status: $(this).is(':checked') ? 1 : 0
    }, null, function (response) {
        showSuccessToast(response.message);
    }, function (error) {
        showErrorToast(error.message);
    })
})

$(document).on('change', '.update-user-status', function () {
    let url = window.baseurl + "common/change-status";
    ajaxRequest('PUT', url, {
        id: $(this).attr('id'),
        table: "users",
        column: "deleted_at",
        status: $(this).is(':checked') ? 1 : 0
    }, null, function (response) {
        showSuccessToast(response.message);
    }, function (error) {
        showErrorToast(error.message);
    })
})
$(document).on('change', '.update-auto-approve-status', function () {
    let url = window.baseurl + "common/change-status";
    ajaxRequest('PUT', url, {
        id: $(this).attr('id'),
        table: "users",
        column: "auto_approve_item",
        status: $(this).is(':checked') ? 1 : 0
    }, null, function (response) {
        showSuccessToast(response.message);
    }, function (error) {
        showErrorToast(error.message);
    });
});

$('#switch_banner_ad_status').on('change', function () {
    $('#banner_ad_id_android').attr('required', $(this).is(':checked'));
    $('#banner_ad_id_ios').attr('required', $(this).is(':checked'));
})
$('.package_type').on('change', function () {
    if ($(this).val() == 'item_listing') {
        $('#package_details').hide();
        $('.payment').hide();
        $('.cheque').hide();
        $('#item-listing-package-div').show();
        $('#advertisement-package-div').hide();

        $('#item-listing-package').attr('required', true);
        $('#advertisement-package').attr('required', false);
    } else if ($(this).val() == 'advertisement') {
        $('#package_details').hide();
        $('.payment').hide();
        $('.cheque').hide();
        $('#item-listing-package-div').hide();
        $('#advertisement-package-div').show();

        $('#advertisement-package').attr('required', true);
        $('#item-listing-package').attr('required', false);
    }
});

$('.package').on('change', function () {
    let package_detail = $(this).find('option:selected').data('details');
    let currency_settings = $('#currency-settings');
    let currency_symbol = currency_settings.data('symbol');
    let currency_position = currency_settings.data('position');

    if (package_detail != null) {
        $('#package_details').show();
        if (parseFloat(package_detail.final_price) > 0) {
            $('.payment').show();
        } else {
            $('.payment').hide();
            $('.cheque').hide();
            $('.payment_gateway').prop('checked', false);
        }
    } else {
        $('#package_details').hide();
        $('.payment').hide();
        $('.cheque').hide();
    }
    let formatted_price = formatPriceWithCurrency(package_detail?.price, currency_symbol, currency_position);
    let formatted_final_price = formatPriceWithCurrency(package_detail?.final_price, currency_symbol, currency_position);
    let formatted_duration = package_detail?.duration ? `${package_detail?.duration} Days` : '';

    $("#package_name").text(package_detail?.name);
    $("#package_price").text(formatted_price);
    $("#package_final_price").text(formatted_final_price);
    $("#package_duration").text(formatted_duration);
});
function formatPriceWithCurrency(price, symbol, position) {
    if (!price && price != 0) return "";
    return position === "left" ? `${symbol} ${price}` : `${price} ${symbol}`;
}

$('.payment_gateway').change(function () {
    if ($(this).val() == 'cheque') {
        $('.cheque').show();
    } else {
        $('.cheque').hide();
    }

    $('.payment').val('').trigger('change');
});

$('#switch_interstitial_ad_status').on('change', function () {
    $('#interstitial_ad_id_android').attr('required', $(this).is(':checked'));
    $('#interstitial_ad_id_ios').attr('required', $(this).is(':checked'));
})

$('#country').on('change', function () {
    let countryId = $(this).val();
    let url = window.baseurl + 'states/search?country_id=' + countryId;
    
    ajaxRequest('GET', url, null, null, function (response) {
        $('#state').html("<option value=''>" + window.trans("--Select State--") + "</option>")
        $.each(response.data, function (key, value) {
            $('#state').append($('<option>', {
                value: value.id,
                text: value.name
            }));
        });
    })
});

$('.country').on('change', function () {
    let countryId = $(this).val();
    let url = window.baseurl + 'states/search?country_id=' + countryId;
    ajaxRequest('GET', url, null, null, function (response) {
        $('#edit_state').html("<option value=''>" + window.trans("--Select State--") + "</option>")
        $.each(response.data, function (key, value) {
            $('#edit_state').append($('<option>', {
                value: value.id,
                text: value.name
            }));
        });
    })
});
$('#state').on('change', function () {
    let stateId = $(this).val();
    let url = window.baseurl + 'cities/search?state_id=' + stateId;
    ajaxRequest('GET', url, null, null, function (response) {
        $('#city').html("<option value=''>" + window.trans("--Select City--") + "</option>")
        $.each(response.data, function (key, value) {
            $('#city').append($('<option>', {
                value: value.id,
                text: value.name
            }));
        });
    })
});
$('#edit_state').on('change', function () {
    let stateId = $(this).val();
    let url = window.baseurl + 'cities/search?state_id=' + stateId;
    ajaxRequest('GET', url, null, null, function (response) {
        $('#edit_city').html("<option value=''>" + window.trans("--Select City--") + "</option>")
        $.each(response.data, function (key, value) {
            $('#edit_city').append($('<option>', {
                value: value.id,
                text: value.name
            }));
        });
    })
});

$('#filter_country').on('change', function () {
    let countryId = $(this).val();
    let url = window.baseurl + 'states/search?country_id=' + countryId;
    ajaxRequest('GET', url, null, null, function (response) {
        $('#filter_state').html("<option value=''>" + window.trans("All") + "</option>")
        $.each(response.data, function (key, value) {
            $('#filter_state').append($('<option>', {
                value: value.id,
                text: value.name
            }));
        });
    })
});

$('#filter_state').on('change', function () {
    let stateId = $(this).val();
    let url = window.baseurl + 'cities/search?state_id=' + stateId;
    ajaxRequest('GET', url, null, null, function (response) {
        $('#filter_city').html("<option value=''>" + window.trans("All") + "</option>")
        $.each(response.data, function (key, value) {
            $('#filter_city').append($('<option>', {
                value: value.id,
                text: value.name
            }));
        });
    })
});

$('#filter_state_item').on('change', function () {
    let stateName = $(this).find('option:selected').text();
    let url = window.baseurl + 'item/cities/search?state_name=' + encodeURIComponent(stateName);
    ajaxRequest('GET', url, null, null, function (response) {
        console.log(url);
        console.log(response);
        $('#filter_city_item').html("<option value=''>" + window.trans("All") + "</option>")
        $.each(response.data, function (key, value) {
            $('#filter_city_item').append($('<option>', {
                value: value.name,
                text: value.name
            }));
        });
    });
});
$('#filter_country_item_test').on('change', function () {
    $('.bootstrap-table-filter-control-state').val('');

    let countryName = $(this).find('option:selected').text();
    let url = window.baseurl + 'item/states/search?country_name=' + encodeURIComponent(countryName);
    ajaxRequest('GET', url, null, null, function (response) {
        console.log(response);
        $('#filter_state_item').html("<option value=''>" + window.trans("All") + "</option>")
        $.each(response.data, function (key, value) {
            $('#filter_state_item').append($('<option>', {
                value: value.name,
                text: value.name
            }));
        });

    });
});
$(document).ready(function () {
    const $areaContainer = $('#areas-container');

    // Function to create new area row
    function createAreaRow(name = '', latitude = '', longitude = '') {
        return `
            <div class="row area-input-group mb-3">
                <div class="col-md-4 form-group">
                    <label for="name" class="mandatory form-label mt-2">Area Name</label>
                    <div class="d-flex">
                        <input type="text" name="name[]" class="form-control me-2" value="${name}" placeholder="Enter Area name">
                    </div>
                </div>
                <div class="form-group col-md-4 col-sm-12">
                    <label for="latitude" class="mandatory form-label mt-2">Latitude</label>
                    <div class="d-flex mb-2">
                        <input type="text" name="latitude[]" class="form-control me-2" value="${latitude}" placeholder="Enter Latitude">
                    </div>
                </div>
                <div class="form-group col-md-4 col-sm-12">
                    <label for="longitude" class="mandatory form-label mt-2">Longitude</label>
                    <div class="d-flex mb-2">
                        <input type="text" name="longitude[]" class="form-control me-2" value="${longitude}" placeholder="Enter Longitude">
                        <button type="button" class="btn btn-danger remove-area-button ms-2">-</button>
                        <button type="button" class="btn btn-secondary add-area-button ms-2">+</button>
                    </div>
                </div>
            </div>
        `;
    }

    // Handle add area button click
    $(document).on('click', '.add-area-button', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const newRow = $(createAreaRow());
        $('#areas-container').append(newRow);
    });

    // Handle remove area button click
    $(document).on('click', '.remove-area-button', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const $areaRows = $('.area-input-group');
        if ($areaRows.length > 1) {
            $(this).closest('.area-input-group').remove();
        } else {
            showErrorToast('At least one area is required');
        }
    });
});
$(document).ready(function () {
    const $cityContainer = $('#city-container');

    // Function to create new city row
    function createCityRow(name = '', latitude = '', longitude = '') {
        return `
            <div class="row city-input-group mb-3">
                 <div class="form-group col-md-4 col-sm-12">
                    <label for="name" class="mandatory form-label mt-2">City Name</label><span class="text-danger">*</span>
                    <div class="d-flex mb-2">
                        <input type="text" name="name[]" class="form-control me-2" value="${name}" placeholder="Enter City name">
                    </div>
                    </div>
                    <div class="form-group col-md-4 col-sm-12">
                    <label for="latitude" class="mandatory form-label mt-2">Latitude</label>
                    <div class="d-flex mb-2">
                        <input type="text" name="latitude[]" class="form-control me-2" value="${latitude}" placeholder="Enter Latitude">
                    </div>
                    </div>
                     <div class="form-group col-md-4 col-sm-12">
                    <label for="longitude" class="mandatory form-label mt-2">Longitude</label>
                    <div class="d-flex mb-2">
                        <input type="text" name="longitude[]" class="form-control me-2" value="${longitude}" placeholder="Enter Longitude">
                        <button type="button" class="btn btn-secondary add-city-button">+</button>
                        <button type="button" class="btn btn-danger remove-city-button ms-2">-</button>
                    </div>
                    </div>
                </div>
        `;
    }

    // Handle add city button click
    $(document).on('click', '.add-city-button', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const $mapRow = $('#city-container').find('#map').closest('.row');
        $(createCityRow()).insertBefore($mapRow);
    });

    // Handle remove city button click
    $(document).on('click', '.remove-city-button', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const $cityRows = $('.city-input-group');
        if ($cityRows.length > 1) {
        $(this).closest('.city-input-group').remove();
        } else {
            showErrorToast('At least one city is required');
        }
    });

});


$('#switch_stripe_gateway').on('change', function () {
    let status = $(this).prop('checked');
    $('[name^="gateway[Stripe]"]').each(function () {
        $(this).prop('required', status);
    });
});

$('#switch_razorpay_gateway').on('change', function () {
    let status = $(this).prop('checked');
    $('[name^="gateway[Razorpay]"]').each(function () {
        $(this).prop('required', status);
    });
});

$('#switch_paystack_gateway').on('change', function () {
    let status = $(this).prop('checked');
    $('[name^="gateway[Paystack]"]').each(function () {
        $(this).prop('required', status);
    });
});

$('#google_map_iframe_link').on('input', function () {
    try {
        let element = $(this).val();
        let src = $(element).attr('src');
        $(this).val(src);
    } catch (err) {
        $(this).val("");
        showErrorToast("Please enter a valid map iframe")
    }

});
$('#category_name').on('input', function () {
    let slug = generateSlug($(this).val())
    $('#category_slug').val(slug);
});

$('.feature-section-name').on('input', function () {
    let slug = generateSlug($(this).val());
    $('.feature-section-slug').val(slug);
});

$('.edit-feature-section-name').on('input', function () {
    let slug = generateSlug($(this).val());
    $('.edit-feature-section-slug').val(slug);
});
$('#title').on('input', function () {
    let slug = generateSlug($(this).val())
    $('#slug').val(slug);
});
function descriptionFormatter(value, row, index) {
    if (value.length > 100) {
        return '<div class="short-description">' + value.substring(0, 50) +
            '... <a href="#" class="view-more" data-index="' + index + '">' + window.trans("View More") + '</a></div>' +
            '<div class="full-description" style="display:none;">' + value +
            ' <a href="#" class="view-more" data-index="' + index + '">' + window.trans("View Less") + '</a></div>';
    } else {
        return value;
    }
}

$(document).ready(function () {
    $('body').on('click', '.view-more', function (e) {
        e.preventDefault();
        var $this = $(this);
        var $row = $this.closest('tr');
        var $fullDescription = $row.find('.full-description');
        var $shortDescription = $row.find('.short-description');

        if ($fullDescription.is(':visible')) {
            $fullDescription.hide();
            $shortDescription.show();
        } else {
            $fullDescription.show();
            $shortDescription.hide();
        }
    });
});


$(document).on('click', '.toggle-subcategories', function() {

    let categoryId = $(this).data('id');
    let categoryRow = $(this).closest('tr');
    let currentLevel = categoryRow.data('level') || 0;

    if ($(this).hasClass('expanded')) {
        $(this).removeClass('expanded').html('<i class="fa fa-plus"></i>');

        let clickedId = $(this).data('id');

        function removeChildren(parentId) {
            $('tr.subcategory-row').filter(function() {
                return $(this).data('parent') == parentId;
            }).each(function() {
                let childId = $(this).data('id');
                removeChildren(childId); // remove grandchildren recursively
                $(this).remove();
            });
        }

        removeChildren(clickedId);
    }else {
        $(this).addClass('expanded').html('<i class="fa fa-minus"></i>');
        let url = `/category/${categoryId}/subcategories`;


        ajaxRequest('GET', url, null, null, function(data) {
            if (!Array.isArray(data)) {
                console.error('Expected an array but got:', data);
                return;
            }
            let nextLevel = currentLevel + 1;
            let subcategoryRows = '';
            let idColVisible = $('#table_list').bootstrapTable('getVisibleColumns').some(c => c.field === 'id');
            data.forEach(subcategory => {
                subcategoryRows += `
                    <tr class="subcategory-row parent-${categoryId}" data-id="${subcategory.id}" data-level="${nextLevel}" data-parent="${categoryId}">
                        ${idColVisible ? `<td class="text-center">${subcategory.id}</td>` : ''}
                        <td>${subCategoryNameFormatter(subcategory.name , subcategory ,nextLevel)}</td>
                        <td class="text-center">${imageFormatter(subcategory.image, subcategory.name)}</td>
                        <td class="text-center">${subCategoryFormatter(subcategory.subcategories_count,subcategory)}</td>
                        <td class="text-center">${customFieldFormatter(subcategory.custom_fields_count,subcategory)}</td>
                        <td class="text-center">${subcategory.advertisements_count}</td>
                        <td class="text-center">${statusSwitchFormatter(subcategory.status, subcategory)}</td>
                        <td>${subcategory.operate}</td>
                    </tr>
                `;
            });
            categoryRow.after(subcategoryRows);
        });

    }
});

function updateMetaLength(inputId, maxPixelWidth, tooLongPixelWidth) {
    const input = $(`#${inputId}`);
    const countElement = $(`#${inputId}_count`);

    if (input.length && countElement.length) {
        const text = input.val().trim();
        let textPixelLength = Math.round(getTextWidth(text, '19.9px Arial'));

        let iconClass = 'fa-exclamation-triangle text-danger';
        let feedbackMessage = `Your page Meta ${inputId === 'meta_title' ? 'title' : 'description'} is too short.`;
        let feedbackColor = 'text-danger';


        if (textPixelLength >= maxPixelWidth && textPixelLength <= tooLongPixelWidth) {
            iconClass = 'fa-check-circle text-success';
            feedbackMessage = `Your page Meta ${inputId === 'meta_title' ? 'title' : 'description'} is an acceptable length.`;
            feedbackColor = 'text-success';
        } else if (textPixelLength > tooLongPixelWidth) {
            feedbackMessage = `Page Meta ${inputId === 'meta_title' ? 'title' : 'description'} should be around ${tooLongPixelWidth} pixels in length`;
        }
        countElement.html(`
            <i class="fa ${iconClass}"></i>
            <span>Meta ${inputId === 'meta_title' ? 'Title' : 'Description'} is <b>${textPixelLength}</b> pixel(s) long</span>
            <span class="${feedbackColor}">--${feedbackMessage}</span>
        `);
    }
}
function getTextWidth(text, font) {
    const canvas = document.createElement('canvas');
    const context = canvas.getContext('2d');
    context.font = font;
    const metrics = context.measureText(text);
    return metrics.width;
}

$('#meta_title').on('input', function() {
    updateMetaLength('meta_title', 240, 580);
});

$('#meta_description').on('input', function() {
    updateMetaLength('meta_description', 400, 920);
});

$('#file_manager').on('change',function (){
    if($(this).val()=="local"){
        $('#s3_div').hide();
    }else if($(this).val()=="s3"){
        $('#s3_div').show();
    }
})

$('#verification_status').change(function () {
    let status = $(this).val();
    if (status === 'rejected') {
        $('#rejectionReasonField').show();
    } else {
        $('#rejectionReasonField').hide();
    }
});
function customValidation() {
    let item = $("select[name=item]").val();
    let category = $("select[name=category_id]").val();
    let link = $("input[name=link]").val();
    if (item == "" && category == "" && link == "") {
        // Display an error message
        $('.invalid-form-error-message').html("Please select either Item, Category, or Add Link").addClass("text-danger");
        return false;
    }

    if ((item != "" && category != "") || (item != "" && link != "") || (category != "" && link != "")) {
        $('.invalid-form-error-message').html("Please select only one field: Item, Category, or Link").addClass("text-danger");
        return false;
    }

    $('.invalid-form-error-message').html('');

    return true;
}
$(function () {
    $(".sortable").sortable({
        revert: true,
        items: "li",
    });
    // $("#draggable").draggable({
    //     connectToSortable: "#sortable",
    //     helper: "clone",
    //     revert: "invalid"
    // });
    $("ul, li").disableSelection();
});

$("#update-team-member-rank-form").on("submit", function (e) {
    e.preventDefault();

    let userOrder = $(".sortable").sortable("toArray"); // Get the new order of items
    let formElement = $(this);
    let submitButtonElement = $(this).find(":submit");
    let url = $(this).attr("action");

    let data = new FormData(this);
    data.append("order", JSON.stringify(userOrder)); // Append order as JSON
    data.append("_method", "POST");

    function successCallback() {
        setTimeout(function () {
            window.location.reload();
        }, 1000);
    }

    formAjaxRequest(
        "POST",
        url,
        data,
        formElement,
        submitButtonElement,
        successCallback
    );
});

document.addEventListener('DOMContentLoaded', function () {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.forEach(function (tooltipTriggerEl) {
        new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

$(document).ready(function () {
    function toggleTwilioSettings() {
        let otpServicesProviderValue = $("#otp-services-provider").val();
        if (otpServicesProviderValue === 'twilio') {
            $("#twilio-sms-settings-div").show();
            $(".twilio-account-settings").attr('required', true);
        } else {
            $(".twilio-account-settings").removeAttr('required');
            $("#twilio-sms-settings-div").hide();
        }
    }

    toggleTwilioSettings();
    $("#otp-services-provider").on('change', function () {
        toggleTwilioSettings();
    });
});

document.addEventListener('DOMContentLoaded', function () {
    let answerInput = document.getElementById('answer');
    if (answerInput) {
        answerInput.addEventListener('input', function () {
            let words = this.value.trim().split(/\s+/).filter(Boolean).length;
            let maxWords = 500;
            if (words > maxWords) {
                this.value = this.value.trim().split(/\s+/).slice(0, maxWords).join(' ');
                alert("Maximum 500 words allowed.");
            }
        });
    }
});
function toggleReportRejectedReasonVisibility() {
    var status = $('#report_status').val();
    var rejectedReasonContainer = $('#report_rejected_reason_container');
    if (status == 'rejected') {
        rejectedReasonContainer.show();
    } else {
        rejectedReasonContainer.hide();
    }
}

$('#report_status').on('change', function () {
    toggleReportRejectedReasonVisibility();
});
$(document).ready(function () {
    function toggleMapSettings() {
        let otpServicesProviderValue = $("#map_provider").val();
        if (otpServicesProviderValue === 'google_places') {
            $("#s3_div").show();
            $("#s3_div").attr('required', true);
            $("#google_map_key_div").show();
            $("#google_map_key_div").attr('required', true);
        } else {
            $("#s3_div").removeAttr('required');
            $("#s3_div").hide();
            $("#google_map_key_div").removeAttr('required');
            $("#google_map_key_div").hide();
        }
    }

    toggleMapSettings();
    $("#map_provider").on('change', function () {
        toggleMapSettings();
    });
});

// Sidebar Toggle Handler - Fix for sidebar closing issue
// Handles both topbar and sidebar burger buttons
(function() {
    function initSidebarToggle() {
        const sidebar = document.getElementById('sidebar');
        const sidebarWrapper = document.querySelector('.sidebar-wrapper');

        if (!sidebar) return;

        // Attach to ALL burger toggle buttons
        document.querySelectorAll('.burger-btn').forEach(function(btn) {
            // Clone to strip old listeners, then re-add
            var newBtn = btn.cloneNode(true);
            btn.parentNode.replaceChild(newBtn, btn);

            newBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();

                sidebar.classList.toggle('active');
                if (sidebarWrapper) sidebarWrapper.classList.toggle('active');
                localStorage.setItem('sidebarState', sidebar.classList.contains('active') ? 'open' : 'closed');

                return false;
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSidebarToggle);
    } else {
        initSidebarToggle();
    }
    setTimeout(initSidebarToggle, 100);
    setTimeout(initSidebarToggle, 500);
})();

// jQuery backup handler for burger buttons
$(document).ready(function() {
    function toggleSidebar() {
        var $sidebar = $('#sidebar');
        var $sidebarWrapper = $('.sidebar-wrapper');
        if ($sidebar.length) {
            $sidebar.toggleClass('active');
            $sidebarWrapper.toggleClass('active');
            localStorage.setItem('sidebarState', $sidebar.hasClass('active') ? 'open' : 'closed');
        }
    }

    // Close sidebar when clicking outside (mobile only)
    $(document).on('click', function(e) {
        if ($(e.target).closest('.burger-btn').length) return;
        setTimeout(function() {
            var $sidebar = $('#sidebar');
            var $sidebarWrapper = $('.sidebar-wrapper');
            if ($(e.target).closest('#sidebar').length) return;
            if ($(window).width() < 992 && $sidebar.hasClass('active')) {
                $sidebar.removeClass('active');
                $sidebarWrapper.removeClass('active');
                localStorage.setItem('sidebarState', 'closed');
            }
        }, 10);
    });

    // Prevent sidebar from closing when clicking inside it
    $(document).on('click', '#sidebar, .sidebar-wrapper, .sidebar-menu', function(e) {
        if (!$(e.target).closest('.sidebar-item.has-sub > .sidebar-link[href="#"]').length) {
            e.stopPropagation();
        }
    });

    $(document).on('click', '.sidebar-link:not([href="#"])', function(e) {
        e.stopPropagation();
    });
});


// NOTE: Sidebar submenu toggle is handled by the smooth initSmoothToggle block
// inside the $(document).ready() above. That single handler covers all cases
// including animated open/close and allowing the active menu to be collapsed.

let countryName = $("#country_item option:selected").text(); // 👈 get name

    // set hidden input with country name
    $('#country-input').val(countryName);
     let itemState = $('#item_state').val();

    // set hidden input
    $('#state-input').val(itemState);
    let itemCity = $('#item_city').val();
    $('#city-input').val(itemCity);
$('#country_item').on('change', function () {
    let countryId = $(this).val();
     let countryName = $("#country_item option:selected").text(); // 👈 get name

    // set hidden input with country name
    $('#country-input').val(countryName);
    let url = window.baseurl + 'states/search?country_id=' + countryId;
    ajaxRequest('GET', url, null, null, function (response) {
        $('#state_item').html("<option value=''>" + window.trans("--Select State--") + "</option>");
        $.each(response.data, function (key, value) {
            let itemState = $('#item_state').val();
            let selected = (value.name == itemState) ? 'selected' : '';
            $('#state_item').append(`<option value="${value.id}" ${selected}>${value.name}</option>`);
        });
    })
});
$('#state_item').on('change', function () {
    let stateId = $(this).val();
    let stateName = $("#state_item option:selected").text(); // 👈 get state name

    // set hidden input
    $('#state-input').val(stateName);

    let url = window.baseurl + 'cities/search?state_id=' + stateId;
    ajaxRequest('GET', url, null, null, function (response) {
        $('#city').html("<option value=''>" + window.trans("--Select City--") + "</option>");
        $.each(response.data, function (key, value) {
            let itemCity = $('#item_city').val();
            let selected = (value.name == itemCity) ? 'selected' : '';
            $('#city').append($('<option>', {
                value: value.id,
                text: value.name,
                selected: selected
            }));
        });
    });
});

$('#city').on('change', function () {
    let cityName = $("#city option:selected").text(); // 👈 get city name
    $('#city-input').val(cityName);
});
$(document).ready(function () {
    $('#type').on('change', function () {
        let selectedType = $(this).val();

        if ($.inArray(selectedType, ['checkbox', 'radio', 'dropdown']) > -1) {
            $('#field-values-div').slideDown(500);
            $('.min-max-fields').slideUp(500);
            $('.field-value-translation').slideDown(500);
        } else if ($.inArray(selectedType, ['fileinput']) > -1) {
            $('#field-values-div').slideUp(500);
            $('.field-value-translation').slideUp(500);
            $('.min-max-fields').slideUp(500);
        } else {
            $('#field-values-div').slideUp(500);
            $('.field-value-translation').slideUp(500);
            $('.min-max-fields').slideDown(500);
        }
    });

    // 🔹 Trigger once on page load
    $('#type').trigger('change');
});
document.addEventListener('DOMContentLoaded', function () {
    const fieldTypeSelect = document.getElementById('type');
    if (!fieldTypeSelect) return;
    const valuesSelect = $('#values');
    const form = document.querySelector('.create-form') || document.querySelector('.edit-form');
     const existingTranslations = window.existingTranslations || {};

    function updateTranslationInputs() {
        const values = valuesSelect.val() || [];
        const requiresTranslation = ['checkbox', 'radio', 'dropdown'].includes(fieldTypeSelect.value);

        document.querySelectorAll('.field-value-translation').forEach(wrapper => {
            const langId = wrapper.getAttribute('id').split('-').pop();
            const container = wrapper.querySelector('.translated-values-container');

            if (!requiresTranslation || values.length === 0) {
                wrapper.style.display = 'none';
                container.innerHTML = '';
                return;
            }

            wrapper.style.display = 'block';
            container.innerHTML = '';

            values.forEach((val, index) => {
               const inputWrapper = document.createElement('div');
                    inputWrapper.className = 'col-md-6 mb-3';

                    const input = document.createElement('input');
                    input.type = 'text';
                    input.className = 'form-control field-translation-input';
                    input.name = `translations[${langId}][value][${index}]`;
                    input.setAttribute('data-index', index);
                    input.placeholder = `Translation for "${val}"`;

                    if (existingTranslations[langId] && Array.isArray(existingTranslations[langId].value)) {
                        input.value = existingTranslations[langId].value[index] || '';
                    }

                    if (requiresTranslation && wrapper.style.display !== 'none') {
                        input.required = true;
                    }

                    inputWrapper.appendChild(input);
                    container.appendChild(inputWrapper);

            });
        });
    }

        function validateTranslationInputs() {
                const values = valuesSelect.val() || [];
                const originalCount = values.length;
                let isValid = true;

                document.querySelectorAll('.field-value-translation').forEach(wrapper => {
                    const langId = wrapper.getAttribute('id').split('-').pop();
                    const errorBox = wrapper.querySelector(`.error-msg-${langId}`);
                    const inputs = wrapper.querySelectorAll('.field-translation-input');

                    errorBox.textContent = '';
                    wrapper.querySelectorAll('.translation-error-msg').forEach(msg => msg.remove());
                    if (inputs.length !== originalCount) {
                        isValid = false;
                        errorBox.textContent = `You must provide ${originalCount} translations.`;
                    }

                    inputs.forEach(input => {
                        const nextEl = input.nextElementSibling;
                        const alreadyHasError = nextEl && nextEl.classList.contains('translation-error-msg');

                        if (!input.value.trim()) {
                            input.classList.add('is-invalid');
                            isValid = false;

                            if (!alreadyHasError) {
                                const error = document.createElement('div');
                                error.className = 'text-danger small translation-error-msg';
                                error.textContent = 'Field value is required.';
                                input.after(error);
                            }
                        } else {
                            input.classList.remove('is-invalid');

                            if (alreadyHasError) {
                                nextEl.remove();
                            }
                        }
                    });
                });

                return isValid;
            }





    fieldTypeSelect.addEventListener('change', updateTranslationInputs);
    valuesSelect.on('change', updateTranslationInputs);

    // Trigger on page load
    updateTranslationInputs(); // <-- THIS will populate inputs on load



    // updateTranslationInputs();

    if (!form) return;

    form.addEventListener('submit', function (e) {
        const values = valuesSelect.val() || [];
        const requiresTranslation = ['checkbox', 'radio', 'dropdown'].includes(fieldTypeSelect.value);

        if (requiresTranslation && values.length > 0) {
            if (!validateTranslationInputs()) {
                e.preventDefault();
                toastr.error('Please ensure all translation values are filled and match the main field values.');
            }
        }
    });
});

 $('#country_translation').on('change', function () {
    let countryId = $(this).val();
    let url = window.baseurl + 'states/search?country_id=' + countryId;

    $('#state_translation').html("<option value=''>" + window.trans("--Select State--") + "</option>");
    $('#state_translation').prop('disabled', true);
    $('#city_translations_container').html(""); // Clear cities on country change

    if (!countryId) return;

    ajaxRequest('GET', url, null, null, function (response) {
        $.each(response.data, function (key, value) {
            $('#state_translation').append($('<option>', {
                value: value.id,
                text: value.name
            }));
        });
        $('#state_translation').prop('disabled', false);
    });
});

$('#state_translation').on('change', function () {
    console.log('changed');
    let stateId = $(this).val();
    $('#city_translations_container').html("");

    if (!stateId) return;

    let url = window.baseurl + 'city-translations/' + stateId;
    console.log(url);
    $.ajax({
        url: url,
        type: 'GET',
        success: function (response) {
            console.log('Success:', response);
            $('#city_translations_container').html(response);
        },
        error: function (xhr) {
            console.log('Error:', xhr);
            $('#city_translations_container').html('<div class="text-danger">Failed to load translations.</div>');
        }
    });

});
// Search filter
let countrySearchInput = document.getElementById('countrySearchInput');
if (countrySearchInput) {
    countrySearchInput.addEventListener('keyup', function() {
        let filter = this.value.toLowerCase();
        document.querySelectorAll('#countryModal .col-md-3').forEach(function(div) {
            let label = div.querySelector('label');
            if (label.textContent.toLowerCase().indexOf(filter) > -1) {
                div.style.display = '';
            } else {
                div.style.display = 'none';
            }
        });
    });
}

// Select all logic
let selectAllCountries = document.getElementById('selectAllCountries');
if (selectAllCountries) {
    selectAllCountries.addEventListener('change', function() {
        let checked = this.checked;
        document.querySelectorAll('#countryModal input[type="checkbox"][name="countries[]"]:not(:disabled)').forEach(function(box) {
            box.checked = checked;
        });
    });
}
function showSweetAlertForDataConfirmPopup(url, method, options = {}) {

    let opt = {
        title: trans("Important Warning!"),
        text: trans("All existing categories and custom fields will be permanently deleted. New dummy data will be added. This action cannot be undone."),
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: trans("Yes, Continue"),
        cancelButtonText: trans("Cancel"),
        data: {},
        successCallBack: function () {},
        errorCallBack: function (response) {},
        ...options,
    };

    Swal.fire({
        title: opt.title,
        text: opt.text,
        icon: opt.icon,
        showCancelButton: opt.showCancelButton,
        confirmButtonColor: opt.confirmButtonColor,
        cancelButtonColor: opt.cancelButtonColor,
        confirmButtonText: opt.confirmButtonText,
        cancelButtonText: opt.cancelButtonText,
    }).then((result) => {
        if (result.isConfirmed) {

            ajaxRequest(
                method,
                url,
                opt.data,
                null,
                (response) => {
                    showSuccessToast(response.message);
                    opt.successCallBack(response);
                },
                (response) => {
                    showErrorToast(response.message);
                    opt.errorCallBack(response);
                }
            );
        }
    });
}

document.addEventListener('DOMContentLoaded', function () {

    document.querySelectorAll('.tagify-input').forEach(function (input) {

        // Prevent double initialization
        if (input.classList.contains('tagify-applied')) return;

        new Tagify(input, {
            delimiters: ",",
            editTags: true,
            duplicate: false,
            dropdown: {
                enabled: 0
            }
        });

        input.classList.add('tagify-applied');
    });

});

/* ============================================================
   ADMIN CHAT FUNCTIONALITY
   ============================================================ */
// (function() {
//     'use strict';
    
//     // Initialize admin chat when config is available
//     function initAdminChat() {
//         console.log('Admin Chat: initAdminChat called');
        
//         // Only initialize if we're on the admin chat page
//         if (typeof window.adminChatConfig === 'undefined') {
//             console.warn('Admin Chat: Config not available');
//             return;
//         }
        
//         // Prevent multiple initializations
//         if (window.adminChatInitialized) {
//             console.log('Admin Chat: Already initialized, skipping');
//             return;
//         }
        
//         if (typeof jQuery === 'undefined') {
//             console.error('Admin Chat: jQuery not available');
//             return;
//         }
        
//         window.adminChatInitialized = true;
//         const config = window.adminChatConfig;
//         console.log('Admin Chat: Starting initialization with config:', config);
//         console.log('Admin Chat: Routes:', config.routes);
//     let selectedProductId = null;
//     let selectedChatId = null;
//     let currentUserId = config.currentUserId;
//     let messagesInterval = null;
//     let productsPage = 1;
//     let productsHasMore = true;
//     let productsLoading = false;
//     let productsSearch = '';
//     let messagesLoading = false;
//     let currentChatId = null;
//     let isPollingActive = false;
//     let chatsPage = 1;
//     let chatsHasMore = true;
//     let chatsLoading = false;
//     let messaging = null;
    
//     // Initialize Firebase if settings are available
//     if (config.firebase && config.firebase.apiKey && config.firebase.projectId) {
//         try {
//             const firebaseConfig = {
//                 apiKey: config.firebase.apiKey,
//                 authDomain: config.firebase.authDomain,
//                 projectId: config.firebase.projectId,
//                 storageBucket: config.firebase.storageBucket,
//                 messagingSenderId: config.firebase.messagingSenderId,
//                 appId: config.firebase.appId
//             };
            
//             if (typeof firebase !== 'undefined' && firebase.apps.length === 0) {
//                 firebase.initializeApp(firebaseConfig);
//             }
            
//             if (typeof firebase !== 'undefined') {
//                 messaging = firebase.messaging();
                
//                 // Request notification permission
//                 messaging.requestPermission().then(() => {
//                     console.log('Notification permission granted.');
                    
//                     // Listen for foreground messages
//                     messaging.onMessage((payload) => {
//                         console.log('Firebase message received:', payload);
                        
//                         // Check if it's a chat notification
//                         const isChatNotification = payload.data && (
//                             payload.data.type === 'chat' || 
//                             payload.data.message_type === 'chat' ||
//                             payload.notification?.data?.type === 'chat'
//                         );
                        
//                         if (isChatNotification) {
//                             const itemOfferId = parseInt(payload.data?.item_offer_id || payload.notification?.data?.item_offer_id || 0);
                            
//                             if (itemOfferId > 0) {
//                                 // If the current chat matches, refresh messages
//                                 if (currentChatId === itemOfferId && !messagesLoading) {
//                                     console.log('Refreshing messages for current chat:', itemOfferId);
//                                     loadMessages(currentChatId, true);
//                                 }
                                
//                                 // Always refresh chat list if a product is selected to update unread counts and last message
//                                 if (selectedProductId) {
//                                     console.log('Refreshing chat list for product:', selectedProductId);
//                                     loadChatList(selectedProductId, false);
//                                 }
//                             }
//                         }
//                     });
//                 }).catch((err) => {
//                     console.log('Unable to get permission to notify.', err);
//                 });
//             }
//         } catch (error) {
//             console.error('Firebase initialization error:', error);
//         }
//     }
    
//     $(document).ready(function() {
//         // Initial load
//         loadProducts();
        
//         // Force scrollbar visibility check after a short delay
//         setTimeout(function() {
//             const productsList = document.getElementById('products-list');
//             if (productsList && productsList.scrollHeight > productsList.clientHeight) {
//                 productsList.style.overflowY = 'scroll';
//             }
//         }, 500);
        
//         // Product search with debounce
//         let searchTimeout;
//         $('#product-search').on('input', function() {
//             clearTimeout(searchTimeout);
//             const search = $(this).val();
//             productsSearch = search;
//             productsPage = 1;
//             productsHasMore = true;
//             $('#products-list').empty();
//             searchTimeout = setTimeout(function() {
//                 loadProducts(search);
//             }, 500);
//         });

//         // Scroll pagination for products
//         $('#products-list').on('scroll', function() {
//             if (productsLoading || !productsHasMore) return;
            
//             const container = $(this);
//             const scrollTop = container.scrollTop();
//             const scrollHeight = container[0].scrollHeight;
//             const clientHeight = container[0].clientHeight;
            
//             // Load more when 50px from bottom
//             if (scrollHeight - scrollTop - clientHeight <= 50) {
//                 loadProducts(productsSearch, true);
//             }
//         });

//         // Scroll pagination for chats
//         $('#chats-list').on('scroll', function() {
//             if (chatsLoading || !chatsHasMore || !selectedProductId) return;
            
//             const container = $(this);
//             const scrollTop = container.scrollTop();
//             const scrollHeight = container[0].scrollHeight;
//             const clientHeight = container[0].clientHeight;
            
//             // Load more when 50px from bottom
//             if (scrollHeight - scrollTop - clientHeight <= 50) {
//                 loadChatList(selectedProductId, true);
//             }
//         });

//         // Refresh chats button
//         $('#refresh-chats-btn').on('click', function() {
//             if (!selectedProductId) return;
            
//             const btn = $(this);
//             btn.addClass('refreshing');
            
//             // Reset pagination
//             chatsPage = 1;
//             chatsHasMore = true;
//             chatsLoading = false;
//             $('#chats-list').empty();
            
//             loadChatList(selectedProductId, false, function() {
//                 btn.removeClass('refreshing');
//             });
//         });

//         // Send message form
//         $('#send-message-form').on('submit', function(e) {
//             e.preventDefault();
//             window.adminChatSendMessage();
//         });

//         // Attach file button
//         $('#attach-file-btn').on('click', function() {
//             $('#file-input').click();
//         });

//         $('#file-input').on('change', function() {
//             if (this.files.length > 0) {
//                 window.adminChatSendMessage();
//             }
//         });

//         // Cleanup on page unload
//         $(window).on('beforeunload', function() {
//             stopPolling();
//         });
//     });

//     function loadProducts(search = '', append = false) {
//         if (productsLoading) return;
//         if (append && !productsHasMore) return;
        
//         productsLoading = true;
        
//         const pageToLoad = append ? productsPage + 1 : 1;
        
//         $.ajax({
//             url: config.routes.products,
//             method: 'GET',
//             data: { 
//                 search: search,
//                 page: pageToLoad
//             },
//             success: function(response) {
//                 productsLoading = false;
//                 if (response.error === false) {
//                     const data = response.data;
//                     productsHasMore = data.has_more || false;
                    
//                     if (append) {
//                         productsPage = pageToLoad;
//                         renderProducts(data.data, true);
//                     } else {
//                         productsPage = data.current_page;
//                         renderProducts(data.data, false);
//                     }
                    
//                     // Ensure scrollbar is visible if content overflows
//                     setTimeout(function() {
//                         const productsList = document.getElementById('products-list');
//                         if (productsList && productsList.scrollHeight > productsList.clientHeight) {
//                             productsList.style.overflowY = 'scroll';
//                         }
//                     }, 100);
//                 }
//             },
//             error: function() {
//                 productsLoading = false;
//                 if (!append) {
//                     toastr.error(config.translations.failedProducts);
//                 }
//             }
//         });
//     }

//     function renderProducts(products, append = false) {
//         const container = $('#products-list');
        
//         if (!append) {
//             container.empty();
//         }

//         if (products.length === 0 && !append) {
//             container.html('<div class="text-center p-4 text-muted">' + config.translations.noProducts + '</div>');
//             return;
//         }

//         products.forEach(function(product) {
//             const baseUrl = (typeof window.baseurl !== 'undefined' ? window.baseurl : window.location.origin + '/').replace(/\/$/, '');
//             const productImage = product.image ? (product.image.startsWith('http') ? product.image : baseUrl + '/' + product.image.replace(/^\//, '')) : config.placeholderImage;
            
//             // Use formatted price from backend, fallback to raw price if formatted_price is empty
//             let priceDisplay = '';
//             if (product.formatted_price) {
//                 priceDisplay = '<small class="text-muted">' + escapeHtml(product.formatted_price) + '</small>';
//             } else if (product.price && product.price > 0) {
//                 // Fallback: format price manually if formatted_price is not available
//                 const price = parseFloat(product.price) || 0;
//                 priceDisplay = '<small class="text-muted">$ ' + price.toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 2}) + '</small>';
//             }
            
//             const productHtml = `
//                 <div class="product-item" data-product-id="${product.id}" onclick="window.adminChatSelectProduct('${product.id}')">
//                     <div class="d-flex align-items-center">
//                         <img src="${productImage}" alt="${product.name || ''}" class="me-2" onerror="this.src='${config.placeholderImage}'">
//                         <div class="flex-grow-1">
//                             <div class="fw-bold">${escapeHtml(product.name || '')}</div>
//                             ${priceDisplay}
//                         </div>
//                     </div>
//                 </div>
//             `;
//             container.append(productHtml);
//         });
//     }

//     window.adminChatSelectProduct = function(productId) {
//         // Stop polling when switching products
//         stopPolling();
//         selectedChatId = null;
//         currentChatId = null;
        
//         selectedProductId = productId;
//         $('.product-item').removeClass('active');
//         $(`.product-item[data-product-id="${productId}"]`).addClass('active');
        
//         // Clear chat window
//         $('#chat-header').hide();
//         $('#chat-input-container').hide();
//         $('#chat-messages').html('<div class="text-center p-4 text-muted">' + config.translations.selectChat + '</div>');
        
//         // Show refresh button
//         $('#refresh-chats-btn').show();
        
//         // Reset pagination
//         chatsPage = 1;
//         chatsHasMore = true;
//         chatsLoading = false;
//         loadChatList(productId, false);
//     };

//     function loadChatList(productId, append = false, callback = null) {
//         if (chatsLoading) {
//             if (callback) callback();
//             return;
//         }
//         if (append && !chatsHasMore) {
//             if (callback) callback();
//             return;
//         }
        
//         chatsLoading = true;
        
//         const pageToLoad = append ? chatsPage + 1 : 1;
        
//         $.ajax({
//             url: config.routes.chatList,
//             method: 'GET',
//             data: { 
//                 product_id: productId,
//                 page: pageToLoad
//             },
//             success: function(response) {
//                 chatsLoading = false;
//                 if (response.error === false) {
//                     const data = response.data;
//                     chatsHasMore = data.has_more || false;
                    
//                     if (append) {
//                         chatsPage = pageToLoad;
//                         renderChats(data.data || [], true);
//                     } else {
//                         chatsPage = data.current_page;
//                         renderChats(data.data || [], false);
//                     }
//                     updateChatsSummary(data);
                    
//                     // Ensure scrollbar is visible if content overflows
//                     setTimeout(function() {
//                         const chatsList = document.getElementById('chats-list');
//                         if (chatsList && chatsList.scrollHeight > chatsList.clientHeight) {
//                             chatsList.style.overflowY = 'scroll';
//                         }
//                     }, 100);
//                 }
//                 if (callback) callback();
//             },
//             error: function() {
//                 chatsLoading = false;
//                 if (!append) {
//                     toastr.error(config.translations.failedChats);
//                 }
//                 if (callback) callback();
//             }
//         });
//     }

//     function renderChats(chats, append = false) {
//         const container = $('#chats-list');
        
//         if (!append) {
//             container.empty();
//         }

//         if (chats.length === 0 && !append) {
//             container.html('<div class="text-center p-4 text-muted">' + config.translations.noChats + '</div>');
//             return;
//         }

//         chats.forEach(function(chat) {
//             const otherUser = chat.other_user || {};
//             const unreadCount = chat.unread_chat_count || 0;
//             let lastMessage = chat.last_message || config.translations.noMessages;
//             // Truncate long messages
//             if (lastMessage.length > 50) {
//                 lastMessage = lastMessage.substring(0, 50) + '...';
//             }
//             const lastMessageTime = chat.last_message_time ? formatTime(chat.last_message_time) : '';

//             const chatHtml = `
//                 <div class="chat-item" data-chat-id="${chat.id}" onclick="window.adminChatSelectChat(${chat.id})">
//                     <div class="d-flex align-items-center">
//                         <img src="${otherUser.profile || config.placeholderImage}" alt="${otherUser.name || ''}" class="me-2" onerror="this.src='${config.placeholderImage}'">
//                         <div class="flex-grow-1" style="min-width: 0;">
//                             <div class="d-flex justify-content-between align-items-center mb-1">
//                                 <div class="fw-bold text-truncate" style="max-width: 120px;">${escapeHtml(otherUser.name || config.translations.unknownUser)}</div>
//                                 <div class="d-flex flex-column align-items-end">
//                                     ${lastMessageTime ? `<div class="text-muted small mb-1">${lastMessageTime}</div>` : ''}
//                                     ${unreadCount > 0 ? `<span class="unread-badge">${unreadCount}</span>` : ''}
//                                 </div>
//                             </div>
//                             <div class="text-muted small text-truncate">${escapeHtml(lastMessage)}</div>
//                         </div>
//                     </div>
//                 </div>
//             `;
//             container.append(chatHtml);
//         });
//     }

//     function updateChatsSummary(data) {
//         const chats = data.data || [];
//         const total = data.total !== undefined ? data.total : chats.length;
//         const unread = chats.reduce((sum, chat) => sum + (parseInt(chat.unread_chat_count) || 0), 0);
//         $('#chats-summary').text(`${total} ${config.translations.message}, ${unread} ${config.translations.unread}`);
//     }

//     window.adminChatSelectChat = function(chatId) {
//         // Prevent multiple calls if same chat is selected
//         if (selectedChatId === chatId && currentChatId === chatId) {
//             return;
//         }
        
//         // Stop any polling (we don't use polling anymore, but keep for safety)
//         stopPolling();
        
//         selectedChatId = chatId;
//         currentChatId = chatId;
//         $('.chat-item').removeClass('active');
//         $(`.chat-item[data-chat-id="${chatId}"]`).addClass('active');
        
//         // Load messages - this will mark them as read on the backend
//         loadMessages(chatId, false, function() {
//             // Refresh chat list to update unread counts after messages are marked as read
//             if (selectedProductId) {
//                 // Small delay to ensure backend has processed the read status
//                 setTimeout(function() {
//                     loadChatList(selectedProductId, false, function() {
//                         // Update the unread badge for the current chat item
//                         const chatItem = $(`.chat-item[data-chat-id="${chatId}"]`);
//                         const unreadBadge = chatItem.find('.unread-badge');
//                         if (unreadBadge.length) {
//                             unreadBadge.remove();
//                         }
//                     });
//                 }, 300);
//             }
//         });
//     };

//     function stopPolling() {
//         if (messagesInterval) {
//             clearInterval(messagesInterval);
//             messagesInterval = null;
//         }
//         isPollingActive = false;
//     }

//     function loadMessages(chatId, silent = false, callback = null) {
//         // Prevent multiple concurrent calls - CRITICAL CHECK
//         if (messagesLoading) {
//             if (callback) callback();
//             return;
//         }
        
//         // Don't load if chat is not selected (unless it's a silent refresh from notification)
//         if (!silent && currentChatId !== chatId) {
//             if (callback) callback();
//             return;
//         }
        
//         // Set loading flag BEFORE making the request
//         messagesLoading = true;
        
//         $.ajax({
//             url: config.routes.messages,
//             method: 'GET',
//             data: { item_offer_id: chatId },
//             timeout: 10000, // 10 second timeout to prevent hanging requests
//             success: function(response) {
//                 // Always reset loading flag first
//                 messagesLoading = false;
                
//                 // Only process if this is still the current chat
//                 if (currentChatId === chatId) {
//                     if (response.error === false) {
//                         const messages = response.data.data || response.data || [];
//                         // Messages are ordered DESC, so reverse to show oldest first
//                         renderMessages(messages.reverse());
//                         if (!silent) {
//                             scrollToBottom();
//                         } else {
//                             // Auto-scroll only if user is near bottom
//                             const container = $('#chat-messages');
//                             const scrollTop = container.scrollTop();
//                             const scrollHeight = container[0].scrollHeight;
//                             const clientHeight = container[0].clientHeight;
//                             // If within 100px of bottom, auto-scroll
//                             if (scrollHeight - scrollTop - clientHeight < 100) {
//                                 scrollToBottom();
//                             }
//                         }
//                     }
//                 }
//                 if (callback) callback();
//             },
//             error: function(xhr, status, error) {
//                 // Always reset loading flag
//                 messagesLoading = false;
//                 if (!silent && currentChatId === chatId) {
//                     if (status !== 'abort') {
//                         toastr.error(config.translations.failedMessages);
//                     }
//                 }
//                 if (callback) callback();
//             },
//             complete: function() {
//                 // Ensure loading flag is reset even if something goes wrong
//                 messagesLoading = false;
//             }
//         });
//     }

//     function renderMessages(messages) {
//         const container = $('#chat-messages');
//         container.empty();

//         if (messages.length === 0) {
//             container.html('<div class="text-center p-4 text-muted">' + config.translations.noMessages + '</div>');
//             return;
//         }

//         // Show chat header and input
//         $('#chat-header').show();
//         $('#chat-input-container').show();

//         // Get chat info for header
//         const chatItem = $(`.chat-item[data-chat-id="${selectedChatId}"]`);
//         const userName = chatItem.find('.fw-bold').text();
//         const userImage = chatItem.find('img').attr('src');
//         $('#chat-user-name').text(userName);
//         $('#chat-user-image').attr('src', userImage);

//         messages.forEach(function(message) {
//             const isSent = message.sender_id == currentUserId;
//             const messageTime = message.created_at ? formatTime(message.created_at) : '';
            
//             let messageContent = '';
//             if (message.message) {
//                 messageContent = `<div>${escapeHtml(message.message)}</div>`;
//             }
//             if (message.file) {
//                 const baseUrl = (typeof window.baseurl !== 'undefined' ? window.baseurl : window.location.origin + '/').replace(/\/$/, '');
//                 const fileUrl = message.file.startsWith('http') ? message.file : baseUrl + '/' + message.file.replace(/^\//, '');
//                 messageContent += `<div class="mt-2"><img src="${fileUrl}" alt="Image" style="max-width: 200px; border-radius: 8px; cursor: pointer;" onclick="window.open('${fileUrl}', '_blank')"></div>`;
//             }
//             if (message.audio) {
//                 const baseUrl = (typeof window.baseurl !== 'undefined' ? window.baseurl : window.location.origin + '/').replace(/\/$/, '');
//                 const audioUrl = message.audio.startsWith('http') ? message.audio : baseUrl + '/' + message.audio.replace(/^\//, '');
//                 messageContent += `<div class="mt-2"><audio controls><source src="${audioUrl}" type="audio/mpeg"></audio></div>`;
//             }

//             let messageHtml = '';
//             if (isSent) {
//                 // Sent messages on the right with profile picture
//                 const userProfile = $('#chat-user-image').attr('src') || config.placeholderImage;
//                 messageHtml = `
//                     <div class="d-flex justify-content-end mb-2 align-items-end">
//                         <div class="message-bubble message-sent">
//                             ${messageContent}
//                             <div class="message-time">${messageTime}</div>
//                         </div>
//                         <img src="${userProfile}" alt="You" class="rounded-circle ms-2" style="width: 30px; height: 30px; object-fit: cover;" onerror="this.src='${config.placeholderImage}'">
//                     </div>
//                 `;
//             } else {
//                 // Received messages on the left
//                 messageHtml = `
//                     <div class="d-flex justify-content-start mb-2 align-items-end">
//                         <div class="message-bubble message-received">
//                             ${messageContent}
//                             <div class="message-time">${messageTime}</div>
//                         </div>
//                     </div>
//                 `;
//             }
//             container.append(messageHtml);
//         });

//         scrollToBottom();
//     }

//     window.adminChatSendMessage = function() {
//         if (!selectedChatId) {
//             toastr.error(config.translations.selectChatFirst);
//             return;
//         }

//         const formData = new FormData();
//         formData.append('item_offer_id', selectedChatId);
//         formData.append('message', $('#message-input').val());
        
//         const fileInput = $('#file-input')[0];
//         if (fileInput.files.length > 0) {
//             formData.append('file', fileInput.files[0]);
//         }

//         $.ajax({
//             url: config.routes.sendMessage,
//             method: 'POST',
//             data: formData,
//             processData: false,
//             contentType: false,
//             headers: {
//                 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
//             },
//             success: function(response) {
//                 if (response.error === false) {
//                     $('#message-input').val('');
//                     $('#file-input').val('');
//                     // Reload messages silently (won't trigger if already loading)
//                     if (!messagesLoading) {
//                         loadMessages(selectedChatId, true);
//                     }
//                     // Refresh chat list to update last message
//                     if (selectedProductId) {
//                         loadChatList(selectedProductId, false);
//                     }
//                 }
//             },
//             error: function(xhr) {
//                 const error = xhr.responseJSON?.message || config.translations.failedSend;
//                 toastr.error(error);
//             }
//         });
//     };

//     function formatTime(dateString) {
//         const date = new Date(dateString);
//         const hours = date.getHours();
//         const minutes = date.getMinutes();
//         const ampm = hours >= 12 ? 'PM' : 'AM';
//         const displayHours = hours % 12 || 12;
//         const displayMinutes = minutes < 10 ? '0' + minutes : minutes;
//         return `${displayHours}:${displayMinutes} ${ampm}`;
//     }

//     function scrollToBottom() {
//         const container = $('#chat-messages');
//         setTimeout(() => {
//             container.scrollTop(container[0].scrollHeight);
//         }, 100);
//     }

//     function escapeHtml(text) {
//         const map = {
//             '&': '&amp;',
//             '<': '&lt;',
//             '>': '&gt;',
//             '"': '&quot;',
//             "'": '&#039;'
//         };
//         return text.replace(/[&<>"']/g, m => map[m]);
//     }
    
//     // Expose functions globally for onclick handlers
//     window.sendMessage = window.adminChatSendMessage;
//     }
    
//     // Wait for both jQuery and config to be available
//     let initRetryCount = 0;
//     const maxRetries = 100; // 5 seconds max wait (100 * 50ms)
    
//     function waitForInit() {
//         initRetryCount++;
//         console.log('Admin Chat waitForInit attempt:', initRetryCount, 'jQuery:', typeof jQuery !== 'undefined', 'Config:', typeof window.adminChatConfig !== 'undefined');
        
//         if (typeof jQuery === 'undefined') {
//             if (initRetryCount < maxRetries) {
//                 setTimeout(waitForInit, 50);
//             } else {
//                 console.error('Admin Chat: jQuery not available after max retries');
//             }
//             return;
//         }
        
//         if (typeof window.adminChatConfig === 'undefined') {
//             if (initRetryCount < maxRetries) {
//                 setTimeout(waitForInit, 50);
//             } else {
//                 console.error('Admin Chat: Config not available after max retries');
//             }
//             return;
//         }
        
//         // Both are available, initialize
//         console.log('Admin Chat: Both jQuery and config available, initializing...');
//         console.log('Admin Chat Config:', window.adminChatConfig);
//         initAdminChat();
//     }
    
//     // Expose waitForInit and initAdminChat globally so they can be called from outside
//     // Expose them immediately, even before the IIFE finishes
//     window.adminChatWaitForInit = waitForInit;
//     window.initAdminChat = initAdminChat;
    
//     console.log('Admin Chat: Functions exposed globally');
    
//     // Start waiting for initialization
//     if (document.readyState === 'loading') {
//         document.addEventListener('DOMContentLoaded', waitForInit);
//     } else {
//         // DOM is already loaded, start waiting
//         waitForInit();
//     }
    
//     // Also create a function that can be called when config becomes available
//     window.adminChatInitWhenReady = function() {
//         console.log('Admin Chat: adminChatInitWhenReady called');
//         if (typeof jQuery !== 'undefined' && typeof window.adminChatConfig !== 'undefined') {
//             if (!window.adminChatInitialized) {
//                 console.log('Admin Chat: Both available, calling initAdminChat');
//                 initAdminChat();
//             } else {
//                 console.log('Admin Chat: Already initialized');
//             }
//         } else {
//             console.log('Admin Chat: Not ready yet, jQuery:', typeof jQuery !== 'undefined', 'Config:', typeof window.adminChatConfig !== 'undefined');
//         }
//     };
    
//     // Also try to initialize when window loads (in case config is set after DOMContentLoaded)
//     window.addEventListener('load', function() {
//         if (typeof jQuery !== 'undefined' && typeof window.adminChatConfig !== 'undefined') {
//             // Only initialize if not already initialized
//             if (!window.adminChatInitialized) {
//                 console.log('Admin Chat: Initializing on window load');
//                 initAdminChat();
//             }
//         }
//     });
    
//     // Listen for custom event to trigger initialization
//     document.addEventListener('adminChatConfigReady', function() {
//         if (typeof jQuery !== 'undefined' && typeof window.adminChatConfig !== 'undefined' && !window.adminChatInitialized) {
//             console.log('Admin Chat: Config ready event received, initializing');
//             initAdminChat();
//         }
//     });
// })();
$(function () {
    const $cat = $('.select-search');
    if ($cat.length) {
        if ($cat.hasClass('select2-hidden-accessible')) {
            $cat.select2('destroy');
        }
        $cat.select2({
            theme: 'bootstrap-5',
            width: '100%',
            allowClear: true,
            placeholder: $cat.data('placeholder') || window.trans("All")
        });
    }
});


/* ===== PAYMENT-GATEWAY-UI START ===== */
(function () {
    function init() {
        var list = document.getElementById('gwGrid');
        if (!list) return;
        var items = Array.prototype.slice.call(document.querySelectorAll('.pg-list-item'));
        var filterBtns = Array.prototype.slice.call(document.querySelectorAll('.pg-filter [data-filter]'));
        var empty = document.getElementById('gwEmpty');
        var current = 'all';

        function show(target) {
            document.querySelectorAll('.pg-detail').forEach(function (d) { d.classList.toggle('active', d.id === target); });
            items.forEach(function (it) { it.classList.toggle('selected', it.getAttribute('data-target') === target); });
        }
        items.forEach(function (it) {
            function sel() { show(it.getAttribute('data-target')); }
            it.addEventListener('click', sel);
            it.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); sel(); } });
        });

        function isActive(it) { return it.classList.contains('is-active'); }
        function applyFilter() {
            var vis = 0;
            items.forEach(function (it) {
                var s = current === 'all' || (current === 'active' && isActive(it)) || (current === 'inactive' && !isActive(it));
                it.style.display = s ? '' : 'none';
                if (s) vis++;
            });
            if (empty) empty.classList.toggle('d-none', vis !== 0);
        }
        filterBtns.forEach(function (b) {
            b.addEventListener('click', function () {
                filterBtns.forEach(function (x) { x.classList.remove('active'); });
                b.classList.add('active');
                current = b.getAttribute('data-filter');
                applyFilter();
            });
        });

        document.querySelectorAll('.pg-detail').forEach(function (d) {
            var it = document.querySelector('.pg-list-item[data-target="' + d.id + '"]');
            if (!it) return;
            function statusInput() { return d.querySelector('.status-switch, input[role="switch"][type="checkbox"], input[name="bank[bank_transfer_status]"][type="checkbox"]'); }
            function sync() {
                var cb = statusInput();
                if (!cb) return;
                it.classList.toggle('is-active', cb.checked);
                it.classList.toggle('is-inactive', !cb.checked);
                applyFilter();
            }
            d.addEventListener('change', function (e) { if (e.target && e.target.matches('input[type="checkbox"]')) sync(); });
        });

        applyFilter();
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
/* ===== PAYMENT-GATEWAY-UI END ===== */


/* ===== SETTINGS-INDEX-UI START ===== */
(function () {
    function debounce(fn, ms) { var t; return function () { var a = arguments, c = this; clearTimeout(t); t = setTimeout(function () { fn.apply(c, a); }, ms); }; }
    function initSearch() {
        var grid = document.getElementById('stSettingsGrid');
        if (!grid) return;
        var input = document.getElementById('stSearch');
        var cards = Array.prototype.slice.call(grid.querySelectorAll('.st-card'));
        var titleEl = document.getElementById('stTitle');
        var countEl = document.getElementById('stCount');
        var empty = document.getElementById('stEmpty');
        var word = (countEl && countEl.getAttribute('data-word')) || 'settings';
        var allTxt = (titleEl && titleEl.getAttribute('data-all')) || 'All settings';
        var resTxt = (titleEl && titleEl.getAttribute('data-results')) || 'Results for';

        function apply() {
            var raw = (input.value || '').trim();
            var q = raw.toLowerCase();
            var vis = 0;
            cards.forEach(function (c) {
                var hay = c.getAttribute('data-search') || '';
                var show = !q || hay.indexOf(q) !== -1;
                c.style.display = show ? '' : 'none';
                if (show) vis++;
            });
            if (countEl) countEl.textContent = vis + ' ' + word;
            if (titleEl) titleEl.textContent = raw ? (resTxt + ' \u201c' + raw + '\u201d') : allTxt;
            if (empty) empty.classList.toggle('d-none', vis !== 0);
        }
        cards.forEach(function (c) {
            c.addEventListener('click', function (e) {
                var q = (input.value || '').toLowerCase().trim();
                var gws = (c.getAttribute('data-gateways') || '').split(' ');
                if (q && gws.indexOf(q) !== -1) {
                    e.preventDefault();
                    var href = c.getAttribute('href');
                    href += (href.indexOf('?') === -1 ? '?' : '&') + 'gateway=' + encodeURIComponent(q);
                    window.location.href = href;
                }
            });
        });
        input.addEventListener('input', debounce(apply, 250));
        input.addEventListener('keydown', function (e) {
            if (e.key !== 'Enter') return;
            e.preventDefault();
            var first = cards.find(function (c) { return c.style.display !== 'none'; });
            if (!first) return;
            var href = first.getAttribute('href');
            var q = (input.value || '').toLowerCase().trim();
            var gws = (first.getAttribute('data-gateways') || '').split(' ');
            if (q && gws.indexOf(q) !== -1) { href += (href.indexOf('?') === -1 ? '?' : '&') + 'gateway=' + encodeURIComponent(q); }
            window.location.href = href;
        });
        document.addEventListener('keydown', function (e) {
            if ((e.metaKey || e.ctrlKey) && (e.key === 'k' || e.key === 'K')) { e.preventDefault(); input.focus(); }
        });
    }
    // Payment-gateway deep link: open a specific gateway when arriving with ?gateway=xxx
    function initDeepLink() {
        var list = document.getElementById('gwGrid');
        if (!list) return;
        var gw = (new URLSearchParams(window.location.search).get('gateway') || '').toLowerCase().trim();
        if (!gw) return;
        var item = document.querySelector('.pg-list-item[data-target="detail_gw_' + gw + '"]');
        if (item) { item.click(); if (item.scrollIntoView) item.scrollIntoView({ block: 'nearest' }); }
    }
    function boot() { initSearch(); initDeepLink(); }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot); else boot();
})();
/* ===== SETTINGS-INDEX-UI END ===== */
