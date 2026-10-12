<?php
/**
 * View: one enquiry in the admin, under TrigrExam > Enquiries.
 *
 * @var array  $enquiry  name, phone, email, subject, message, date, delete_url.
 * @var string $back_url Address of the list of the enquiries.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap">
  <h1>Enquiry</h1>
  <p><a class="button" href="<?php echo esc_url( $back_url ); ?>">&larr; All enquiries</a></p>
  <table class="form-table" role="presentation">
    <tr><th scope="row">Name</th><td><?php echo esc_html( $enquiry['name'] ); ?></td></tr>
    <tr><th scope="row">Phone</th><td><?php echo esc_html( $enquiry['phone'] ); ?></td></tr>
    <tr><th scope="row">Email</th><td><a href="<?php echo esc_url( 'mailto:' . $enquiry['email'] ); ?>"><?php echo esc_html( $enquiry['email'] ); ?></a></td></tr>
    <tr><th scope="row">Subject</th><td><?php echo esc_html( $enquiry['subject'] ); ?></td></tr>
    <tr><th scope="row">Message</th><td><?php echo nl2br( esc_html( $enquiry['message'] ) ); ?></td></tr>
    <tr><th scope="row">Received</th><td><?php echo esc_html( $enquiry['date'] ); ?></td></tr>
  </table>
  <p><a class="button button-link-delete" href="<?php echo esc_url( $enquiry['delete_url'] ); ?>" onclick="return confirm('Delete this enquiry?');">Delete</a></p>
</div>
