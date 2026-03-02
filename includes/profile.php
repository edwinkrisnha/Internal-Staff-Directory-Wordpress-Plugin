<?php
/**
 * Employee profile field definitions and user meta CRUD.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Returns the canonical list of employee profile fields.
 * Single source of truth — used by admin forms, front-end display, and CRUD.
 *
 * @return array<string, string> Field key => label.
 */
function employee_dir_fields() {
	return [
		'department'   => __( 'Department', 'internal-staff-directory' ),
		'job_title'    => __( 'Job Title', 'internal-staff-directory' ),
		'phone'        => __( 'Phone', 'internal-staff-directory' ),
		'office'       => __( 'Office / Location', 'internal-staff-directory' ),
		'bio'          => __( 'Bio', 'internal-staff-directory' ),
		'photo_url'    => __( 'Profile Photo URL', 'internal-staff-directory' ),
		'linkedin_url' => __( 'LinkedIn URL', 'internal-staff-directory' ),
		'start_date'   => __( 'Start Date', 'internal-staff-directory' ),
		// Social & contact handles
		'whatsapp'     => __( 'WhatsApp', 'internal-staff-directory' ),
		'telegram'     => __( 'Telegram', 'internal-staff-directory' ),
		'discord'      => __( 'Discord', 'internal-staff-directory' ),
		'instagram'    => __( 'Instagram', 'internal-staff-directory' ),
		'facebook'     => __( 'Facebook', 'internal-staff-directory' ),
		'twitter'      => __( 'Twitter / X', 'internal-staff-directory' ),
		'youtube'      => __( 'YouTube', 'internal-staff-directory' ),
		'tiktok'       => __( 'TikTok', 'internal-staff-directory' ),
	];
}

/**
 * Get all employee profile fields for a user.
 *
 * @param int $user_id
 * @return array<string, string> Keyed by field name.
 */
function employee_dir_get_profile( $user_id ) {
	$profile = [];
	foreach ( array_keys( employee_dir_fields() ) as $field ) {
		$profile[ $field ] = (string) get_user_meta( $user_id, 'employee_dir_' . $field, true );
	}
	// Resigned status — admin-only fields, not part of employee_dir_fields().
	$profile['resigned']      = get_user_meta( $user_id, 'employee_dir_resigned', true ) === '1';
	$profile['resigned_date'] = (string) get_user_meta( $user_id, 'employee_dir_resigned_date', true );
	return $profile;
}

/**
 * Save employee profile fields for a user.
 * All sanitization happens here — callers pass raw input.
 *
 * @param int   $user_id
 * @param array $data Raw input data.
 */
function employee_dir_save_profile( $user_id, array $data ) {
	$sanitizers = [
		'department'   => 'sanitize_text_field',
		'job_title'    => 'sanitize_text_field',
		'phone'        => 'sanitize_text_field',
		'office'       => 'sanitize_text_field',
		'bio'          => 'sanitize_textarea_field',
		'photo_url'    => 'esc_url_raw',
		'linkedin_url' => 'esc_url_raw',
		'start_date'   => 'sanitize_text_field',
		// Social & contact
		'whatsapp'     => 'sanitize_text_field',
		'telegram'     => 'sanitize_text_field',
		'discord'      => 'sanitize_text_field',
		'instagram'    => 'sanitize_text_field',
		'facebook'     => 'esc_url_raw',
		'twitter'      => 'sanitize_text_field',
		'youtube'      => 'esc_url_raw',
		'tiktok'       => 'sanitize_text_field',
	];

	foreach ( $sanitizers as $field => $sanitizer ) {
		if ( array_key_exists( $field, $data ) ) {
			update_user_meta( $user_id, 'employee_dir_' . $field, $sanitizer( $data[ $field ] ) );
		}
	}

	// Save per-user social visibility preferences.
	if ( array_key_exists( 'hidden_social_fields', $data ) ) {
		$allowed_social = employee_dir_social_fields();
		$hidden         = array_values( array_filter(
			(array) $data['hidden_social_fields'],
			fn( $f ) => in_array( $f, $allowed_social, true )
		) );
		update_user_meta( $user_id, 'employee_dir_hidden_social_fields', $hidden );
	}

	// Save resigned status.
	if ( array_key_exists( 'resigned', $data ) ) {
		update_user_meta( $user_id, 'employee_dir_resigned', $data['resigned'] ? '1' : '' );
	}
	if ( array_key_exists( 'resigned_date', $data ) ) {
		$date = sanitize_text_field( $data['resigned_date'] );
		$date = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ? $date : '';
		update_user_meta( $user_id, 'employee_dir_resigned_date', $date );
	}

	// Bust the departments cache whenever any profile is saved — department
	// values may have been added, changed, or removed.
	if ( array_key_exists( 'department', $data ) ) {
		delete_transient( 'employee_dir_departments' );
	}
}

