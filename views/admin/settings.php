<?php
/**
 * View: the settings page of the plugin, under TrigrExam > Settings.
 *
 * @var string $enquiry_email  Address the enquiry form mails to.
 * @var string     $enquiry_mobile Mobile number for the SMS about an enquiry, or ''.
 * @var string     $msg91_authkey  Auth key of the MSG91 account, or ''.
 * @var string     $msg91_template Id of the SMS template in MSG91, or ''.
 * @var array|null $sms_status     What the last SMS did: sent, message, time. Or null when none was tried yet.
 * @var string     $custom_css     CSS for every page of the site, or ''.
 * @var string     $custom_js      JavaScript for every page of the site, or ''.
 * @var bool       $can_edit_code  The user may change the custom CSS and JavaScript.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap">
  <h1>TrigrExam Settings</h1>
<?php settings_errors(); ?>
  <div class="trigrexam-price">
    <div class="trigrexam-price-info">
      <span class="trigrexam-price-label">Plugin price</span>
      <div class="trigrexam-price-name">TrigrExam</div>
    </div>
    <div class="trigrexam-price-tag">
      <span class="trigrexam-price-currency">&#8377;</span><span class="trigrexam-price-amount">3,000</span>
      <span class="trigrexam-price-period">per year</span>
    </div>
  </div>
<form method="post" action="options.php">
<?php settings_fields( 'trigrexam' ); ?>
    <table class="form-table" role="presentation">
      <tr>
        <th scope="row"><label for="trigrexam_enquiry_email">Enquiry email</label></th>
        <td>
          <input type="email" id="trigrexam_enquiry_email" name="trigrexam_settings[enquiry_email]" value="<?php echo esc_attr( $enquiry_email ); ?>" class="regular-text" required>
          <p class="description">The "Quick Enquiry" form sends its messages to this address.</p>
        </td>
      </tr>
      <tr>
        <th scope="row"><label for="trigrexam_enquiry_mobile">Enquiry mobile</label></th>
        <td>
          <input type="tel" id="trigrexam_enquiry_mobile" name="trigrexam_settings[enquiry_mobile]" value="<?php echo esc_attr( $enquiry_mobile ); ?>" class="regular-text">
          <p class="description">The mobile number for the SMS about a new enquiry, with the country code, like +919876543210. Leave it empty for no SMS.</p>
        </td>
      </tr>
    </table>
    <h2>SMS with MSG91</h2>
    <p>An SMS about a new enquiry goes to the enquiry mobile once the three fields are filled in. The template is made in the MSG91 panel and can use <code>##name##</code>, <code>##phone##</code>, <code>##email##</code> and <code>##subject##</code>.</p>
    <table class="form-table" role="presentation">
      <tr>
        <th scope="row"><label for="trigrexam_msg91_authkey">MSG91 auth key</label></th>
        <td><input type="password" id="trigrexam_msg91_authkey" name="trigrexam_settings[msg91_authkey]" value="<?php echo esc_attr( $msg91_authkey ); ?>" class="regular-text" autocomplete="off"></td>
      </tr>
      <tr>
        <th scope="row"><label for="trigrexam_msg91_template">MSG91 template ID</label></th>
        <td><input type="text" id="trigrexam_msg91_template" name="trigrexam_settings[msg91_template]" value="<?php echo esc_attr( $msg91_template ); ?>" class="regular-text" autocomplete="off"></td>
      </tr>
<?php if ( $sms_status ) : ?>
      <tr>
        <th scope="row">Last SMS</th>
        <td><?php echo $sms_status['sent'] ? 'Sent' : 'Not sent'; ?> on <?php echo esc_html( $sms_status['time'] ); ?>. MSG91 answered: <?php echo esc_html( $sms_status['message'] ); ?></td>
      </tr>
<?php endif; ?>
    </table>
    <h2>Custom CSS and JavaScript</h2>
    <p>For every page of the site, not the admin. Write the code only, without the <code>&lt;style&gt;</code> or <code>&lt;script&gt;</code> tags.</p>
    <table class="form-table" role="presentation">
      <tr>
        <th scope="row"><label for="trigrexam_custom_css">Custom CSS</label></th>
        <td>
          <textarea id="trigrexam_custom_css" name="trigrexam_settings[custom_css]" rows="10" class="large-text code" spellcheck="false"<?php echo $can_edit_code ? '' : ' readonly'; ?>><?php echo esc_textarea( $custom_css ); ?></textarea>
          <p class="description">Put in the head of the page, after the styles of the theme.</p>
        </td>
      </tr>
      <tr>
        <th scope="row"><label for="trigrexam_custom_js">Custom JavaScript</label></th>
        <td>
          <textarea id="trigrexam_custom_js" name="trigrexam_settings[custom_js]" rows="10" class="large-text code" spellcheck="false"<?php echo $can_edit_code ? '' : ' readonly'; ?>><?php echo esc_textarea( $custom_js ); ?></textarea>
          <p class="description">Put at the end of the page, after the scripts of the theme, so jQuery is there.</p>
        </td>
      </tr>
    </table>
<?php submit_button(); ?>
  </form>
</div>
