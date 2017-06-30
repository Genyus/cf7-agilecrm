<?php
/**
 * @author Gary McPherson (genyus) <gary@ingenyus.com>
 * @license http://www.gnu.org/licenses/agpl-3.0.html
 */
?>
<h3><?php echo esc_html( __( 'Agile CRM Settings', 'contact-form-7-agilecrm-integration' ) ); ?></h3>

<div class="contact-form-editor-box-agilecrm">

  <p><label for="enable-agilecrm"><input type="checkbox" id="enable-agilecrm" name="enable-agilecrm" class="toggle-form-table" value="1"<?php echo ( ! empty( $agilecrm['enable'] ) ) ? ' checked="checked"' : ''; ?> /> <?php echo esc_html( __( 'Enable Agile CRM sync', 'contact-form-7-agilecrm-integration' ) ); ?></label></p>

<fieldset>

  <legend><?php echo esc_html( __( "Use parameters to add additional api parameters e.g. contact_type=Individual&source=wordpress", 'contact-form-7-agilecrm-integration' ) ); ?></legend>

  <table class="form-table">
    <tbody>
    <tr>
      <th scope="row">
        <label for="action"><?php echo esc_html( __( 'Additional parameters', 'contact-form-7-agilecrm-integration' ) ); ?></label>
      </th>
      <td>
        <input type="text" id="agilecrm-parameters" name="agilecrm-parameters" class="large-text code" size="70" value="<?php echo esc_attr( $agilecrm['parameters'] ); ?>" />
      </td>
    </tr>
    </tbody>
  </table>
  <?php AgileCF7Addon::get_instance()->load_form_fields($agilecrm); ?>
</fieldset>

</div>