/**
 * Returns true when the given user is marked as resigned.
 *
 * @param int $user_id
 * @return bool
 */
function employee_dir_is_resigned( $user_id ) {
	return get_user_meta( $user_id, 'employee_dir_resigned', true ) === '1';
}

/**
 * Return a deterministic hex color for a department name.
 * Uses crc32 to map any string to one of 8 distinct professional palette colors.
 *
 * @param string $dept Department name.
 * @return string Hex color (e.g. '#3b82f6'), or '' when $dept is empty.
 */
function employee_dir_dept_color( $dept ) {
	if ( '' === (string) $dept ) {
		return '';
	}
	$palette = [ '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#06b6d4', '#84cc16' ];
	return $palette[ abs( crc32( $dept ) ) % count( $palette ) ];
}

/**
 * Compute a human-readable tenure string from a start date.
 *
 * @param string $start_date Date string in YYYY-MM-DD format.
 * @return string e.g. '3 yrs', '< 1 yr', or '' on invalid input.
 */
function employee_dir_years_at_company( $start_date ) {
	if ( '' === (string) $start_date ) {
		return '';
	}
	// Normalize YYYY-MM to YYYY-MM-01 so DateTime parses it unambiguously.
	if ( preg_match( '/^\d{4}-\d{2}$/', $start_date ) ) {
		$start_date .= '-01';
	}
	try {
		$start = new DateTime( $start_date );
		$now   = new DateTime( 'today' );
		$years = (int) $start->diff( $now )->y;
		return $years >= 1 ? $years . ' yrs' : '< 1 yr';
	} catch ( Exception $e ) {
		return '';
	}
}

/**
 * Get all unique, non-empty department values across all users.
 *
 * @return string[]
 */
function employee_dir_get_departments() {
	$cached = get_transient( 'employee_dir_departments' );
	if ( false !== $cached ) {
		return $cached;
	}

	global $wpdb;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$rows = $wpdb->get_col(
		"SELECT DISTINCT meta_value
		 FROM {$wpdb->usermeta}
		 WHERE meta_key = 'employee_dir_department'
		   AND meta_value != ''
		 ORDER BY meta_value ASC"
	);

	$departments = $rows ?: [];
	set_transient( 'employee_dir_departments', $departments, HOUR_IN_SECONDS );

	return $departments;
}

/**
 * Returns the earliest year allowed in the start-date year dropdown.
 * Computed dynamically as current year minus 20 years.
 *
 * @return int
 */
function employee_dir_start_year_floor() {
	return (int) gmdate( 'Y' ) - 10;
}

// ---------------------------------------------------------------------------
// Social field helpers
// ---------------------------------------------------------------------------

/**
 * Returns the canonical list of social/contact field keys.
 * Single source of truth for all social-field consumers.
 *
 * @return string[]
 */
function employee_dir_social_fields() {
	return [ 'whatsapp', 'telegram', 'discord', 'instagram', 'facebook', 'twitter', 'youtube', 'tiktok' ];
}

/**
 * Returns the social field keys a user has chosen to hide from the directory.
 *
 * @param int $user_id
 * @return string[]
 */
function employee_dir_get_hidden_social_fields( $user_id ) {
	return (array) get_user_meta( $user_id, 'employee_dir_hidden_social_fields', true );
}

/**
 * Returns [ url, label ] for a given social field key + stored value.
 * url is null for Discord (no universal deep-link scheme).
 *
 * @param string $key   Social field key.
 * @param string $value Stored value (username or URL).
 * @return array{ 0: string|null, 1: string }
 */
function employee_dir_social_link( $key, $value ) {
	$value = trim( $value );
	switch ( $key ) {
		case 'whatsapp':
			// wa.me expects digits only (country code + number, no +).
			$number = preg_replace( '/\D/', '', $value );
			return [ 'https://wa.me/' . $number, __( 'WhatsApp', 'internal-staff-directory' ) ];
		case 'telegram':
			return [ 'https://t.me/' . rawurlencode( ltrim( $value, '@' ) ), __( 'Telegram', 'internal-staff-directory' ) ];
		case 'discord':
			// No universal invite/profile link — display text only.
			return [ null, __( 'Discord', 'internal-staff-directory' ) ];
		case 'instagram':
			return [ 'https://instagram.com/' . rawurlencode( ltrim( $value, '@' ) ) . '/', __( 'Instagram', 'internal-staff-directory' ) ];
		case 'facebook':
			return [ $value, __( 'Facebook', 'internal-staff-directory' ) ]; // already a full URL
		case 'twitter':
			return [ 'https://x.com/' . rawurlencode( ltrim( $value, '@' ) ), __( 'Twitter / X', 'internal-staff-directory' ) ];
		case 'youtube':
			return [ $value, __( 'YouTube', 'internal-staff-directory' ) ]; // already a full URL
		case 'tiktok':
			return [ 'https://tiktok.com/@' . rawurlencode( ltrim( $value, '@' ) ), __( 'TikTok', 'internal-staff-directory' ) ];
		default:
			return [ null, '' ];
	}
}

