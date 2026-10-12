<?php
/**
 * View: the links as cards beside each other, for a shortcode like [announcement view="card"].
 * Several lists are several rows of cards, each under the title of its list.
 *
 * @var array $lists Key of the list => title (of the list), links. Each link: title, url.
 *                   The url is '' for a link without an address.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="trigrexam-view-cards-lists">
<?php foreach ( $lists as $key => $list ) : ?>
<?php if ( count( $lists ) > 1 ) : ?>
  <h3><?php echo esc_html( $list['title'] ); ?></h3>
<?php endif; ?>
  <div class="trigrexam-view-cards trigrexam-view-<?php echo esc_attr( $key ); ?>">
<?php foreach ( $list['links'] as $link ) : ?>
<?php if ( $link['url'] !== '' ) : ?>
    <a class="trigrexam-view-card" href="<?php echo esc_url( $link['url'] ); ?>"><?php echo esc_html( $link['title'] ); ?></a>
<?php else : ?>
    <span class="trigrexam-view-card"><?php echo esc_html( $link['title'] ); ?></span>
<?php endif; ?>
<?php endforeach; ?>
  </div>
<?php endforeach; ?>
</div>
