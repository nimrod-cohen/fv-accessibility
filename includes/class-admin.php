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
    $tab = isset($_POST['active_tab']) ? sanitize_key($_POST['active_tab']) : '';

    if ($tab === 'position') {
    foreach (['desktop', 'mobile'] as $vp) {
      $vpdata = isset($_POST['position'][$vp]) && is_array($_POST['position'][$vp]) ? $_POST['position'][$vp] : [];
      $settings['position'][$vp]['side']     = in_array($vpdata['side'] ?? '', ['right', 'center', 'left'], true) ? $vpdata['side'] : 'right';
      $settings['position'][$vp]['anchor']   = in_array($vpdata['anchor'] ?? '', ['top', 'middle', 'bottom'], true) ? $vpdata['anchor'] : 'bottom';
      $settings['position'][$vp]['offset_x'] = max(0, (int) ($vpdata['offset_x'] ?? 0));
      $settings['position'][$vp]['offset_y'] = (int) ($vpdata['offset_y'] ?? 0);
      $settings['position'][$vp]['size']     = max(24, min(120, (int) ($vpdata['size'] ?? 56)));
    }
      // Position-tab-only fields:
      $bp = (int) ($_POST['advanced']['mobile_breakpoint'] ?? 768);
      $settings['advanced']['mobile_breakpoint'] = max(320, min(1440, $bp));
      if (!empty($_POST['shortcut'])) {
        $settings['shortcut'] = sanitize_text_field(wp_unslash($_POST['shortcut']));
      }
      $settings['enabled'] = !empty($_POST['enabled']);
    }

    if ($tab === 'appearance') {
      $btn = sanitize_hex_color($_POST['appearance']['button_color'] ?? '');
      if ($btn) $settings['appearance']['button_color'] = $btn;
      $ic  = sanitize_hex_color($_POST['appearance']['icon_color'] ?? '');
      if ($ic) $settings['appearance']['icon_color'] = $ic;
    }

    if ($tab === 'features') {
      $features_post = isset($_POST['features']) && is_array($_POST['features']) ? $_POST['features'] : [];
      $features_out  = [];
      foreach (Features::all() as $f) {
        $features_out[$f['id']] = !empty($features_post[$f['id']]);
      }
      $settings['features'] = $features_out;
    }

    if ($tab === 'statement' && isset($_POST['statement']) && is_array($_POST['statement'])) {
      $stmt_post = $_POST['statement'];
      foreach (['coordinator_name', 'coordinator_role', 'coordinator_phone', 'business_name'] as $k) {
        if (isset($stmt_post[$k])) {
          $settings['statement'][$k] = sanitize_text_field(wp_unslash($stmt_post[$k]));
        }
      }
      if (isset($stmt_post['coordinator_email'])) {
        $settings['statement']['coordinator_email'] = sanitize_email(wp_unslash($stmt_post['coordinator_email']));
      }
      if (isset($stmt_post['exemption_text'])) {
        $settings['statement']['exemption_text'] = sanitize_textarea_field(wp_unslash($stmt_post['exemption_text']));
      }
      $settings['statement']['last_updated'] = current_time('Y-m-d');
    }

    if ($tab === 'advanced') {
      $settings['advanced']['cleanup_on_uninstall'] = !empty($_POST['advanced']['cleanup_on_uninstall']);
      if (isset($_POST['advanced']['exclude_pages']) && is_array($_POST['advanced']['exclude_pages'])) {
        $settings['advanced']['exclude_pages'] = array_values(array_filter(
          array_map('absint', $_POST['advanced']['exclude_pages'])
        ));
      } else {
        $settings['advanced']['exclude_pages'] = [];
      }
      if (isset($_POST['advanced']['custom_css'])) {
        $settings['advanced']['custom_css'] = wp_strip_all_tags(wp_unslash($_POST['advanced']['custom_css']));
      }
    }

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
        <input type="hidden" name="active_tab" value="<?php echo esc_attr($tab); ?>">

        <div class="fv-a11y-tab-content">
          <?php
          switch ($tab) {
            case 'position':   self::render_position_tab($settings); break;
            case 'appearance': self::render_appearance_tab($settings); break;
            case 'features':   self::render_features_tab($settings); break;
            case 'statement':  self::render_statement_tab($settings); break;
            case 'advanced':   self::render_advanced_tab($settings); break;
            case 'compliance':  self::render_compliance_tab($settings); break;
            default:
              echo '<div class="fv-a11y-coming-soon"><p>'
                . esc_html__('מודול זה יוטמע בגרסאות הבאות.', 'fv-accessibility')
                . '</p></div>';
          }
          ?>
        </div>

        <?php if (in_array($tab, ['position', 'appearance', 'features', 'statement', 'advanced'], true)): ?>
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

      <div class="fv-a11y-device-toggle" role="tablist">
        <button type="button" class="fv-a11y-device-btn is-active" data-device="desktop" role="tab" aria-selected="true">
          <span class="fv-a11y-device-icon"><?php echo Icons::get('monitor'); ?></span>
          <span><?php esc_html_e('מחשב', 'fv-accessibility'); ?></span>
        </button>
        <button type="button" class="fv-a11y-device-btn" data-device="mobile" role="tab" aria-selected="false">
          <span class="fv-a11y-device-icon"><?php echo Icons::get('smartphone'); ?></span>
          <span><?php esc_html_e('נייד', 'fv-accessibility'); ?></span>
        </button>
      </div>

      <?php
      self::render_position_form('desktop', $settings['position']['desktop'], false);
      self::render_position_form('mobile',  $settings['position']['mobile'],  true);
      ?>

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

  /**
   * Per-device form: visual 3×3 anchor picker on the left, numeric inputs
   * on the right. Only one device's form is visible at a time (via the
   * device-toggle JS); both forms POST regardless.
   */
  private static function render_position_form($vp, $cfg, $hidden) {
    $sides = [
      ['top',    'right'], ['top',    'center'], ['top',    'left'],
      ['middle', 'right'], ['middle', 'center'], ['middle', 'left'],
      ['bottom', 'right'], ['bottom', 'center'], ['bottom', 'left'],
    ];
    ?>
    <div class="fv-a11y-device-form" data-device="<?php echo esc_attr($vp); ?>" <?php if ($hidden) echo 'hidden'; ?>>
      <div class="fv-a11y-position-layout">
        <div class="fv-a11y-pos-picker-wrap">
          <div class="fv-a11y-pos-picker" role="radiogroup" aria-label="<?php esc_attr_e('עיגון הכפתור', 'fv-accessibility'); ?>">
            <?php foreach ($sides as [$anchor, $side]):
              $is_active = $cfg['anchor'] === $anchor && $cfg['side'] === $side;
            ?>
              <button type="button"
                      class="fv-a11y-pos-cell <?php echo $is_active ? 'is-active' : ''; ?>"
                      data-anchor="<?php echo esc_attr($anchor); ?>"
                      data-side="<?php echo esc_attr($side); ?>"
                      role="radio"
                      aria-checked="<?php echo $is_active ? 'true' : 'false'; ?>"
                      aria-label="<?php echo esc_attr(self::pos_label($anchor, $side)); ?>"></button>
            <?php endforeach; ?>
          </div>
          <p class="fv-a11y-pos-hint"><?php esc_html_e('לחצו על הקצה הרצוי כדי לעגן את הכפתור.', 'fv-accessibility'); ?></p>
        </div>

        <div class="fv-a11y-pos-inputs">
          <input type="hidden" name="position[<?php echo esc_attr($vp); ?>][side]"   value="<?php echo esc_attr($cfg['side']); ?>"   data-pos-input="side">
          <input type="hidden" name="position[<?php echo esc_attr($vp); ?>][anchor]" value="<?php echo esc_attr($cfg['anchor']); ?>" data-pos-input="anchor">

          <div class="fv-a11y-grid-2">
            <div>
              <label><?php esc_html_e('היסט אופקי (px)', 'fv-accessibility'); ?></label>
              <input type="number" name="position[<?php echo esc_attr($vp); ?>][offset_x]" min="0" max="500" step="1"
                     value="<?php echo esc_attr($cfg['offset_x']); ?>">
            </div>
            <div>
              <label><?php esc_html_e('היסט אנכי (px)', 'fv-accessibility'); ?></label>
              <input type="number" name="position[<?php echo esc_attr($vp); ?>][offset_y]" min="0" max="500" step="1"
                     value="<?php echo esc_attr($cfg['offset_y']); ?>">
            </div>
            <div>
              <label><?php esc_html_e('גודל הכפתור (px)', 'fv-accessibility'); ?></label>
              <input type="number" name="position[<?php echo esc_attr($vp); ?>][size]" min="24" max="120" step="1"
                     value="<?php echo esc_attr($cfg['size']); ?>">
            </div>
          </div>
        </div>
      </div>
    </div>
    <?php
  }

  private static function pos_label($anchor, $side) {
    $a = ['top' => 'למעלה', 'middle' => 'באמצע', 'bottom' => 'למטה'];
    $s = ['right' => 'בצד ימין', 'center' => 'במרכז', 'left' => 'בצד שמאל'];
    return ($a[$anchor] ?? '') . ' ' . ($s[$side] ?? '');
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

  private static function render_features_tab($settings) {
    $enabled = Features::enabled_map();
    $cats    = Features::categories();
    $by_cat  = [];
    foreach (Features::all() as $f) {
      $by_cat[$f['category']][] = $f;
    }
    ?>
    <div class="fv-a11y-card">
      <h2><?php esc_html_e('בחירת יכולות', 'fv-accessibility'); ?></h2>
      <p class="description">
        <?php esc_html_e('סמנו אילו יכולות יוצגו בתפריט הנגישות באתר. ההתנהגות עצמה של כל יכולת נטענת בהדרגה במודולים הבאים — כך שעל אתר ספציפי ניתן כבר עכשיו לכוון את היכולות שיופיעו ללקוח הקצה לכשיוטמעו.', 'fv-accessibility'); ?>
      </p>
      <?php foreach ($cats as $cat_id => $cat_label): if (empty($by_cat[$cat_id])) continue; ?>
        <div class="fv-a11y-card-inner">
          <h3><?php echo esc_html($cat_label); ?></h3>
          <div class="fv-a11y-feature-grid">
            <?php foreach ($by_cat[$cat_id] as $f): ?>
              <label class="fv-a11y-toggle">
                <input type="checkbox"
                       name="features[<?php echo esc_attr($f['id']); ?>]"
                       value="1"
                       <?php checked(!empty($enabled[$f['id']])); ?>>
                <span><?php echo esc_html($f['label']); ?></span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <?php
  }

  private static function render_statement_tab($settings) {
    $stmt = $settings['statement'];
    $stmt_pid  = (int) ($stmt['page_id'] ?? 0);
    $stmt_link = $stmt_pid ? get_edit_post_link($stmt_pid) : '';
    ?>
    <div class="fv-a11y-card">
      <h2><?php esc_html_e('פרטי רכז/ת הנגישות', 'fv-accessibility'); ?></h2>
      <p class="description">
        <?php esc_html_e('פרטים אלה מופיעים בעמוד הצהרת הנגישות וגם משמשים כיעד למייל מטופס הפניות. שדות אלו נדרשים בתקנה 35.', 'fv-accessibility'); ?>
      </p>
      <div class="fv-a11y-grid-2">
        <div>
          <label><?php esc_html_e('שם מלא', 'fv-accessibility'); ?></label>
          <input type="text" name="statement[coordinator_name]" value="<?php echo esc_attr($stmt['coordinator_name']); ?>" class="regular-text">
        </div>
        <div>
          <label><?php esc_html_e('תפקיד', 'fv-accessibility'); ?></label>
          <input type="text" name="statement[coordinator_role]" value="<?php echo esc_attr($stmt['coordinator_role']); ?>" class="regular-text">
        </div>
        <div>
          <label><?php esc_html_e('אימייל', 'fv-accessibility'); ?></label>
          <input type="email" name="statement[coordinator_email]" value="<?php echo esc_attr($stmt['coordinator_email']); ?>" class="regular-text" dir="ltr">
        </div>
        <div>
          <label><?php esc_html_e('טלפון', 'fv-accessibility'); ?></label>
          <input type="text" name="statement[coordinator_phone]" value="<?php echo esc_attr($stmt['coordinator_phone']); ?>" class="regular-text" dir="ltr">
        </div>
        <div>
          <label><?php esc_html_e('שם העסק / האתר', 'fv-accessibility'); ?></label>
          <input type="text" name="statement[business_name]" value="<?php echo esc_attr($stmt['business_name']); ?>" class="regular-text">
        </div>
      </div>
    </div>

    <div class="fv-a11y-card">
      <h2><?php esc_html_e('פטורים', 'fv-accessibility'); ?></h2>
      <p class="description">
        <?php esc_html_e('פירוט פטורים מהתאמות נגישות שניתנו לפי החוק (אם רלוונטי). השאירו ריק אם אין פטורים.', 'fv-accessibility'); ?>
      </p>
      <textarea name="statement[exemption_text]" rows="4" class="large-text" dir="rtl"><?php echo esc_textarea($stmt['exemption_text']); ?></textarea>
    </div>

    <div class="fv-a11y-card">
      <h2><?php esc_html_e('עמוד ההצהרה', 'fv-accessibility'); ?></h2>
      <?php if ($stmt_pid && get_post_status($stmt_pid) === 'publish'): ?>
        <p>
          <?php esc_html_e('עמוד ההצהרה נוצר אוטומטית והוא משתמש בקיצור', 'fv-accessibility'); ?>
          <code dir="ltr">[fv_accessibility_statement]</code>.
          <a href="<?php echo esc_url($stmt_link); ?>"><?php esc_html_e('עריכת העמוד', 'fv-accessibility'); ?></a>
          ·
          <a href="<?php echo esc_url(get_permalink($stmt_pid)); ?>" target="_blank" rel="noopener"><?php esc_html_e('צפייה', 'fv-accessibility'); ?></a>
        </p>
      <?php else: ?>
        <p><?php esc_html_e('לא נמצא עמוד הצהרה. הוא ייווצר אוטומטית בהפעלה הבאה של התוסף, או שניתן ליצור עמוד חדש ולהדביק בתוכו את הקיצור', 'fv-accessibility'); ?>
          <code dir="ltr">[fv_accessibility_statement]</code>.
        </p>
      <?php endif; ?>
    </div>
    <?php
  }

  private static function render_compliance_tab($settings) {
    $nonce = wp_create_nonce(Compliance::NONCE);
    ?>
    <div class="fv-a11y-card">
      <h2><?php esc_html_e('בדיקת תקינות', 'fv-accessibility'); ?></h2>
      <p class="description">
        <?php esc_html_e('הסורק עובר על דף הבית ועל מספר עמודים אחרונים, ובוחן בעיות נפוצות מתקן ת"י 5568 / WCAG 2.1: תמונות ללא alt, היררכיית כותרות, שדות טופס ללא label, מאפיין lang ב‑<html>, קישור "דלג לתוכן", וקישורים גנריים. בדיקת ניגודיות (1.4.3) דורשת חישוב סגנונות בצד הלקוח ולא נכללת בסריקה זו — מומלץ להפעיל את התוסף axe DevTools בדפדפן לבדיקה מקיפה.', 'fv-accessibility'); ?>
      </p>
      <p>
        <button type="button" class="button button-primary" id="fv-a11y-scan-run">
          <?php esc_html_e('הפעל סריקה', 'fv-accessibility'); ?>
        </button>
        <span class="spinner" style="float:none;margin:0 8px;"></span>
      </p>
      <div id="fv-a11y-scan-result" class="fv-a11y-scan-result" hidden></div>
    </div>
    <script>
    (function () {
      var btn = document.getElementById('fv-a11y-scan-run');
      var out = document.getElementById('fv-a11y-scan-result');
      if (!btn || !out) return;
      btn.addEventListener('click', function () {
        btn.disabled = true;
        var spinner = btn.parentNode.querySelector('.spinner');
        if (spinner) spinner.classList.add('is-active');
        out.hidden = true;
        var fd = new FormData();
        fd.append('action', '<?php echo esc_js(Compliance::ACTION); ?>');
        fd.append('nonce',  '<?php echo esc_js($nonce); ?>');
        fetch(ajaxurl, { method: 'POST', body: fd, credentials: 'same-origin' })
          .then(function (r) { return r.json(); })
          .then(function (j) {
            btn.disabled = false;
            if (spinner) spinner.classList.remove('is-active');
            if (!j.success) { out.innerHTML = '<p style="color:#b91c1c">' + (j.data && j.data.message || 'Error') + '</p>'; out.hidden = false; return; }
            renderReport(j.data);
            out.hidden = false;
          })
          .catch(function () {
            btn.disabled = false;
            if (spinner) spinner.classList.remove('is-active');
            out.innerHTML = '<p style="color:#b91c1c">Network error</p>';
            out.hidden = false;
          });
      });

      function renderReport(data) {
        var html = '<h3>תוצאות סריקה</h3>';
        html += '<p class="description">' + esc(data.note) + '</p>';
        var totalIssues = 0;
        var pages = Object.keys(data.report);
        for (var i = 0; i < pages.length; i++) {
          var key = pages[i];
          var rec = data.report[key];
          var url = data.urls[i];
          html += '<div class="fv-a11y-scan-page"><h4>' + esc(url) + '</h4>';
          if (rec.error) {
            html += '<p style="color:#b91c1c">שגיאה: ' + esc(rec.error) + '</p>';
          } else if (!rec.issues || !rec.issues.length) {
            html += '<p style="color:#065f46">לא נמצאו בעיות.</p>';
          } else {
            html += '<table class="widefat striped"><thead><tr><th>חומרה</th><th>WCAG</th><th>תיאור</th><th>דוגמה</th></tr></thead><tbody>';
            for (var j = 0; j < rec.issues.length; j++) {
              var iss = rec.issues[j]; totalIssues++;
              var sev = iss.severity === 'error' ? '🔴 שגיאה' : iss.severity === 'warn' ? '🟡 אזהרה' : 'ℹ️ מידע';
              html += '<tr><td>' + sev + '</td><td>' + esc(iss.wcag) + '</td><td>' + esc(iss.msg) + '</td><td><code>' + esc(iss.sample) + '</code></td></tr>';
            }
            html += '</tbody></table>';
          }
          html += '</div>';
        }
        html = '<p><strong>נמצאו ' + totalIssues + ' בעיות סך הכול.</strong></p>' + html;
        out.innerHTML = html;
      }
      function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]; }); }
    })();
    </script>
    <?php
  }

  private static function render_advanced_tab($settings) {
    $adv = $settings['advanced'];
    ?>
    <div class="fv-a11y-card">
      <h2><?php esc_html_e('עמודים מוחרגים', 'fv-accessibility'); ?></h2>
      <p class="description"><?php esc_html_e('בעמודים המסומנים, כפתור הנגישות לא יופיע (שימושי לדפי תשלום, לדוגמה).', 'fv-accessibility'); ?></p>
      <?php
      $pages = get_pages([
        'sort_column' => 'post_title',
        'sort_order'  => 'ASC',
        'number'      => 200,
      ]);
      $excluded = (array) ($adv['exclude_pages'] ?? []);
      ?>
      <div class="fv-a11y-exclude-list">
        <?php foreach ($pages as $page): ?>
          <label class="fv-a11y-toggle">
            <input type="checkbox" name="advanced[exclude_pages][]" value="<?php echo esc_attr($page->ID); ?>" <?php checked(in_array($page->ID, $excluded, true)); ?>>
            <span><?php echo esc_html($page->post_title); ?></span>
          </label>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="fv-a11y-card">
      <h2><?php esc_html_e('CSS מותאם אישית', 'fv-accessibility'); ?></h2>
      <p class="description"><?php esc_html_e('CSS שיוזרם בכל עמוד באתר, מיועד להתאמות עיצוב מתקדמות. תגיות HTML מוסרות בעת השמירה.', 'fv-accessibility'); ?></p>
      <textarea name="advanced[custom_css]" rows="6" class="large-text code" dir="ltr"><?php echo esc_textarea($adv['custom_css'] ?? ''); ?></textarea>
    </div>

    <div class="fv-a11y-card">
      <h2><?php esc_html_e('הסרת נתונים', 'fv-accessibility'); ?></h2>
      <label class="fv-a11y-toggle">
        <input type="checkbox" name="advanced[cleanup_on_uninstall]" value="1" <?php checked(!empty($adv['cleanup_on_uninstall'])); ?>>
        <span><?php esc_html_e('בעת הסרת התוסף — מחק את ההגדרות מבסיס הנתונים (לא ניתן לשחזור)', 'fv-accessibility'); ?></span>
      </label>
    </div>
    <?php
  }
}
