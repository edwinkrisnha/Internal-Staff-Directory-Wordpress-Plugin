<?php
/**
 * [employee_my_profile] widget template.
 *
 * Variables provided by employee_dir_my_profile_shortcode():
 *   @var WP_User $user     Current logged-in user.
 *   @var array   $profile  Employee profile meta (from employee_dir_get_profile).
 *   @var array   $atts     Sanitised shortcode attributes.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$photo_size_map = [ 'small' => 40, 'medium' => 64, 'large' => 96 ];
$photo_px       = $photo_size_map[ $atts['photo_size'] ] ?? 0;

$full_name = trim( $user->first_name . ' ' . $user->last_name );
if ( '' === $full_name ) {
	$full_name = $user->display_name;
}

$profile_url = get_edit_profile_url( $user->ID );
$show_link   = '1' === $atts['link'];
$show_name   = '1' === $atts['show_name'];
$show_title  = '1' === $atts['show_title'];
$has_title   = $show_title && ! empty( $profile['job_title'] );
?>
<div class="ed-my-profile">

	<?php if ( $show_name || $has_title ) : ?>
	<div class="ed-my-profile__info">

		<?php if ( $show_name ) : ?>
			<span class="ed-my-profile__name">
				<?php if ( $show_link ) : ?>
					<a href="<?php echo esc_url( $profile_url ); ?>"><?php echo esc_html( $full_name ); ?></a>
				<?php else : ?>
					<?php echo esc_html( $full_name ); ?>
				<?php endif; ?>
			</span>
		<?php endif; ?>

		<?php if ( $has_title ) : ?>
			<span class="ed-my-profile__title"><?php echo esc_html( $profile['job_title'] ); ?></span>
		<?php endif; ?>

	</div>
	<?php endif; ?>

	<?php if ( $photo_px > 0 ) :
		$photo = employee_dir_get_avatar_url( $user, $photo_px );
	?>
		<?php if ( $show_link ) : ?>
		<a href="<?php echo esc_url( $profile_url ); ?>" class="ed-my-profile__photo-link" tabindex="-1" aria-hidden="true">
		<?php endif; ?>
			<img
				class="ed-my-profile__photo ed-my-profile__photo--<?php echo esc_attr( $atts['photo_size'] ); ?>"
				src="<?php echo esc_url( $photo ); ?>"
				alt="<?php echo esc_attr( $full_name ); ?>"
				width="<?php echo esc_attr( $photo_px ); ?>"
				height="<?php echo esc_attr( $photo_px ); ?>"
			/>
		<?php if ( $show_link ) : ?>
		</a>
		<?php endif; ?>
	<?php endif; ?>

</div>
