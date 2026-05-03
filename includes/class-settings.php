<?php
namespace FVAccessibility;

defined('ABSPATH') || exit;

/**
 * Single source of truth for plugin settings.
 *
 * Stored as one serialized option (`fv_accessibility_settings`) — one DB read
 * per request after first cache hit. New keys go through defaults() so older
 * installs auto-merge missing fields without explicit migrations.
 */
class Settings {
  const OPTION_KEY = 'fv_accessibility_settings';

  public static function defaults() {
    return [
      'enabled'  => true,
      'shortcut' => 'ctrl+u',
      'position' => [
        'desktop' => [
          'side'     => 'right',  // right|left
          'anchor'   => 'bottom', // top|middle|bottom
          'offset_x' => 20,
          'offset_y' => 20,
          'size'     => 56,
        ],
        'mobile' => [
          'side'     => 'right',
          'anchor'   => 'bottom',
          'offset_x' => 12,
          'offset_y' => 12,
          'size'     => 48,
        ],
      ],
      'appearance' => [
        'button_color' => '#1d4ed8',
        'icon_color'   => '#ffffff',
        'panel_theme'  => 'light',
      ],
      'features'  => [],   // populated in later modules
      'statement' => [
        'page_id'           => 0,
        'coordinator_name'  => '',
        'coordinator_email' => '',
        'coordinator_phone' => '',
        'coordinator_role'  => '',
        'business_name'     => '',
        'exemption_text'    => '',
        'last_updated'      => '',
      ],
      'advanced' => [
        'mobile_breakpoint' => 768,
        'exclude_pages'     => [],
        'show_footer_icon'  => true,
        'cleanup_on_uninstall' => false,
      ],
    ];
  }

  public static function get() {
    $opt = get_option(self::OPTION_KEY, []);
    return self::deep_merge(self::defaults(), is_array($opt) ? $opt : []);
  }

  public static function update($values) {
    update_option(self::OPTION_KEY, $values, false);
  }

  private static function deep_merge($defaults, $values) {
    foreach ($values as $key => $val) {
      if (is_array($val) && isset($defaults[$key]) && is_array($defaults[$key])) {
        $defaults[$key] = self::deep_merge($defaults[$key], $val);
      } else {
        $defaults[$key] = $val;
      }
    }
    return $defaults;
  }

  /**
   * Translate a position config block into the CSS declarations needed to
   * place the button absolutely. Centralised so admin-side preview and
   * frontend output stay in lockstep.
   */
  public static function position_css($cfg) {
    $side   = in_array($cfg['side'] ?? '', ['right', 'left'], true) ? $cfg['side'] : 'right';
    $anchor = in_array($cfg['anchor'] ?? '', ['top', 'middle', 'bottom'], true) ? $cfg['anchor'] : 'bottom';
    $x      = max(0, (int) ($cfg['offset_x'] ?? 20));
    $y      = (int) ($cfg['offset_y'] ?? 20);
    $size   = max(24, min(120, (int) ($cfg['size'] ?? 56)));

    $css = "width:{$size}px;height:{$size}px;";

    if ($side === 'right') {
      $css .= "right:{$x}px;left:auto;";
    } else {
      $css .= "left:{$x}px;right:auto;";
    }

    if ($anchor === 'top') {
      $css .= "top:{$y}px;bottom:auto;transform:none;";
    } elseif ($anchor === 'bottom') {
      $css .= "bottom:{$y}px;top:auto;transform:none;";
    } else { // middle
      $css .= "top:calc(50% + {$y}px);bottom:auto;transform:translateY(-50%);";
    }

    return $css;
  }
}
