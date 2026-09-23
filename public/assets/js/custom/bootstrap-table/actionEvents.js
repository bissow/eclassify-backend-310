window.languageEvents = {
    'click .edit_btn': function (e, value, row) {
        $('.filepond').filepond('removeFile');
        $('input[type=file]').each(function(){
            if(!$(this).hasClass('not-reset')){
                $(this).val('');
            }
        });


        var isDefaultLang = (row.code === 'en');
        $("#edit_is_default_lang").val(isDefaultLang ? '1' : '0');

        $("#edit_name").val(row.name);
        $("#edit_name_in_english").val(row.name_in_english);
        $("#edit_code").val(row.code);
        $("#edit_country_code").val(row.country_code);
        $("#edit_rtl_switch").prop('checked', row.rtl);
        $("#edit_rtl").val(row.rtl ? 1 : 0);

        // Toggle non-default fields visibility
        if (isDefaultLang) {
            $(".non-default-field").hide();
            // Disable parsley validation on hidden required fields
            $(".non-default-field").find('[data-parsley-required]').attr('data-parsley-required', 'false');
        } else {
            $(".non-default-field").show();
            // Re-enable parsley validation on shown fields
            $(".non-default-field").find('[data-parsley-required="false"]').attr('data-parsley-required', 'true');
        }

        // ✅ Update download links dynamically
        $("#download_panel_file").attr("href", "/language/" + row.id + "/download/panel");
        $("#download_app_file").attr("href", "/language/" + row.id + "/download/app");
        $("#download_web_file").attr("href", "/language/" + row.id + "/download/web");
    },
    'click .delete_btn': function (e, value, row) {
        e.preventDefault();
        showDeleteLanguagePopupModal($(this).attr('href'), {
            successCallBack: function () {
                $('#table_list').bootstrapTable('refresh');
            }
        });
    }
};


// window.SeoSettingEvents = {
//     'click .edit_btn': function (e, value, row) {
//         $('.filepond').filepond('removeFile')
//         $("#edit_page").val(row.page);
//         $("#edit_title").val(row.title);
//         $("#edit_description").val(row.description);
//         $("#edit_keywords").val(row.keywords);
//     }
// };
window.SeoSettingEvents = {
    'click .edit_btn': function (e, value, row) {
        $("#edit_page").val(row.page);
        $('#edit_image').filepond('removeFile');

        if (row.image) {
            $('#edit_image_preview').html('<img src="' + row.image + '" class="img-thumbnail" style="max-height: 150px;">');
        } else {
            $('#edit_image_preview').html('');
        }
        // Clear all translation fields first to avoid stale data from a previously opened row
        $('[id^="edit_title_"]').val('');
        $('[id^="edit_description_"]').val('');
        $('[id^="edit_keywords_"]').val('');
        $('[id^="edit_schema_"]').val('');

        $("#edit_title_1").val(row.title ?? '');
        $("#edit_description_1").val(row.description ?? '');
        $("#edit_keywords_1").val(row.keywords ?? '');
        $("#edit_schema_1").val(row.schema ?? '');

        let translations = row.translations ?? [];
        translations.forEach(function (translation) {
            const langId = translation.language_id;
            const key = translation.key;
            const value = translation.value;
            if (key === 'title') {
                $("#edit_title_" + langId).val(value);
            } else if (key === 'description') {
                $("#edit_description_" + langId).val(value);
            } else if (key === 'keywords') {
                $("#edit_keywords_" + langId).val(value);
            } else if (key === 'schema') {
                $("#edit_schema_" + langId).val(value);
            }
        });
    }
};

window.customFieldValueEvents = {
    'click .edit_btn': function (e, value, row) {
        $("#new_custom_field_value").val(row.value);
        $("#old_custom_field_value").val(row.value);
    }
}
window.verificationFieldValueEvents = {
    'click .edit_btn': function (e, value, row) {
        $("#new_verification_field_value").val(row.value);
        $("#old_verification_field_value").val(row.value);
    }
}


window.itemEvents = {
    'click .editdata': function (e, value, row) {
        // Handled by document-level delegation in items/index.blade.php
    },

    'click .edit-status': function (e, value, row) {
        $("#id").val(row.id);
        $('#status').val(row.status).trigger('change');
        $('#rejected_reason').val(row.rejected_reason);
    }
}

