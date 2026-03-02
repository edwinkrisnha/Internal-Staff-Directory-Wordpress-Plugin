<?php
/**
 * HR Staff Management tab for the Internal Staff Directory settings page.
 *
 * Provides a "Staff" tab alongside the existing "Settings" tab, allowing HR
 * to create, view, edit, and remove employees from the directory — all within
 * the single Settings → Internal Staff Directory menu item.
 *
 * All actions are guarded by current_user_can('edit_users').
 * Form submissions use the admin_post_ hook pattern (POST/Redirect/GET).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ---------------------------------------------------------------------------
// Shared utilities
// ---------------------------------------------------------------------------

/**
 * Returns true when the given user ID is in the blocked_users list.
 *
 * @param int $user_id
 * @return bool
 */
function employee_dir_hr_is_user_blocked( $user_id ) {
	$settings = employee_dir_get_settings();
	return in_array( (int) $user_id, array_map( 'absint', (array) $settings['blocked_users'] ), true );
}

/**
 * Return the base URL for the Staff tab.
 *
 * @param array $extra Additional query args to merge.
 * @return string
 */
function employee_dir_hr_tab_url( array $extra = [] ) {
	return add_query_arg(
		array_merge( [ 'page' => 'employee-dir-settings', 'tab' => 'staff' ], $extra ),
		admin_url( 'options-general.php' )
	);
}

/**
 * Render the month + year dropdowns for the start date field.
 * Used by both the edit and create views.
 *
 * @param string $saved_month Two-digit saved month, e.g. '03'. Empty string if unset.
 * @param string $saved_year  Four-digit saved year, e.g. '2022'. Empty string if unset.
 */
function employee_dir_hr_render_start_date_selects( $saved_month, $saved_year ) {
	$current_year = (int) gmdate( 'Y' );
	$floor_year   = employee_dir_start_year_floor();
	$months       = [
		'01' => __( 'January',   'internal-staff-directory' ),
		'02' => __( 'February',  'internal-staff-directory' ),
		'03' => __( 'March',     'internal-staff-directory' ),
		'04' => __( 'April',     'internal-staff-directory' ),
		'05' => __( 'May',       'internal-staff-directory' ),
		'06' => __( 'June',      'internal-staff-directory' ),
		'07' => __( 'July',      'internal-staff-directory' ),
		'08' => __( 'August',    'internal-staff-directory' ),
		'09' => __( 'September', 'internal-staff-directory' ),
		'10' => __( 'October',   'internal-staff-directory' ),
		'11' => __( 'November',  'internal-staff-directory' ),
		'12' => __( 'December',  'internal-staff-directory' ),
	];
	?>
	<select name="ed_start_month" id="ed_start_month">
		<option value=""><?php esc_html_e( '— Month —', 'internal-staff-directory' ); ?></option>
		<?php foreach ( $months as $num => $name ) : ?>
			<option value="<?php echo esc_attr( $num ); ?>" <?php selected( $saved_month, $num ); ?>>
				<?php echo esc_html( $name ); ?>
			</option>
		<?php endforeach; ?>
	</select>
	<select name="ed_start_year" id="ed_start_year" style="margin-left:6px;">
		<option value=""><?php esc_html_e( '— Year —', 'internal-staff-directory' ); ?></option>
		<?php for ( $y = $current_year; $y >= $floor_year; $y-- ) : ?>
			<option value="<?php echo esc_attr( $y ); ?>" <?php selected( $saved_year, (string) $y ); ?>>
				<?php echo esc_html( $y ); ?>
			</option>
		<?php endfor; ?>
	</select>
	<?php
}

/**
 * Parse the start date from POST data.
 * Reads ed_start_year + ed_start_month, validates, and returns 'YYYY-MM' or ''.
 *
 * @param array $post_data Raw POST array (typically $_POST).
 * @return string
 */
function employee_dir_hr_parse_start_date_from_post( array $post_data ) {
	$year  = isset( $post_data['ed_start_year'] )  ? absint( $post_data['ed_start_year'] )  : 0;
	$month = isset( $post_data['ed_start_month'] ) ? absint( $post_data['ed_start_month'] ) : 0;
	$floor = employee_dir_start_year_floor();
	if ( $year >= $floor && $year <= (int) gmdate( 'Y' ) && $month >= 1 && $month <= 12 ) {
		return sprintf( '%04d-%02d', $year, $month );
	}
	return '';
}

/**
 * Extract employee directory profile field values from raw POST data.
 * Single source of truth used by both the save and create handlers.
 *
 * @param array $post_data Raw POST array (typically $_POST).
 * @return array Unsanitized field values keyed by profile field name.
 *               Sanitization is delegated to employee_dir_save_profile().
 */
