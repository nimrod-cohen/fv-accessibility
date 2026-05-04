<?php
namespace FVAccessibility;

defined('ABSPATH') || exit;

/**
 * Registry of every adjustment the menu can offer. Used by the Features
 * admin tab (toggle on/off) and — starting in module 3 — by the frontend
 * to decide which menu items to render and which DOM classes to support.
 *
 * The list is intentionally declarative so each module that lands the
 * actual behavior just hooks into the same `id`s; nothing else changes.
 */
class Features {
  public static function categories() {
    return [
      'profiles'   => __('פרופילים', 'fv-accessibility'),
      'content'    => __('התאמות תוכן', 'fv-accessibility'),
      'color'      => __('צבע וניגודיות', 'fv-accessibility'),
      'media'      => __('מדיה ותנועה', 'fv-accessibility'),
      'navigation' => __('ניווט וסמן', 'fv-accessibility'),
    ];
  }

  public static function all() {
    return [
      // Profiles — one-click presets that compose the per-feature toggles.
      ['id' => 'profile_blind',       'category' => 'profiles', 'label' => 'נגישות לעיוורים',         'default' => true],
      ['id' => 'profile_low_vision',  'category' => 'profiles', 'label' => 'נגישות לכבדי ראייה',      'default' => true],
      ['id' => 'profile_color_blind', 'category' => 'profiles', 'label' => 'נגישות לעיוורי צבעים',    'default' => true],
      ['id' => 'profile_cognitive',   'category' => 'profiles', 'label' => 'נגישות למוגבלי קוגניציה', 'default' => true],
      ['id' => 'profile_motor',       'category' => 'profiles', 'label' => 'נגישות לנכי מוטוריקה',    'default' => true],

      // Content adjustments
      ['id' => 'text_size',          'category' => 'content', 'label' => 'גודל טקסט',                 'default' => true],
      ['id' => 'line_spacing',       'category' => 'content', 'label' => 'ריווח שורות',               'default' => true],
      ['id' => 'word_spacing',       'category' => 'content', 'label' => 'ריווח מילים',               'default' => true],
      ['id' => 'letter_spacing',     'category' => 'content', 'label' => 'ריווח אותיות',              'default' => true],
      ['id' => 'readable_font',      'category' => 'content', 'label' => 'גופן קריא',                 'default' => true],
      ['id' => 'dyslexic_font',      'category' => 'content', 'label' => 'גופן ידידותי לדיסלקסיה',    'default' => true],
      ['id' => 'text_align',         'category' => 'content', 'label' => 'יישור טקסט',                'default' => true],
      ['id' => 'page_zoom',          'category' => 'content', 'label' => 'הגדלת תצוגה',               'default' => true],
      ['id' => 'larger_targets',     'category' => 'content', 'label' => 'הגדלת כפתורים ואלמנטי קלט', 'default' => true],
      ['id' => 'highlight_headings', 'category' => 'content', 'label' => 'הדגשת כותרות',              'default' => true],
      ['id' => 'highlight_links',    'category' => 'content', 'label' => 'הדגשת קישורים',             'default' => true],
      ['id' => 'highlight_focus',    'category' => 'content', 'label' => 'הדגשה במעבר עכבר/פוקוס',    'default' => true],
      ['id' => 'image_descriptions', 'category' => 'content', 'label' => 'תיאור לתמונות',             'default' => true],
      ['id' => 'content_magnifier',  'category' => 'content', 'label' => 'הגדלת תכנים בריחוף',        'default' => true],
      ['id' => 'line_height',        'category' => 'content', 'label' => 'גובה שורה',                 'default' => true],

      // Color & contrast
      ['id' => 'contrast',           'category' => 'color', 'label' => 'ניגודיות (בהירה / כהה)',       'default' => true],
      ['id' => 'monochrome',         'category' => 'color', 'label' => 'מונוכרום',                    'default' => true],
      ['id' => 'invert_colors',      'category' => 'color', 'label' => 'מוד ניגודיות הפוכה',          'default' => true],
      ['id' => 'saturation',         'category' => 'color', 'label' => 'רוויה גבוהה / נמוכה',         'default' => true],
      ['id' => 'color_picker',       'category' => 'color', 'label' => 'התאמת צבעים',                 'default' => true],

      // Media & motion
      ['id' => 'pause_animations',   'category' => 'media', 'label' => 'ביטול הנפשות',                'default' => true],
      ['id' => 'hide_images',        'category' => 'media', 'label' => 'הסתרת תמונות',                'default' => true],
      ['id' => 'block_flashing',     'category' => 'media', 'label' => 'חסימת הבהובים (>3Hz)',         'default' => true],
      ['id' => 'mute_media',         'category' => 'media', 'label' => 'השתק מדיה',                   'default' => true],

      // Navigation & cursor
      ['id' => 'cursor',             'category' => 'navigation', 'label' => 'סמן גדול (שחור / לבן)',  'default' => true],
      ['id' => 'keyboard_nav',       'category' => 'navigation', 'label' => 'ניווט מקלדת',            'default' => true],
      ['id' => 'reading_ruler',      'category' => 'navigation', 'label' => 'מדריך קריאה',            'default' => true],
      ['id' => 'reading_mask',       'category' => 'navigation', 'label' => 'מיקוד קריאה',            'default' => true],
      ['id' => 'reader_mode',        'category' => 'navigation', 'label' => 'תצוגת קריאה',            'default' => true],
      ['id' => 'page_structure',     'category' => 'navigation', 'label' => 'מבנה העמוד',             'default' => true],
      ['id' => 'page_outline',       'category' => 'navigation', 'label' => 'סיכום עמוד',             'default' => true],

    ];
  }

  /**
   * id => bool map, merging stored flags over registry defaults.
   */
  public static function enabled_map() {
    $settings = Settings::get();
    $stored   = is_array($settings['features'] ?? null) ? $settings['features'] : [];
    $map      = [];
    foreach (self::all() as $f) {
      $map[$f['id']] = array_key_exists($f['id'], $stored) ? !empty($stored[$f['id']]) : !empty($f['default']);
    }
    return $map;
  }
}
