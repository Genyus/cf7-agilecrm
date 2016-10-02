<?php
/**
 * @author Gary McPherson (genyus) <gary@ingenyus.com>
 * @license http://www.gnu.org/licenses/agpl-3.0.html
 */

class cf7_agile_admin {
  const NONCE = 'cf7_agile_admin';


  protected static $initiated = false;

  public static function init() {
    if (!self::$initiated) {
      self::$initiated = true;
      add_action( 'admin_menu', array( 'cf7_agile_admin', 'admin_menu' ) );
      add_action( 'admin_enqueue_scripts', array('cf7_agile_admin', 'admin_enqueue_scripts') );
      add_action( 'wpcf7_save_contact_form', array('cf7_agile_admin', 'save_contact_form'));

      add_filter( 'wpcf7_editor_panels', array('cf7_agile_admin', 'panels'));
    }
  }

  public static function admin_menu() {
    add_options_page( __('AgileCRM Settings', 'contact-form-7-agilecrm-integration'), __('AgileCRM Settings', 'contact-form-7-agilecrm-integration'), 'manage_options', 'cf7_agile_admin', array( 'cf7_agile_admin', 'display_page' ) );
  }

  public static function get_page_url( $page = 'config' ) {

    $args = array( 'page' => 'cf7_agile_admin' );
    $url = add_query_arg( $args, admin_url( 'options-general.php' ) );

    return $url;
  }

  public static function display_page() {
    $host = cf7_agile_settings::getHost();
    $site_key = cf7_agile_settings::getSiteKey();
    $api_key = cf7_agile_settings::getApiKey();
    $path = cf7_agile_settings::getPath();

    if (isset($_POST['host'])) {
      cf7_agile_settings::setHost($_POST['host']);
    }
    if (isset($_POST['site_key'])) {
      cf7_agile_settings::setSiteKey($_POST['site_key']);
    }
    if (isset($_POST['api_key'])) {
      cf7_agile_settings::setApiKey($_POST['api_key']);
    }
    if (isset($_POST['path'])) {
      cf7_agile_settings::setPath($_POST['path']);
    }
    cf7_agile_admin::view( 'settings', compact( 'host', 'site_key', 'api_key', 'path') );
  }

  public static function view( $name, array $args = array() ) {
    $args = apply_filters( 'cf7_agile_view_arguments', $args, $name );

    foreach ( $args AS $key => $val ) {
      $$key = $val;
    }

    load_plugin_textdomain( 'contact-form-7-agilecrm-integration' );

    $file = CF7_AGILE__PLUGIN_DIR . 'views/'. $name . '.php';

    include( $file );
  }

  /**
   * Add a Agile setting panel to the contact form admin section.
   *
   * @param array $panels
   * @return array
   */
  public static function panels($panels) {
    $panels['contact-form-7-agilecrm-integration'] = array(
      'title' => __( 'AgileCRM', 'contact-form-7-agilecrm-integration' ),
      'callback' => array('cf7_agile_admin', 'agilecrm_panel'),
    ) ;
    return $panels;
  }

  public static function agilecrm_panel($post) {
    $agilecrm = $post->prop('agilecrm' );
    cf7_agile_admin::view('agilecrm_panel', array('post' => $post, 'agilecrm' => $agilecrm));
  }

  public static function save_contact_form($contact_form) {
    $properties = $contact_form->get_properties();
    $agilecrm = $properties['agilecrm'];

    $agilecrm['enable'] = true;

    if ( isset( $_POST['agilecrm-entity'] ) ) {
      $agilecrm['entity'] = trim( $_POST['agilecrm-entity'] );
    }
    if ( isset( $_POST['agilecrm-action'] ) ) {
      $agilecrm['action'] = trim( $_POST['agilecrm-action'] );
    }
    if ( isset( $_POST['agilecrm-parameters'] ) ) {
      $agilecrm['parameters'] = trim( $_POST['agilecrm-parameters'] );
    }

    $properties['agilecrm'] = $agilecrm;
    $contact_form->set_properties($properties);
  }

  public static function admin_enqueue_scripts($hook_suffix) {
    if ( false === strpos( $hook_suffix, 'wpcf7' ) ) {
      return;
    }

    wp_enqueue_script( 'cf7_agile-admin',
      CF7_AGILE__PLUGIN_URL. 'js/admin.js',
      array( 'jquery', 'jquery-ui-tabs' )
    );
  }

}