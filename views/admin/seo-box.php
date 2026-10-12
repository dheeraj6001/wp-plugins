<?php
/**
 * View: the "SEO Options" box on the screen that edits a page or a post.
 *
 * @var string $description Description of the page for search engines, or ''.
 * @var string $keywords    Its keywords, with commas between them, or ''.
 * @var string $nonce       Rendered nonce field of the box.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<?php echo $nonce; ?>
<table width="100%">
  <tr>
    <td><label for="trigrexam_seo_description"><b>Seo Description:</b></label></td>
    <td width="60%">
      <textarea class="widefat" id="trigrexam_seo_description" name="trigrexam_seo[description]"><?php echo esc_textarea( $description ); ?></textarea>
    </td>
  </tr>
  <tr>
    <td><label for="trigrexam_seo_keywords"><b>Seo Keywords:</b><br>(Comma separated. Example: key, word, sample, seo)</label></td>
    <td width="60%">
      <textarea class="widefat" id="trigrexam_seo_keywords" name="trigrexam_seo[keywords]"><?php echo esc_textarea( $keywords ); ?></textarea>
    </td>
  </tr>
</table>
