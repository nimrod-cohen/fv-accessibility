<?php
namespace FVAccessibility;

defined('ABSPATH') || exit;

/**
 * Accessibility-feedback channel — required by Regulation 35.
 *
 * Two surfaces:
 *   - shortcode `[fv_accessibility_feedback]` (used inside the statement
 *     page and embeddable elsewhere)
 *   - inline form rendered inside the floating-button drawer panel
 *
 * Both submit via the same admin-ajax action. We email the configured
 * coordinator (falling back to admin_email so submissions never disappear).
 */
class Feedback {
  const ACTION = 'fv_a11y_feedback';
  const NONCE  = 'fv_a11y_feedback_nonce';

  public static function init() {
    add_shortcode('fv_accessibility_feedback', [__CLASS__, 'render']);
    add_action('wp_ajax_'        . self::ACTION, [__CLASS__, 'handle_ajax']);
    add_action('wp_ajax_nopriv_' . self::ACTION, [__CLASS__, 'handle_ajax']);
  }

  public static function render($atts = []) {
    $atts = shortcode_atts(['variant' => 'inline'], $atts, 'fv_accessibility_feedback');
    $nonce = wp_create_nonce(self::NONCE);
    $page_url = is_singular() ? get_permalink() : home_url(add_query_arg([], $_SERVER['REQUEST_URI'] ?? ''));
    ob_start();
    ?>
    <form class="fv-a11y-feedback-form" data-variant="<?php echo esc_attr($atts['variant']); ?>" novalidate dir="rtl">
      <input type="hidden" name="nonce" value="<?php echo esc_attr($nonce); ?>">
      <input type="hidden" name="page_url" value="<?php echo esc_attr($page_url); ?>">
      <p class="fv-a11y-fb-desc">
        <?php esc_html_e('נשמח לשמוע על כל קושי שנתקלתם בו. תיאור מפורט יעזור לנו לטפל בעניין במהירות.', 'fv-accessibility'); ?>
      </p>
      <label>
        <span><?php esc_html_e('שם מלא', 'fv-accessibility'); ?></span>
        <input type="text" name="name" required autocomplete="name">
      </label>
      <label>
        <span><?php esc_html_e('אימייל', 'fv-accessibility'); ?></span>
        <input type="email" name="email" required dir="ltr" autocomplete="email">
      </label>
      <label>
        <span><?php esc_html_e('תיאור הבעיה', 'fv-accessibility'); ?></span>
        <textarea name="message" required rows="4"></textarea>
      </label>
      <button type="submit" class="fv-a11y-fb-submit">
        <span class="fv-a11y-fb-submit-text"><?php esc_html_e('שלח', 'fv-accessibility'); ?></span>
      </button>
      <div class="fv-a11y-fb-status" role="status" aria-live="polite"></div>
    </form>
    <?php
    return ob_get_clean();
  }

  public static function handle_ajax() {
    check_ajax_referer(self::NONCE, 'nonce');

    $name    = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
    $email   = sanitize_email(wp_unslash($_POST['email'] ?? ''));
    $message = sanitize_textarea_field(wp_unslash($_POST['message'] ?? ''));
    $url     = esc_url_raw(wp_unslash($_POST['page_url'] ?? ''));

    if ($name === '' || $message === '' || !is_email($email)) {
      wp_send_json_error([
        'message' => __('נא למלא את כל השדות בצורה תקינה.', 'fv-accessibility'),
      ], 400);
    }

    $settings = Settings::get();
    $to = !empty($settings['statement']['coordinator_email']) && is_email($settings['statement']['coordinator_email'])
      ? $settings['statement']['coordinator_email']
      : get_option('admin_email');

    $subject = sprintf(
      /* translators: %s: site name */
      __('[%s] פנייה חדשה דרך טופס הנגישות', 'fv-accessibility'),
      get_bloginfo('name')
    );

    $body  = __('התקבלה פנייה חדשה דרך טופס הנגישות באתר.', 'fv-accessibility') . "\n\n";
    $body .= __('שם:', 'fv-accessibility')          . " {$name}\n";
    $body .= __('אימייל:', 'fv-accessibility')       . " {$email}\n";
    $body .= __('כתובת העמוד:', 'fv-accessibility')  . " {$url}\n\n";
    $body .= __('תיאור:', 'fv-accessibility')        . "\n{$message}\n";

    $headers = [
      'Content-Type: text/plain; charset=UTF-8',
      sprintf('Reply-To: %s <%s>', $name, $email),
    ];

    $sent = wp_mail($to, $subject, $body, $headers);
    if (!$sent) {
      wp_send_json_error([
        'message' => __('שגיאה בשליחה. נסו שוב מאוחר יותר או פנו ישירות לרכז/ת הנגישות.', 'fv-accessibility'),
      ], 500);
    }
    wp_send_json_success([
      'message' => __('הפנייה נשלחה בהצלחה. תודה — נחזור אליכם בהקדם.', 'fv-accessibility'),
    ]);
  }
}