window.packageEvents = {
    'click .edit_btn': function (e, value, row) {
        // Clear all translation fields first
        $('[id^="edit_name_"]').val('');
        $('[id^="edit_description_"]').val('');

        // Set English (language ID 1) fields
        $('#edit_name_1').val(row.name);
        $('#edit_description_1').val(row.description);
        
        // Set non-translatable fields (in English tab)
        $('#edit_price').val(row.price);
        $('#edit_discount_in_percentage').val(row.discount_in_percentage);
        $('#edit_final_price').val(row.final_price);
        $('#edit_ios_product_id').val(row.ios_product_id);

        // Populate translations for other languages
        if (row.translations && Array.isArray(row.translations)) {
            row.translations.forEach(function (trans) {
                const langId = trans.language_id;
                if (langId != 1) { // Skip English as it's already set above
                    $('#edit_name_' + langId).val(trans.name || '');
                    $('#edit_description_' + langId).val(trans.description || '');
                }
            });
        }

        // Handle duration
        if (row.duration && row.duration.toString().toLowerCase() === "unlimited") {
            $('#edit_duration_type_unlimited').prop('checked', true);
            $('#edit_durationLimit').val('');
            $('#edit_limitation_for_duration').hide();
        } else {
            $('#edit_duration_type_limited').prop('checked', true);
            $('#edit_limitation_for_duration').show();
            $('#edit_durationLimit').val(row.duration || '');
        }

        // Handle item limit
        if (row.item_limit && row.item_limit.toString().toLowerCase() === "unlimited") {
            $('#edit_item_limit_type_unlimited').prop('checked', true);
            $('#edit_ForLimit').val('');
            $('#edit_limitation_for_limit').hide();
        } else {
            $('#edit_item_limit_type_limited').prop('checked', true);
            $('#edit_limitation_for_limit').show();
            $('#edit_ForLimit').val(row.item_limit || '');
        }
    }
};

window.advertisementPackageEvents = {
    'click .edit_btn': function (e, value, row) {
        // Clear all translation fields first
        $('[id^="edit_name_"]').val('');
        $('[id^="edit_description_"]').val('');

        // Set English (language ID 1) fields
        $('#edit_name_1').val(row.name);
        $('#edit_description_1').val(row.description);
        
        // Set non-translatable fields (in English tab)
        $('#edit_price').val(row.price);
        $('#edit_discount_in_percentage').val(row.discount_in_percentage);
        $('#edit_final_price').val(row.final_price);
        $('#edit_durationLimit').val(row.duration || '');
        $('#edit_ForLimit').val(row.item_limit || '');
        $('#edit_ios_product_id').val(row.ios_product_id);
        row.translations.forEach(function (translation) {
            const langId = translation.language_id;
            $("#edit_name_" + langId).val(translation.name);
            $("#edit_description_" + langId).val(translation.description);
        });
    }
};

window.reportReasonEvents = {
    'click .edit_btn': function (e, value, row) {
        let translations = row.translations ?? [];

        // Reset all language inputs first (clear old values)
        $("[id^=edit_reason_]").val("");

        // Set English reason (default)
        $("#edit_reason_1").val(row.reason);

        // Fill translations if available
        translations.forEach(function (translation) {
            const langId = translation.language_id;
            $("#edit_reason_" + langId).val(translation.reason);
        });

        // Set the form action URL if needed
        // $(".edit-form").attr("action", `/report-reasons/${row.id}`);
    }
}

