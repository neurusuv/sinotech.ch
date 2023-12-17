jQuery(document).ready(function ($) {


    $('.wpc-pull-stats').on('click', function (e){
        e.preventDefault();
        let button = $(this);
        let state = $(button).html();

        $(button).html('Updating...');

        $.post(ajaxurl, {
            action: 'wps_ic_pull_stats',
        }, function (response) {
            $(button).html(state);
        });

        return false;
    });


    var links = $('.ajax-run-critical'); //We can add data-status to links to know which ones need to be run?
    var processed_links = 0;
    var process_all = 0;

    const range = document.getElementById('optimizationLevel');
    const setValue = () => {

        $('.wpc-slider-text>div[data-value="' + range.value + '"]').trigger('click');

        const newValue = Number((range.value - range.min) * 100 / (range.max - range.min)),
            newPosition = 16 - (newValue * 0.32);
        document.documentElement.style.setProperty("--range-progress", `calc(${newValue}% + (${newPosition}px))`);
    };

    function rangeSlider() {
        const newValue = Number((range.value - range.min) * 100 / (range.max - range.min)),
            newPosition = 16 - (newValue * 0.32);
        document.documentElement.style.setProperty("--range-progress", `calc(${newValue}% + (${newPosition}px))`);
    }

    if (range) {
        rangeSlider();


        document.addEventListener("DOMContentLoaded", setValue);
        //rangeImg.addEventListener('input', setValueImg);
        range.addEventListener('input', setValue);
    }

    function process_next_link() {
        links[processed_links].click();
        if (processed_links < links.length - 1) {
            processed_links = processed_links + 1;
        } else {
            process_all = 0;
        }
    }

    $('.ajax-run-critical-all').on('click', function (e) {
        e.preventDefault();
        process_all = 1;
        console.log(process_all)
        process_next_link();
    });

    $('.ajax-run-critical').on('click', function (e) {
        e.preventDefault();
        var pageID = $(this).data('page-id');

        var link = this;
        var status = $('#status_' + pageID);
        var assets_count_img = $('#assets_img_' + pageID);
        var assets_count_css = $('#assets_css_' + pageID);
        var assets_count_js = $('#assets_js_' + pageID);
        link.text = 'In Progress';
        $.post(ajaxurl, {
            action: 'wps_ic_critical_get_assets',
            pageID: pageID
        }, function (response) {
            var files = JSON.parse(response.data);

            assets_count_img.html(files.img);
            assets_count_css.html(files.css);
            assets_count_js.html(files.js);

            $.post(ajaxurl, {
                action: 'wps_ic_critical_run',
                pageID: pageID
            }, function (response) {
                if (response.success) {
                    link.text = 'Done';
                    status.html('<div class="wpc-critical-circle wpc-done"></div>');
                } else {
                    link.text = 'Error';
                    status.html('<div class="wpc-critical-circle wpc-error"></div>');
                    $(link).after('<div class="wpc-custom-tooltip"><i class="tooltip-icon" title="'+ response.data.msg +'"></i></div>')
                }

                if (process_all === 1) {
                    process_next_link();
                }
            });

        });

        return false;
    });


    $('#optimizationLevel').on('change', function (e) {
        e.preventDefault();

        $('.action-buttons').fadeOut(500, function () {
            $('.save-button').fadeIn(500);
        });

        return false;
    });

    $('#optimizationLevel_img').on('change', function (e) {
        e.preventDefault();

        $('.action-buttons').fadeOut(500, function () {
            $('.save-button').fadeIn(500);
        });

        return false;
    });


    $('.wpc-ic-settings-v2-checkbox').on('change', function (e) {
        e.preventDefault();

        var parent = $(this).parents('.option-item');
        var beforeValue = $(this).attr('checked');
        var optionName = $(this).data('option-name');
        var newValue = 1;

        if (beforeValue == 'checked') {
            // It was already active, remove checked
            $(this).removeAttr('checked');
            $('.circle-check', parent).removeClass('active');
        } else {
            // It's not active, activate
            $(this).attr('checked', 'checked');
            $('.circle-check', parent).addClass('active');
        }

        $('.save-button').fadeIn(500);
        //$('.wpc-preset-dropdown>option').removeAttr('selected').prop('selected', false);
        //$('.wpc-preset-dropdown>option:eq(2)').attr('selected', 'selected').prop('selected', true);

        $('input[name="wpc_preset_mode"]').val('custom');
        $('a', '.wpc-dropdown-menu').removeClass('active');
        $('button', '.wpc-dropdown').html('Custom');
        $('a[data-value="custom"]', '.wpc-dropdown-menu').addClass('active');

        //var selectedValue = $('.wpc-preset-dropdown').val();
        $.post(ajaxurl, {
            action: 'wpc_ic_ajax_set_preset',
            value: 'custom',
        }, function (response) {

        });

        return false;
    });


    $('.wpc-ic-settings-v2-checkbox-ajax-save').on('change', function (e) {
        e.preventDefault();

        var parent = $(this).parents('.option-item');
        var beforeValue = $(this).attr('checked');
        var optionName = $(this).data('option-name');
        var newValue = 1;

        if (beforeValue == 'checked') {
            // It was already active, remove checked
            $(this).removeAttr('checked');
            newValue = 0;
        } else {
            // It's not active, activate
            $(this).attr('checked', 'checked');
        }

        $.post(ajaxurl, {
            action: 'wps_ic_ajax_v2_checkbox',
            optionName: optionName,
            optionValue: newValue
        }, function (response) {
            if (response.data.newValue == '1') {
                $('.circle-check', parent).addClass('active');
            } else {
                $('.circle-check', parent).removeClass('active');
            }
        });

        return false;
    });

});