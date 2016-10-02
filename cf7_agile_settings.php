<?php
/**
 * @author Gary McPherson (genyus) <gary@ingenyus.com>
 * @license http://www.gnu.org/licenses/agpl-3.0.html
 */

class cf7_agile_settings {

  public static function getHost() {
    return get_option('cf7_agile_host');
  }

  public static function getPath() {
    return get_option('cf7_agile_path') ? get_option('cf7_agile_path') : '/sites/all/modules/agilecrm/extern/rest.php';
  }

  public static function setHost($host) {
    update_option( 'cf7_agile_host', $host );
  }

  public static function setPath($path) {
    update_option( 'cf7_agile_path', $path );
  }

  public static function getSiteKey() {
    return get_option('cf7_agile_site_key');
  }

  public static function setSiteKey($key) {
    update_option( 'cf7_agile_site_key', $key );
  }

  public static function getApiKey() {
    return get_option('cf7_agile_api_key');
  }

  public static function setApiKey($key) {
    update_option( 'cf7_agile_api_key', $key );
  }

}