/**
 * Returns a hardcoded inline SVG icon string for a social platform.
 * Output is safe to echo directly (no user input involved).
 *
 * @param string $key Social field key.
 * @return string SVG markup, or empty string for unknown keys.
 */
function employee_dir_social_icon_svg( $key ) {
	$icons = [
		'whatsapp'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>',
		'telegram'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16" aria-hidden="true"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>',
		'discord'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16" aria-hidden="true"><path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057.1 18.09.12 18.12.143 18.14a19.9 19.9 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028c.462-.63.874-1.295 1.226-1.994a.076.076 0 0 0-.041-.106 13.107 13.107 0 0 1-1.872-.892.077.077 0 0 1-.008-.128 10.2 10.2 0 0 0 .372-.292.074.074 0 0 1 .077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.892.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.03zM8.02 15.33c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.956-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.956 2.418-2.157 2.418zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.955-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.946 2.418-2.157 2.418z"/></svg>',
		'instagram' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16" aria-hidden="true"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z"/></svg>',
		'facebook'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16" aria-hidden="true"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>',
		'twitter'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16" aria-hidden="true"><path d="M18.901 1.153h3.68l-8.04 9.19L24 22.846h-7.406l-5.8-7.584-6.638 7.584H.474l8.6-9.83L0 1.154h7.594l5.243 6.932ZM17.61 20.644h2.039L6.486 3.24H4.298Z"/></svg>',
		'youtube'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16" aria-hidden="true"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>',
		'tiktok'    => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16" aria-hidden="true"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>',
	];
	return isset( $icons[ $key ] ) ? $icons[ $key ] : '';
}

// ---------------------------------------------------------------------------
// Avatar integration
// ---------------------------------------------------------------------------

/**
 * Resolve a WP user ID from the mixed $id_or_email argument used by avatar hooks.
 * Returns 0 when the value cannot be mapped to a known user (e.g. a Gravatar hash).
 *
 * @param int|string|WP_User|WP_Post|WP_Comment $id_or_email
 * @return int
 */
function employee_dir_resolve_avatar_user_id( $id_or_email ) {
	if ( is_numeric( $id_or_email ) ) {
		return absint( $id_or_email );
	}
	if ( $id_or_email instanceof WP_User ) {
		return $id_or_email->ID;
	}
	if ( $id_or_email instanceof WP_Post ) {
		return (int) $id_or_email->post_author;
	}
	if ( $id_or_email instanceof WP_Comment ) {
		return (int) $id_or_email->user_id;
	}
	if ( is_string( $id_or_email ) && is_email( $id_or_email ) ) {
		$user = get_user_by( 'email', $id_or_email );
		return $user ? $user->ID : 0;
	}
	return 0;
}

/**
 * Override WP's avatar with the plugin's profile photo when one is stored.
 * Hooked to pre_get_avatar_data so the plugin photo is used everywhere
 * get_avatar() / get_avatar_url() is called — comments, author pages, etc.
 *
 * @param array                               $args        Avatar data args passed by WP.
 * @param int|string|WP_User|WP_Post|WP_Comment $id_or_email
 * @return array
 */
function employee_dir_avatar_data( $args, $id_or_email ) {
	$user_id = employee_dir_resolve_avatar_user_id( $id_or_email );
	if ( ! $user_id ) {
		return $args;
	}

	$url = get_user_meta( $user_id, 'employee_dir_photo_url', true );
	if ( $url ) {
		$args['url']          = $url;
		$args['found_avatar'] = true;
		return $args;
	}

	// No plugin photo — fall back to DiceBear so WP's default avatar is never shown.
	$user = get_userdata( $user_id );
	if ( $user ) {
		$settings  = employee_dir_get_settings();
		$full_name = trim( $user->first_name . ' ' . $user->last_name );
		if ( '' === $full_name ) {
			$full_name = $user->display_name;
		}
		$args['url']          = 'https://api.dicebear.com/9.x/' . $settings['dicebear_style'] . '/svg?seed=' . rawurlencode( $full_name );
		$args['found_avatar'] = true;
	}

	return $args;
}
add_filter( 'pre_get_avatar_data', 'employee_dir_avatar_data', 10, 2 );

/**
 * Return the display avatar URL for a directory employee.
 * The pre_get_avatar_data filter above handles priority (plugin photo → DiceBear),
 * so this is a thin wrapper that adds escaping and a size hint for template callers.
 *
 * @param WP_User $user
 * @param int     $size Pixel size hint.
 * @return string Already-escaped URL, safe to echo directly.
 */
function employee_dir_get_avatar_url( WP_User $user, $size = 64 ) {
	return esc_url( get_avatar_url( $user->ID, [ 'size' => $size ] ) );
}