window.featuredSectionEvents = {
    'click .edit_btn': function (e, value, row) {
        // Clear all translation fields first
        $('[id^="edit_title_"]').val('');
        $('[id^="edit_description_"]').val('');

        // Set English (language ID 1) fields
        $('#edit_title_1').val(row.title);
        $('#edit_description_1').val(row.description);
        
        // Set non-translatable fields (in English tab)
        $('#edit_slug').val(row.slug);
        $('#edit_filter').val(row.filter).trigger('change');
        
        // Populate translations for other languages
        if (row.translations && Array.isArray(row.translations)) {
            row.translations.forEach(function (trans) {
                const langId = trans.language_id;
                if (langId != 1) { // Skip English as it's already set above
                    $('#edit_title_' + langId).val(trans.name || '');
                    $('#edit_description_' + langId).val(trans.description || '');
                }
            });
        }
        
        // Handle filter-specific fields
        if (row.filter === "price_criteria") {
            $('#edit_price_criteria').show();
            $('#edit_min_price').val(row.min_price || '');
            $('#edit_max_price').val(row.max_price || '');
        } else {
            $('#edit_price_criteria').hide();
            $('#edit_min_price').val('');
            $('#edit_max_price').val('');
        }
        
        if (row.filter == "category_criteria") {
            $('#edit_category_criteria').show();
            if (row.value && row.value != '') {
                $('#edit_category_id').val(row.value.split(',')).trigger('change');
            } else {
                $('#edit_category_id').val('').trigger('change');
            }
        } else {
            $('#edit_category_criteria').hide();
            $('#edit_category_id').val('').trigger('change');
        }

        if (row.filter == "item_selection") {
            $('#edit_item_selection_criteria').show();
            $('#edit_item_filter_category').val('').trigger('change');
            $('#edit_item_id').empty().trigger('change');

            if (row.value && row.value != '') {
                var selectedIds = row.value.split(',');
                $.get('/feature-section/items-by-ids', { item_ids: selectedIds.join(',') }, function (res) {
                    if (res && res.data) {
                        $.each(res.data, function (i, item) {
                            $('#edit_item_id').append(new Option(item.name, item.id, true, true));
                        });
                        $('#edit_item_id').trigger('change');
                        var count = res.data.length;
                        $('#edit_item_count_badge').text(count).toggle(count > 0);
                    }
                });
            }
        } else {
            $('#edit_item_selection_criteria').hide();
            $('#edit_item_filter_category').val('').trigger('change');
            $('#edit_item_id').empty().trigger('change');
        }

        // Set style
        $('input[name="style"]').prop('checked', false);
        $('input[name="style"][value="' + row.style + '"]').prop('checked', true);

        // Lock filter-specific fields: read-only display, cannot be changed after creation.
        // Keep native <select> enabled so its value still submits; only block UI interaction.
        $('#edit_filter, #edit_category_id, #edit_item_filter_category, #edit_item_id')
            .next('.select2-container')
            .css({ 'pointer-events': 'none', 'opacity': '0.65' });
    }
};

window.staffEvents = {
    'click .edit_btn': function (e, value, row) {
        $('#edit_role').val(row.roles[0].id);
        $('#edit_name').val(row.name);
        $('#edit_email').val(row.email);
    }
}
window.verificationfeildEvents = {
    'click .edit_btn': function (e, value, row) {
        $('#edit_name').val(row.name);
        $('#edit_is_required').val(row.is_required)
    }
}

window.userEvents = {
    'click .assign_package': function (e, value, row) {
        $("#user_id").val(row.id);
        $('.package_type').prop('checked', false);

        // $('#item-listing-package-div').hide();
        // $('#advertisement-package-div').hide();

        $('#advertisement-package').attr('required', false);
        $('#item-listing-package').attr('required', false);

        $('#package_details').hide();
        $('.payment').hide();
        $('.cheque').hide();
    },
    'click .manage_packages': function (e, value, row) {
        // This is handled in the customer/index.blade.php file
        // The button already has data-user-id attribute
    }
}

// window.faqEvents = {
//     'click .edit_btn': function (e, value, row) {
//         $('#edit_question').val(row.question);
//         $('#edit_answer').val(row.answer);
//     }
// }

window.faqEvents = {
    'click .edit_btn': function (e, value, row) {
        let updateUrl = "{{ url('admin/faq') }}/" + row.id;
        $('.edit-form').attr('action', updateUrl);
        $("[id^=edit_question_]").val("");
        $("[id^=edit_answer_]").val("");
        $('#edit_faq_id').val(row.id);
        $("#edit_question_1").val(row.question);
        $("#edit_answer_1").val(row.answer);
        let translations = row.translations ?? [];
        translations.forEach(function (translation) {
            const langId = translation.language_id;
            $("#edit_question_" + langId).val(translation.question);
            $("#edit_answer_" + langId).val(translation.answer);
        });
    }
};

