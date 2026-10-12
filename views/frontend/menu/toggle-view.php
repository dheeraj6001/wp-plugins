<?php
/**
 * View: each list as a toggle, for a shortcode like [download syllabus view="toggle"]: a click on the title
 * of a list opens and closes its links. The first one is open. It needs no script: the browser does it.
 *
 * @var array $lists Key of the list => title (of the list), links. Each link: title, url.
 *                   The url is '' for a link without an address.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$first = true;
?>
<div class="trigrexam-view-toggles">
<?php foreach ( $lists as $key => $list ) : ?>
  <details class="trigrexam-view-toggle trigrexam-view-<?php echo esc_attr( $key ); ?>"<?php echo $first ? ' open' : ''; ?>>
    <summary><?php echo esc_html( $list['title'] ); ?></summary>
    <ul>
<?php foreach ( $list['links'] as $link ) : ?>
      <li><?php if ( $link['url'] !== '' ) : ?><a href="<?php echo esc_url( $link['url'] ); ?>"><?php echo esc_html( $link['title'] ); ?></a><?php else : ?><?php echo esc_html( $link['title'] ); ?><?php endif; ?></li>
<?php endforeach; ?>
    </ul>
  </details>
<?php $first = false; ?>
<?php endforeach; ?>
</div>
