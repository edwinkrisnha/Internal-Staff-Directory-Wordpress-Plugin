<?php
/**
 * Birthday spotlight template.
 *
 * Variables provided by employee_dir_birthdays_shortcode():
 *   @var array[]   $birthday_entries  Each entry: ['user' => WP_User, 'offset' => int, 'profile' => array].
 *   @var string[]  $visible_fields    Fields enabled in plugin settings.
 */

if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="internal-staff-directory ed-birthdays" id="ed-birthdays">

	<div class="ed-results" id="ed-results">
		<?php if ( $birthday_entries ) : ?>
			<?php foreach ( $birthday_entries as $entry ) :
				$user            = $entry['user'];
				$profile         = $entry['profile'];
				$birthday_offset = $entry['offset'];
				include __DIR__ . '/profile-card.php';
			endforeach; ?>
		<?php else : ?>
			<p class="ed-no-results"><?php esc_html_e( 'No birthdays in this period.', 'internal-staff-directory' ); ?></p>
		<?php endif; ?>
	</div>

</div>
