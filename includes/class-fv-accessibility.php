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
    add_action('wp_head', [$this, 'inline_state_bootstrap'], 1);
    add_action('wp_head', [$this, 'inline_styles'], 99);
    add_action('wp_footer', [$this, 'render_button']);

    Statement::init();
    Feedback::init();

    if (is_admin()) {
      Admin::register();
    }
  }

  public static function activate() {
    // Auto-create the accessibility statement page if missing. Required by
    // Regulation 35: every business must publish an accessibility statement.
    // Page content is just the shortcode so coordinator-detail edits in the
    // settings reflect immediately, without re-saving the page.
    $existing = get_page_by_path('accessibility');
    if (!$existing) {
      $page_id = wp_insert_post([
        'post_title'   => __('הצהרת נגישות', 'fv-accessibility'),
        'post_name'    => 'accessibility',
        'post_status'  => 'publish',
        'post_type'    => 'page',
        'post_content' => "<!-- wp:shortcode -->\n[fv_accessibility_statement]\n<!-- /wp:shortcode -->",
      ]);
      if (!is_wp_error($page_id)) {
        $settings = Settings::get();
        $settings['statement']['page_id']       = $page_id;
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
      'fv-accessibility-features',
      FV_ACCESSIBILITY_URL . 'assets/css/fv-a11y-features.css',
      [],
      FV_ACCESSIBILITY_VERSION
    );

    wp_enqueue_style(
      'fv-accessibility',
      FV_ACCESSIBILITY_URL . 'assets/css/fv-a11y.css',
      ['fv-accessibility-features'],
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
      'ajax'     => [
        'url'    => admin_url('admin-ajax.php'),
        'action' => Feedback::ACTION,
      ],
      'i18n'     => [
        'menuLabel'    => __('תפריט נגישות', 'fv-accessibility'),
        'closeLabel'   => __('סגור תפריט נגישות', 'fv-accessibility'),
        'sending'      => __('שולח...', 'fv-accessibility'),
        'submit'       => __('שלח', 'fv-accessibility'),
        'errorGeneric' => __('שגיאה בשליחה. נסו שוב.', 'fv-accessibility'),
        'announceOn'   => __('%s — פעיל', 'fv-accessibility'),
        'announceOff'  => __('%s — כובה', 'fv-accessibility'),
        'announceStep' => __('%1$s — שלב %2$d מתוך %3$d', 'fv-accessibility'),
        'announceReset'=> __('כל ההתאמות אופסו.', 'fv-accessibility'),
        'imgNoAlt'     => __('(תמונה ללא תיאור)', 'fv-accessibility'),
      ],
    ]);
  }

  /**
   * Tiny inline script in <head> that reads the saved state from
   * localStorage / cookie and adds the matching `fv-*` classes to <html>
   * before the page paints. Without this, every refresh would briefly show
   * the page in its default state and then snap into the user's preferred
   * adjustments — visible jank that defeats the purpose of accessibility.
   *
   * Kept deliberately self-contained and non-strict: any failure must fall
   * back to "no classes added" rather than throw.
   */
  public function inline_state_bootstrap() {
    if (!$this->should_render()) return;
    ?>
<script id="fv-a11y-bootstrap">
(function(){try{var s=null;try{s=localStorage.getItem('fv_a11y_state');}catch(e){}if(!s){var m=document.cookie.match(/(?:^|;\s*)fv_a11y_state=([^;]+)/);if(m)s=decodeURIComponent(m[1]);}if(!s)return;var st=JSON.parse(s),cls=[];if(st.textSize)cls.push('fv-text-size-'+st.textSize);if(st.lineSpacing)cls.push('fv-line-spacing-'+st.lineSpacing);if(st.wordSpacing)cls.push('fv-word-spacing-'+st.wordSpacing);if(st.letterSpacing)cls.push('fv-letter-spacing-'+st.letterSpacing);if(st.lineHeight)cls.push('fv-line-height-'+st.lineHeight);if(st.pageZoom)cls.push('fv-page-zoom-'+st.pageZoom);if(st.textAlign)cls.push('fv-text-align-'+st.textAlign);if(st.readableFont)cls.push('fv-readable-font');if(st.dyslexicFont)cls.push('fv-dyslexic-font');if(st.largerTargets)cls.push('fv-larger-targets');if(st.highlightHeadings)cls.push('fv-highlight-headings');if(st.highlightLinks)cls.push('fv-highlight-links');if(st.highlightFocus)cls.push('fv-highlight-focus');if(st.imageDescriptions)cls.push('fv-image-descriptions');if(st.contentMagnifier)cls.push('fv-content-magnifier');if(cls.length)document.documentElement.classList.add.apply(document.documentElement.classList,cls);}catch(e){}})();
</script>
    <?php
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

    $custom = isset($settings['advanced']['custom_css']) ? trim((string) $settings['advanced']['custom_css']) : '';

    echo "<style id='fv-a11y-pos'>";
    echo ".fv-a11y-button{position:fixed;{$desktop_css}background:" . esc_attr($bg) . ";color:" . esc_attr($fg) . ";}";
    echo "@media (max-width:{$bp}px){.fv-a11y-button{{$mobile_css}}}";
    if ($custom !== '') {
      // Tags were already stripped on save (wp_strip_all_tags in Admin::handle_save).
      echo "\n/* fv-accessibility custom css */\n" . $custom;
    }
    echo "</style>\n";
  }

  /**
   * Module-3 control definitions. As subsequent modules add behaviors for
   * other categories (color, media, navigation, cognitive), entries get
   * appended here.
   *
   * Each control has:
   *   id     — matches the registry id and the JS state key (snake_case)
   *   label  — Hebrew button label
   *   type   — 'step' | 'toggle' | 'cycle'
   *   steps  — number of step states (for type=step)
   *   cycle  — comma-separated values rotated through (for type=cycle)
   */
  private function module3_controls() {
    return [
      ['id' => 'text_size',          'label' => 'גודל טקסט',                'type' => 'step',   'steps' => 4],
      ['id' => 'line_spacing',       'label' => 'ריווח שורות',              'type' => 'step',   'steps' => 3],
      ['id' => 'word_spacing',       'label' => 'ריווח מילים',              'type' => 'step',   'steps' => 3],
      ['id' => 'letter_spacing',     'label' => 'ריווח אותיות',             'type' => 'step',   'steps' => 3],
      ['id' => 'line_height',        'label' => 'גובה שורה',                'type' => 'step',   'steps' => 3],
      ['id' => 'readable_font',      'label' => 'גופן קריא',                'type' => 'toggle'],
      ['id' => 'dyslexic_font',      'label' => 'גופן לדיסלקסיה',           'type' => 'toggle'],
      ['id' => 'text_align',         'label' => 'יישור טקסט',               'type' => 'cycle',  'cycle' => 'right,left,center,justify'],
      ['id' => 'page_zoom',          'label' => 'הגדלת תצוגה',              'type' => 'step',   'steps' => 3],
      ['id' => 'larger_targets',     'label' => 'הגדלת כפתורי פעולה',       'type' => 'toggle'],
      ['id' => 'highlight_headings', 'label' => 'הדגשת כותרות',             'type' => 'toggle'],
      ['id' => 'highlight_links',    'label' => 'הדגשת קישורים',            'type' => 'toggle'],
      ['id' => 'highlight_focus',    'label' => 'הדגשת פוקוס',              'type' => 'toggle'],
      ['id' => 'image_descriptions', 'label' => 'תיאור לתמונות',            'type' => 'toggle'],
      ['id' => 'content_magnifier',  'label' => 'הגדלת תוכן בריחוף',        'type' => 'toggle'],
    ];
  }

  /**
   * Render the feature button grid in the drawer's main section. Only
   * controls whose feature id is enabled in admin Settings → Features
   * actually appear, so site owners can curate the menu per audience.
   */
  private function render_controls() {
    $enabled = Features::enabled_map();
    $cats    = Features::categories();
    $controls = $this->module3_controls();

    $by_cat = [];
    foreach ($controls as $c) {
      // Find the feature's category from the registry
      $cat = 'content';
      foreach (Features::all() as $f) {
        if ($f['id'] === $c['id']) { $cat = $f['category']; break; }
      }
      $c['category'] = $cat;
      if (empty($enabled[$c['id']])) continue;
      $by_cat[$cat][] = $c;
    }

    foreach ($cats as $cat_id => $cat_label) {
      if (empty($by_cat[$cat_id])) continue;
      ?>
      <div class="fv-a11y-controls-cat">
        <h3><?php echo esc_html($cat_label); ?></h3>
        <div class="fv-a11y-grid">
          <?php foreach ($by_cat[$cat_id] as $c): ?>
            <button type="button"
                    class="fv-a11y-ctl"
                    data-feature="<?php echo esc_attr($c['id']); ?>"
                    data-type="<?php echo esc_attr($c['type']); ?>"
                    <?php if ($c['type'] === 'step'): ?>data-steps="<?php echo (int) $c['steps']; ?>"<?php endif; ?>
                    <?php if ($c['type'] === 'cycle'): ?>data-cycle="<?php echo esc_attr($c['cycle']); ?>"<?php endif; ?>
                    aria-pressed="false">
              <span class="fv-a11y-ctl-label"><?php echo esc_html($c['label']); ?></span>
              <span class="fv-a11y-ctl-state" aria-hidden="true"></span>
            </button>
          <?php endforeach; ?>
        </div>
      </div>
      <?php
    }
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
      <svg class="fv-a11y-icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"
           style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:2;">
        <g transform="matrix(1.35395,0,0,1.34887,-4.24735,-0.39702)">
          <circle cx="12" cy="4" r="1.6"/>
        </g>
        <path d="M5.5,7.5L12,8.81L18.5,7.5C18.925,7.469 19.794,7.57 19.794,8.393C19.794,9.215 18.36,9.411 17.766,9.627C17.766,9.627 13.047,10.395 13.047,11.085C13.047,11.613 12,10.991 12,10.991C12,10.991 11.058,11.66 11.137,11.112C11.264,10.24 5.592,9.64 5.592,9.64C4.839,9.418 4.096,9.29 4.096,8.468C4.096,7.645 5.061,7.49 5.5,7.5Z" style="fill-rule:nonzero;"/>
        <g transform="matrix(1,0,0,1,0,-0.0239911)">
          <path d="M10.932,10.429C11.127,11.801 9.842,16.102 8.4,20.3C8.358,20.412 8.337,20.531 8.337,20.65C8.337,21.2 8.79,21.653 9.34,21.653C9.758,21.653 10.134,21.392 10.28,21L12,15.029L13.72,21C13.866,21.392 14.242,21.653 14.66,21.653C15.21,21.653 15.663,21.2 15.663,20.65C15.663,20.531 15.642,20.412 15.6,20.3C14.157,16.596 12.718,10.494 13.187,10.487C14.497,10.467 11.199,9.234 10.932,10.429Z" style="fill-rule:nonzero;"/>
        </g>
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
        <section class="fv-a11y-section fv-a11y-section-main" data-section="main">
          <div class="fv-a11y-announce" role="status" aria-live="polite" aria-atomic="true"></div>
          <?php $this->render_controls(); ?>
          <button type="button" class="fv-a11y-reset" data-action="reset">
            <?php esc_html_e('איפוס כל ההתאמות', 'fv-accessibility'); ?>
          </button>
          <button type="button" class="fv-a11y-feedback-trigger" data-target="feedback">
            <?php esc_html_e('דווח על בעיית נגישות', 'fv-accessibility'); ?>
          </button>
        </section>
        <section class="fv-a11y-section fv-a11y-section-feedback" data-section="feedback" hidden>
          <button type="button" class="fv-a11y-section-back" data-target="main" aria-label="<?php esc_attr_e('חזרה לתפריט הראשי', 'fv-accessibility'); ?>">
            <?php esc_html_e('← חזרה', 'fv-accessibility'); ?>
          </button>
          <h3 class="fv-a11y-section-title"><?php esc_html_e('דווח על בעיית נגישות', 'fv-accessibility'); ?></h3>
          <?php echo Feedback::render(['variant' => 'drawer']); ?>
        </section>
      </div>
      <footer class="fv-a11y-panel-footer">
        <a href="<?php echo esc_url($stmt_url); ?>" class="fv-a11y-statement-link"><?php echo $stmt_label; ?></a>
      </footer>
    </div>
    <?php
  }

}
