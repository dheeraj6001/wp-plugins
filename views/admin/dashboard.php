<?php
/**
 * View: the dashboard of the plugin, the page a click on "TrigrExam" in the admin menu opens.
 * Every page of that menu is a card, which opens the page.
 *
 * @var array $cards Each one: title, url, icon (class of a dashicon).
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap">
  <h1>TrigrExam</h1>
  <div class="trigrexam-cards">
<?php foreach ( $cards as $card ) : ?>
    <a class="trigrexam-card" href="<?php echo esc_url( $card['url'] ); ?>">
      <span class="trigrexam-card-icon dashicons <?php echo esc_attr( $card['icon'] ); ?>" aria-hidden="true"></span>
      <span class="trigrexam-card-title"><?php echo esc_html( $card['title'] ); ?></span>
    </a>
<?php endforeach; ?>
  </div>
</div>
