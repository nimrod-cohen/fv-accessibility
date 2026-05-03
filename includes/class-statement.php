<?php
namespace FVAccessibility;

defined('ABSPATH') || exit;

/**
 * Renders the accessibility statement, required by Regulation 35. The
 * statement is a shortcode (`[fv_accessibility_statement]`) so coordinator
 * details edited in the Settings page reflect on the page immediately,
 * without having to re-save the page content.
 *
 * The auto-created `/accessibility/` page contains just the shortcode.
 */
class Statement {
  public static function init() {
    add_shortcode('fv_accessibility_statement', [__CLASS__, 'render']);
  }

  public static function render($atts = []) {
    $settings = Settings::get();
    $stmt     = $settings['statement'];
    ob_start();
    include FV_ACCESSIBILITY_DIR . 'templates/statement-he.php';
    return ob_get_clean();
  }

  /**
   * Returns the URL of the statement page (whatever the admin set or the
   * activation hook created), falling back to /accessibility/.
   */
  public static function url() {
    $settings = Settings::get();
    $pid = (int) ($settings['statement']['page_id'] ?? 0);
    if ($pid && get_post_status($pid) === 'publish') {
      return get_permalink($pid);
    }
    return home_url('/accessibility/');
  }
}