window.areaEvents = {
    'click .edit_btn': function (e, value, row) {
        $('#edit_name').val(row.name);
        $('#edit_country').val(row.country_id);
        $('#edit_state').val(row.state_id);
        $('#edit_latitude').val(row.latitude);
        $('#edit_longitude').val(row.longitude);

        let cityUrl = window.baseurl + 'cities/search?state_id=' + row.state_id;
        ajaxRequest('GET', cityUrl, null, null, function (response) {
            $('#edit_city').html("<option value=''>" + window.trans("--Select City--") + "</option>")
            $.each(response.data, function (key, value) {
                $('#edit_city').append($('<option>', {
                    value: value.id,
                    text: value.name
                }));
            });
            $('#edit_city').val(row.city_id);
        });

        // Initialize map after modal is shown
        $('#editModal').on('shown.bs.modal', function () {
            // Get coordinates from the row data
            const lat = parseFloat(row.latitude) || 0;
            const lng = parseFloat(row.longitude) || 0;

            // Initialize map with current coordinates
            const editMap = window.mapUtils.initializeMap('edit_map', lat, lng);

            // Create a marker at the current position
            let currentMarker = L.marker([lat, lng], {
                draggable: true
            }).addTo(editMap);

            // Update coordinates when marker is dragged
            currentMarker.on('dragend', function(event) {
                const position = event.target.getLatLng();
                $('#edit_latitude').val(position.lat);
                $('#edit_longitude').val(position.lng);
            });

            // Update marker position and coordinates when map is clicked
            editMap.on('click', function(e) {
                const position = e.latlng;
                currentMarker.setLatLng(position);
                $('#edit_latitude').val(position.lat);
                $('#edit_longitude').val(position.lng);
            });
        });

        // Clean up when modal is hidden
        $('#editModal').on('hidden.bs.modal', function () {
            window.mapUtils.removeMap('edit_map');
            $(this).off('shown.bs.modal');
            $(this).off('hidden.bs.modal');
        });
    }
}
window.cityEvents = {
    'click .edit_btn': function (e, value, row) {
        $('#edit_country').val(row.country_id);
        $('#edit_state').val(row.state_id);
        $('#edit_name').val(row.name);
        $('#edit_latitude').val(row.latitude);
        $('#edit_longitude').val(row.longitude);

        // Initialize map after modal is shown
        $('#editModal').on('shown.bs.modal', function () {
            // Get coordinates from the row data
            const lat = parseFloat(row.latitude) || 0;
            const lng = parseFloat(row.longitude) || 0;

            // Initialize map with current coordinates
            const editMap = window.mapUtils.initializeMap('edit_map', lat, lng);

            // Create a marker at the current position
            let currentMarker = L.marker([lat, lng], {
                draggable: true
            }).addTo(editMap);

            // Update coordinates when marker is dragged
            currentMarker.on('dragend', function(event) {
                const position = event.target.getLatLng();
                $('#edit_latitude').val(position.lat);
                $('#edit_longitude').val(position.lng);
            });

            // Update marker position and coordinates when map is clicked
            editMap.on('click', function(e) {
                const position = e.latlng;
                currentMarker.setLatLng(position);
                $('#edit_latitude').val(position.lat);
                $('#edit_longitude').val(position.lng);
            });
        });

        // Clean up when modal is hidden
        $('#editModal').on('hidden.bs.modal', function () {
            window.mapUtils.removeMap('edit_map');
            $('#edit_map').html('');
            $(this).off('shown.bs.modal');
            $(this).off('hidden.bs.modal');
        });
    }
}
window.verificationEvents = {
    'click .view-verification-fields': function (e, value, row) {
        // Seller always submits verification data in the default language only, no per-language tabs needed
        let content = `<table class="table">
            <tr>
                <th width="10%">${LangTrans("No.")}</th>
                <th width="25%">${LangTrans("Name")}</th>
                <th width="65%">${LangTrans("Value")}</th>
            </tr>`;

        let count = 1;
        $.each(row.verification_field_values, function (key, field) {
            let fieldName = field.verification_field.name;
            let fieldValue = field.value;

            let displayValue = '';
            if (fieldValue) {
                if (typeof fieldValue === 'string' && fieldValue.includes('verification_field_files')) {
                    displayValue = `<a class='text-decoration-underline' href='${fieldValue}' target='_blank'>${LangTrans('Click Here')}</a>`;
                } else {
                    displayValue = Array.isArray(fieldValue) ? fieldValue.join(', ') : fieldValue;
                }
            } else {
                displayValue = LangTrans('No value provided');
            }

            content += `<tr>
                <td>${count}</td>
                <td>${fieldName}</td>
                <td class="text-break">${displayValue}</td>
            </tr>`;
            count++;
        });

        content += `</table>`;

        $('#verification_fields').html(content);
        $('#editModal').modal('show');
    },

    'click .edit_btn': function (e, value, row) {
        $("#rejection_reason").val("");
        $('#verification_status').val(row.status).trigger('change');
        $('#rejection_reason').val(row.rejection_reason);
    }
};
window.reviewReportEvents = {
    'click .edit_btn': function (e, value, row) {
        if(row.report_status == 'reported'){
            $('#report_status').val('').trigger('change');
        }else{
            $('#report_status').val(row.report_status).trigger('change');
        }
        $('#report_rejected_reason').val(row.report_rejected_reason);
    }
}
