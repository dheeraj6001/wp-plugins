<?php
/**
 * View: one item of a list of links on its admin page. The bar is dragged to move it; "Edit" opens its fields.
 *
 * @var string $key        Key of the item in the posted form.
 * @var array  $item       title, page_id, url.
 * @var string $page_title Title of its page, or ''.
 * @var string $page_url   Address of its page, or ''.
 * @var bool   $missing    Its page is deleted or not published, so the site does not show the item.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$name = 'items[' . $key . ']';
?>
<li class="trigrexam-item <?php echo $item['page_id'] ? 'trigrexam-item-page' : 'trigrexam-item-custom'; ?>">
  <div class="trigrexam-item-bar">
    <span class="trigrexam-item-title"><?php echo esc_html( $item['title'] ); ?></span>
    <span class="trigrexam-item-type"><?php echo $item['page_id'] ? 'Page' : 'Custom Link'; ?></span>
    <button type="button" class="button-link trigrexam-item-toggle" aria-expanded="false">Edit</button>
  </div>
  <div class="trigrexam-item-settings" hidden>
    <input type="hidden" class="trigrexam-item-page-id" name="<?php echo esc_attr( $name ); ?>[page_id]" value="<?php echo (int) $item['page_id']; ?>">
    <p><label>Title<br>
      <input type="text" class="widefat trigrexam-item-title-input" name="<?php echo esc_attr( $name ); ?>[title]" value="<?php echo esc_attr( $item['title'] ); ?>"></label></p>
    <p class="trigrexam-item-url"><label>URL<br>
      <input type="text" class="widefat code trigrexam-item-url-input" name="<?php echo esc_attr( $name ); ?>[url]" value="<?php echo esc_attr( $item['url'] ); ?>"></label></p>
    <p class="trigrexam-item-original">Page: <a class="trigrexam-item-page-link" href="<?php echo esc_url( $page_url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $page_title ); ?></a>
<?php if ( $missing ) : ?>
      <span class="trigrexam-item-missing">This page is deleted or not published, so the site does not show this item.</span>
<?php endif; ?>
    </p>
    <p><button type="button" class="button-link button-link-delete trigrexam-item-remove">Remove</button></p>
  </div>
</li>
