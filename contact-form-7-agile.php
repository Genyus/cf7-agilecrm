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
GitHub Plugin URI: Genyus/cf7-agilecrm
GitHub Branch: master
*/
define('CF7_AGILE__PLUGIN_URL', plugin_dir_url(__FILE__));
define('CF7_AGILE__PLUGIN_DIR', plugin_dir_path(__FILE__));
defined('ABSPATH') or die('Plugin file cannot be accessed directly.');
include_once ABSPATH.'wp-admin/includes/plugin.php';
//get the base class
if (!class_exists('WPCF7_Service')) {
    require_once plugin_dir_path(__FILE__).'/../contact-form-7/includes/integration.php';
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

        public static function get_instance()
        {
            if (empty(self::$instance)) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        public static function view($name, array $args = array())
        {
            $args = apply_filters('cf7_agile_view_arguments', $args, $name);

            foreach ($args as $key => $val) {
                $$key = $val;
            }

            load_plugin_textdomain('contact-form-7-agilecrm-integration');

            $file = CF7_AGILE__PLUGIN_DIR.'views/'.$name.'.php';

            include $file;
        }

        /**
         * Add a Agile setting panel to the contact form admin section.
         *
         * @param array $panels
         *
         * @return array
         */
        public function panels($panels)
        {
            if (wpcf7_admin_has_edit_cap()) {
                $panels['contact-form-7-agilecrm-integration'] = array(
                    'title' => __('Agile CRM', 'contact-form-7-agilecrm-integration'),
                    'callback' => array($this, 'agilecrm_panel')
                );
            }

            return $panels;
        }

        public function agilecrm_panel($post)
        {
            $agilecrm = $post->prop('agilecrm');
            self::view('agilecrm_panel', array('post' => $post, 'agilecrm' => $agilecrm));
        }

        public function __construct()
        {
            if (!class_exists('WPCF7')) {
                require_once plugin_dir_path(__FILE__).'/../contact-form-7/wp-contact-form-7.php';
            }
            $this->settings = WPCF7::get_option('agilecrm');
            self::$instance = $this;

            //register actions or hooks
            add_action('init', array(&$this, 'start_session'));
            add_action('wp_footer', array(&$this, 'set_email'), 98765);

            add_action('wpcf7_init', array(&$this, 'wpcf7_agilecrm_register_service'));
            add_action('wpcf7_before_send_mail', array(&$this, 'sync_entries_to_agile'));
            add_action('wpcf7_save_contact_form', array(&$this, 'save_contact_form'));

            add_filter('wpcf7_editor_panels', array(&$this, 'panels'));
            add_filter('wpcf7_contact_form_properties', array(&$this, 'contact_form_properties'));
        }

        private function log_trace($message = '') {
            $trace = debug_backtrace();
            if ($message) {
                error_log($message);
            }
            $caller = array_shift($trace);
            $function_name = $caller['function'];
            error_log(sprintf('%s: Called from %s:%s', $function_name, $caller['file'], $caller['line']));
            foreach ($trace as $entry_id => $entry) {
                $entry['file'] = $entry['file'] ? : '-';
                $entry['line'] = $entry['line'] ? : '-';
                if (empty($entry['class'])) {
                    error_log(sprintf('%s %3s. %s() %s:%s', $function_name, $entry_id + 1, $entry['function'], $entry['file'], $entry['line']));
                } else {
                    error_log(sprintf('%s %3s. %s->%s() %s:%s', $function_name, $entry_id + 1, $entry['class'], $entry['function'], $entry['file'], $entry['line']));
                }
            }
        }
        private function menu_page_url($args = '')
        {
            $args = wp_parse_args($args, array());

            $url = menu_page_url('wpcf7-integration', false);
            $url = add_query_arg(array('service' => 'agilecrm'), $url);

            if (!empty($args)) {
                $url = add_query_arg($args, $url);
            }

            return $url;
        }

        public function get_title()
        {
            return __('Agile CRM', 'contact-form-7');
        }

        public function is_active()
        {
            $apikey = $this->get_apikey();
            $domain = $this->get_domain();
            $email = $this->get_email();

            return $apikey && $domain && $email;
        }

        public function get_categories()
        {
            return array('crm');
        }

        public function icon()
        {
            echo sprintf('<img src="%1s" style="display:block;clear:both;width:150px;" />', plugin_dir_url(__FILE__).'js/agile500.png');
        }

        public function link()
        {
            echo sprintf('<a href="%1$s">%2$s</a>',
                'https://www.agilecrm.com',
                'agilecrm.com');
        }

        public function load($action = '')
        {
            if ('update' == $action) {
                if ('POST' === $_SERVER['REQUEST_METHOD']) {
                    check_admin_referer('update', 'wpcf7-agilecrm-setup');

                    $apikey = isset($_POST['apikey']) ? trim($_POST['apikey']) : '';
                    $domain = isset($_POST['domain']) ? trim($_POST['domain']) : '';
                    $email = isset($_POST['email']) ? trim($_POST['email']) : '';

                    if ($apikey && $domain && $email) {
                        WPCF7::update_option('agilecrm', array('domain' => $domain, 'apikey' => $apikey, 'email' => $email));
                        $redirect_to = $this->menu_page_url(array(
                            'message' => 'success', ));
                    } elseif ('' === $apikey && '' === $domain && '' === $email) {
                        WPCF7::update_option('agilecrm', null);
                        $redirect_to = $this->menu_page_url(array(
                            'message' => 'success', ));
                    } else {
                        $redirect_to = $this->menu_page_url(array(
                            'action' => 'setup',
                            'message' => 'invalid', ));
                    }

                    wp_safe_redirect($redirect_to);
                    exit();
                }
            }
        }

        public function admin_notice($message = '')
        {
            if ('invalid' == $message) {
                echo sprintf(
                    '<div class="error notice notice-error is-dismissible"><p><strong>%1$s</strong>: %2$s</p></div>',
                    esc_html(__('ERROR', 'contact-form-7')),
                    esc_html(__('Invalid key values.', 'contact-form-7')));
            }

            if ('success' == $message) {
                echo sprintf('<div class="updated notice notice-success is-dismissible"><p>%s</p></div>',
                    esc_html(__('Settings saved.', 'contact-form-7')));
            }
        }

        public function display($action = '')
        {
            ?>
<p><?php echo esc_html(__('Agile CRM is an affordable, all-in-one CRM.', 'contact-form-7')); ?></p>

<?php
            if ('setup' == $action) {
                $this->display_setup();

                return;
            }

            if ($this->is_active()) {
                $apikey = $this->get_apikey();
                $domain = $this->get_domain();
                $email = $this->get_email(); ?>
<table class="form-table">
<tbody>
<tr>
	<th scope="row"><?php echo esc_html(__('Domain', 'contact-form-7')); ?></th>
	<td class="code"><?php echo esc_html($domain); ?></td>
</tr>
<tr>
	<th scope="row"><?php echo esc_html(__('Admin Email', 'contact-form-7')); ?></th>
	<td class="code"><?php echo esc_html($email); ?></td>
</tr>
<tr>
	<th scope="row"><?php echo esc_html(__('API Key', 'contact-form-7')); ?></th>
	<td class="code"><?php echo esc_html($apikey); ?></td>
</tr>
</tbody>
</table>

<p><a href="<?php echo esc_url($this->menu_page_url('action=setup')); ?>" class="button"><?php echo esc_html(__('Reset Keys', 'contact-form-7')); ?></a></p>

<?php

            } else {
                ?>
<div style="width: auto; height: auto; color: #8a6d3b; background-color: #fcf8e3; border: 1px solid #faebcc; border-radius: 5px; padding:0 15px;">
	<h4><?php echo esc_html(__('Need an Agile CRM account?', 'contact-form-7')); ?></h4>
	<p id="create_account_text"><?php echo esc_html(__("It's fast and free for up to 10 users", 'contact-form-7')); ?></p>
	<div style="margin-bottom: 20px;"><a href="https://www.agilecrm.com/pricing?utm_source=contactform7&utm_medium=website&utm_campaign=integration" target="_blank" class="button"><?php echo esc_html(__('Create a new account', 'contact-form-7')); ?></a></div>
</div>
<p><a href="<?php echo esc_url($this->menu_page_url('action=setup')); ?>" class="button"><?php echo esc_html(__('Configure Domain', 'contact-form-7')); ?></a></p>

<p><?php echo sprintf(esc_html(__('For more details, see %s.', 'contact-form-7')), wpcf7_link(__('https://github.com/agilecrm/rest-api#api-key', 'contact-form-7'), __('the documentation', 'contact-form-7'))); ?></p>
<?php

            }
        }

        public function display_setup()
        {
            ?>
<form method="post" action="<?php echo esc_url($this->menu_page_url('action=setup')); ?>">
	<?php wp_nonce_field('update', 'wpcf7-agilecrm-setup'); ?>
	<?php settings_fields($this->tag.'-settings-group'); ?>
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
	<p class="submit"><input type="submit" class="button button-primary" value="<?php echo esc_attr(__('Save', 'contact-form-7')); ?>" name="submit" /></p>
</form>
<?php

        }

        public function wpcf7_agilecrm_register_service()
        {
            $integration = WPCF7_Integration::get_instance();

            $categories = array(
                'crm' => __('CRM', 'contact-form-7'), );

            foreach ($categories as $name => $category) {
                $integration->add_category($name, $category);
            }

            $services = array(
                'agilecrm' => self::get_instance(), );

            foreach ($services as $name => $service) {
                $integration->add_service($name, $service);
            }
        }

        private function get_setting($setting)
        {
            $settings = (array) $this->settings;

            if (isset($settings[$setting])) {
                return $settings[$setting];
            } else {
                return false;
            }
        }

        public function get_apikey()
        {
            return $this->get_setting('apikey');
        }

        public function get_domain()
        {
            return $this->get_setting('domain');
        }

        public function get_email()
        {
            return $this->get_setting('email');
        }

        /**
         * Start PHP session if not started earlier.
         */
        public function start_session()
        {
            if (!session_id()) {
                session_start();
            }
        }

        public function contact_form_properties($properties)
        {
            if (!isset($properties['agilecrm'])) {
                $properties['agilecrm'] = array(
                    'enable' => false,
                    'parameters' => 'subject=Submitted via Contact Form 7'
                );
            }

            return $properties;
        }

        public function save_contact_form($contact_form)
        {
            $properties = $contact_form->get_properties();
            $agilecrm = $properties['agilecrm'];

            $agilecrm['enable'] = true;

            if (isset($_POST['agilecrm-parameters'])) {
                $agilecrm['parameters'] = trim($_POST['agilecrm-parameters']);
            }

            if (isset($_POST['agilecrm_cf7_form_map_hard_tag'])) {
                $agilecrm['hard_tag'] = trim($_POST['agilecrm_cf7_form_map_hard_tag']);
            }

            $properties['agilecrm'] = $agilecrm;
            $contact_form->set_properties($properties);
        }

        /**
         * Load Agile CRM contact properties.
         */
        public function load_form_fields($agilecrm = null)
        {
            global $wpdb;

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
                'notes' => array('name' => 'Notes', 'is_required' => false, 'type' => 'SYSTEM', 'is_address' => false),
            );

            $customFields = $this->agile_http('custom-fields/scope?scope=CONTACT', null, 'GET');

            if ($customFields) {
                $customFields = json_decode($customFields, true);
                foreach ($customFields as $customField) {
                    $agileFields[self::clean($customField['field_label'])] = array(
                        'name' => $customField['field_label'],
                        'is_required' => (bool) $customField['is_required'],
                        'type' => 'CUSTOM,'.$customField['field_type'],
                        'is_address' => false,
                    );
                }
            }

            update_option('agilecrm_cf7_contact_fields', $agileFields);

            $mapFieldsMarkup = '';
            foreach ($agileFields as $fieldKey => $fieldVal) {
                $mapFieldsMarkup .= '<tr valign="top"><th scope="row">'.$fieldVal['name'];
                $required = '';
                if ($fieldVal['is_required']) {
                    $mapFieldsMarkup .= '<span style="color:#FF0000"> *</span>';
                    $required = 'class="required" required';
                }
                $mapFieldsMarkup .= '</th>';
                $mapFieldsMarkup .= '<td>'.$fieldKey.'</td></tr>';
            }

            $agilecrm_cf7_form_map = get_option('agilecrm_cf7_form_map');

            $responseJson = array(
                'markup' => '',
                'selectedFields' => ($agilecrm_cf7_form_map && isset($agilecrm_cf7_form_map['form_'.$formId])) ? $agilecrm_cf7_form_map['form_'.$formId] : array(),
            );

            $responseJson['markup'] .= '<h3>Add a tag to all contacts created from this form</h3>';
            $responseJson['markup'] .= '<table class="form-table"><tbody><tr valign="top">'
                .'<th scope="row" style="width: 136px;">Tag</th>'
                .'<td><input type="text" name="agilecrm_cf7_form_map_hard_tag" id="agilecrm_form_field_hard_tag" value="' . esc_attr($agilecrm['hard_tag']) . '"><br>'
                .'<small>Tag name should start with an alphabet and can not contain special characters other than space and underscore.</small></td>'
                .'</tr></tbody></table>';

            $responseJson['markup'] .= '<h3 class="title">Available Agile CRM contact properties</h3>';
            $responseJson['markup'] .= '<table class="form-table" style="width:33%"><tbody>';
            $responseJson['markup'] .= '<tr valign="top"><th scope="row">Agile property</th><td><strong>Form field</strong></td></tr>';
            $responseJson['markup'] .= $mapFieldsMarkup;
            $responseJson['markup'] .= '</tbody></table>';

            echo $responseJson['markup'];
        }

        /**
         * Syncs form entries to Agile CRM whenever a mapped form is submited.
         */
        public function sync_entries_to_agile($wpcf7)
        {
            $properties = $wpcf7->get_properties();

            if (empty($properties['agilecrm']['enable'])) {
                return;
            }

            $submission = WPCF7_Submission::get_instance();
            $submittedData = $submission->get_posted_data();
            $data = array();

            foreach ($submittedData as $key => $val) {
                if (is_array($val)) {
                    $val = implode(', ', $val);
                }
                $data[$key] = $val;
            }

            $parameters = explode('&', $properties['agilecrm']['parameters']);

            foreach ($parameters as $param) {
                list($key, $val) = explode('=', $param);
                if (!empty($key)) {
                    $data[$key] = $val;
                }
            }

            $agileFields = get_option('agilecrm_cf7_contact_fields');
            $contactProperties = array();
            $addressProp = array();

            foreach ($agileFields as $fieldKey => $fieldVal) {
                if (isset($data[$fieldKey]) && !empty($data[$fieldKey])) {
                    $fieldTypeArray = explode(',', $fieldVal['type']);
                    if (in_array('CUSTOM', $fieldTypeArray)) {
                        $valueEntered = trim($data[$fieldKey]);
                        if (isset($fieldTypeArray[1]) && $fieldTypeArray[1] == 'DATE') {
                            /*
                              These formats are supported m/d/y, d-m-y, d.m.y, y/m/d, y-m-d
                             */
                            if ($valueEntered != '') {
                                $valueEntered = strtotime($valueEntered.' 12:00:00');
                            }
                        }
                        if ($valueEntered != '') {
                            $contactProperties[] = array(
                                'name' => $fieldVal['name'],
                                'value' => $valueEntered,
                                'type' => $fieldTypeArray[0],
                            );
                        }
                    } elseif (in_array('SYSTEM', $fieldTypeArray)) {
                        if ($fieldVal['is_address']) {
                            $addressField = explode('_', $fieldKey);
                            $addressProp[$addressField[1]] = $data[$fieldKey];
                        } else {
                            if ($fieldKey != 'tags' && $fieldKey != 'notes') {
                                if ($data[$fieldKey] != '') {
                                    $contactProperties[] = array(
                                        'name' => $fieldKey,
                                        'value' => $data[$fieldKey],
                                        'type' => $fieldTypeArray[0],
                                    );
                                }
                            }
                        }
                    }
                }
            }

            if ($addressProp) {
                $contactProperties[] = array(
                    'name' => 'address',
                    'value' => json_encode($addressProp),
                    'type' => 'SYSTEM',
                );
            }

            $finalData = array('properties' => $contactProperties);

            //tags
            $finalData['tags'] = array();

            if (isset($data['tags']) && !empty($data['tags'])) {
                $tags = explode(',', trim($data['tags']));

                foreach ($tags as $tag) {
                    if (self::startsWithNumber($tag)) {
                        $tag = preg_replace('/[0-9]+/', '', mb_ereg_replace('[^ \w]+', '', $tag));
                    }
                    $finalData['tags'][] = preg_replace('!\s+!', ' ', mb_ereg_replace('[^ \w]+', '', trim($tag)));
                }
            }
            if (isset($data['hard_tag']) && !empty($data['hard_tag'])) {
                $hardTags = explode(',', trim($data['hard_tag']));
                foreach ($hardTags as $hTag) {
                    if (self::startsWithNumber($hTag)) {
                        $finalData['tags'][] = preg_replace('/[0-9]+/', '', mb_ereg_replace('[^ \w]+', '', trim($hTag)));
                    } else {
                        $finalData['tags'][] = preg_replace('!\s+!', ' ', mb_ereg_replace('[^ \w]+', '', trim($hTag)));
                    }
                }
            }
            $finalData['tags'] = array_filter($finalData['tags']);

            if (isset($data['email']) && $data['email'] != '') {
                $resultedContact = array();
                $isExistsResult = $this->agile_http('contacts/search/email/'.$data['email'], null, 'GET');
                if ($isExistsResult && $isExistsResult != '') {
                    //replaces long to string, then decode
                    $resultedContact = json_decode(preg_replace('/("\w+"):(\d+(\.\d+)?)/', '\\1:"\\2"', $isExistsResult), true);
                    if (isset($resultedContact['id'])) {
                        $this->agile_http('contacts/edit/tags', json_encode(array(
                            'id' => sprintf('%.0f', $resultedContact['id']),
                            'tags' => $finalData['tags'],
                        )), 'PUT');

                        $finalData['id'] = sprintf('%.0f', $resultedContact['id']);
                        $this->agile_http('contacts/edit-properties', json_encode($finalData), 'PUT');
                    }
                } else {
                    $createdResult = $this->agile_http('contacts', json_encode($finalData), 'POST');

                    if ($createdResult) {
                        $resultedContact = json_decode($createdResult, true);

                        if (isset($resultedContact['id'])) {
                            $finalData['id'] = sprintf('%.0f', $resultedContact['id']);
                        }
                    }
                }
                //for web tracking
                $_SESSION['agileCRMTrackEmail'] = $data['email'];

                if (isset($finalData['id'])) {
                    //creating notes if it is mapped
                    if ($data['notes'] != '') {
                        $noteJson = json_encode(array(
                            'subject' => ($data['subject'] != '') ? $data['subject'] : 'Submitted via Contact Form 7',
                            'description' => $data['notes'],
                            'contact_ids' => array($finalData['id']),
                        ));
                        $this->agile_http('notes', $noteJson, 'POST');
                    }
                }

                //$wpcf7->skip_mail = true;
            }
        }

        /**
         * Set user entered email to track web activities.
         */
        public function set_email()
        {
            if (isset($_SESSION['agileCRMTrackEmail'])) {
                echo '<script> ';
                echo 'if(typeof _agile != "undefined") { ';
                echo '_agile.set_email("'.$_SESSION['agileCRMTrackEmail'].'");';
                echo ' }';
                echo ' </script>';
                unset($_SESSION['agileCRMTrackEmail']);
            }
        }

        /**
         * AgileCRM Request Wrapper function.
         */
        public function agile_http($endPoint, $data, $requestMethod)
        {
            $agile_domain = $this->get_domain();
            $agile_email = $this->get_email();
            $agile_api_key = $this->get_apikey();

            if ($agile_domain && $agile_email && $agile_api_key) {
                $agile_url = 'https://'.$agile_domain.'.agilecrm.com/dev/api/';

                $ch = curl_init();
                //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                //curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
                //curl_setopt($ch, CURLOPT_UNRESTRICTED_AUTH, true);

                switch ($requestMethod) {
                    case 'POST':
                        curl_setopt($ch, CURLOPT_URL, $agile_url.$endPoint);
                        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                        break;
                    case 'GET':
                        curl_setopt($ch, CURLOPT_URL, $agile_url.$endPoint);
                        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
                        break;
                    case 'PUT':
                        curl_setopt($ch, CURLOPT_URL, $agile_url.$endPoint);
                        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                        break;
                    case 'DELETE':
                        curl_setopt($ch, CURLOPT_URL, $agile_url.$endPoint);
                        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                        break;
                    default:
                        break;
                }

                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type : application/json; charset : UTF-8;', 'Accept: application/json'));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_USERPWD, $agile_email.':'.$agile_api_key);
                curl_setopt($ch, CURLOPT_TIMEOUT, 120);

                $output = curl_exec($ch);
                $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                error_log('Status code: ' . $statusCode . ', output: ' . $output);
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
         * Checks whether a string starts with a number or not.
         *
         * @param string
         *
         * @return bool TRUE if string starts with a number, FALSE otherwise
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
