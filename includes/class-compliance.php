<?php
namespace FVAccessibility;

defined('ABSPATH') || exit;

/**
 * Built-in compliance scanner. Crawls a sample of the site (home + a few
 * recent published posts/pages) and runs HTML-level WCAG checks against
 * the high-impact failure modes:
 *
 *   1.1.1 — Non-text content        (img missing alt)
 *   1.3.1 — Info and relationships  (form fields missing label, h1 missing,
 *                                     heading-level skips)
 *   2.4.1 — Bypass blocks            (no skip link)
 *   2.4.4 — Link purpose             (empty / "click here" links)
 *   3.1.1 — Language of page         (html missing lang)
 *
 * Contrast (1.4.3) is intentionally *not* checked here — doing it correctly
 * requires computed styles, which we don't have server-side. We surface the
 * advice in the result UI to install the axe DevTools browser extension
 * for that one rule.
 *
 * Results are summarized by rule with up to five offending selectors per
 * rule per page (so the report stays scannable without dumping every node).
 */
class Compliance {
  const ACTION = 'fv_a11y_scan';
  const NONCE  = 'fv_a11y_scan_nonce';

  public static function init() {
    add_action('wp_ajax_' . self::ACTION, [__CLASS__, 'handle_ajax']);
  }

  public static function handle_ajax() {
    if (!current_user_can('manage_options')) {
      wp_send_json_error(['message' => __('אין הרשאה.', 'fv-accessibility')], 403);
    }
    check_ajax_referer(self::NONCE, 'nonce');

    $urls = self::sample_urls();
    $report = [];
    foreach ($urls as $url) {
      $report[$url] = self::scan_url($url);
    }
    wp_send_json_success([
      'urls'   => array_values($urls),
      'report' => $report,
      'note'   => __('בדיקת ניגודיות (WCAG 1.4.3) דורשת חישוב סגנונות בצד הלקוח — לא נכלל בסריקה זו. מומלץ להפעיל לצד התוסף את התוסף "axe DevTools" בדפדפן לבדיקה מקיפה.', 'fv-accessibility'),
    ]);
  }

  private static function sample_urls() {
    $urls = ['home' => home_url('/')];
    $posts = get_posts([
      'post_type'      => ['page', 'post'],
      'post_status'    => 'publish',
      'numberposts'    => 5,
      'orderby'        => 'date',
      'order'          => 'DESC',
      'fields'         => 'ids',
    ]);
    foreach ($posts as $pid) {
      $urls[$pid] = get_permalink($pid);
    }
    return $urls;
  }

  private static function scan_url($url) {
    $r = wp_remote_get($url, [
      'timeout'   => 15,
      'sslverify' => false,
      'cookies'   => $_COOKIE, // Auth so private/draft pages render correctly.
    ]);
    if (is_wp_error($r)) {
      return ['error' => $r->get_error_message(), 'issues' => []];
    }
    $code = wp_remote_retrieve_response_code($r);
    if ($code < 200 || $code >= 400) {
      return ['error' => "HTTP $code", 'issues' => []];
    }
    $html = wp_remote_retrieve_body($r);
    return ['error' => null, 'issues' => self::run_checks($html)];
  }

  private static function run_checks($html) {
    $issues = [];
    if (!class_exists('DOMDocument')) return $issues;

    libxml_use_internal_errors(true);
    $doc = new \DOMDocument();
    $doc->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_NOWARNING | LIBXML_NOERROR);
    libxml_clear_errors();

    $xpath = new \DOMXPath($doc);

    // 3.1.1 — html lang attribute
    $htmls = $doc->getElementsByTagName('html');
    if ($htmls->length > 0) {
      $h = $htmls->item(0);
      if (!$h->hasAttribute('lang') || trim($h->getAttribute('lang')) === '') {
        $issues[] = ['rule' => 'html-has-lang', 'wcag' => '3.1.1', 'severity' => 'error',
                     'msg' => 'תג <html> ללא מאפיין lang', 'sample' => '<html>'];
      }
    }

    // 1.1.1 — img alt
    $missing_alt = [];
    foreach ($doc->getElementsByTagName('img') as $img) {
      if (!$img->hasAttribute('alt')) {
        $missing_alt[] = self::short_selector($img);
      }
    }
    if ($missing_alt) {
      $issues[] = ['rule' => 'image-alt', 'wcag' => '1.1.1', 'severity' => 'error',
                   'msg' => sprintf('%d תמונות ללא alt', count($missing_alt)),
                   'sample' => implode(', ', array_slice($missing_alt, 0, 5))];
    }

    // 1.3.1 — h1 missing
    $h1s = $doc->getElementsByTagName('h1');
    if ($h1s->length === 0) {
      $issues[] = ['rule' => 'no-h1', 'wcag' => '1.3.1', 'severity' => 'warn',
                   'msg' => 'בעמוד אין תג h1', 'sample' => ''];
    } elseif ($h1s->length > 1) {
      $issues[] = ['rule' => 'multiple-h1', 'wcag' => '1.3.1', 'severity' => 'info',
                   'msg' => sprintf('בעמוד יש %d תגי h1 (מומלץ אחד)', $h1s->length), 'sample' => ''];
    }

