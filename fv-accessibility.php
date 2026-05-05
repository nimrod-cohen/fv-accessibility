<?php
/**
 * Plugin Name:       FV Accessibility
 * Plugin URI:        https://github.com/nimrod-cohen/fv-accessibility
 * Description:       WordPress accessibility plugin compliant with Israeli Standard IS 5568 (WCAG 2.1 AA) and Regulation 35 of the Equal Rights for Persons with Disabilities (Service Accessibility Adjustments) Regulations, 5773-2013. Performs real DOM modifications — not a cosmetic overlay.
 * Version:           1.0.5
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            nimrod-cohen
 * Author URI:        https://github.com/nimrod-cohen
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       fv-accessibility
 * Domain Path:       /languages
 */

if (!defined('ABSPATH')) exit;

// Single source of truth: the Version: header above. `get_file_data()` is
// loaded by WordPress core and reads only the file header (cheap), so we
// don't have to update a version constant in two places on every release.
$fv_a11y_header = get_file_data(__FILE__, ['Version' => 'Version']);
define('FV_ACCESSIBILITY_VERSION', $fv_a11y_header['Version'] ?: '0.0.0');
define('FV_ACCESSIBILITY_FILE', __FILE__);
define('FV_ACCESSIBILITY_DIR', plugin_dir_path(__FILE__));
define('FV_ACCESSIBILITY_URL', plugin_dir_url(__FILE__));
unset($fv_a11y_header);

require_once FV_ACCESSIBILITY_DIR . 'includes/class-settings.php';
require_once FV_ACCESSIBILITY_DIR . 'includes/class-features.php';
require_once FV_ACCESSIBILITY_DIR . 'includes/class-icons.php';
require_once FV_ACCESSIBILITY_DIR . 'includes/class-statement.php';
require_once FV_ACCESSIBILITY_DIR . 'includes/class-feedback.php';
require_once FV_ACCESSIBILITY_DIR . 'includes/class-compliance.php';
require_once FV_ACCESSIBILITY_DIR . 'includes/class-fv-accessibility.php';
require_once FV_ACCESSIBILITY_DIR . 'includes/class-admin.php';
require_once FV_ACCESSIBILITY_DIR . 'includes/github-updater.php';

add_action('plugins_loaded', function () {
  load_plugin_textdomain(
    'fv-accessibility',
    false,
    dirname(plugin_basename(__FILE__)) . '/languages'
  );
  \FVAccessibility\Plugin::get_instance()->init();
});

add_action('init', function () {
  if (is_admin()) {
    new \FVAccessibility\GitHubPluginUpdater(__FILE__);
  }
});

register_activation_hook(__FILE__, ['\FVAccessibility\Plugin', 'activate']);
register_deactivation_hook(__FILE__, ['\FVAccessibility\Plugin', 'deactivate']);
