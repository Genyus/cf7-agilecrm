<div class="wrap">

  <h2><?php esc_html_e( 'Agile CRM Settings' , 'contact-form-7-agilecrm-integration');?></h2>


  <div class="postbox-container" style="">
    <div id="normal-sortables" class="meta-box-sortables ui-sortable">
      <div id="referrers" class="postbox ">
        <div class="handlediv" title="Click to toggle"><br></div>
        <h3 class="hndle"><span><?php esc_html_e( 'Settings' , 'contact-form-7-agilecrm-integration');?></span></h3>
        <form name="cf7_agile_admin" id="cf7_agile_admin" action="<?php echo esc_url( cf7_agile_admin::get_page_url() ); ?>" method="POST">
          <div class="inside">
            <table cellspacing="0">
              <tbody>
              <tr>
                <th width="20%" align="left" scope="row"><?php esc_html_e('Agile CRM Host', 'contact-form-7-agilecrm-integration');?></th>
                <td width="5%"/>
                <td align="left">
                  <span><input id="host" name="host" type="text" size="15" value="<?php echo esc_attr( cf7_agile_settings::getHost() ); ?>" class="regular-text code"></span>
                </td>
              </tr>

              <tr>
                <th width="20%" align="left" scope="row"><?php esc_html_e('Agile CRM Path', 'contact-form-7-agilecrm-integration');?></th>
                <td width="5%"/>
                <td align="left">
                  <span><input id="path" name="path" type="text" size="15" value="<?php echo esc_attr( cf7_agile_settings::getPath() ); ?>" class="regular-text code"></span>
                </td>
              </tr>

              <tr>
                <th width="20%" align="left" scope="row"><?php esc_html_e('Agile CRM Site Key', 'contact-form-7-agilecrm-integration');?></th>
                <td width="5%"/>
                <td align="left">
                  <span><input id="site_key" name="site_key" type="text" size="15" value="<?php echo esc_attr( cf7_agile_settings::getSiteKey() ); ?>" class="regular-text code"></span>
                </td>
              </tr>

              <tr>
                <th width="20%" align="left" scope="row"><?php esc_html_e('Agile CRM API Key', 'contact-form-7-agilecrm-integration');?></th>
                <td width="5%"/>
                <td align="left">
                  <span><input id="api_key" name="api_key" type="text" size="15" value="<?php echo esc_attr( cf7_agile_settings::getApiKey() ); ?>" class="regular-text code"></span>
                </td>
              </tr>

              </tbody>
            </table>
          </div>
          <div id="major-publishing-actions">
            <?php wp_nonce_field(cf7_agile_admin::NONCE) ?>
            <div id="publishing-action">
              <input type="hidden" name="action" value="enter-key">
              <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e('Save Changes', 'contact-form-7-agilecrm-integration');?>">

            </div>
            <div class="clear"></div>
          </div>
        </form>
      </div>
    </div>
  </div>

</div>