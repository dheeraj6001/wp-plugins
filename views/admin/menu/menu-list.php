<?php
/**
 * View: the admin page of a list of links, like TrigrExam > Announcements. On the left the pages and
 * the custom link that can be added, on the right the items of the list, like Appearance > Menus.
 *
 * @var string    $title       Title of the list.
 * @var string    $shortcode   Tag of the shortcode that shows the list.
 * @var string    $rows        Rendered items of the list.
 * @var string    $template    Rendered empty item, which the script copies for a new one.
 * @var WP_Post[] $pages       The pages that can be added.
 * @var string    $action      Address the list is posted to.
 * @var string    $save_action Name of the action that saves the list.
 * @var bool      $saved       The list was just saved.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap">
  <h1><?php echo esc_html( $title ); ?></h1>
  <p><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=trigrexam' ) ); ?>">&larr; Back</a></p>
<?php if ( $saved ) : ?>
  <div class="notice notice-success is-dismissible"><p>The list is saved.</p></div>
<?php endif; ?>
  <p>The <code>[<?php echo esc_html( $shortcode ); ?>]</code> shortcode shows these links, in this order. Add a page or a custom link, drag the items into order, then save.</p>
  <p>To show them as another component, name it in the shortcode: <code>[<?php echo esc_html( $shortcode ); ?> view="table"]</code>. The components are <code>marquee</code> (one scrolling line), <code>list</code>, <code>lists</code> (a heading with the links under it), <code>table</code>, <code>card</code>, <code>toggle</code> and <code>custom-layout</code> (the columns of the footer).</p>

  <div class="trigrexam-menu">
    <div class="trigrexam-add">
      <div class="trigrexam-box">
        <h2>Pages</h2>
        <div class="trigrexam-box-inside">
<?php if ( $pages ) : ?>
          <p><input type="search" class="widefat" id="trigrexam-pages-search" placeholder="Search pages" aria-label="Search pages"></p>
          <ul class="trigrexam-pages">
<?php foreach ( $pages as $page ) : ?>
            <li><label><input type="checkbox" value="<?php echo (int) $page->ID; ?>" data-title="<?php echo esc_attr( $page->post_title ); ?>" data-url="<?php echo esc_url( get_permalink( $page ) ); ?>"> <?php echo esc_html( $page->post_title !== '' ? $page->post_title : '(no title)' ); ?></label></li>
<?php endforeach; ?>
          </ul>
          <button type="button" class="button" id="trigrexam-add-pages">Add to <?php echo esc_html( $title ); ?></button>
<?php else : ?>
          <p>There are no published pages.</p>
<?php endif; ?>
        </div>
      </div>
      <div class="trigrexam-box">
        <h2>Custom Link</h2>
        <div class="trigrexam-box-inside">
          <p><label>URL<br><input type="text" class="widefat code" id="trigrexam-custom-url" placeholder="https://"></label></p>
          <p><label>Title<br><input type="text" class="widefat" id="trigrexam-custom-title"></label></p>
          <button type="button" class="button" id="trigrexam-add-custom">Add to <?php echo esc_html( $title ); ?></button>
        </div>
      </div>
    </div>

    <form class="trigrexam-list" method="post" action="<?php echo esc_url( $action ); ?>">
      <input type="hidden" name="action" value="<?php echo esc_attr( $save_action ); ?>">
<?php wp_nonce_field( $save_action ); ?>
      <ul class="trigrexam-items" id="trigrexam-items"><?php echo $rows; ?></ul>
      <p class="trigrexam-empty">There is nothing in this list yet. Add a page or a custom link from the left.</p>
<?php submit_button( 'Save ' . $title ); ?>
    </form>
  </div>
</div>
<script type="text/html" id="trigrexam-item-template"><?php echo $template; ?></script>
