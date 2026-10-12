<?php
/**
 * View: a plain list of links, for a shortcode like [announcement view="list"]. Several lists are one list.
 *
 * @var array $links Each one: title, url. The url is '' for a link without an address.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<ul class="trigrexam-view-list">
<?php foreach ( $links as $link ) : ?>
  <li><?php if ( $link['url'] !== '' ) : ?><a href="<?php echo esc_url( $link['url'] ); ?>"><?php echo esc_html( $link['title'] ); ?></a><?php else : ?><?php echo esc_html( $link['title'] ); ?><?php endif; ?></li>
<?php endforeach; ?>
</ul>
