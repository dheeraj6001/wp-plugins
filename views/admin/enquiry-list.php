<?php
/**
 * View: the list of the enquiries in the admin, under TrigrExam > Enquiries. The ones not opened yet are bold.
 *
 * @var array  $enquiries  Each one: id, name, phone, email, subject, message, new, date, view_url, delete_url.
 * @var int    $total      Number of enquiries.
 * @var int    $unread     Number of enquiries not opened yet.
 * @var string $pagination Rendered links to the other pages, or ''.
 * @var bool   $deleted    An enquiry was just deleted.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap">
  <h1>Enquiries</h1>
  <p><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=trigrexam' ) ); ?>">&larr; Back</a></p>
<?php if ( $deleted ) : ?>
  <div class="notice notice-success is-dismissible"><p>The enquiry is deleted.</p></div>
<?php endif; ?>
  <p><?php echo (int) $total; ?> enquiries, <?php echo (int) $unread; ?> new.</p>
  <table class="wp-list-table widefat fixed striped">
    <thead>
      <tr>
        <th scope="col">Name</th>
        <th scope="col">Phone</th>
        <th scope="col">Email</th>
        <th scope="col">Subject</th>
        <th scope="col">Message</th>
        <th scope="col">Received</th>
      </tr>
    </thead>
    <tbody>
<?php if ( ! $enquiries ) : ?>
      <tr><td colspan="6">No enquiries yet.</td></tr>
<?php endif; ?>
<?php foreach ( $enquiries as $enquiry ) : ?>
      <tr>
        <td>
          <a class="row-title<?php echo $enquiry['new'] ? '' : ' trigrexam-enquiry-read'; ?>" href="<?php echo esc_url( $enquiry['view_url'] ); ?>"><?php echo esc_html( $enquiry['name'] ); ?></a><?php echo $enquiry['new'] ? ' &mdash; <span class="post-state">New</span>' : ''; ?>
          <div class="row-actions visible">
            <a href="<?php echo esc_url( $enquiry['view_url'] ); ?>">View</a> |
            <span class="delete"><a href="<?php echo esc_url( $enquiry['delete_url'] ); ?>" onclick="return confirm('Delete this enquiry?');">Delete</a></span>
          </div>
        </td>
        <td><?php echo esc_html( $enquiry['phone'] ); ?></td>
        <td><a href="<?php echo esc_url( 'mailto:' . $enquiry['email'] ); ?>"><?php echo esc_html( $enquiry['email'] ); ?></a></td>
        <td><?php echo esc_html( $enquiry['subject'] ); ?></td>
        <td><?php echo esc_html( wp_trim_words( $enquiry['message'], 20 ) ); ?></td>
        <td><?php echo esc_html( $enquiry['date'] ); ?></td>
      </tr>
<?php endforeach; ?>
    </tbody>
  </table>
<?php if ( $pagination ) : ?>
  <div class="tablenav bottom"><div class="tablenav-pages"><span class="pagination-links"><?php echo $pagination; ?></span></div></div>
<?php endif; ?>
</div>
