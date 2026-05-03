<?php
/**
 * Cleanup on uninstall, only if the admin opted in via Settings → Advanced
 * → "Cleanup on uninstall". The auto-created /accessibility/ page is left
 * alone — that's user content, not plugin metadata.
 */
defined('WP_UNINSTALL_PLUGIN') || exit;

$opt = get_option('fv_accessibility_settings', []);
if (is_array($opt) && !empty($opt['advanced']['cleanup_on_uninstall'])) {
  delete_option('fv_accessibility_settings');
}
