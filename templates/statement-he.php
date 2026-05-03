<?php
/**
 * Hebrew accessibility statement template. Rendered by the
 * `[fv_accessibility_statement]` shortcode.
 *
 * @var array $stmt     Statement settings block from `Settings::get()`.
 * @var array $settings Full settings array.
 */
defined('ABSPATH') || exit;

$business_name = !empty($stmt['business_name']) ? $stmt['business_name'] : get_bloginfo('name');
$last_updated  = !empty($stmt['last_updated']) ? date_i18n('d/m/Y', strtotime($stmt['last_updated'])) : date_i18n('d/m/Y');
?>
<div class="fv-a11y-statement" dir="rtl">
  <h2><?php esc_html_e('הצהרת נגישות', 'fv-accessibility'); ?></h2>

  <p>
    <strong><?php echo esc_html($business_name); ?></strong>
    <?php esc_html_e('רואה חשיבות עליונה בכך שהאתר יהיה נגיש לכלל הציבור, לרבות אנשים עם מוגבלות, ופועל בהתאם לתקן הישראלי ת"י 5568 ברמת AA (מבוסס WCAG 2.1) ולתקנה 35 לתקנות שוויון זכויות לאנשים עם מוגבלות (התאמות נגישות לשירות), התשע"ג‑2013.', 'fv-accessibility'); ?>
  </p>

  <h3><?php esc_html_e('תקן הנגישות', 'fv-accessibility'); ?></h3>
  <p><?php esc_html_e('האתר נבדק ועומד בדרישות התקן הישראלי ת"י 5568 ברמת AA, בהתבסס על הנחיות הבינלאומיות WCAG 2.1.', 'fv-accessibility'); ?></p>

  <h3><?php esc_html_e('התאמות שבוצעו באתר', 'fv-accessibility'); ?></h3>
  <ul>
    <li><?php esc_html_e('הותקן תפריט נגישות שיוצר התאמות אמיתיות ב‑DOM (ולא overlay): שינוי גודל טקסט, מצבי ניגודיות, סמן מוגבר, גופן ידידותי לדיסלקסיה ועוד.', 'fv-accessibility'); ?></li>
    <li><?php esc_html_e('כל אזורי האתר ניתנים לתפעול באמצעות מקלדת בלבד, עם חיווי פוקוס ברור.', 'fv-accessibility'); ?></li>
    <li><?php esc_html_e('כותרות וסמנטיקה נכונה, ניגודיות צבעים בהתאם לרמת AA, וטקסטים חלופיים לתמונות.', 'fv-accessibility'); ?></li>
    <li><?php esc_html_e('תמיכה בתוכנות הקראה (Screen Readers) כגון NVDA, JAWS ו‑VoiceOver.', 'fv-accessibility'); ?></li>
    <li><?php esc_html_e('הימנעות מהבהובים מעל 3Hz בהתאם לסעיף 2.3.1 של WCAG.', 'fv-accessibility'); ?></li>
  </ul>

  <h3><?php esc_html_e('פטורים', 'fv-accessibility'); ?></h3>
  <?php if (!empty($stmt['exemption_text'])): ?>
    <p><?php echo wp_kses_post(nl2br(esc_html($stmt['exemption_text']))); ?></p>
  <?php else: ?>
    <p><?php esc_html_e('לא קיימים פטורים ידועים נכון לעדכון האחרון.', 'fv-accessibility'); ?></p>
  <?php endif; ?>

  <h3><?php esc_html_e('פניות בנושא נגישות', 'fv-accessibility'); ?></h3>
  <p><?php esc_html_e('בכל בעיה, הצעה לשיפור או פנייה בנושא נגישות, ניתן לפנות לרכז/ת הנגישות:', 'fv-accessibility'); ?></p>
  <ul class="fv-a11y-coordinator">
    <?php if (!empty($stmt['coordinator_name'])): ?>
      <li><strong><?php esc_html_e('שם:', 'fv-accessibility'); ?></strong> <?php echo esc_html($stmt['coordinator_name']); ?></li>
    <?php endif; ?>
    <?php if (!empty($stmt['coordinator_role'])): ?>
      <li><strong><?php esc_html_e('תפקיד:', 'fv-accessibility'); ?></strong> <?php echo esc_html($stmt['coordinator_role']); ?></li>
    <?php endif; ?>
    <?php if (!empty($stmt['coordinator_email'])): ?>
      <li><strong><?php esc_html_e('אימייל:', 'fv-accessibility'); ?></strong>
        <a href="mailto:<?php echo esc_attr($stmt['coordinator_email']); ?>" dir="ltr">
          <?php echo esc_html($stmt['coordinator_email']); ?>
        </a>
      </li>
    <?php endif; ?>
    <?php if (!empty($stmt['coordinator_phone'])): ?>
      <li><strong><?php esc_html_e('טלפון:', 'fv-accessibility'); ?></strong>
        <a href="tel:<?php echo esc_attr(preg_replace('/[^\d+]/', '', $stmt['coordinator_phone'])); ?>" dir="ltr">
          <?php echo esc_html($stmt['coordinator_phone']); ?>
        </a>
      </li>
    <?php endif; ?>
  </ul>

  <h3><?php esc_html_e('דווחו על בעיית נגישות', 'fv-accessibility'); ?></h3>
  <?php echo do_shortcode('[fv_accessibility_feedback]'); ?>

  <p class="fv-a11y-statement-meta">
    <em><?php printf(
      /* translators: %s: date */
      esc_html__('עודכן לאחרונה: %s', 'fv-accessibility'),
      esc_html($last_updated)
    ); ?></em>
  </p>

  <p class="fv-a11y-statement-disclaimer">
    <small><?php esc_html_e('הערה: עמידה בתקן הנגישות אינה תחליף לאתר נגיש מלכתחילה. תפריט הנגישות הוא כלי משלים שמתבצעות בו התאמות אמיתיות ב‑DOM, לצד מאמץ מתמשך לכתוב קוד וטקסט נגישים ביסודם. פסיקות בית המשפט המחוזי בתל‑אביב (2022 ואילך) קבעו במפורש כי שכבת overlay לבדה אינה ממלאת את דרישות תקן 5568.', 'fv-accessibility'); ?></small>
  </p>
</div>
