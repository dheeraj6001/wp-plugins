<?php
/**
 * View: the lists as the columns of the footer, for a shortcode like [about download others view="custom-layout"].
 * Each list is one column: its title as the heading and its links under it, with the HTML and the classes of the
 * footer of the Career Endeavour theme (parts/footer-2.php), so they look as its own columns did. There is no
 * element around the columns: they stand in the row of the footer, beside its "Contact us" column.
 * This is the file to change for another layout.
 *
 * @var array $lists Key of the list => title (of the list), links. Each link: title, url.
 *                   The url is '' for a link without an address.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<?php foreach ( $lists as $key => $list ) : ?>
<div class="trigrexam-view-column trigrexam-view-<?php echo esc_attr( $key ); ?>">
  <div class="box-layout">
    <h5 class="subtitle"><?php echo esc_html( $list['title'] ); ?></h5>
    <ul class="menu">
<?php foreach ( $list['links'] as $link ) : ?>
      <li class="menu-item"><?php if ( $link['url'] !== '' ) : ?><a href="<?php echo esc_url( $link['url'] ); ?>"><?php echo esc_html( $link['title'] ); ?></a><?php else : ?><?php echo esc_html( $link['title'] ); ?><?php endif; ?></li>
<?php endforeach; ?>
    </ul>
  </div>
</div>
<?php endforeach; ?>
