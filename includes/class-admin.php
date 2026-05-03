<?php
namespace FVAccessibility;

defined('ABSPATH') || exit;

class Admin {
  const PAGE_SLUG = 'fv-accessibility';
  const NONCE     = 'fv_accessibility_save';

  public static function register() {
    add_action('admin_menu',            [__CLASS__, 'add_menu']);
    add_action('admin_init',            [__CLASS__, 'handle_save']);
    add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_admin']);
  }

  public static function add_menu() {
    add_options_page(
      __('נגישות', 'fv-accessibility'),
      __('נגישות', 'fv-accessibility'),
      'manage_options',
      self::PAGE_SLUG,
      [__CLASS__, 'render_page']
    );
  }

  public static function enqueue_admin($hook) {
    if ($hook !== 'settings_page_' . self::PAGE_SLUG) return;
    wp_enqueue_style(
      'fv-accessibility-admin',
      FV_ACCESSIBILITY_URL . 'assets/css/fv-a11y-admin.css',
      [],
      FV_ACCESSIBILITY_VERSION
    );
    wp_enqueue_script(
      'fv-accessibility-admin',
      FV_ACCESSIBILITY_URL . 'assets/js/fv-a11y-admin.js',
      [],
      FV_ACCESSIBILITY_VERSION,
      true
    );
  }

  public static function handle_save() {
    if (!current_user_can('manage_options')) return;
    if (empty($_POST['fv_accessibility_save'])) return;
    check_admin_referer(self::NONCE);

    $settings = Settings::get();

    foreach (['desktop', 'mobile'] as $vp) {
      $vpdata = isset($_POST['position'][$vp]) && is_array($_POST['position'][$vp]) ? $_POST['position'][$vp] : [];
      $settings['position'][$vp]['side']     = in_array($vpdata['side'] ?? '', ['right', 'left'], true) ? $vpdata['side'] : 'right';
      $settings['position'][$vp]['anchor']   = in_array($vpdata['anchor'] ?? '', ['top', 'middle', 'bottom'], true) ? $vpdata['anchor'] : 'bottom';
      $settings['position'][$vp]['offset_x'] = max(0, (int) ($vpdata['offset_x'] ?? 0));
      $settings['position'][$vp]['offset_y'] = (int) ($vpdata['offset_y'] ?? 0);
      $settings['position'][$vp]['size']     = max(24, min(120, (int) ($vpdata['size'] ?? 56)));
    }

    $btn = sanitize_hex_color($_POST['appearance']['button_color'] ?? '');
    if ($btn) $settings['appearance']['button_color'] = $btn;
    $ic  = sanitize_hex_color($_POST['appearance']['icon_color'] ?? '');
    if ($ic) $settings['appearance']['icon_color'] = $ic;

    if (!empty($_POST['shortcut'])) {
      $settings['shortcut'] = sanitize_text_field(wp_unslash($_POST['shortcut']));
    }

    $bp = (int) ($_POST['advanced']['mobile_breakpoint'] ?? 768);
    $settings['advanced']['mobile_breakpoint'] = max(320, min(1440, $bp));

    $settings['enabled'] = !empty($_POST['enabled']);

    Settings::update($settings);
    add_settings_error('fv_accessibility', 'saved', __('ההגדרות נשמרו.', 'fv-accessibility'), 'success');
  }

  public static function render_page() {
    if (!current_user_can('manage_options')) return;
    $settings = Settings::get();
    $tab      = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'position';
    $tabs     = [
      'position'   => __('מיקום', 'fv-accessibility'),
      'appearance' => __('עיצוב', 'fv-accessibility'),
      'features'   => __('יכולות', 'fv-accessibility'),
      'statement'  => __('הצהרת נגישות', 'fv-accessibility'),
      'advanced'   => __('מתקדם', 'fv-accessibility'),
      'compliance' => __('בדיקת תקינות', 'fv-accessibility'),
    ];
    settings_errors('fv_accessibility');
    ?>
    <div class="wrap fv-a11y-admin" dir="rtl">
      <h1><?php esc_html_e('הגדרות נגישות', 'fv-accessibility'); ?></h1>
      <p class="description">
        <?php esc_html_e('תוסף נגישות התואם לתקן ישראלי 5568 (WCAG 2.1 AA) ולתקנה 35 לתקנות שוויון זכויות לאנשים עם מוגבלות. כפתור הנגישות מציע התאמות אמיתיות ב‑DOM — לא שכבת תצוגה (overlay).', 'fv-accessibility'); ?>
      </p>

      <h2 class="nav-tab-wrapper">
        <?php foreach ($tabs as $key => $label): ?>
          <a class="nav-tab <?php echo $tab === $key ? 'nav-tab-active' : ''; ?>"
             href="<?php echo esc_url(admin_url('options-general.php?page=' . self::PAGE_SLUG . '&tab=' . $key)); ?>">
            <?php echo esc_html($label); ?>
          </a>
        <?php endforeach; ?>
      </h2>

      <form method="post" action="">
        <?php wp_nonce_field(self::NONCE); ?>

        <div class="fv-a11y-tab-content">
          <?php
          switch ($tab) {
            case 'position':   self::render_position_tab($settings); break;
            case 'appearance': self::render_appearance_tab($settings); break;
            default:
              echo '<div class="fv-a11y-coming-soon"><p>'
                . esc_html__('מודול זה יוטמע בגרסאות הבאות.', 'fv-accessibility')
                . '</p></div>';
          }
          ?>
        </div>

        <?php if (in_array($tab, ['position', 'appearance'], true)): ?>
          <p class="submit">
            <button type="submit" name="fv_accessibility_save" value="1" class="button button-primary">
              <?php esc_html_e('שמור הגדרות', 'fv-accessibility'); ?>
            </button>
          </p>
        <?php endif; ?>
      </form>
    </div>
    <?php
  }

