<div class="wrap">
    <?php $this->plugin_settings_tabs(); ?>
    <br>
    <div style="width: 300px;height: 64px;"><?php echo '<img src="' . plugins_url('js/agile500.png', dirname(__FILE__)) . '" > '; ?></div>
    <h3>Do not have an account with Agile CRM? <span style="font-size: 75%;">It's fast and free for two users</span></h3>    
    <div style="width: auto; height: auto; color: #8a6d3b; background-color: #fcf8e3; border: 1px solid #faebcc; border-radius: 5px">
        <div style="margin-top: 20px; margin-left: 50px;"><a href="https://www.agilecrm.com/pricing?utm_source=gravityforms&utm_medium=website&utm_campaign=integration" target="_blank" class="button">Create a new account</a></div>
        <p style="margin: 15px 20px 20px 50px;" id="create_account_text">Once you have created, please come back and fill in the details below</p>
    </div>

    <h3>Already have an account? <span style="font-size: 75%;">Enter your details</span></h3>

    <form method="post" action="options.php"> 
        <?php settings_fields($this->tag . '-settings-group'); ?>
        <?php do_settings_sections($this->tag); ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><label for="agilecrm_gf_domain">Domain</label></th>
                <td>
                    <span style="padding:3px; margin:0px; border: 1px solid #dfdfdf; border-right: 0px; background-color:#eee;">https://</span>
                    <input type="text"  name="agilecrm_gf_domain" id="agilecrm_gf_domain" value="<?php echo get_option('agilecrm_gf_domain'); ?>" style="width: 100px; margin: 0px; border-radius: 0px;" required="">
                    <span style="margin:0px; padding: 3px; border: 1px solid #dfdfdf; background-color:#eee; border-left: 0px;">.agilecrm.com</span><br><small>If you are using abc.agilecrm.com, enter abc</small>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="agilecrm_gf_admin_email">Domain User</label></th>
                <td>
                    <input type="text" style="width:250px;" name="agilecrm_gf_admin_email" id="agilecrm_gf_admin_email" value="<?php echo get_option('agilecrm_gf_admin_email'); ?>" placeholder="admin user email" required=""><br>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="agilecrm_gf_api_key">API Key</label></th>
                <td>
                    <input type="text" style="width:250px;" name="agilecrm_gf_api_key" id="agilecrm_gf_api_key" value="<?php echo get_option('agilecrm_gf_api_key'); ?>" placeholder="REST api key" required=""><br>
                    <small>For instructions to find your API key, please click <a href="https://github.com/agilecrm/rest-api#api-key" target="_blank">here</a></small>
                </td>
            </tr>
        </table>
        <?php submit_button(); ?>
    </form>

</div>