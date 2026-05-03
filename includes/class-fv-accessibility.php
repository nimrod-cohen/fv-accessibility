<?php
namespace FVAccessibility;

defined('ABSPATH') || exit;

/**
 * Main controller. Owns the frontend hooks (enqueue / inline styles / button
 * markup) and delegates the admin UI to the Admin class.
 */
class Plugin {
  private static $instance = null;

  public static function get_instance() {
    if (self::$instance === null) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  public function init() {
    add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend']);
    add_action('wp_head', [$this, 'inline_styles'], 99);
    add_action('wp_footer', [$this, 'render_button']);

    if (is_admin()) {
      Admin::register();
    }
  }

  public static function activate() {
    // Auto-create the accessibility statement page if missing. Required by
    // Regulation 35: every business must publish an accessibility statement.
    $existing = get_page_by_path('accessibility');
    if (!$existing) {
      $page_id = wp_insert_post([
        'post_title'   => __('הצהרת נגישות', 'fv-accessibility'),
        'post_name'    => 'accessibility',
        'post_status'  => 'publish',
        'post_type'    => 'page',
        'post_content' => '<!-- wp:paragraph --><p>'
          . esc_html__('תוכן הצהרת הנגישות יוטמע במודולים הבאים. ניתן לערוך עמוד זה ידנית בכל עת.', 'fv-accessibility')
          . '</p><!-- /wp:paragraph -->',
      ]);
      if (!is_wp_error($page_id)) {
        $settings = Settings::get();
        $settings['statement']['page_id']      = $page_id;
        $settings['statement']['business_name'] = get_bloginfo('name');
        $settings['statement']['last_updated']  = current_time('Y-m-d');
        Settings::update($settings);
      }
    }
  }

  public static function deactivate() {
    // Intentionally empty — preserve user data on deactivate. Cleanup on
    // uninstall only if the user opted in (handled by uninstall.php in a
    // later module).
  }

  public function should_render() {
    $settings = Settings::get();
    if (empty($settings['enabled'])) return false;

    $excluded = $settings['advanced']['exclude_pages'] ?? [];
    if (!empty($excluded) && is_page($excluded)) return false;

    return true;
  }

  public function enqueue_frontend() {
    if (!$this->should_render()) return;

    wp_enqueue_style(
      'fv-accessibility',
      FV_ACCESSIBILITY_URL . 'assets/css/fv-a11y.css',
      [],
      FV_ACCESSIBILITY_VERSION
    );

    wp_enqueue_script(
      'fv-accessibility',
      FV_ACCESSIBILITY_URL . 'assets/js/fv-a11y.js',
      [],
      FV_ACCESSIBILITY_VERSION,
      true
    );

    $settings = Settings::get();
    wp_localize_script('fv-accessibility', 'fvA11yConfig', [
      'shortcut' => $settings['shortcut'],
      'i18n'     => [
        'menuLabel'   => __('תפריט נגישות', 'fv-accessibility'),
        'closeLabel'  => __('סגור תפריט נגישות', 'fv-accessibility'),
        'comingSoon'  => __('הגדרות הנגישות יוטמעו במודולים הבאים.', 'fv-accessibility'),
      ],
    ]);
  }

  public function inline_styles() {
    if (!$this->should_render()) return;
    $settings = Settings::get();
    $d  = $settings['position']['desktop'];
    $m  = $settings['position']['mobile'];
    $bp = max(320, (int) ($settings['advanced']['mobile_breakpoint'] ?? 768));
    $bg = sanitize_hex_color($settings['appearance']['button_color'] ?? '') ?: '#1d4ed8';
    $fg = sanitize_hex_color($settings['appearance']['icon_color'] ?? '')   ?: '#ffffff';

    $desktop_css = Settings::position_css($d);
    $mobile_css  = Settings::position_css($m);

    echo "<style id='fv-a11y-pos'>";
    echo ".fv-a11y-button{position:fixed;{$desktop_css}background:" . esc_attr($bg) . ";color:" . esc_attr($fg) . ";}";
    echo "@media (max-width:{$bp}px){.fv-a11y-button{{$mobile_css}}}";
    echo "</style>\n";
  }

  public function render_button() {
    if (!$this->should_render()) return;
    $settings    = Settings::get();
    $stmt_pid    = (int) ($settings['statement']['page_id'] ?? 0);
    $stmt_url    = $stmt_pid ? get_permalink($stmt_pid) : home_url('/accessibility/');
    $label       = esc_attr__('תפריט נגישות', 'fv-accessibility');
    $title       = esc_html__('תפריט נגישות', 'fv-accessibility');
    $close_label = esc_attr__('סגור', 'fv-accessibility');
    $coming      = esc_html__('פעולות הנגישות יוטמעו במודולים הבאים.', 'fv-accessibility');
    $stmt_label  = esc_html__('הצהרת נגישות', 'fv-accessibility');
    ?>
    <button id="fv-a11y-trigger"
            class="fv-a11y-button"
            type="button"
            aria-label="<?php echo $label; ?>"
            aria-expanded="false"
            aria-controls="fv-a11y-panel">
      <svg class="fv-a11y-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
        <!-- ISA wheelchair pictogram (simplified, GPL-safe) -->
        <circle cx="12" cy="3.6" r="1.9"/>
        <path d="M9.2 7.2v5.6a2.2 2.2 0 0 0 2.2 2.2h3.9l1.6 4.4a1 1 0 0 0 1.9-.7l-1.6-4.4a2 2 0 0 0-1.9-1.3h-3.7V9.2h4.4a1 1 0 1 0 0-2H9.2zM9.4 15.8a4.5 4.5 0 1 0 4 6.5l-1.7-1a2.6 2.6 0 1 1-1.6-3.7v-1.8z"/>
      </svg>
    </button>
    <div id="fv-a11y-panel"
         class="fv-a11y-panel"
         role="dialog"
         aria-modal="false"
         aria-labelledby="fv-a11y-panel-title"
         aria-hidden="true"
         hidden
         dir="rtl">
      <header class="fv-a11y-panel-header">
        <h2 id="fv-a11y-panel-title" tabindex="-1"><?php echo $title; ?></h2>
        <button type="button" class="fv-a11y-panel-close" aria-label="<?php echo $close_label; ?>">&times;</button>
      </header>
      <div class="fv-a11y-panel-body">
        <p class="fv-a11y-placeholder"><?php echo $coming; ?></p>
      </div>
      <footer class="fv-a11y-panel-footer">
        <a href="<?php echo esc_url($stmt_url); ?>" class="fv-a11y-statement-link"><?php echo $stmt_label; ?></a>
      </footer>
    </div>
    <?php
  }
}