  private static function render_position_tab($settings) {
    ?>
    <div class="fv-a11y-card">
      <h2><?php esc_html_e('הפעלה', 'fv-accessibility'); ?></h2>
      <label class="fv-a11y-toggle">
        <input type="checkbox" name="enabled" value="1" <?php checked(!empty($settings['enabled'])); ?>>
        <span><?php esc_html_e('הצג כפתור נגישות באתר', 'fv-accessibility'); ?></span>
      </label>
    </div>

    <div class="fv-a11y-card">
      <h2><?php esc_html_e('מיקום הכפתור', 'fv-accessibility'); ?></h2>
      <p class="description"><?php esc_html_e('ניתן להגדיר מיקום שונה במחשב ובמכשיר נייד.', 'fv-accessibility'); ?></p>

      <div class="fv-a11y-pos-grid">
        <?php
        self::render_position_card('desktop', __('מחשב', 'fv-accessibility'), $settings['position']['desktop']);
        self::render_position_card('mobile',  __('נייד',  'fv-accessibility'), $settings['position']['mobile']);
        ?>
      </div>

      <div class="fv-a11y-card-inner">
        <label><?php esc_html_e('רוחב מסך גבולי לנייד (px)', 'fv-accessibility'); ?></label>
        <input type="number" name="advanced[mobile_breakpoint]" min="320" max="1440" step="1"
               value="<?php echo esc_attr($settings['advanced']['mobile_breakpoint']); ?>">
        <p class="description"><?php esc_html_e('מתחת לרוחב זה ייכנסו לתוקף הגדרות הנייד.', 'fv-accessibility'); ?></p>
      </div>
    </div>

    <div class="fv-a11y-card">
      <h2><?php esc_html_e('קיצור מקלדת', 'fv-accessibility'); ?></h2>
      <p class="description"><?php esc_html_e('צירוף מקשים לפתיחת תפריט הנגישות. שימו לב שצירופים מסוימים (כגון Ctrl+U) משמשים גם את הדפדפן.', 'fv-accessibility'); ?></p>
      <input type="text" name="shortcut" value="<?php echo esc_attr($settings['shortcut']); ?>"
             placeholder="ctrl+u" class="regular-text" dir="ltr">
      <p class="description"><?php esc_html_e('דוגמאות: ctrl+u, alt+a, ctrl+shift+9.', 'fv-accessibility'); ?></p>
    </div>
    <?php
  }

  private static function render_position_card($vp, $label, $cfg) {
    ?>
    <div class="fv-a11y-card-inner">
      <h3><?php echo esc_html($label); ?></h3>
      <div class="fv-a11y-grid-2">
        <div>
          <label><?php esc_html_e('צד', 'fv-accessibility'); ?></label>
          <select name="position[<?php echo esc_attr($vp); ?>][side]">
            <option value="right" <?php selected($cfg['side'], 'right'); ?>><?php esc_html_e('ימין', 'fv-accessibility'); ?></option>
            <option value="left"  <?php selected($cfg['side'], 'left'); ?>><?php esc_html_e('שמאל', 'fv-accessibility'); ?></option>
          </select>
        </div>
        <div>
          <label><?php esc_html_e('עיגון אנכי', 'fv-accessibility'); ?></label>
          <select name="position[<?php echo esc_attr($vp); ?>][anchor]">
            <option value="top"    <?php selected($cfg['anchor'], 'top'); ?>><?php esc_html_e('למעלה', 'fv-accessibility'); ?></option>
            <option value="middle" <?php selected($cfg['anchor'], 'middle'); ?>><?php esc_html_e('אמצע', 'fv-accessibility'); ?></option>
            <option value="bottom" <?php selected($cfg['anchor'], 'bottom'); ?>><?php esc_html_e('למטה', 'fv-accessibility'); ?></option>
          </select>
        </div>
        <div>
          <label><?php esc_html_e('היסט אופקי (px)', 'fv-accessibility'); ?></label>
          <input type="number" name="position[<?php echo esc_attr($vp); ?>][offset_x]" min="0" max="500" step="1"
                 value="<?php echo esc_attr($cfg['offset_x']); ?>">
        </div>
        <div>
          <label><?php esc_html_e('היסט אנכי (px)', 'fv-accessibility'); ?></label>
          <input type="number" name="position[<?php echo esc_attr($vp); ?>][offset_y]" min="-500" max="500" step="1"
                 value="<?php echo esc_attr($cfg['offset_y']); ?>">
        </div>
        <div>
          <label><?php esc_html_e('גודל הכפתור (px)', 'fv-accessibility'); ?></label>
          <input type="number" name="position[<?php echo esc_attr($vp); ?>][size]" min="24" max="120" step="1"
                 value="<?php echo esc_attr($cfg['size']); ?>">
        </div>
      </div>
    </div>
    <?php
  }

  private static function render_appearance_tab($settings) {
    ?>
    <div class="fv-a11y-card">
      <h2><?php esc_html_e('צבעים', 'fv-accessibility'); ?></h2>
      <div class="fv-a11y-grid-2">
        <div>
          <label><?php esc_html_e('צבע רקע הכפתור', 'fv-accessibility'); ?></label>
          <input type="color" name="appearance[button_color]" value="<?php echo esc_attr($settings['appearance']['button_color']); ?>">
        </div>
        <div>
          <label><?php esc_html_e('צבע אייקון', 'fv-accessibility'); ?></label>
          <input type="color" name="appearance[icon_color]" value="<?php echo esc_attr($settings['appearance']['icon_color']); ?>">
        </div>
      </div>
    </div>
    <?php
  }
}
