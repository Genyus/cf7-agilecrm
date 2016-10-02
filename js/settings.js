jQuery(function ($) {

    $admin_url = window.location.href.substr(0, window.location.href.search('wp-admin/'));
    $ajax_spinner = '<span id="ajax_spinner"> <img src="' + $admin_url + '/wp-admin/images/wpspin_light.gif" /></span>';

    $('#agilecrm_gf_sync_form').change(function () {
        var formID = $(this).val();
        $('#wp_agile_ajax_result').empty();
        if (formID != 0 && formID !== '') {
            $('#agilecrm_gf_mapped_forms').prop('checked', false);
            $('#agilecrm_gf_mapped_forms_row').show();
            $('#agilecrm_gf_mapped_forms').val(formID);
            if ($('option:selected', this).attr('data-isSynced') == '1') {
                $('#agilecrm_gf_mapped_forms').trigger('click');
            }
        } else {
            $('#agilecrm_gf_mapped_forms').prop('checked', false);
            $('#agilecrm_gf_mapped_forms').val("");
            $('#agilecrm_gf_mapped_forms_row').hide();
        }
    });

    $('#agilecrm_gf_mapped_forms').click(function () {

        var isChecked = $(this).is(':checked');
        var formID = $('#agilecrm_gf_sync_form').val();
        if (isChecked && formID != 0 && formID !== '') {
            $(this).val(formID);
            $('#ajax_spinner').remove();
            $('#agilecrm_gf_mapped_forms_label').after($ajax_spinner);

            $.post(ajaxurl, {action: 'agilecrm_gf_load_fields', formid: formID}, function (response) {

                var responseJson = response.substring(response.indexOf('{'));
                var responseObj = JSON.parse(responseJson);
                $('#wp_agile_ajax_result').html(responseObj['markup']);

                for (var key in responseObj['selectedFields']) {
                    $('#agilecrm_form_field_' + key).val(responseObj['selectedFields'][key]);
                }
                $('#ajax_spinner').delay(50).fadeOut('slow');
            });
        } else {
            $('#wp_agile_ajax_result').empty();
        }
    });

    $('form#agilecrm_gf_form_map').submit(function (e) {
        e.preventDefault();
        var data = $(this).serializeObject();

        $('#ajax_spinner').remove();
        $('#agilecrm_gf_form_map #updateFields').after($ajax_spinner);

        $.post(ajaxurl, data, function (response) {
            var response_text = (response) ? ' Updated!' : ' Error!';
            // $('#ajax_spinner').html(response);
            $('#ajax_spinner').html(response_text);
            $('#ajax_spinner').delay(2000).fadeOut('slow');
        });

    });

});

jQuery.fn.serializeObject = function ()
{
    var o = {};
    var a = this.serializeArray();
    jQuery.each(a, function () {
        if (o[this.name] !== undefined) {
            if (!o[this.name].push) {
                o[this.name] = [o[this.name]];
            }
            o[this.name].push(this.value || '');
        } else {
            o[this.name] = this.value || '';
        }
    });
    return o;
};