function employee_dir_hr_profile_data_from_post( array $post_data ) {
	$data = [
		'department'   => isset( $post_data['ed_department'] )   ? wp_unslash( $post_data['ed_department'] )   : '',
		'job_title'    => isset( $post_data['ed_job_title'] )    ? wp_unslash( $post_data['ed_job_title'] )    : '',
		'phone'        => isset( $post_data['ed_phone'] )        ? wp_unslash( $post_data['ed_phone'] )        : '',
		'office'       => isset( $post_data['ed_office'] )       ? wp_unslash( $post_data['ed_office'] )       : '',
		'bio'          => isset( $post_data['ed_bio'] )          ? wp_unslash( $post_data['ed_bio'] )          : '',
		'photo_url'    => isset( $post_data['ed_photo_url'] )    ? wp_unslash( $post_data['ed_photo_url'] )    : '',
		'linkedin_url' => isset( $post_data['ed_linkedin_url'] ) ? wp_unslash( $post_data['ed_linkedin_url'] ) : '',
		'start_date'   => employee_dir_hr_parse_start_date_from_post( $post_data ),
		// Social fields
		'whatsapp'     => isset( $post_data['ed_whatsapp'] )     ? wp_unslash( $post_data['ed_whatsapp'] )     : '',
		'telegram'     => isset( $post_data['ed_telegram'] )     ? wp_unslash( $post_data['ed_telegram'] )     : '',
		'discord'      => isset( $post_data['ed_discord'] )      ? wp_unslash( $post_data['ed_discord'] )      : '',
		'instagram'    => isset( $post_data['ed_instagram'] )    ? wp_unslash( $post_data['ed_instagram'] )    : '',
		'facebook'     => isset( $post_data['ed_facebook'] )     ? wp_unslash( $post_data['ed_facebook'] )     : '',
		'twitter'      => isset( $post_data['ed_twitter'] )      ? wp_unslash( $post_data['ed_twitter'] )      : '',
		'youtube'      => isset( $post_data['ed_youtube'] )      ? wp_unslash( $post_data['ed_youtube'] )      : '',
		'tiktok'       => isset( $post_data['ed_tiktok'] )       ? wp_unslash( $post_data['ed_tiktok'] )       : '',
	];

	// Collect hidden social fields: every social field NOT in ed_show_social[] is hidden.
	$social_keys = employee_dir_social_fields();
	$show_social = ( isset( $post_data['ed_show_social'] ) && is_array( $post_data['ed_show_social'] ) )
		? array_map( 'sanitize_key', array_keys( $post_data['ed_show_social'] ) )
		: [];
	$data['hidden_social_fields'] = array_values( array_diff( $social_keys, $show_social ) );

	// Resigned status.
	$data['resigned']      = ! empty( $post_data['ed_resigned'] );
	$data['resigned_date'] = isset( $post_data['ed_resigned_date'] ) ? wp_unslash( $post_data['ed_resigned_date'] ) : '';

	return $data;
}

// ---------------------------------------------------------------------------
// Tab dispatcher + notices
// ---------------------------------------------------------------------------

/**
 * Main entry point called by settings.php when tab=staff.
 * Dispatches to the correct inner renderer based on $_GET['view'].
 */
