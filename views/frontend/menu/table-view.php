<?php
/**
 * View: the links as a table, for a shortcode like [announcement view="table"]: a number, the title and a
 * button that opens the link. Several lists are several tables, each under the title of its list.
 *
 * @var array $lists Key of the list => title (of the list), links. Each link: title, url.
 *                   The url is '' for a link without an address.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="trigrexam-view-tables">
<?php foreach ( $lists as $key => $list ) : ?>
  <table class="trigrexam-view-table trigrexam-view-<?php echo esc_attr( $key ); ?>">
<?php if ( count( $lists ) > 1 ) : ?>
    <caption><?php echo esc_html( $list['title'] ); ?></caption>
<?php endif; ?>
    <thead>
      <tr><th scope="col">#</th><th scope="col">Title</th><th scope="col">Link</th></tr>
    </thead>
    <tbody>
<?php foreach ( array_values( $list['links'] ) as $number => $link ) : ?>
      <tr>
        <td><?php echo (int) $number + 1; ?></td>
        <td><?php echo esc_html( $link['title'] ); ?></td>
        <td><?php if ( $link['url'] !== '' ) : ?><a class="trigrexam-view-button" href="<?php echo esc_url( $link['url'] ); ?>">View</a><?php endif; ?></td>
      </tr>
<?php endforeach; ?>
    </tbody>
  </table>
<?php endforeach; ?>
</div>
