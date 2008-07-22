<?php
// $Id$

/**
 * @file flag.tpl.php
 * Default theme implementation to display a flag link, and a message after the action 
 * is carried out.
 *
 * Available variables:
 *
 * - $flag: The flag object itself. You will only need to use it when the
 *   following variables don't suffice.
 * - $flag_name_css: The flag name, with all "_" replaced with "-". For use in 'class'
 *   attributes.
 *
 * - $action: The action the link is about to carry out, either "flag" or "unflag".
 * - $last_action: The action, as a passive English verb, either "flagged" or 
 *   "unflagged", that led to the current status of the flag.
 *
 * - $link_href: The URL for the flag link.
 * - $link_text: The text to show for the link.
 * - $link_title: The title attribute for the link.
 *
 * - $message_text: The long message to show after a flag action has been carried out.
 * - $after_flagging: This template is called for the link both before and after being
 *   flagged. If displaying to the user immediately after flagging, this value
 *   will be boolean TRUE. This is usually used in conjunction with immedate
 *   JavaScript-based toggling of flags.
 * - $setup: TRUE when this template is parsed for the first time; Use this 
 *   flag to carry out procedures that are needed only once; e.g., linking to CSS 
 *   and JS files.
 *
 * NOTE: This template spaces out the <span> tags for clarity only. When doing some
 * advanced theming you may have to remove all the whitespace.
 */
?>
<?php
  if ($setup) {
    drupal_add_css(drupal_get_path('module', 'flag') .'/theme/flag.css');
    drupal_add_js(drupal_get_path('module', 'flag') .'/theme/flag.js');
  }
  flag_add_extra_js($flag, $action, $content_id, $after_flagging);
?>
<span class="flag-wrapper flag-<?php echo $flag_name_css; ?>">
  <a href="<?php echo $link_href; ?>" title="<?php echo $link_title; ?>" class="flag <?php echo $action; ?>-action <?php echo $after_flagging ? $last_action : ''; ?>"><?php echo strip_tags($link_text); ?></a>
  <?php if ($after_flagging): ?>
    <span class="flag-message flag-<?php echo $last_action; ?>-message">
      <?php echo $message_text; ?>
    </span>
  <?php endif; ?>
</span>
