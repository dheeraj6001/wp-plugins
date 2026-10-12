<?php
/**
 * View: the enquiry form itself. The popup and the page form both show it.
 *
 * @var string $form    Which form this is: 'popup' or 'page'.
 * @var string $heading Heading above the fields, or ''.
 * @var string $anchor  HTML id of the form, to scroll back to it after sending, or ''.
 * @var array  $values  Field name => value typed so far.
 * @var array  $errors  Field name => error message.
 * @var string $message Message below the form, or ''.
 * @var bool   $sent    The enquiry was sent.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$action = remove_query_arg( 'enquiry' ) . ( $anchor !== '' ? '#' . $anchor : '' );
?>
<div class="wpcf7"<?php echo $anchor !== '' ? ' id="' . esc_attr( $anchor ) . '"' : ''; ?>>
  <form class="wpcf7-form" method="post" action="<?php echo esc_url( $action ); ?>">
    <input type="hidden" name="trigrexam_enquiry" value="<?php echo esc_attr( $form ); ?>">
    <p style="display:none"><label>Leave this field empty <input type="text" name="enquiry_website" value="" tabindex="-1" autocomplete="off"></label></p>
<?php if ( $heading !== '' ) : ?>
    <h3 class="enquiry"><?php echo esc_html( $heading ); ?></h3>
<?php endif; ?>
    <p><label> Your Name <sup>*</sup>
      <input type="text" name="enquiry_name" class="wpcf7-form-control wpcf7-text" value="<?php echo esc_attr( $values['name'] ); ?>" required></label>
<?php if ( isset( $errors['name'] ) ) : ?>
      <span class="wpcf7-not-valid-tip"><?php echo esc_html( $errors['name'] ); ?></span>
<?php endif; ?>
    </p>
    <p><label> Your Phone <sup>*</sup>
      <input type="text" name="enquiry_phone" class="wpcf7-form-control wpcf7-text" value="<?php echo esc_attr( $values['phone'] ); ?>" required></label>
<?php if ( isset( $errors['phone'] ) ) : ?>
      <span class="wpcf7-not-valid-tip"><?php echo esc_html( $errors['phone'] ); ?></span>
<?php endif; ?>
    </p>
    <p><label> Your Email <sup>*</sup>
      <input type="email" name="enquiry_email" class="wpcf7-form-control wpcf7-text wpcf7-email" value="<?php echo esc_attr( $values['email'] ); ?>" required></label>
<?php if ( isset( $errors['email'] ) ) : ?>
      <span class="wpcf7-not-valid-tip"><?php echo esc_html( $errors['email'] ); ?></span>
<?php endif; ?>
    </p>
    <p><label> Subject
      <input type="text" name="enquiry_subject" class="wpcf7-form-control wpcf7-text" value="<?php echo esc_attr( $values['subject'] ); ?>"></label>
<?php if ( isset( $errors['subject'] ) ) : ?>
      <span class="wpcf7-not-valid-tip"><?php echo esc_html( $errors['subject'] ); ?></span>
<?php endif; ?>
    </p>
    <p><label> Your Message
      <textarea name="enquiry_message" class="wpcf7-form-control wpcf7-textarea" cols="40" rows="10"><?php echo esc_textarea( $values['message'] ); ?></textarea></label>
<?php if ( isset( $errors['message'] ) ) : ?>
      <span class="wpcf7-not-valid-tip"><?php echo esc_html( $errors['message'] ); ?></span>
<?php endif; ?>
    </p>
    <p><input type="submit" class="wpcf7-form-control wpcf7-submit" value="Send"></p>
<?php if ( $message !== '' ) : ?>
    <div class="wpcf7-response-output <?php echo $sent ? 'wpcf7-mail-sent-ok' : 'wpcf7-validation-errors'; ?>"><?php echo esc_html( $message ); ?></div>
<?php endif; ?>
  </form>
</div>
