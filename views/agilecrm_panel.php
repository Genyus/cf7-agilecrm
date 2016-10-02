<?php
/**
 * @author Gary McPherson (genyus) <gary@ingenyus.com>
 * @license http://www.gnu.org/licenses/agpl-3.0.html
 */
?>
<h3><?php echo esc_html( __( 'AgileCRM Settings', 'contact-form-7-agilecrm-integration' ) ); ?></h3>

<div class="contact-form-editor-box-agilecrm">

  <p><label for="enable-agilecrm"><input type="checkbox" id="enable-agilecrm" name="enable-agilecrm" class="toggle-form-table" value="1"<?php echo ( ! empty( $agilecrm['enable'] ) ) ? ' checked="checked"' : ''; ?> /> <?php echo esc_html( __( 'Enable CIVICRM processing', 'contact-form-7-agilecrm-integration' ) ); ?></label></p>

<fieldset>

  <legend><?php echo esc_html( __( "Fill in a AgileCRM API entity and action. E.g. Entity: Contact, action: create. Use parameters to add additional api parameters e.g. contact_type=Individual&source=wordpress", 'contact-form-7-agilecrm-integration' ) ); ?></legend>

  <table class="form-table">
    <tbody>

    <tr>
      <th scope="row">
        <label for="entity"><?php echo esc_html( __( 'Entity', 'contact-form-7-agilecrm-integration' ) ); ?></label>
      </th>
      <td>
        <input type="text" id="agilecrm-entity" name="agilecrm-entity" class="large-text code" size="70" value="<?php echo esc_attr( $agilecrm['entity'] ); ?>" />
      </td>
    </tr>
    <tr>
      <th scope="row">
        <label for="action"><?php echo esc_html( __( 'Action', 'contact-form-7-agilecrm-integration' ) ); ?></label>
      </th>
      <td>
        <input type="text" id="agilecrm-action" name="agilecrm-action" class="large-text code" size="70" value="<?php echo esc_attr( $agilecrm['action'] ); ?>" />
      </td>
    </tr>
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

</fieldset>

</div>