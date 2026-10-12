<?php
/**
 * View: several lists of links together, for a shortcode like [iitjam_announcement neet_announcement].
 *
 * @var array $lists Key of the list => title (of the list), links. Each link: title, url.
 *                   The url is '' for a link without an address.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="menu-lists">
<?php foreach ( $lists as $key => $list ) : ?>
  <div class="menu-lists-list menu-lists-<?php echo esc_attr( $key ); ?>">
    <h3><?php echo esc_html( $list['title'] ); ?></h3>
    <ul>
<?php foreach ( $list['links'] as $link ) : ?>
      <li><?php if ( $link['url'] !== '' ) : ?><a href="<?php echo esc_url( $link['url'] ); ?>"><?php echo esc_html( $link['title'] ); ?></a><?php else : ?><?php echo esc_html( $link['title'] ); ?><?php endif; ?></li>
<?php endforeach; ?>
    </ul>
  </div>
<?php endforeach; ?>
</div>
