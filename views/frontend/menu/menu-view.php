<?php
/**
 * View: scrolling announcements.
 *
 * @var array $links The announcements list. Each one: title, url. The url is '' for an announcement without a link.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="notice-mar"><marquee behavior="scroll" scrolldelay="100" scrollamount="5" onmouseover="this.stop()" onmouseout="this.start()" class="marq">
<?php foreach ( $links as $link ) : ?>
<?php if ( $link['url'] !== '' ) : ?>
 <i class="newb"></i><a href="<?php echo esc_url( $link['url'] ); ?>"><?php echo esc_html( $link['title'] ); ?></a>
<?php else : ?>
 <i class="newb"></i><?php echo esc_html( $link['title'] ); ?>
<?php endif; ?>
<?php endforeach; ?>
</marquee></div>
