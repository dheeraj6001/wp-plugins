<?php
/**
 * View: the "Quick Enquiry" button and the popup that holds the enquiry form.
 * assets/trigrexam.js opens and closes the popup.
 *
 * @var string $form Rendered enquiry form.
 * @var bool   $open Show the popup right away.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="openenquiry">
  <button type="button" class="open-button">Quick Enquiry</button>
</div>
<div class="form-popup" id="myForm"<?php echo $open ? ' style="display:block"' : ''; ?>>
  <span class="close">&times;</span>
<?php echo $form; ?>
</div>
