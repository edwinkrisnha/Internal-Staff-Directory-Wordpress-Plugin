<?php
/**
 * Full employee profile page template.
 *
 * Variables provided by employee_dir_profile_template_redirect():
 *   @var WP_User $user
 *   @var array   $profile  Keys: department, job_title, phone, office, bio, photo_url,
 *                                 linkedin_url, start_date
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$full_name   = trim( $user->first_name . ' ' . $user->last_name );
if ( '' === $full_name ) {
	$full_name = $user->display_name;
}

$is_resigned   = ! empty( $profile['resigned'] );
$resigned_date = $profile['resigned_date'] ?? '';

$photo = employee_dir_get_avatar_url( $user, 128 );

// Back link: referrer if it's on the same site, otherwise omit.
$back_url  = '';
$referrer  = wp_get_referer();
if ( $referrer && str_starts_with( $referrer, home_url() ) ) {
	$back_url = $referrer;
}
?>
<main class="ed-profile-page" id="ed-profile-page">
	<div class="ed-profile-page__inner">

		<?php if ( $back_url ) : ?>
			<p class="ed-profile-page__back">
				<a href="<?php echo esc_url( $back_url ); ?>">
					&larr; <?php esc_html_e( 'Back to directory', 'internal-staff-directory' ); ?>
				</a>
			</p>
		<?php endif; ?>

		<div class="ed-profile-page__header">
			<img
				class="ed-profile-page__photo"
				src="<?php echo $photo; // Already escaped above. ?>"
				alt="<?php echo esc_attr( $full_name ); ?>"
				width="128"
				height="128"
			/>
			<div class="ed-profile-page__headline">
				<h1 class="ed-profile-page__name">
				<?php echo esc_html( $full_name ); ?>
				<?php if ( $is_resigned ) : ?>
					<span class="ed-profile-page__resigned-badge">
						<?php esc_html_e( 'Former Employee', 'internal-staff-directory' ); ?>
					</span>
				<?php endif; ?>
			</h1>

				<?php if ( ! empty( $profile['job_title'] ) ) : ?>
					<p class="ed-profile-page__title"><?php echo esc_html( $profile['job_title'] ); ?></p>
				<?php endif; ?>

				<?php if ( ! empty( $profile['department'] ) ) : ?>
					<p class="ed-profile-page__dept"><?php echo esc_html( $profile['department'] ); ?></p>
				<?php endif; ?>
			</div>
		</div>

		<dl class="ed-profile-page__details">
			<div class="ed-profile-page__detail-row">
				<dt><?php esc_html_e( 'Email', 'internal-staff-directory' ); ?></dt>
				<dd>
					<a href="mailto:<?php echo esc_attr( $user->user_email ); ?>">
						<?php echo esc_html( $user->user_email ); ?>
					</a>
				</dd>
			</div>

			<?php if ( ! empty( $profile['phone'] ) ) : ?>
				<div class="ed-profile-page__detail-row">
					<dt><?php esc_html_e( 'Phone', 'internal-staff-directory' ); ?></dt>
					<dd>
						<a href="tel:<?php echo esc_attr( preg_replace( '/[^\d+]/', '', $profile['phone'] ) ); ?>">
							<?php echo esc_html( $profile['phone'] ); ?>
						</a>
					</dd>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $profile['office'] ) ) : ?>
				<div class="ed-profile-page__detail-row">
					<dt><?php esc_html_e( 'Office / Location', 'internal-staff-directory' ); ?></dt>
					<dd><?php echo esc_html( $profile['office'] ); ?></dd>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $profile['linkedin_url'] ) ) : ?>
				<div class="ed-profile-page__detail-row">
					<dt><?php esc_html_e( 'LinkedIn', 'internal-staff-directory' ); ?></dt>
					<dd>
						<a href="<?php echo esc_url( $profile['linkedin_url'] ); ?>" target="_blank" rel="noopener noreferrer">
							<?php echo esc_html( $profile['linkedin_url'] ); ?>
						</a>
					</dd>
				</div>
			<?php endif; ?>

			<?php
			// Social links — respects per-user hide only; profile page ignores global visible_fields (existing behaviour).
			$hidden_social = employee_dir_get_hidden_social_fields( $user->ID );
			foreach ( employee_dir_social_fields() as $social_key ) :
				if ( in_array( $social_key, $hidden_social, true ) ) continue;
				if ( empty( $profile[ $social_key ] ) )              continue;
				[ $social_url, $social_label ] = employee_dir_social_link( $social_key, $profile[ $social_key ] );
			?>
				<div class="ed-profile-page__detail-row">
					<dt><?php echo esc_html( $social_label ); ?></dt>
					<dd>
						<?php if ( $social_url ) : ?>
							<a href="<?php echo esc_url( $social_url ); ?>" target="_blank" rel="noopener noreferrer">
								<?php echo esc_html( $profile[ $social_key ] ); ?>
							</a>
						<?php else : ?>
							<?php echo esc_html( $profile[ $social_key ] ); // Discord: no link — display username ?>
						<?php endif; ?>
					</dd>
				</div>
			<?php endforeach; ?>

			<?php if ( ! empty( $profile['start_date'] ) ) : ?>
				<?php
				$start_label  = esc_html( $profile['start_date'] );
				$start_tenure = employee_dir_years_at_company( $profile['start_date'] );
				try {
					$start_label = ( new DateTime( $profile['start_date'] ) )->format( 'F Y' );
				} catch ( Exception $e ) {} // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
				?>
				<div class="ed-profile-page__detail-row">
					<dt><?php esc_html_e( 'Start Date', 'internal-staff-directory' ); ?></dt>
					<dd>
						<?php echo esc_html( $start_label ); ?>
						<?php if ( $start_tenure ) : ?>
							<span class="ed-profile-page__tenure">(<?php echo esc_html( $start_tenure ); ?>)</span>
						<?php endif; ?>
					</dd>
				</div>
			<?php endif; ?>
			<?php if ( $is_resigned && $resigned_date ) : ?>
				<div class="ed-profile-page__detail-row">
					<dt><?php esc_html_e( 'Resigned', 'internal-staff-directory' ); ?></dt>
					<dd><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $resigned_date ) ) ); ?></dd>
				</div>
			<?php endif; ?>
		</dl>

		<?php if ( ! empty( $profile['bio'] ) ) : ?>
			<div class="ed-profile-page__bio">
				<h2><?php esc_html_e( 'About', 'internal-staff-directory' ); ?></h2>
				<p><?php echo nl2br( esc_html( $profile['bio'] ) ); ?></p>
			</div>
		<?php endif; ?>

	</div>
</main>