    // 1.3.1 — heading hierarchy skips (h2 → h4)
    $skips = [];
    $prev = 0;
    foreach ($xpath->query('//h1|//h2|//h3|//h4|//h5|//h6') as $h) {
      $level = (int) substr($h->nodeName, 1);
      if ($prev > 0 && $level > $prev + 1) {
        $skips[] = sprintf('h%d → h%d ("%s")', $prev, $level, mb_substr(trim($h->textContent), 0, 30));
      }
      $prev = $level;
    }
    if ($skips) {
      $issues[] = ['rule' => 'heading-order', 'wcag' => '1.3.1', 'severity' => 'warn',
                   'msg' => sprintf('דילוג ברמות כותרת (%d)', count($skips)),
                   'sample' => implode('; ', array_slice($skips, 0, 3))];
    }

    // 1.3.1 — form fields without label
    $unlabeled = [];
    foreach ($xpath->query('//input[not(@type="hidden") and not(@type="submit") and not(@type="button")]|//select|//textarea') as $field) {
      $id = $field->getAttribute('id');
      $aria = $field->getAttribute('aria-label') . $field->getAttribute('aria-labelledby');
      $has_label = false;
      if ($id) {
        $labels = $xpath->query("//label[@for='" . $id . "']");
        if ($labels->length > 0) $has_label = true;
      }
      if (!$has_label && $aria === '' && !self::is_descendant_of($field, 'label')) {
        $unlabeled[] = self::short_selector($field);
      }
    }
    if ($unlabeled) {
      $issues[] = ['rule' => 'form-label', 'wcag' => '1.3.1', 'severity' => 'error',
                   'msg' => sprintf('%d שדות טופס ללא label', count($unlabeled)),
                   'sample' => implode(', ', array_slice($unlabeled, 0, 5))];
    }

    // 2.4.1 — skip link presence (look for any anchor inside the first
    // 200 chars of <body> pointing to #main / #content / similar)
    $bodies = $doc->getElementsByTagName('body');
    $has_skip = false;
    if ($bodies->length > 0) {
      $first_links = $xpath->query('(//body//a)[position() <= 4]', $bodies->item(0));
      foreach ($first_links as $a) {
        $href = $a->getAttribute('href');
        if (preg_match('/^#(main|content|primary|skip|fv-a11y-skip-target)/i', $href)) {
          $has_skip = true;
          break;
        }
      }
    }
    if (!$has_skip) {
      $issues[] = ['rule' => 'skip-link', 'wcag' => '2.4.1', 'severity' => 'warn',
                   'msg' => 'לא נמצא קישור "דלג לתוכן" בתחילת העמוד',
                   'sample' => 'התוסף מזריק קישור כשתפעילו את "ניווט מקלדת" — אך מומלץ להוסיפו גם בתבנית עצמה'];
    }

    // 2.4.4 — empty / generic links
    $bad_links = [];
    foreach ($doc->getElementsByTagName('a') as $a) {
      if (!$a->hasAttribute('href')) continue;
      $text = trim($a->textContent);
      $aria = trim($a->getAttribute('aria-label'));
      if ($text === '' && $aria === '') {
        $bad_links[] = self::short_selector($a);
      } elseif (preg_match('/^(לחצו כאן|כאן|click here|here|read more|מידע נוסף|קרא עוד)$/iu', $text) && $aria === '') {
        $bad_links[] = self::short_selector($a) . ' ("' . mb_substr($text, 0, 30) . '")';
      }
    }
    if ($bad_links) {
      $issues[] = ['rule' => 'link-name', 'wcag' => '2.4.4', 'severity' => 'warn',
                   'msg' => sprintf('%d קישורים ריקים או גנריים', count($bad_links)),
                   'sample' => implode(', ', array_slice($bad_links, 0, 5))];
    }

    return $issues;
  }

  private static function short_selector(\DOMElement $el) {
    $s = $el->nodeName;
    if ($el->hasAttribute('id')) {
      $s .= '#' . $el->getAttribute('id');
    } elseif ($el->hasAttribute('class')) {
      $first = preg_split('/\s+/', trim($el->getAttribute('class')))[0] ?? '';
      if ($first) $s .= '.' . $first;
    } elseif ($el->hasAttribute('name')) {
      $s .= '[name="' . $el->getAttribute('name') . '"]';
    } elseif ($el->hasAttribute('src')) {
      $src = basename($el->getAttribute('src'));
      $s .= '[src=…' . mb_substr($src, 0, 24) . ']';
    }
    return $s;
  }

  private static function is_descendant_of(\DOMElement $node, $tag) {
    $p = $node->parentNode;
    while ($p && $p->nodeType === XML_ELEMENT_NODE) {
      if (strtolower($p->nodeName) === $tag) return true;
      $p = $p->parentNode;
    }
    return false;
  }
}