function employee_dir_hr_render_staff_tab() {
	if ( ! current_user_can( 'edit_users' ) ) {
		echo '<p>' . esc_html__( 'You do not have permission to manage staff.', 'internal-staff-directory' ) . '</p>';
		return;
	}

	employee_dir_hr_render_notices();

	$view = isset( $_GET['view'] ) ? sanitize_key( $_GET['view'] ) : 'list'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	if ( 'edit' === $view ) {
		$user_id = isset( $_GET['user_id'] ) ? absint( $_GET['user_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		employee_dir_hr_render_edit_view( $user_id );
	} elseif ( 'create' === $view ) {
		employee_dir_hr_render_create_view();
	} else {
		employee_dir_hr_render_list_view();
	}
}

/**
 * Print an admin notice based on query-string params set by action handlers.
 */
function employee_dir_hr_render_notices() {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	if ( ! empty( $_GET['saved'] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Staff member saved.', 'internal-staff-directory' ) . '</p></div>';
	} elseif ( ! empty( $_GET['created'] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Staff member created.', 'internal-staff-directory' ) . '</p></div>';
	} elseif ( ! empty( $_GET['removed'] ) ) {
		echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html__( 'User removed from directory.', 'internal-staff-directory' ) . '</p></div>';
	} elseif ( ! empty( $_GET['restored'] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'User restored to directory.', 'internal-staff-directory' ) . '</p></div>';
	} elseif ( ! empty( $_GET['error'] ) ) {
		$msg = isset( $_GET['ed_notice'] ) ? sanitize_text_field( rawurldecode( wp_unslash( $_GET['ed_notice'] ) ) ) : __( 'An error occurred. Please try again.', 'internal-staff-directory' );
		echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $msg ) . '</p></div>';
	}
	// phpcs:enable
}

// ---------------------------------------------------------------------------
// List view
// ---------------------------------------------------------------------------

/**
 * Render the staff table — all WP users, including blocked ones.
 * Results are paginated at 50 per page.
 */
function employee_dir_hr_render_list_view() {
	$per_page    = 50;
	$paged       = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$total_users = count_users();
	$total       = (int) $total_users['total_users'];
	$total_pages = (int) ceil( $total / $per_page );
	$paged       = min( $paged, max( 1, $total_pages ) );

	$users = get_users( [
		'orderby' => 'display_name',
		'order'   => 'ASC',
		'number'  => $per_page,
		'offset'  => ( $paged - 1 ) * $per_page,
	] );
	?>
	<div style="margin-top:1.5rem;">
		<a href="<?php echo esc_url( employee_dir_hr_tab_url( [ 'view' => 'create' ] ) ); ?>"
		   class="button button-primary" style="margin-bottom:1rem;">
			<?php esc_html_e( '+ Add New Staff Member', 'internal-staff-directory' ); ?>
		</a>

		<?php if ( empty( $users ) ) : ?>
			<p><?php esc_html_e( 'No users found.', 'internal-staff-directory' ); ?></p>
		<?php else : ?>
		<table class="wp-list-table widefat fixed striped" style="margin-top:0.5rem;">
			<thead>
				<tr>
					<th scope="col" style="width:22%;"><?php esc_html_e( 'Name', 'internal-staff-directory' ); ?></th>
					<th scope="col" style="width:22%;"><?php esc_html_e( 'Email', 'internal-staff-directory' ); ?></th>
					<th scope="col" style="width:18%;"><?php esc_html_e( 'Department', 'internal-staff-directory' ); ?></th>
					<th scope="col" style="width:18%;"><?php esc_html_e( 'Job Title', 'internal-staff-directory' ); ?></th>
					<th scope="col" style="width:12%;"><?php esc_html_e( 'Status', 'internal-staff-directory' ); ?></th>
					<th scope="col" style="width:8%;"><?php esc_html_e( 'Actions', 'internal-staff-directory' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $users as $user ) :
					$profile     = employee_dir_get_profile( $user->ID );
					$is_blocked  = employee_dir_hr_is_user_blocked( $user->ID );
					$is_resigned = ! empty( $profile['resigned'] );
					$full_name   = trim( $user->first_name . ' ' . $user->last_name ) ?: $user->display_name;
					$edit_url   = employee_dir_hr_tab_url( [ 'view' => 'edit', 'user_id' => $user->ID ] );
					$remove_url = wp_nonce_url(
						admin_url( 'admin-post.php?action=employee_dir_hr_remove_user&user_id=' . $user->ID ),
						'employee_dir_hr_remove_' . $user->ID
					);
					$restore_url = wp_nonce_url(
						admin_url( 'admin-post.php?action=employee_dir_hr_restore_user&user_id=' . $user->ID ),
						'employee_dir_hr_restore_' . $user->ID
					);
				?>
				<tr>
					<td>
						<a href="<?php echo esc_url( $edit_url ); ?>">
							<strong><?php echo esc_html( $full_name ); ?></strong>
						</a>
						<br>
						<small style="color:#666;"><?php echo esc_html( $user->user_login ); ?></small>
					</td>
					<td><?php echo esc_html( $user->user_email ); ?></td>
					<td><?php echo esc_html( $profile['department'] ); ?></td>
					<td><?php echo esc_html( $profile['job_title'] ); ?></td>
					<td>
						<?php if ( $is_resigned ) : ?>
							<span style="display:inline-block;padding:2px 8px;border-radius:3px;font-size:12px;background:#fee2e2;color:#7f1d1d;">
								<?php esc_html_e( 'Resigned', 'internal-staff-directory' ); ?>
							</span>
						<?php elseif ( $is_blocked ) : ?>
							<span style="display:inline-block;padding:2px 8px;border-radius:3px;font-size:12px;background:#fef3c7;color:#b45309;">
								<?php esc_html_e( 'Removed', 'internal-staff-directory' ); ?>
							</span>
						<?php else : ?>
							<span style="display:inline-block;padding:2px 8px;border-radius:3px;font-size:12px;background:#dcfce7;color:#166534;">
								<?php esc_html_e( 'Active', 'internal-staff-directory' ); ?>
							</span>
						<?php endif; ?>
					</td>
					<td>
						<a href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Edit', 'internal-staff-directory' ); ?></a>
						&nbsp;|&nbsp;
						<?php if ( $is_blocked ) : ?>
							<a href="<?php echo esc_url( $restore_url ); ?>"><?php esc_html_e( 'Restore', 'internal-staff-directory' ); ?></a>
						<?php else : ?>
							<a href="<?php echo esc_url( $remove_url ); ?>"
							   onclick="return confirm('<?php echo esc_js( __( 'Remove this user from the directory?', 'internal-staff-directory' ) ); ?>');">
								<?php esc_html_e( 'Remove', 'internal-staff-directory' ); ?>
							</a>
						<?php endif; ?>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<?php if ( $total_pages > 1 ) : ?>
		<div class="tablenav bottom" style="margin-top:0.75rem;">
			<div class="tablenav-pages">
				<span class="displaying-num">
					<?php
					printf(
						/* translators: %d: total number of users */
						esc_html( _n( '%d user', '%d users', $total, 'internal-staff-directory' ) ),
						(int) $total
					);
					?>
				</span>
				<span class="pagination-links">
					<?php if ( $paged > 1 ) : ?>
						<a class="prev-page button" href="<?php echo esc_url( employee_dir_hr_tab_url( [ 'paged' => $paged - 1 ] ) ); ?>">
							<span aria-hidden="true">&lsaquo;</span>
						</a>
					<?php else : ?>
						<span class="prev-page button disabled" aria-hidden="true">&lsaquo;</span>
					<?php endif; ?>
					<span class="paging-input">
						<?php
						printf(
							/* translators: 1: current page, 2: total pages */
							esc_html__( '%1$d of %2$d', 'internal-staff-directory' ),
							(int) $paged,
							(int) $total_pages
						);
						?>
					</span>
					<?php if ( $paged < $total_pages ) : ?>
						<a class="next-page button" href="<?php echo esc_url( employee_dir_hr_tab_url( [ 'paged' => $paged + 1 ] ) ); ?>">
							<span aria-hidden="true">&rsaquo;</span>
						</a>
					<?php else : ?>
						<span class="next-page button disabled" aria-hidden="true">&rsaquo;</span>
					<?php endif; ?>
				</span>
			</div>
		</div>
		<?php endif; ?>

		<?php endif; ?>
	</div>
	<?php
}

// ---------------------------------------------------------------------------
// Edit view
// ---------------------------------------------------------------------------

/**
 * Render the edit form for an existing user.
 *
 * @param int $user_id
 */
function employee_dir_hr_render_edit_view( $user_id ) {
	$user = get_userdata( $user_id );
	if ( ! $user ) {
		wp_redirect( employee_dir_hr_tab_url( [ 'error' => 1, 'ed_notice' => rawurlencode( __( 'User not found.', 'internal-staff-directory' ) ) ] ) );
		exit;
	}

	$profile = employee_dir_get_profile( $user_id );

	// Parse saved start date into month and year.
	$saved_month = $saved_year = '';
	if ( preg_match( '/^(\d{4})-(\d{2})/', $profile['start_date'], $m ) ) {
		$saved_year  = $m[1];
		$saved_month = $m[2];
	}

	$all_roles    = wp_roles()->roles;
	$current_role = ! empty( $user->roles ) ? $user->roles[0] : '';
	?>
	<p style="margin-top:1rem;">
		<a href="<?php echo esc_url( employee_dir_hr_tab_url() ); ?>">
			&larr; <?php esc_html_e( 'Back to staff list', 'internal-staff-directory' ); ?>
		</a>
	</p>

	<h2><?php
		/* translators: %s: employee display name */
		printf( esc_html__( 'Edit: %s', 'internal-staff-directory' ), esc_html( $user->display_name ) );
	?></h2>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action"     value="employee_dir_hr_save_user">
		<input type="hidden" name="hr_user_id" value="<?php echo esc_attr( $user_id ); ?>">
		<?php wp_nonce_field( 'employee_dir_hr_save_user_' . $user_id ); ?>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="hr_first_name"><?php esc_html_e( 'First Name', 'internal-staff-directory' ); ?></label></th>
				<td><input type="text" id="hr_first_name" name="hr_first_name" value="<?php echo esc_attr( $user->first_name ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th scope="row"><label for="hr_last_name"><?php esc_html_e( 'Last Name', 'internal-staff-directory' ); ?></label></th>
				<td><input type="text" id="hr_last_name" name="hr_last_name" value="<?php echo esc_attr( $user->last_name ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th scope="row"><label for="hr_display_name"><?php esc_html_e( 'Display Name', 'internal-staff-directory' ); ?></label></th>
				<td><input type="text" id="hr_display_name" name="hr_display_name" value="<?php echo esc_attr( $user->display_name ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th scope="row"><label for="hr_email"><?php esc_html_e( 'Email', 'internal-staff-directory' ); ?></label></th>
				<td><input type="email" id="hr_email" name="hr_email" value="<?php echo esc_attr( $user->user_email ); ?>" class="regular-text" required></td>
			</tr>
			<tr>
				<th scope="row"><label for="hr_role"><?php esc_html_e( 'Role', 'internal-staff-directory' ); ?></label></th>
				<td>
					<select id="hr_role" name="hr_role">
						<?php foreach ( $all_roles as $slug => $role ) : ?>
							<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $current_role, $slug ); ?>>
								<?php echo esc_html( translate_user_role( $role['name'] ) ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>

			<?php // ---- Employee directory fields ---- ?>
			<tr><td colspan="2"><hr><strong><?php esc_html_e( 'Directory Fields', 'internal-staff-directory' ); ?></strong></td></tr>

			<tr>
				<th scope="row"><label for="ed_department"><?php esc_html_e( 'Department', 'internal-staff-directory' ); ?></label></th>
				<td><input type="text" id="ed_department" name="ed_department" value="<?php echo esc_attr( $profile['department'] ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th scope="row"><label for="ed_job_title"><?php esc_html_e( 'Job Title', 'internal-staff-directory' ); ?></label></th>
				<td><input type="text" id="ed_job_title" name="ed_job_title" value="<?php echo esc_attr( $profile['job_title'] ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th scope="row"><label for="ed_phone"><?php esc_html_e( 'Phone', 'internal-staff-directory' ); ?></label></th>
				<td><input type="text" id="ed_phone" name="ed_phone" value="<?php echo esc_attr( $profile['phone'] ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th scope="row"><label for="ed_office"><?php esc_html_e( 'Office / Location', 'internal-staff-directory' ); ?></label></th>
				<td><input type="text" id="ed_office" name="ed_office" value="<?php echo esc_attr( $profile['office'] ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th scope="row"><label for="ed_photo_url"><?php esc_html_e( 'Profile Photo URL', 'internal-staff-directory' ); ?></label></th>
				<td>
					<?php employee_dir_admin_render_photo_field( $profile['photo_url'] ); ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="ed_linkedin_url"><?php esc_html_e( 'LinkedIn URL', 'internal-staff-directory' ); ?></label></th>
				<td>
					<input type="url" id="ed_linkedin_url" name="ed_linkedin_url" value="<?php echo esc_attr( $profile['linkedin_url'] ); ?>" class="regular-text" placeholder="https://linkedin.com/in/">
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="ed_start_month"><?php esc_html_e( 'Start Date', 'internal-staff-directory' ); ?></label></th>
				<td>
					<?php employee_dir_hr_render_start_date_selects( $saved_month, $saved_year ); ?>
					<p class="description"><?php esc_html_e( 'The month this employee joined the company.', 'internal-staff-directory' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="ed_bio"><?php esc_html_e( 'Bio', 'internal-staff-directory' ); ?></label></th>
				<td><textarea id="ed_bio" name="ed_bio" rows="4" class="large-text"><?php echo esc_textarea( $profile['bio'] ); ?></textarea></td>
			</tr>
		</table>

		<?php
		$hidden_social = employee_dir_get_hidden_social_fields( $user_id );
		employee_dir_admin_render_social_fields( $profile, $hidden_social );
		employee_dir_admin_render_employment_status( $profile );
		?>

		<?php submit_button( __( 'Save Changes', 'internal-staff-directory' ) ); ?>
	</form>
	<?php
}

// ---------------------------------------------------------------------------
// Create view
// ---------------------------------------------------------------------------

/**
 * Render the create form for a new staff member.
 */
function employee_dir_hr_render_create_view() {
	$all_roles = wp_roles()->roles;
	?>
	<p style="margin-top:1rem;">
		<a href="<?php echo esc_url( employee_dir_hr_tab_url() ); ?>">
			&larr; <?php esc_html_e( 'Back to staff list', 'internal-staff-directory' ); ?>
		</a>
	</p>

	<h2><?php esc_html_e( 'Add New Staff Member', 'internal-staff-directory' ); ?></h2>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="employee_dir_hr_create_user">
		<?php wp_nonce_field( 'employee_dir_hr_create_user' ); ?>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="hr_username"><?php esc_html_e( 'Username', 'internal-staff-directory' ); ?> <span class="required" aria-hidden="true">*</span></label></th>
				<td>
					<input type="text" id="hr_username" name="hr_username" value="" class="regular-text" required autocomplete="off">
					<p class="description"><?php esc_html_e( 'Unique login name. Cannot be changed later.', 'internal-staff-directory' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="hr_email"><?php esc_html_e( 'Email', 'internal-staff-directory' ); ?> <span class="required" aria-hidden="true">*</span></label></th>
				<td><input type="email" id="hr_email" name="hr_email" value="" class="regular-text" required></td>
			</tr>
			<tr>
				<th scope="row"><label for="hr_first_name"><?php esc_html_e( 'First Name', 'internal-staff-directory' ); ?></label></th>
				<td><input type="text" id="hr_first_name" name="hr_first_name" value="" class="regular-text"></td>
			</tr>
			<tr>
				<th scope="row"><label for="hr_last_name"><?php esc_html_e( 'Last Name', 'internal-staff-directory' ); ?></label></th>
				<td><input type="text" id="hr_last_name" name="hr_last_name" value="" class="regular-text"></td>
			</tr>
			<tr>
				<th scope="row"><label for="hr_role"><?php esc_html_e( 'Role', 'internal-staff-directory' ); ?></label></th>
				<td>
					<select id="hr_role" name="hr_role">
						<?php foreach ( $all_roles as $slug => $role ) : ?>
							<option value="<?php echo esc_attr( $slug ); ?>">
								<?php echo esc_html( translate_user_role( $role['name'] ) ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Welcome Email', 'internal-staff-directory' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="hr_send_email" value="1" checked>
						<?php esc_html_e( 'Send a welcome email with login instructions', 'internal-staff-directory' ); ?>
					</label>
				</td>
			</tr>

			<?php // ---- Employee directory fields ---- ?>
			<tr><td colspan="2"><hr><strong><?php esc_html_e( 'Directory Fields', 'internal-staff-directory' ); ?></strong></td></tr>

			<tr>
				<th scope="row"><label for="ed_department"><?php esc_html_e( 'Department', 'internal-staff-directory' ); ?></label></th>
				<td><input type="text" id="ed_department" name="ed_department" value="" class="regular-text"></td>
			</tr>
			<tr>
				<th scope="row"><label for="ed_job_title"><?php esc_html_e( 'Job Title', 'internal-staff-directory' ); ?></label></th>
				<td><input type="text" id="ed_job_title" name="ed_job_title" value="" class="regular-text"></td>
			</tr>
			<tr>
				<th scope="row"><label for="ed_phone"><?php esc_html_e( 'Phone', 'internal-staff-directory' ); ?></label></th>
				<td><input type="text" id="ed_phone" name="ed_phone" value="" class="regular-text"></td>
			</tr>
			<tr>
				<th scope="row"><label for="ed_office"><?php esc_html_e( 'Office / Location', 'internal-staff-directory' ); ?></label></th>
				<td><input type="text" id="ed_office" name="ed_office" value="" class="regular-text"></td>
			</tr>
			<tr>
				<th scope="row"><label for="ed_photo_url"><?php esc_html_e( 'Profile Photo URL', 'internal-staff-directory' ); ?></label></th>
				<td>
					<?php employee_dir_admin_render_photo_field( '' ); ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="ed_linkedin_url"><?php esc_html_e( 'LinkedIn URL', 'internal-staff-directory' ); ?></label></th>
				<td>
					<input type="url" id="ed_linkedin_url" name="ed_linkedin_url" value="" class="regular-text" placeholder="https://linkedin.com/in/">
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="ed_start_month"><?php esc_html_e( 'Start Date', 'internal-staff-directory' ); ?></label></th>
				<td>
					<?php employee_dir_hr_render_start_date_selects( '', '' ); ?>
					<p class="description"><?php esc_html_e( 'The month this employee joined the company.', 'internal-staff-directory' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="ed_bio"><?php esc_html_e( 'Bio', 'internal-staff-directory' ); ?></label></th>
				<td><textarea id="ed_bio" name="ed_bio" rows="4" class="large-text"></textarea></td>
			</tr>
		</table>

		<?php
		// New user: no hidden social fields yet — all default to visible (checked).
		employee_dir_admin_render_social_fields( [], [] );
		?>

		<?php submit_button( __( 'Create Staff Member', 'internal-staff-directory' ) ); ?>
	</form>
	<?php
}

// ---------------------------------------------------------------------------
// admin_post_ handlers
// ---------------------------------------------------------------------------

/**
 * Save changes to an existing user (WP core fields + employee_dir_ meta).
 * Hooked to admin_post_employee_dir_hr_save_user.
 */
function employee_dir_hr_handle_save_user() {
	$user_id = isset( $_POST['hr_user_id'] ) ? absint( $_POST['hr_user_id'] ) : 0;

	check_admin_referer( 'employee_dir_hr_save_user_' . $user_id );

	if ( ! current_user_can( 'edit_users' ) ) {
		wp_die( esc_html__( 'You do not have permission to edit users.', 'internal-staff-directory' ) );
	}

	if ( ! get_userdata( $user_id ) ) {
		wp_redirect( employee_dir_hr_tab_url( [ 'error' => 1, 'ed_notice' => rawurlencode( __( 'User not found.', 'internal-staff-directory' ) ) ] ) );
		exit;
	}

	$valid_roles = array_keys( wp_roles()->roles );
	$role        = isset( $_POST['hr_role'] ) ? sanitize_key( wp_unslash( $_POST['hr_role'] ) ) : '';
	if ( ! in_array( $role, $valid_roles, true ) ) {
		$role = '';
	}

	$userdata = [
		'ID'           => $user_id,
		'first_name'   => isset( $_POST['hr_first_name'] )   ? sanitize_text_field( wp_unslash( $_POST['hr_first_name'] ) )   : '',
		'last_name'    => isset( $_POST['hr_last_name'] )    ? sanitize_text_field( wp_unslash( $_POST['hr_last_name'] ) )    : '',
		'display_name' => isset( $_POST['hr_display_name'] ) ? sanitize_text_field( wp_unslash( $_POST['hr_display_name'] ) ) : '',
		'user_email'   => isset( $_POST['hr_email'] )        ? sanitize_email( wp_unslash( $_POST['hr_email'] ) )             : '',
	];
	if ( '' !== $role ) {
		$userdata['role'] = $role;
	}

	$result = wp_update_user( $userdata );
	if ( is_wp_error( $result ) ) {
		wp_redirect( employee_dir_hr_tab_url( [
			'view'      => 'edit',
			'user_id'   => $user_id,
			'error'     => 1,
			'ed_notice' => rawurlencode( $result->get_error_message() ),
		] ) );
		exit;
	}

	// Save employee_dir_ meta via the centralized helper.
	employee_dir_save_profile( $user_id, employee_dir_hr_profile_data_from_post( $_POST ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

	wp_redirect( employee_dir_hr_tab_url( [ 'saved' => 1 ] ) );
	exit;
}
add_action( 'admin_post_employee_dir_hr_save_user', 'employee_dir_hr_handle_save_user' );

/**
 * Create a new WP user + save employee_dir_ meta.
 * Hooked to admin_post_employee_dir_hr_create_user.
 */
function employee_dir_hr_handle_create_user() {
	check_admin_referer( 'employee_dir_hr_create_user' );

	if ( ! current_user_can( 'edit_users' ) ) {
		wp_die( esc_html__( 'You do not have permission to create users.', 'internal-staff-directory' ) );
	}

	$error_redirect = function( $message ) {
		wp_redirect( employee_dir_hr_tab_url( [
			'view'      => 'create',
			'error'     => 1,
			'ed_notice' => rawurlencode( $message ),
		] ) );
		exit;
	};

	$username   = isset( $_POST['hr_username'] ) ? sanitize_user( wp_unslash( $_POST['hr_username'] ) ) : '';
	$email      = isset( $_POST['hr_email'] )    ? sanitize_email( wp_unslash( $_POST['hr_email'] ) )   : '';
	$first_name = isset( $_POST['hr_first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['hr_first_name'] ) ) : '';
	$last_name  = isset( $_POST['hr_last_name'] )  ? sanitize_text_field( wp_unslash( $_POST['hr_last_name'] ) )  : '';

	if ( '' === $username ) {
		$error_redirect( __( 'Username is required.', 'internal-staff-directory' ) );
	}
	if ( username_exists( $username ) ) {
		$error_redirect( __( 'That username is already taken.', 'internal-staff-directory' ) );
	}
	if ( ! is_email( $email ) ) {
		$error_redirect( __( 'Please enter a valid email address.', 'internal-staff-directory' ) );
	}
	if ( email_exists( $email ) ) {
		$error_redirect( __( 'That email address is already registered.', 'internal-staff-directory' ) );
	}

	$valid_roles = array_keys( wp_roles()->roles );
	$role        = isset( $_POST['hr_role'] ) ? sanitize_key( wp_unslash( $_POST['hr_role'] ) ) : '';
	if ( ! in_array( $role, $valid_roles, true ) ) {
		$role = get_option( 'default_role', 'subscriber' );
	}

	$display_name = trim( $first_name . ' ' . $last_name ) ?: $username;

	$user_id = wp_insert_user( [
		'user_login'   => $username,
		'user_email'   => $email,
		'first_name'   => $first_name,
		'last_name'    => $last_name,
		'display_name' => $display_name,
		'role'         => $role,
		'user_pass'    => wp_generate_password(),
	] );

	if ( is_wp_error( $user_id ) ) {
		$error_redirect( $user_id->get_error_message() );
	}

	if ( ! empty( $_POST['hr_send_email'] ) ) {
		wp_new_user_notification( $user_id, null, 'both' );
	}

	// Save employee_dir_ meta.
	employee_dir_save_profile( $user_id, employee_dir_hr_profile_data_from_post( $_POST ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

	wp_redirect( employee_dir_hr_tab_url( [ 'created' => 1 ] ) );
	exit;
}
add_action( 'admin_post_employee_dir_hr_create_user', 'employee_dir_hr_handle_create_user' );

/**
 * Add a user to the blocked_users list (removes them from the directory).
 * Hooked to admin_post_employee_dir_hr_remove_user.
 */
function employee_dir_hr_handle_remove_user() {
	$user_id = isset( $_GET['user_id'] ) ? absint( $_GET['user_id'] ) : 0;

	check_admin_referer( 'employee_dir_hr_remove_' . $user_id );

	if ( ! current_user_can( 'edit_users' ) ) {
		wp_die( esc_html__( 'You do not have permission to manage users.', 'internal-staff-directory' ) );
	}

	if ( ! get_userdata( $user_id ) ) {
		wp_redirect( employee_dir_hr_tab_url( [ 'error' => 1, 'ed_notice' => rawurlencode( __( 'User not found.', 'internal-staff-directory' ) ) ] ) );
		exit;
	}

	$settings = employee_dir_get_settings();
	$blocked  = array_map( 'absint', (array) $settings['blocked_users'] );
	if ( ! in_array( $user_id, $blocked, true ) ) {
		$blocked[] = $user_id;
	}
	$settings['blocked_users'] = array_values( array_unique( $blocked ) );
	update_option( 'employee_dir_settings', $settings );

	wp_redirect( employee_dir_hr_tab_url( [ 'removed' => 1 ] ) );
	exit;
}
add_action( 'admin_post_employee_dir_hr_remove_user', 'employee_dir_hr_handle_remove_user' );

/**
 * Remove a user from the blocked_users list (restores them to the directory).
 * Hooked to admin_post_employee_dir_hr_restore_user.
 */
function employee_dir_hr_handle_restore_user() {
	$user_id = isset( $_GET['user_id'] ) ? absint( $_GET['user_id'] ) : 0;

	check_admin_referer( 'employee_dir_hr_restore_' . $user_id );

	if ( ! current_user_can( 'edit_users' ) ) {
		wp_die( esc_html__( 'You do not have permission to manage users.', 'internal-staff-directory' ) );
	}

	if ( ! get_userdata( $user_id ) ) {
		wp_redirect( employee_dir_hr_tab_url( [ 'error' => 1, 'ed_notice' => rawurlencode( __( 'User not found.', 'internal-staff-directory' ) ) ] ) );
		exit;
	}

	$settings          = employee_dir_get_settings();
	$settings['blocked_users'] = array_values( array_filter(
		array_map( 'absint', (array) $settings['blocked_users'] ),
		function( $id ) use ( $user_id ) {
			return $id !== $user_id;
		}
	) );
	update_option( 'employee_dir_settings', $settings );

	wp_redirect( employee_dir_hr_tab_url( [ 'restored' => 1 ] ) );
	exit;
}
add_action( 'admin_post_employee_dir_hr_restore_user', 'employee_dir_hr_handle_restore_user' );

// ---------------------------------------------------------------------------
// Asset enqueueing
// ---------------------------------------------------------------------------

/**
 * Enqueue wp.media and the uploader JS on the HR Staff tab (edit and create views only).
 *
 * @param string $hook Current admin page hook.
 */
function employee_dir_hr_enqueue_media( $hook ) {
	if ( 'options-general.php' !== $hook ) {
		return;
	}
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	if ( ! isset( $_GET['page'] ) || 'employee-dir-settings' !== $_GET['page'] ) {
		return;
	}
	if ( ! isset( $_GET['tab'] ) || 'staff' !== $_GET['tab'] ) {
		return;
	}
	$view = isset( $_GET['view'] ) ? sanitize_key( $_GET['view'] ) : '';
	// phpcs:enable
	if ( ! in_array( $view, [ 'edit', 'create' ], true ) ) {
		return;
	}
	wp_enqueue_media();
	wp_add_inline_script( 'media-editor', employee_dir_admin_photo_vars(), 'before' );
	wp_add_inline_script( 'media-editor', employee_dir_admin_photo_js() );
}
add_action( 'admin_enqueue_scripts', 'employee_dir_hr_enqueue_media' );
