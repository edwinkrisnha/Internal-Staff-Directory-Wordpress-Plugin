<?php
/**
 * Employee Directory fields on the WordPress user edit/profile screen.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Render employee profile fields on the user edit screen.
 *
 * @param WP_User $user
 */
function employee_dir_show_extra_profile_fields( $user ) {
	if ( ! current_user_can( 'edit_user', $user->ID ) ) {
		return;
	}

	$profile       = employee_dir_get_profile( $user->ID );
	$fields        = employee_dir_fields();
	$social_keys   = employee_dir_social_fields();
	?>
	<h2><?php esc_html_e( 'Employee Directory', 'internal-staff-directory' ); ?></h2>
	<table class="form-table" role="presentation">
		<?php foreach ( $fields as $key => $label ) : ?>
		<?php if ( in_array( $key, $social_keys, true ) ) continue; // rendered in the Social section below ?>
		<tr>
			<th scope="row">
				<label for="<?php echo 'start_date' === $key ? 'ed_start_month' : 'ed_' . esc_attr( $key ); ?>">
					<?php echo esc_html( $label ); ?>
				</label>
			</th>
			<td>
				<?php if ( 'bio' === $key ) : ?>
					<textarea
						id="ed_<?php echo esc_attr( $key ); ?>"
						name="ed_<?php echo esc_attr( $key ); ?>"
						rows="4"
						class="large-text"
					><?php echo esc_textarea( $profile[ $key ] ?? '' ); ?></textarea>
				<?php elseif ( 'photo_url' === $key ) : ?>
					<?php employee_dir_admin_render_photo_field( $profile[ $key ] ?? '' ); ?>
				<?php elseif ( 'linkedin_url' === $key ) : ?>
					<input
						type="url"
						id="ed_<?php echo esc_attr( $key ); ?>"
						name="ed_<?php echo esc_attr( $key ); ?>"
						value="<?php echo esc_attr( $profile[ $key ] ?? '' ); ?>"
						class="regular-text"
						placeholder="https://"
					/>
					<p class="description">
						<?php esc_html_e( 'e.g. https://linkedin.com/in/yourname', 'internal-staff-directory' ); ?>
					</p>
				<?php elseif ( 'start_date' === $key ) : ?>
					<?php
					$raw_start  = $profile[ $key ] ?? '';
					$saved_year = $saved_month = '';
					if ( preg_match( '/^(\d{4})-(\d{2})/', $raw_start, $m ) ) {
						$saved_year  = $m[1];
						$saved_month = $m[2];
					}
					$current_year = (int) gmdate( 'Y' );
					$months = [
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
						<?php for ( $y = $current_year; $y >= employee_dir_start_year_floor(); $y-- ) : ?>
							<option value="<?php echo esc_attr( $y ); ?>" <?php selected( $saved_year, (string) $y ); ?>>
								<?php echo esc_html( $y ); ?>
							</option>
						<?php endfor; ?>
					</select>
					<p class="description">
						<?php esc_html_e( 'The month this employee joined the company. Used to show tenure on the directory card.', 'internal-staff-directory' ); ?>
					</p>
				<?php elseif ( 'birth_date' === $key ) : ?>
					<?php
					$raw_birth    = $profile[ $key ] ?? '';
					$saved_bmonth = '';
					$saved_bday   = '';
					if ( preg_match( '/^(\d{2})-(\d{2})$/', $raw_birth, $m ) ) {
						$saved_bmonth = $m[1];
						$saved_bday   = $m[2];
					}
					$months = [
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
					<select name="ed_birth_month" id="ed_birth_month">
						<option value=""><?php esc_html_e( '— Month —', 'internal-staff-directory' ); ?></option>
						<?php foreach ( $months as $num => $name ) : ?>
							<option value="<?php echo esc_attr( $num ); ?>" <?php selected( $saved_bmonth, $num ); ?>>
								<?php echo esc_html( $name ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<select name="ed_birth_day" id="ed_birth_day" style="margin-left:6px;">
						<option value=""><?php esc_html_e( '— Day —', 'internal-staff-directory' ); ?></option>
						<?php for ( $d = 1; $d <= 31; $d++ ) : ?>
							<option value="<?php echo esc_attr( sprintf( '%02d', $d ) ); ?>" <?php selected( $saved_bday, sprintf( '%02d', $d ) ); ?>>
								<?php echo esc_html( $d ); ?>
							</option>
						<?php endfor; ?>
					</select>
					<p class="description">
						<?php esc_html_e( 'Month and day only — year is not stored. Used by the [employee_birthdays] shortcode.', 'internal-staff-directory' ); ?>
					</p>
				<?php else : ?>
					<input
						type="text"
						id="ed_<?php echo esc_attr( $key ); ?>"
						name="ed_<?php echo esc_attr( $key ); ?>"
						value="<?php echo esc_attr( $profile[ $key ] ?? '' ); ?>"
						class="regular-text"
					/>
				<?php endif; ?>
			</td>
		</tr>
		<?php endforeach; ?>
	</table>

	<?php
	$hidden_social = employee_dir_get_hidden_social_fields( $user->ID );
	employee_dir_admin_render_social_fields( $profile, $hidden_social );
	?>
	<?php
	// Employment status section — only admins who can manage other users see this.
	if ( current_user_can( 'edit_users' ) ) :
		employee_dir_admin_render_employment_status( $profile );
	endif;
	?>
	<?php
}
add_action( 'show_user_profile', 'employee_dir_show_extra_profile_fields' );
add_action( 'edit_user_profile', 'employee_dir_show_extra_profile_fields' );

/**
 * Save employee profile fields when a user profile is updated.
 *
 * @param int $user_id
 */
function employee_dir_save_extra_profile_fields( $user_id ) {
	if ( ! current_user_can( 'edit_user', $user_id ) ) {
		return;
	}

	$current_year = (int) gmdate( 'Y' );
	$data         = [];
	foreach ( array_keys( employee_dir_fields() ) as $field ) {
		if ( 'start_date' === $field ) {
			// Assembled from two separate selects; never typed by the user.
			$year  = isset( $_POST['ed_start_year'] )  ? absint( wp_unslash( $_POST['ed_start_year'] ) )  : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$month = isset( $_POST['ed_start_month'] ) ? absint( wp_unslash( $_POST['ed_start_month'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$data['start_date'] = ( $year >= employee_dir_start_year_floor() && $year <= $current_year && $month >= 1 && $month <= 12 )
				? sprintf( '%04d-%02d', $year, $month )
				: '';
		} elseif ( 'birth_date' === $field ) {
			// Assembled from two separate selects (month + day); no year stored.
			$bmonth = isset( $_POST['ed_birth_month'] ) ? absint( wp_unslash( $_POST['ed_birth_month'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$bday   = isset( $_POST['ed_birth_day'] )   ? absint( wp_unslash( $_POST['ed_birth_day'] ) )   : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$candidate = ( $bmonth >= 1 && $bmonth <= 12 && $bday >= 1 && $bday <= 31 )
				? sprintf( '%02d-%02d', $bmonth, $bday )
				: '';
			// Final validation via sanitizer (handles edge cases like Feb 30).
			$data['birth_date'] = employee_dir_sanitize_birth_date( $candidate );
		} elseif ( isset( $_POST[ 'ed_' . $field ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$data[ $field ] = wp_unslash( $_POST[ 'ed_' . $field ] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		}
	}

	// Collect hidden social fields: every social field NOT in ed_show_social[] is hidden.
	$social_keys  = employee_dir_social_fields();
	$show_social  = ( isset( $_POST['ed_show_social'] ) && is_array( $_POST['ed_show_social'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
		? array_map( 'sanitize_key', array_keys( $_POST['ed_show_social'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
		: [];
	$data['hidden_social_fields'] = array_values( array_diff( $social_keys, $show_social ) );

	// Resigned status — only admins who manage other users may change this.
	if ( current_user_can( 'edit_users' ) ) {
		$data['resigned']      = isset( $_POST['ed_resigned'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$data['resigned_date'] = isset( $_POST['ed_resigned_date'] ) ? wp_unslash( $_POST['ed_resigned_date'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	}

	employee_dir_save_profile( $user_id, $data );
}
add_action( 'personal_options_update',  'employee_dir_save_extra_profile_fields' );
add_action( 'edit_user_profile_update', 'employee_dir_save_extra_profile_fields' );

// ---------------------------------------------------------------------------
// Social & Contact fields shared renderer
// ---------------------------------------------------------------------------

/**
 * Render the Social & Contact fields section (inputs + per-field show/hide toggles).
 * Used by the WP profile screen and the HR Staff tab edit/create views.
 *
 * @param array    $profile       Current profile meta values (keyed by field key).
 * @param string[] $hidden_social Field keys the user has chosen to hide (empty = nothing hidden).
 */
function employee_dir_admin_render_social_fields( array $profile, array $hidden_social ) {
	$url_fields  = [ 'facebook', 'youtube' ];   // stored as full URLs
	$hints       = [
		'whatsapp'  => __( 'Number with country code, e.g. +12025551234', 'internal-staff-directory' ),
		'telegram'  => __( 'Username or phone, e.g. @handle or +12025551234', 'internal-staff-directory' ),
		'discord'   => __( 'Username, e.g. username or username#1234', 'internal-staff-directory' ),
		'instagram' => __( 'Username, e.g. @handle', 'internal-staff-directory' ),
		'facebook'  => __( 'Full profile URL, e.g. https://facebook.com/yourname', 'internal-staff-directory' ),
		'twitter'   => __( 'Username, e.g. @handle', 'internal-staff-directory' ),
		'youtube'   => __( 'Channel URL, e.g. https://youtube.com/@yourchannel', 'internal-staff-directory' ),
		'tiktok'    => __( 'Username, e.g. @handle', 'internal-staff-directory' ),
	];
	$labels      = [
		'whatsapp'  => __( 'WhatsApp', 'internal-staff-directory' ),
		'telegram'  => __( 'Telegram', 'internal-staff-directory' ),
		'discord'   => __( 'Discord', 'internal-staff-directory' ),
		'instagram' => __( 'Instagram', 'internal-staff-directory' ),
		'facebook'  => __( 'Facebook', 'internal-staff-directory' ),
		'twitter'   => __( 'Twitter / X', 'internal-staff-directory' ),
		'youtube'   => __( 'YouTube', 'internal-staff-directory' ),
		'tiktok'    => __( 'TikTok', 'internal-staff-directory' ),
	];
	?>
	<h2><?php esc_html_e( 'Social & Contact', 'internal-staff-directory' ); ?></h2>
	<p class="description" style="margin-bottom:1rem;">
		<?php esc_html_e( 'Uncheck "Show in directory" to hide a field from the staff directory card and profile page.', 'internal-staff-directory' ); ?>
	</p>
	<table class="form-table" role="presentation">
		<?php foreach ( employee_dir_social_fields() as $key ) :
			$is_url  = in_array( $key, $url_fields, true );
			$value   = $profile[ $key ] ?? '';
			$is_visible = ! in_array( $key, $hidden_social, true );
		?>
		<tr>
			<th scope="row">
				<label for="ed_<?php echo esc_attr( $key ); ?>">
					<?php echo esc_html( $labels[ $key ] ); ?>
				</label>
			</th>
			<td>
				<input
					type="<?php echo $is_url ? 'url' : 'text'; ?>"
					id="ed_<?php echo esc_attr( $key ); ?>"
					name="ed_<?php echo esc_attr( $key ); ?>"
					value="<?php echo esc_attr( $value ); ?>"
					class="regular-text"
					placeholder="<?php echo $is_url ? 'https://' : ''; ?>"
				/>
				<br>
				<label style="display:inline-flex;align-items:center;gap:4px;margin-top:6px;">
					<input
						type="checkbox"
						name="ed_show_social[<?php echo esc_attr( $key ); ?>]"
						value="1"
						<?php checked( $is_visible ); ?>
					/>
					<?php esc_html_e( 'Show in directory', 'internal-staff-directory' ); ?>
				</label>
				<?php if ( ! empty( $hints[ $key ] ) ) : ?>
					<p class="description"><?php echo esc_html( $hints[ $key ] ); ?></p>
				<?php endif; ?>
			</td>
		</tr>
		<?php endforeach; ?>
	</table>
	<?php
}

// ---------------------------------------------------------------------------
// Employment status section (admin-only)
// ---------------------------------------------------------------------------

/**
 * Render the Employment Status section on the user profile / edit screen.
 * Only shown to users who can manage other users (edit_users capability).
 *
 * @param array $profile Current profile meta values (includes 'resigned' and 'resigned_date').
 */
function employee_dir_admin_render_employment_status( array $profile ) {
	$is_resigned   = ! empty( $profile['resigned'] );
	$resigned_date = $profile['resigned_date'] ?? '';
	?>
	<h2><?php esc_html_e( 'Employment Status', 'internal-staff-directory' ); ?></h2>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><?php esc_html_e( 'Resigned', 'internal-staff-directory' ); ?></th>
			<td>
				<label>
					<input
						type="checkbox"
						name="ed_resigned"
						value="1"
						<?php checked( $is_resigned ); ?>
					/>
					<?php esc_html_e( 'Mark as resigned (Former Employee)', 'internal-staff-directory' ); ?>
				</label>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="ed_resigned_date"><?php esc_html_e( 'Resignation Date', 'internal-staff-directory' ); ?></label>
			</th>
			<td>
				<input
					type="date"
					id="ed_resigned_date"
					name="ed_resigned_date"
					value="<?php echo esc_attr( $resigned_date ); ?>"
					class="regular-text"
				/>
				<p class="description"><?php esc_html_e( 'Optional. Displayed on the employee\'s profile page.', 'internal-staff-directory' ); ?></p>
			</td>
		</tr>
	</table>
	<?php
}

// ---------------------------------------------------------------------------
// Media library uploader + server-side square crop for profile photo
// ---------------------------------------------------------------------------

/**
 * Render the profile photo input with a media library picker button and live preview.
 * Used on both the WP admin profile screen and the HR Staff tab.
 *
 * @param string $value Current photo URL (may be empty).
 */
function employee_dir_admin_render_photo_field( $value ) {
	$has_photo = '' !== (string) $value;
	?>
	<input
		type="url"
		id="ed_photo_url"
		name="ed_photo_url"
		value="<?php echo esc_attr( $value ); ?>"
		class="regular-text"
		placeholder="https://"
	/>
	<button type="button" class="button" id="ed-photo-select" style="margin-left:4px;">
		<?php esc_html_e( 'Select Photo', 'internal-staff-directory' ); ?>
	</button>
	<button type="button" class="button-link" id="ed-photo-remove"
		style="margin-left:8px;color:#a00;<?php echo $has_photo ? '' : 'display:none;'; ?>">
		<?php esc_html_e( 'Remove', 'internal-staff-directory' ); ?>
	</button>
	<span id="ed-photo-spinner" class="spinner" style="float:none;margin-left:4px;vertical-align:middle;<?php echo $has_photo ? 'display:none;' : 'display:none;'; ?>"></span>
	<br>
	<img
		id="ed-photo-preview"
		src="<?php echo esc_url( $value ); ?>"
		alt=""
		style="margin-top:8px;width:80px;height:80px;border-radius:4px;object-fit:cover;<?php echo $has_photo ? '' : 'display:none;'; ?>"
	>
	<p class="description">
		<?php esc_html_e( 'Choose a photo from the media library — non-square images are automatically center-cropped to a square. Leave blank to use the generated avatar.', 'internal-staff-directory' ); ?>
	</p>
	<?php
}

/**
 * Returns a JS variable block that localises ajaxUrl and the crop nonce.
 * Output as an inline script BEFORE the main photo uploader JS.
 *
 * @return string
 */
function employee_dir_admin_photo_vars() {
	return 'var employeeDirAdminPhoto=' . wp_json_encode( [
		'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
		'cropNonce' => wp_create_nonce( 'employee_dir_photo_crop' ),
	] ) . ';';
}

/**
 * Returns the inline JS for the photo uploader.
 * After the user selects an image, a server-side AJAX call center-crops it to a
 * square if needed, then populates the URL input and preview thumbnail.
 *
 * @return string
 */
function employee_dir_admin_photo_js() {
	return "jQuery(function($){
	function setPhoto(url){
		$('#ed_photo_url').val(url);
		$('#ed-photo-preview').attr('src',url).show();
		$('#ed-photo-remove').show();
		$('#ed-photo-spinner').hide();
	}
	var frame;
	$(document).on('click','#ed-photo-select',function(e){
		e.preventDefault();
		if(!frame){
			frame=wp.media({
				title:'Select Profile Photo',
				button:{text:'Select Photo'},
				multiple:false,
				library:{type:'image'}
			});
			frame.on('select',function(){
				var att=frame.state().get('selection').first().toJSON();
				if(att.width===att.height){
					setPhoto(att.url);
					return;
				}
				$('#ed-photo-spinner').show();
				$.post(employeeDirAdminPhoto.ajaxUrl,{
					action:'employee_dir_auto_crop_photo',
					nonce:employeeDirAdminPhoto.cropNonce,
					id:att.id
				},function(res){
					if(res.success){setPhoto(res.data.url);}
					else{setPhoto(att.url);}
				}).fail(function(){setPhoto(att.url);});
			});
		}
		frame.open();
	});
	$(document).on('click','#ed-photo-remove',function(e){
		e.preventDefault();
		$('#ed_photo_url').val('');
		$('#ed-photo-preview').attr('src','').hide();
		$(this).hide();
	});
});";
}

/**
 * Enqueue wp.media and the uploader JS on the user profile / user-edit screen.
 *
 * @param string $hook Current admin page hook.
 */
function employee_dir_admin_enqueue_media( $hook ) {
	if ( ! in_array( $hook, [ 'profile.php', 'user-edit.php' ], true ) ) {
		return;
	}
	wp_enqueue_media();
	wp_add_inline_script( 'media-editor', employee_dir_admin_photo_vars(), 'before' );
	wp_add_inline_script( 'media-editor', employee_dir_admin_photo_js() );
}
add_action( 'admin_enqueue_scripts', 'employee_dir_admin_enqueue_media' );

/**
 * AJAX handler: center-crop an existing media attachment to a square.
 *
 * Accepts POST params:
 *   id    (int)    WP attachment ID
 *   nonce (string) employee_dir_photo_crop nonce
 *
 * Returns JSON { success: true, data: { url: '...' } }
 *          or  JSON { success: false, data: { message: '...' } }
 */
function employee_dir_ajax_auto_crop_photo() {
	check_ajax_referer( 'employee_dir_photo_crop', 'nonce' );

	if ( ! current_user_can( 'upload_files' ) ) {
		wp_send_json_error( [ 'message' => __( 'Permission denied.', 'internal-staff-directory' ) ] );
	}

	$attachment_id = absint( isset( $_POST['id'] ) ? $_POST['id'] : 0 ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	if ( ! $attachment_id || ! wp_attachment_is_image( $attachment_id ) ) {
		wp_send_json_error( [ 'message' => __( 'Invalid image.', 'internal-staff-directory' ) ] );
	}

	$file = get_attached_file( $attachment_id );
	if ( ! $file || ! file_exists( $file ) ) {
		wp_send_json_error( [ 'message' => __( 'Image file not found.', 'internal-staff-directory' ) ] );
	}

	// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	$size = @getimagesize( $file );
	if ( ! $size ) {
		wp_send_json_error( [ 'message' => __( 'Could not read image dimensions.', 'internal-staff-directory' ) ] );
	}

	list( $orig_w, $orig_h ) = $size;

	// Already square — return the original URL without any processing.
	if ( $orig_w === $orig_h ) {
		wp_send_json_success( [ 'url' => wp_get_attachment_url( $attachment_id ) ] );
		return;
	}

	// Deterministic output path: same source always produces the same square file.
	$pathinfo    = pathinfo( $file );
	$square_file = $pathinfo['dirname'] . '/ed-' . $attachment_id . '-square.' . strtolower( $pathinfo['extension'] );

	// Return cached square if it already exists.
	if ( file_exists( $square_file ) ) {
		$upload_dir = wp_upload_dir();
		$url = str_replace(
			wp_normalize_path( $upload_dir['basedir'] ),
			$upload_dir['baseurl'],
			wp_normalize_path( $square_file )
		);
		wp_send_json_success( [ 'url' => $url ] );
		return;
	}

	// Center-crop to the smaller dimension.
	$sq    = min( $orig_w, $orig_h );
	$src_x = (int) floor( ( $orig_w - $sq ) / 2 );
	$src_y = (int) floor( ( $orig_h - $sq ) / 2 );

	$editor = wp_get_image_editor( $file );
	if ( is_wp_error( $editor ) ) {
		wp_send_json_error( [ 'message' => $editor->get_error_message() ] );
	}

	$cropped = $editor->crop( $src_x, $src_y, $sq, $sq );
	if ( is_wp_error( $cropped ) ) {
		wp_send_json_error( [ 'message' => $cropped->get_error_message() ] );
	}

	$saved = $editor->save( $square_file );
	if ( is_wp_error( $saved ) ) {
		wp_send_json_error( [ 'message' => $saved->get_error_message() ] );
	}

	$upload_dir = wp_upload_dir();
	$url = str_replace(
		wp_normalize_path( $upload_dir['basedir'] ),
		$upload_dir['baseurl'],
		wp_normalize_path( $saved['path'] )
	);

	wp_send_json_success( [ 'url' => $url ] );
}
add_action( 'wp_ajax_employee_dir_auto_crop_photo', 'employee_dir_ajax_auto_crop_photo' );
