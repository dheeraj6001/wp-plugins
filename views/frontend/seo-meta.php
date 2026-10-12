<?php
/**
 * View: the description and the keywords of a page, as meta tags in its head.
 *
 * @var string $description Description of the page.
 * @var string $keywords    Its keywords, or '' for a page without them.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<meta name="description" content="<?php echo esc_attr( $description ); ?>">
<?php if ( $keywords !== '' ) : ?>
<meta name="keywords" content="<?php echo esc_attr( $keywords ); ?>">
<?php endif; ?>
