<div class="wrap">
    <?php $this->plugin_settings_tabs(); ?>

    <form method="post" action="#" id="agilecrm_gf_form_map">
        <?php if (!extension_loaded('curl')) { ?>
        <div id="warningMsg" style="color: #cc3300; font-weight: bold; padding: 5px 0">Error : cURL library is not loaded. Enable cURL extension in your server to make the plugin work properly.</div>
        <?php } ?>

        <h3 class="title">Select the form to link it with Agile CRM</h3>

        <input type="hidden" name="action" value="agilecrm_gf_map_fields">
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><label for="agilecrm_gf_sync_form">Gravity Form</label></th>
                <td>
                    <select id="agilecrm_gf_sync_form" name="agilecrm_gf_sync_form" required="">
                        <option value="">Select form</option>
                        <?php
                        $forms = RGFormsModel::get_forms(null, "title");
                        $syncedForms = get_option('agilecrm_gf_mapped_forms');
                        foreach ($forms as $form) {
                            $isSynced = false;
                            if ($syncedForms && in_array($form->id, $syncedForms)) {
                                $isSynced = true;
                            }
                            echo '<option data-isSynced="' . $isSynced . '" value="' . $form->id . '" >' . $form->title . '</option>';
                        }
                        ?>
                    </select>
                </td>
            </tr>
            <tr valign="top" id="agilecrm_gf_mapped_forms_row" style="display: none;">
                <th scope="row"></th>
                <td><input id="agilecrm_gf_mapped_forms" name="agilecrm_gf_mapped_forms[]" type="checkbox"/> <label id="agilecrm_gf_mapped_forms_label" for="agilecrm_gf_mapped_forms">Integrate this form with Agile</label></td>
            </tr>
        </table>

        <div id="wp_agile_ajax_result"></div>
        <p class="submit"><input type="submit" name="updateFields" id="updateFields" class="button button-primary" value="Save Changes"> <span id="ajax_spinner"></span></p>

    </form>
</div>
<?php echo '<script src="' . plugins_url('js/settings.js', dirname(__FILE__)) . '"></script>'; ?>