<?php
/**
 * View: the dashboard of the plugin, the page a click on "TrigrExam" in the admin menu opens.
 * The pages of the plugin are cards, in sections: the part of the site they are used in. A card opens its page.
 *
 * @var array $sections Each one: title, cards. Each card: title, subtitle (a line about it, or ''), url,
 *                      icon (class of a dashicon).
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap">
  <h1>Dashboard</h1>
<?php foreach ( $sections as $section ) : ?>
  <h2 class="trigrexam-section"><?php echo esc_html( $section['title'] ); ?></h2>
  <div class="trigrexam-cards">
<?php foreach ( $section['cards'] as $card ) : ?>
    <a class="trigrexam-card" href="<?php echo esc_url( $card['url'] ); ?>">
      <span class="trigrexam-card-icon dashicons <?php echo esc_attr( $card['icon'] ); ?>" aria-hidden="true"></span>
      <span class="trigrexam-card-title"><?php echo esc_html( $card['title'] ); ?></span>
<?php if ( $card['subtitle'] !== '' ) : ?>
      <span class="trigrexam-card-subtitle"><?php echo esc_html( $card['subtitle'] ); ?></span>
<?php endif; ?>
    </a>
<?php endforeach; ?>
  </div>
<?php endforeach; ?>
</div>
