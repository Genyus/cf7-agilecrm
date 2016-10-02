<?php
/**
 * @author Gary McPherson (genyus) <gary@ingenyus.com>
 * @license http://www.gnu.org/licenses/agpl-3.0.html
 */

/*
Plugin Name: Contact Form 7 Agile CRM integration
Plugin URI: https://ingenyus.com
Description: Agile CRM integration plugin for Contact Form 7. Sync form entries to Agile easily.
Version: 1.0.0
Author: Gary McPherson
Author URI: https://ingenyus.com
License: AGPLv3
Text Domain: ccontact-form-7-agilecrm-integration
*/
defined('ABSPATH') or die('Plugin file cannot be accessed directly.');
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
//get the base class
if(!class_exists('WPCF7_Service')) {
    require_once plugin_dir_path( __FILE__ ) . '/../contact-form-7/includes/integration.php';
}

if (is_plugin_active('contact-form-7/wp-contact-form-7.php') && !class_exists('AgileCF7Addon')) {

    class AgileCF7Addon extends WPCF7_Service
    {

        protected $tag = 'agile-cf7-addon';
        private $account_settings_tab = 'account';
        private $form_settings_tab = 'form';
        private $plugin_settings_tabs = array();
        protected $name = 'Contact Form 7 Agile CRM Add-On';
        protected $version = '1.0';

		private static $instance;
		private $settings;

		public static function get_instance() {
			if ( empty( self::$instance ) ) {
				self::$instance = new self;
			}

			return self::$instance;
		}
	
        function __construct()
        {
			if(!class_exists('WPCF7')) {
				require_once plugin_dir_path( __FILE__ ) . '/../contact-form-7/wp-contact-form-7.php';
			}
			$this->settings = WPCF7::get_option( 'agilecrm' );

            //register actions or hooks
            add_action('init', array(&$this, 'start_session'));
            add_action('wp_footer', array(&$this, 'set_email'), 98765);

            add_action('admin_init', array(&$this, 'admin_init'));
            add_action('admin_menu', array(&$this, 'add_menu'));

            add_action('wpcf7_init', array(&$this, 'wpcf7_agilecrm_register_service'));
            add_action('wpcf7_before_send_mail', array(&$this, 'sync_entries_to_agile'), 10, 2);

            add_action('wp_ajax_agilecrm_cf7_load_fields', array(&$this, 'load_form_fields'));
            add_action('wp_ajax_agilecrm_cf7_map_fields', array(&$this, 'map_form_fields'));
            
        }

		private function menu_page_url( $args = '' ) {
			$args = wp_parse_args( $args, array() );

			$url = menu_page_url( 'wpcf7-integration', false );
			$url = add_query_arg( array( 'service' => 'agilecrm' ), $url );

			if ( ! empty( $args) ) {
				$url = add_query_arg( $args, $url );
			}

			return $url;
		}

		public function get_title() {
			return __( 'Agile CRM', 'contact-form-7' );
		}

		public function is_active() {
			$apikey = $this->get_apikey();
			$domain = $this->get_domain();
			$email = $this->get_email();
			return $apikey && $domain && $email;
		}

		public function get_categories() {
			return array( 'crm' );
		}

		public function icon() {
			echo sprintf('<img src="%1s" style="display:block;clear:both;width:150px;" />', plugin_dir_url(__FILE__) . 'js/agile500.png');
		}

		public function link() {
			echo sprintf( '<a href="%1$s">%2$s</a>',
				'https://www.agilecrm.com',
				'agilecrm.com' );
		}

		public function load( $action = '' ) {
			if ( 'update' == $action ) {
				if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
					check_admin_referer( 'update', 'wpcf7-agilecrm-setup' );

					$apikey = isset( $_POST['apikey'] ) ? trim( $_POST['apikey'] ) : '';
					$domain = isset( $_POST['domain'] ) ? trim( $_POST['domain'] ) : '';
					$email = isset( $_POST['email'] ) ? trim( $_POST['email'] ) : '';

					if ( $apikey && $domain && $email ) {
						WPCF7::update_option( 'agilecrm', array( 'domain' => $domain, 'apikey' => $apikey, 'email' => $email ) );
						$redirect_to = $this->menu_page_url( array(
							'message' => 'success' ) );
					} elseif ( '' === $apikey && '' === $domain && '' === $email) {
						WPCF7::update_option( 'agilecrm', null );
						$redirect_to = $this->menu_page_url( array(
							'message' => 'success' ) );
					} else {
						$redirect_to = $this->menu_page_url( array(
							'action' => 'setup',
							'message' => 'invalid' ) );
					}

					wp_safe_redirect( $redirect_to );
					exit();
				}
			}
		}

		public function admin_notice( $message = '' ) {
			if ( 'invalid' == $message ) {
				echo sprintf(
					'<div class="error notice notice-error is-dismissible"><p><strong>%1$s</strong>: %2$s</p></div>',
					esc_html( __( "ERROR", 'contact-form-7' ) ),
					esc_html( __( "Invalid key values.", 'contact-form-7' ) ) );
			}

			if ( 'success' == $message ) {
				echo sprintf( '<div class="updated notice notice-success is-dismissible"><p>%s</p></div>',
					esc_html( __( 'Settings saved.', 'contact-form-7' ) ) );
			}
		}

		public function display( $action = '' ) {
?>
<p><?php echo esc_html( __( "Agile CRM is an affordable, all-in-one CRM.", 'contact-form-7' ) ); ?></p>

<?php
			if ( 'setup' == $action ) {
				$this->display_setup();
				return;
			}

			if ( $this->is_active() ) {
				$apikey = $this->get_apikey();
				$domain = $this->get_domain();
				$email = $this->get_email();
?>
<table class="form-table">
<tbody>
<tr>
	<th scope="row"><?php echo esc_html( __( 'Domain', 'contact-form-7' ) ); ?></th>
	<td class="code"><?php echo esc_html( $domain  ); ?></td>
</tr>
<tr>
	<th scope="row"><?php echo esc_html( __( 'Admin Email', 'contact-form-7' ) ); ?></th>
	<td class="code"><?php echo esc_html( $email  ); ?></td>
</tr>
<tr>
	<th scope="row"><?php echo esc_html( __( 'API Key', 'contact-form-7' ) ); ?></th>
	<td class="code"><?php echo esc_html( $apikey ); ?></td>
</tr>
</tbody>
</table>

<p><a href="<?php echo esc_url( $this->menu_page_url( 'action=setup' ) ); ?>" class="button"><?php echo esc_html( __( "Reset Keys", 'contact-form-7' ) ); ?></a></p>

<?php
			} else {
?>
<div style="width: auto; height: auto; color: #8a6d3b; background-color: #fcf8e3; border: 1px solid #faebcc; border-radius: 5px; padding:0 15px;">
	<h4><?php echo esc_html( __( "Need an Agile CRM account?", 'contact-form-7' ) ); ?></h4>
	<p id="create_account_text"><?php echo esc_html( __( "It's fast and free for up to 10 users", 'contact-form-7' ) ); ?></p>
	<div style="margin-bottom: 20px;"><a href="https://www.agilecrm.com/pricing?utm_source=contactform7&utm_medium=website&utm_campaign=integration" target="_blank" class="button"><?php echo esc_html( __( "Create a new account", 'contact-form-7' ) ); ?></a></div>
</div>
<p><a href="<?php echo esc_url( $this->menu_page_url( 'action=setup' ) ); ?>" class="button"><?php echo esc_html( __( "Configure Domain", 'contact-form-7' ) ); ?></a></p>

<p><?php echo sprintf( esc_html( __( "For more details, see %s.", 'contact-form-7' ) ), wpcf7_link( __( 'https://github.com/agilecrm/rest-api#api-key', 'contact-form-7' ), __( 'the documentation', 'contact-form-7' ) ) ); ?></p>
<?php
			}
		}

		public function display_setup() {
?>
<form method="post" action="<?php echo esc_url( $this->menu_page_url( 'action=setup' ) ); ?>">
	<?php wp_nonce_field( 'update', 'wpcf7-agilecrm-setup' ); ?>
	<?php settings_fields($this->tag . '-settings-group'); ?>
	<?php do_settings_sections($this->tag); ?>
	<table class="form-table">
		<tr valign="top">
			<th scope="row"><label for="domain">Domain</label></th>
			<td>
				<span style="padding:3px; margin:0px; border: 1px solid #dfdfdf; border-right: 0px; background-color:#eee;">https://</span>
				<input type="text"  name="domain" id="domain" value="<?php echo get_option('domain'); ?>" style="width: 100px; margin: 0px; border-radius: 0px;" required="">
				<span style="margin:0px; padding: 3px; border: 1px solid #dfdfdf; background-color:#eee; border-left: 0px;">.agilecrm.com</span><br><small>If you are using abc.agilecrm.com, enter abc</small>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="email">Admin Email</label></th>
			<td>
				<input type="text" style="width:250px;" name="email" id="email" value="<?php echo get_option('email'); ?>" placeholder="admin user email" required=""><br>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="apikey">API Key</label></th>
			<td>
				<input type="text" style="width:250px;" name="apikey" id="apikey" value="<?php echo get_option('apikey'); ?>" placeholder="REST api key" required=""><br>
				<small>For instructions to find your API key, please click <a href="https://github.com/agilecrm/rest-api#api-key" target="_blank">here</a></small>
			</td>
		</tr>
	</table>
	<p class="submit"><input type="submit" class="button button-primary" value="<?php echo esc_attr( __( 'Save', 'contact-form-7' ) ); ?>" name="submit" /></p>
</form>
<?php
		}

		public function wpcf7_agilecrm_register_service() {
			error_log('wpcf7_agilecrm_register_service entered');
			$integration = WPCF7_Integration::get_instance();

			$categories = array(
				'crm' => __( 'CRM', 'contact-form-7' ) );

			foreach ( $categories as $name => $category ) {
				$integration->add_category( $name, $category );
			}

			$services = array(
				'agilecrm' => AgileCF7Addon::get_instance() );

			foreach ( $services as $name => $service ) {
				$integration->add_service( $name, $service );
			}
			error_log('wpcf7_agilecrm_register_service ended');
		}

		private function get_setting($setting) {
			$settings = (array) $this->settings;

			if ( isset( $settings[$setting] ) ) {
				return $settings[$setting];
			} else {
				return false;
			}
		}
	
		public function get_apikey() {
			return $this->get_setting('apikey');
		}

		public function get_domain() {
			return $this->get_setting('domain');
		}

		public function get_email() {
			return $this->get_setting('email');
		}

        /**
         * Start PHP session if not started earlier
         */
        public function start_session()
        {
            if (!session_id()) {
                session_start();
            }
        }

        /**
         * hook into WP's admin_init action hook
         */
        public function admin_init()
        {
            // Set up the settings for this plugin
            $this->init_settings();
            $this->plugin_settings_tabs[$this->account_settings_tab] = 'Account Details';
            $this->plugin_settings_tabs[$this->form_settings_tab] = 'Form Settings';
        }

        /**
         * Initialize some custom settings
         */
        public function init_settings()
        {
            // register the settings for this plugin
            register_setting($this->tag . '-settings-group', 'agilecrm_cf7_domain');
            register_setting($this->tag . '-settings-group', 'agilecrm_cf7_admin_email');
            register_setting($this->tag . '-settings-group', 'agilecrm_cf7_api_key');

            register_setting($this->tag . '-settings-group1', 'agilecrm_cf7_form_map');
            register_setting($this->tag . '-settings-group2', 'agilecrm_cf7_contact_fields');
            register_setting($this->tag . '-settings-group3', 'agilecrm_cf7_mapped_forms');

            add_settings_section($this->tag . '-section-one', '', '', $this->tag);
        }

        /**
         * add a menu
         */
        public function add_menu()
        {
            add_options_page('Settings-' . $this->name, 'Agile Contact Form 7', 'manage_options', $this->tag, array(&$this, 'plugin_settings_page'));
        }

        /**
         * Generate plugin setting tabs
         */
        public function plugin_settings_tabs()
        {
            $current_tab = (isset($_GET['tab']) && isset($this->plugin_settings_tabs[$_GET['tab']])) ? $_GET['tab'] : $this->account_settings_tab;

            echo '<h2 class="nav-tab-wrapper">';
            foreach ($this->plugin_settings_tabs as $tab_key => $tab_caption) {
                $active = $current_tab == $tab_key ? 'nav-tab-active' : '';
                echo '<a class="nav-tab ' . $active . '" href="?page=' . $this->tag . '&tab=' . $tab_key . '">' . $tab_caption . '</a>';
            }
            echo '</h2>';
        }

        /**
         * Menu Callback
         */
        public function plugin_settings_page()
        {
        	error_log('plugin_settings_page called');
            if (!current_user_can('manage_options')) {
                wp_die(__('You do not have sufficient permissions to access this page.'));
            }

            // Render the settings template based on the tab selected
            $current_tab = (isset($_GET['tab']) && isset($this->plugin_settings_tabs[$_GET['tab']])) ? $_GET['tab'] : $this->account_settings_tab;
            include(sprintf("%s/templates/" . $current_tab . "-tab.php", dirname(__FILE__)));
        }

        /**
         * Load form fields related to form id through Ajax
         */
        public function load_form_fields()
        {
            global $wpdb;
            $formId = $_POST['formid'];

            $agileFields = array(
                'first_name' => array('name' => 'First name', 'is_required' => true, 'type' => 'SYSTEM', 'is_address' => false),
                'last_name' => array('name' => 'Last name', 'is_required' => false, 'type' => 'SYSTEM', 'is_address' => false),
                'company' => array('name' => 'Company', 'is_required' => false, 'type' => 'SYSTEM', 'is_address' => false),
                'title' => array('name' => 'Job description', 'is_required' => false, 'type' => 'SYSTEM', 'is_address' => false),
                'tags' => array('name' => 'Tag', 'is_required' => false, 'type' => 'SYSTEM', 'is_address' => false),
                'email' => array('name' => 'Email', 'is_required' => true, 'type' => 'SYSTEM', 'is_address' => false),
                'phone' => array('name' => 'Phone', 'is_required' => false, 'type' => 'SYSTEM', 'is_address' => false),
                'website' => array('name' => 'Website', 'is_required' => false, 'type' => 'SYSTEM', 'is_address' => false),
                'address_address' => array('name' => 'Address', 'is_required' => false, 'type' => 'SYSTEM', 'is_address' => true),
                'address_city' => array('name' => 'City', 'is_required' => false, 'type' => 'SYSTEM', 'is_address' => true),
                'address_state' => array('name' => 'State', 'is_required' => false, 'type' => 'SYSTEM', 'is_address' => true),
                'address_zip' => array('name' => 'Zip', 'is_required' => false, 'type' => 'SYSTEM', 'is_address' => true),
                'address_country' => array('name' => 'Country', 'is_required' => false, 'type' => 'SYSTEM', 'is_address' => true),
                'notes' => array('name' => 'Note', 'is_required' => false, 'type' => 'SYSTEM', 'is_address' => false)
            );

            $customFields = $this->agile_http("custom-fields/scope?scope=CONTACT", null, "GET");

            if ($customFields) {
                $customFields = json_decode($customFields, true);
                foreach ($customFields as $customField) {
                    $agileFields[AgileCF7Addon::clean($customField['field_label'])] = array(
                        'name' => $customField['field_label'],
                        'is_required' => (boolean) $customField['is_required'],
                        'type' => 'CUSTOM,' . $customField['field_type'],
                        'is_address' => false
                    );
                }
            }

            update_option("agilecrm_cf7_contact_fields", $agileFields);


            $mapFieldsMarkup = '';
            foreach ($agileFields as $fieldKey => $fieldVal) {
                $mapFieldsMarkup .= '<tr valign="top"><th scope="row">' . $fieldVal['name'];
                $required = '';
                if ($fieldVal['is_required']) {
                    $mapFieldsMarkup .= '<span style="color:#FF0000"> *</span>';
                    $required = 'class="required" required';
                }
                $mapFieldsMarkup .= '</th>';
                $mapFieldsMarkup .= '<td><input id="agilecrm_form_field_' . $fieldKey . '" name="agilecrm_cf7_form_map[' . $fieldKey . ']"' . $required . ' /></td></tr>';
            }

            $agilecrm_cf7_form_map = get_option('agilecrm_cf7_form_map');

            $responseJson = array(
                'markup' => '',
                'selectedFields' => ($agilecrm_cf7_form_map && isset($agilecrm_cf7_form_map['form_' . $formId])) ? $agilecrm_cf7_form_map['form_' . $formId] : array()
            );

            $responseJson['markup'] .= '<h3 class="title">Map Gravity form fields to Agile CRM contact properties</h3>';

            $responseJson['markup'] .= '<table class="form-table" style="width:33%"><tbody>';
            $responseJson['markup'] .= '<tr valign="top"><th scope="row">Agile property</th><td><strong>Form field</strong></td></tr>';
            $responseJson['markup'] .= $mapFieldsMarkup;
            $responseJson['markup'] .= '</tbody></table>';

            $responseJson['markup'] .= '<h3>Add a tag to all contacts created from this form</h3>';
            $responseJson['markup'] .= '<table class="form-table"><tbody><tr valign="top">'
                . '<th scope="row" style="width: 136px;">Tag</th>'
                . '<td><input type="text" name="agilecrm_cf7_form_map[hard_tag]" id="agilecrm_form_field_hard_tag"><br>'
                . '<small>Tag name should start with an alphabet and can not contain special characters other than space and underscore.</small></td>'
                . '</tr></tbody></table>';

            echo json_encode($responseJson);
            die();
        }

        /**
         * Save form mapped fields to database via Ajax
         */
        public function map_form_fields()
        {
            global $wpdb;
            $agilecrm_cf7_form_map = get_option('agilecrm_cf7_form_map');
            $agilecrm_form_sync_id = $_POST['agilecrm_cf7_sync_form'];

            //save checked forms ids  
            $agilecrm_cf7_mapped_forms = get_option('agilecrm_cf7_mapped_forms');
            if (isset($_POST['agilecrm_cf7_mapped_forms'])) {
                $syncedForms = $_POST['agilecrm_cf7_mapped_forms'];
                if (in_array($agilecrm_form_sync_id, $agilecrm_cf7_mapped_forms)) {
                    $syncedForms = array();
                }
                if ($agilecrm_cf7_mapped_forms != false) {
                    $syncedForms = array_merge($agilecrm_cf7_mapped_forms, $syncedForms);
                }
            } else {
                $syncedForms = $agilecrm_cf7_mapped_forms;
                if (($key = array_search($agilecrm_form_sync_id, $syncedForms)) !== false) {
                    unset($syncedForms[$key]);
                }
            }
            $update = update_option('agilecrm_cf7_mapped_forms', $syncedForms);

            if (isset($_POST['agilecrm_cf7_form_map'])) {
                $formFields['form_' . $agilecrm_form_sync_id] = $_POST['agilecrm_cf7_form_map'];
                if ($agilecrm_cf7_form_map != false) {
                    $formFields = array_merge($agilecrm_cf7_form_map, $formFields);
                }

                if (isset($formFields['form_' . $agilecrm_form_sync_id]['hard_tag']) && $formFields['form_' . $agilecrm_form_sync_id]['hard_tag'] != '') {
                    $formFields['form_' . $agilecrm_form_sync_id]['hard_tag'] = trim($formFields['form_' . $agilecrm_form_sync_id]['hard_tag']);
                }

                $update = update_option('agilecrm_cf7_form_map', $formFields);
            }

            echo ($update) ? '1' : '0';

            die();
        }

        /**
         * Syncs form entries to Agile CRM whenever a mapped form is submited.
         */
        public function sync_entries_to_agile($entry, $form)
        {
            $agilecrm_cf7_form_map = get_option('agilecrm_cf7_form_map');
            $agilecrm_cf7_mapped_forms = get_option('agilecrm_cf7_mapped_forms');

            $formId = $entry['form_id'];
            if ($formId) {
                if ($agilecrm_cf7_mapped_forms && in_array($formId, $agilecrm_cf7_mapped_forms)) {
                    if ($agilecrm_cf7_form_map && isset($agilecrm_cf7_form_map['form_' . $formId])) {

                        $agileFields = get_option('agilecrm_cf7_contact_fields');
                        $mappedFields = $agilecrm_cf7_form_map['form_' . $formId];
                        $contactProperties = array();
                        $addressProp = array();

                        foreach ($agileFields as $fieldKey => $fieldVal) {
                            if ($mappedFields[$fieldKey] != '') {

                                $fieldTypeArray = explode(",", $fieldVal['type']);
                                if (in_array('CUSTOM', $fieldTypeArray)) {
                                    $valueEntered = trim($entry[$mappedFields[$fieldKey]]);
                                    if (isset($fieldTypeArray[1]) && $fieldTypeArray[1] == "DATE") {
                                        /*
                                          These formats are supported m/d/y, d-m-y, d.m.y, y/m/d, y-m-d
                                         */
                                        if ($valueEntered != "") {
                                            $valueEntered = strtotime($valueEntered . " 12:00:00");
                                        }
                                    }
                                    if($valueEntered != "") {
                                        $contactProperties[] = array(
                                            "name" => $fieldVal['name'],
                                            "value" => $valueEntered,
                                            "type" => $fieldTypeArray[0]
                                        );   
                                    }
                                } elseif (in_array('SYSTEM', $fieldTypeArray)) {
                                    if ($fieldVal['is_address']) {
                                        $addressField = explode("_", $fieldKey);
                                        $addressProp[$addressField[1]] = $entry[$mappedFields[$fieldKey]];
                                    } else {
                                        if ($fieldKey != 'tags' && $fieldKey != 'notes') {
                                            if($entry[$mappedFields[$fieldKey]] != "") {
                                                $contactProperties[] = array(
                                                    "name" => $fieldKey,
                                                    "value" => $entry[$mappedFields[$fieldKey]],
                                                    "type" => $fieldTypeArray[0]
                                                );   
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        if ($addressProp) {
                            $contactProperties[] = array(
                                "name" => "address",
                                "value" => json_encode($addressProp),
                                "type" => "SYSTEM"
                            );
                        }

                        $finalData = array("properties" => $contactProperties);
                        
                        //tags
                        $finalData['tags'] = array();

                        if ($mappedFields["tags"] != '') {
                            if (AgileCF7Addon::startsWithNumber($mappedFields["tags"])) {
                                $entry[$mappedFields['tags']] = preg_replace('/[0-9]+/', '', mb_ereg_replace('[^ \w]+', '', $entry[$mappedFields['tags']]));
                            }
                            $finalData['tags'][] = preg_replace('!\s+!', ' ', mb_ereg_replace('[^ \w]+', '', trim($entry[$mappedFields['tags']])));
                        }
                        if ($mappedFields["hard_tag"] != '') {
                            $hardTags = explode(",", trim($mappedFields['hard_tag']));
                            foreach ($hardTags as $hTag) {
                                if (AgileCF7Addon::startsWithNumber($hTag)) {
                                    $finalData['tags'][] = preg_replace('/[0-9]+/', '', mb_ereg_replace('[^ \w]+', '', trim($hTag)));
                                } else {
                                    $finalData['tags'][] = preg_replace('!\s+!', ' ', mb_ereg_replace('[^ \w]+', '', trim($hTag)));
                                }
                            }
                        }
                        $finalData['tags'] = array_filter($finalData['tags']);

                        if (isset($entry[$mappedFields['email']]) && $entry[$mappedFields['email']] != '') {
                            $resultedContact = array();
                            $isExistsResult = $this->agile_http("contacts/search/email/" . $entry[$mappedFields['email']], null, "GET");
                            if ($isExistsResult && $isExistsResult != '') {
                                //replaces long to string, then decode
                                $resultedContact = json_decode(preg_replace('/("\w+"):(\d+(\.\d+)?)/', '\\1:"\\2"', $isExistsResult), true);
                                if (isset($resultedContact['id'])) {
                                    
                                    $this->agile_http("contacts/edit/tags", json_encode(array(
                                        "id" => sprintf('%.0f', $resultedContact['id']),
                                        "tags" => $finalData['tags']
                                    )), "PUT");

                                    $finalData['id'] = sprintf('%.0f', $resultedContact['id']);
                                    $this->agile_http("contacts/edit-properties", json_encode($finalData), "PUT");
                                }
                            } else {
                                $createdResult = $this->agile_http("contacts", json_encode($finalData), "POST");
                                if ($createdResult) {
                                    $resultedContact = json_decode($createdResult, true);
                                    if (isset($resultedContact['id'])) {
                                        $finalData['id'] = sprintf('%.0f', $resultedContact['id']);
                                    }
                                }
                            }
                            //for web tracking
                            $_SESSION['agileCRMTrackEmail'] = $entry[$mappedFields['email']];


                            if (isset($finalData['id'])) {
                                //creating notes if it is mapped
                                if ($mappedFields["notes"] != '' && $entry[$mappedFields['notes']] != '') {
                                    $noteJson = json_encode(array(
                                        "subject" => "Note from Gravity forms",
                                        "description" => $entry[$mappedFields['notes']],
                                        "contact_ids" => array($finalData['id'])
                                    ));
                                    $this->agile_http("notes", $noteJson, "POST");
                                }
                            }
                        }
                    }
                }
            }
        }

        /**
         * Set user entered email to track web activities
         */
        public function set_email()
        {
            if (isset($_SESSION['agileCRMTrackEmail'])) {
                echo '<script> ';
                echo 'if(typeof _agile != "undefined") { ';
                echo '_agile.set_email("' . $_SESSION['agileCRMTrackEmail'] . '");';
                echo ' }';
                echo ' </script>';
                unset($_SESSION['agileCRMTrackEmail']);
            }
        }

        /**
         * AgileCRM Request Wrapper function
         */
        public function agile_http($endPoint, $data, $requestMethod)
        {
            $agile_domain = get_option('agilecrm_cf7_domain');
            $agile_email = get_option('agilecrm_cf7_admin_email');
            $agile_api_key = get_option('agilecrm_cf7_api_key');

            if ($agile_domain && $agile_email && $agile_api_key) {
                $agile_url = "https://" . $agile_domain . ".agilecrm.com/dev/api/";

                $ch = curl_init();
                //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                //curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
                //curl_setopt($ch, CURLOPT_UNRESTRICTED_AUTH, true);

                switch ($requestMethod) {
                    case "POST":
                        curl_setopt($ch, CURLOPT_URL, $agile_url . $endPoint);
                        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                        break;
                    case "GET":
                        curl_setopt($ch, CURLOPT_URL, $agile_url . $endPoint);
                        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
                        break;
                    case "PUT":
                        curl_setopt($ch, CURLOPT_URL, $agile_url . $endPoint);
                        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                        break;
                    case "DELETE":
                        curl_setopt($ch, CURLOPT_URL, $agile_url . $endPoint);
                        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
                        break;
                    default:
                        break;
                }

                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type : application/json; charset : UTF-8;', 'Accept: application/json'));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_USERPWD, $agile_email . ':' . $agile_api_key);
                curl_setopt($ch, CURLOPT_TIMEOUT, 120);

                $output = curl_exec($ch);
                $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($statusCode == 200) {
                    return $output;
                } elseif ($statusCode == 401) {
                    return false;
                }
            }

            return false;
        }

        /**
         * Sanitize custom field names, return value is used as a key.
         */
        public static function clean($string)
        {
            $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
            $string = preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
            return preg_replace('/-+/', '-', $string); // Replaces multiple hyphens with single one.
        }

        /**
         * Checks whether a string starts with a number or not
         * 
         * @param string
         * @return boolean TRUE if string starts with a number, FALSE otherwise.
         */
        public static function startsWithNumber($str)
        {
            if (strlen($str) > 0) {
                if ($str[0] != null) {
                    return is_numeric($str[0]);
                } else {
                    return is_numeric($str);
                }
            }
            return false;
        }
    }

    //class end

    new AgileCF7Addon();
}
/*
define( 'CF7_AGILE__PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CF7_AGILE__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

require_once( CF7_AGILE__PLUGIN_DIR . 'cf7_agile_settings.php' );
require_once( CF7_AGILE__PLUGIN_DIR . 'class.api.php' );

if ( is_admin() ) {
  require_once( CF7_AGILE__PLUGIN_DIR . 'cf7_agile_admin.php' );
  add_action( 'init', array( 'cf7_agile_admin', 'init' ) );
}

add_filter( 'wpcf7_contact_form_properties', 'contact_form_properties');
add_action('wpcf7_before_send_mail', 'cf7_agile_before_send_mail');

function cf7_agile_before_send_mail($contact_form) {
  $properties = $contact_form->get_properties();
  if (empty($properties['agilecrm']['enable'])) {
    return;
  }

  $api = new civicrm_api3 (array (
    'server' => cf7_agile_settings::getHost(),
    'api_key'=> cf7_agile_settings::getApiKey(),
    'key'=> cf7_agile_settings::getSiteKey(),
    'path' => cf7_agile_settings::getPath(),
  ));
  $action = $properties['agilecrm']['action'];
  $entity = $properties['agilecrm']['entity'];

  $submission = WPCF7_Submission::get_instance();
  $submittedData = $submission->get_posted_data();
  $data = array();
  foreach($submittedData as $key => $val) {
    if (is_array($val)) {
      $val = implode(", ", $val);
    }
    $data[$key] = $val;
  }

  $parameters = explode("&", $properties['agilecrm']['parameters']);
  foreach($parameters as $param) {
    list($key, $val) = explode("=", $param);
    if (!empty($key)) {
      $data[$key] = $val;
    }
  }
  $result = $api->call($entity, $action, $data);
}

function contact_form_properties($properties) {

  if (!isset($properties['agilecrm'])) {
    $properties['agilecrm'] = array(
      'enable' => false,
      'entity' => 'Contact',
      'action' => 'create',
      'parameters' => 'contact_type=Individual&source=Wordpress'
    );
  }
  return $properties;
}*/