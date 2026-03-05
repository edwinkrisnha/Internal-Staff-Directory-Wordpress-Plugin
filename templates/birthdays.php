<?php
/**
 * Birthday spotlight template — festive horizontal scroll carousel.
 *
 * Variables provided by employee_dir_birthdays_shortcode():
 *   @var array[]   $birthday_entries  Each entry: ['user' => WP_User, 'offset' => int, 'profile' => array].
 *   @var string[]  $visible_fields    Fields enabled in plugin settings.
 *   @var int       $birthday_columns  Number of grid columns (1–3).
 */

if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="ed-birthdays" id="ed-birthdays">

	<div class="ed-birthdays__header" aria-hidden="true">
		<span class="ed-birthdays__ornament"></span>
		<span class="ed-birthdays__icon">🎂</span>
		<span class="ed-birthdays__ornament"></span>
	</div>

	<?php if ( $birthday_entries ) : ?>
		<div class="ed-bday-carousel" data-columns="<?php echo esc_attr( $birthday_columns ); ?>">
			<?php foreach ( $birthday_entries as $entry ) :
				$user     = $entry['user'];
				$profile  = $entry['profile'];
				$offset   = $entry['offset'];
				$is_today = ( 0 === $offset );

				$full_name = trim( $user->first_name . ' ' . $user->last_name );
				if ( '' === $full_name ) {
					$full_name = $user->display_name;
				}

				$photo       = employee_dir_get_avatar_url( $user, 80 );
				$profile_url = employee_dir_get_profile_url( $user );
				$label       = employee_dir_format_birthday_label( $offset );

				// Format birth_date (MM-DD) as "March 15" — respects visible_fields setting.
				$bd_label = '';
				if ( ! empty( $profile['birth_date'] ) && in_array( 'birth_date', $visible_fields, true ) ) {
					$bd_parts = explode( '-', $profile['birth_date'] );
					if ( 2 === count( $bd_parts ) ) {
						try {
							$bd_dt    = new DateTime( '2000-' . $profile['birth_date'] );
							$bd_label = $bd_dt->format( 'F j' );
						} catch ( Exception $e ) {} // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
					}
				}
			?>
			<article
				class="ed-bday-card<?php echo $is_today ? ' ed-bday-card--today' : ''; ?>"
				aria-label="<?php echo esc_attr( $full_name ); ?>"
			>
				<?php if ( $is_today ) : ?>
					<div class="ed-bday-card__sparkle" aria-hidden="true">✨</div>
				<?php endif; ?>

				<div class="ed-bday-card__photo-wrap">
					<a href="<?php echo esc_url( $profile_url ); ?>" tabindex="-1" aria-hidden="true">
						<img
							class="ed-bday-card__photo"
							src="<?php echo esc_url( $photo ); ?>"
							alt="<?php echo esc_attr( $full_name ); ?>"
							width="80"
							height="80"
							loading="lazy"
						/>
					</a>
				</div>

				<div class="ed-bday-card__body">
					<h3 class="ed-bday-card__name">
						<a href="<?php echo esc_url( $profile_url ); ?>">
							<?php echo esc_html( $full_name ); ?>
						</a>
					</h3>

					<?php if ( ! empty( $profile['job_title'] ) && in_array( 'job_title', $visible_fields, true ) ) : ?>
						<p class="ed-bday-card__title"><?php echo esc_html( $profile['job_title'] ); ?></p>
					<?php endif; ?>

					<?php if ( ! empty( $profile['department'] ) && in_array( 'department', $visible_fields, true ) ) : ?>
						<p class="ed-bday-card__dept"><?php echo esc_html( $profile['department'] ); ?></p>
					<?php endif; ?>

					<?php if ( $bd_label ) : ?>
						<p class="ed-bday-card__date"><?php echo esc_html( $bd_label ); ?></p>
					<?php endif; ?>
				</div>

				<div class="ed-bday-card__footer">
					<span class="ed-bday-card__label<?php echo $is_today ? ' ed-bday-card__label--today' : ''; ?>">
						<?php echo $is_today ? '🎂' : '🎈'; ?>
						<?php echo esc_html( $label ); ?>
					</span>
				</div>
			</article>
			<?php endforeach; ?>
		</div>

	<?php else : ?>
		<div class="ed-bday-empty">
			<span class="ed-bday-empty__icon" aria-hidden="true">🎈</span>
			<p><?php esc_html_e( 'No birthdays in this period.', 'internal-staff-directory' ); ?></p>
		</div>
	<?php endif; ?>

</div>
