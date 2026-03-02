<?php
/**
 * Shortcode, WP_User_Query wrapper, AJAX handler, and asset enqueueing.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ---------------------------------------------------------------------------
// Query helpers
// ---------------------------------------------------------------------------

/**
 * Build and run a WP_User_Query for employees, returning the query object.
 * Use this when you need both results and total count (e.g. for pagination).
 *
 * @param array $args {
 *   @type string   $search          Search term matched against name and email.
 *   @type string   $department      Filter by exact department value.
 *   @type int      $per_page        Number of results. Default: value from plugin settings.
 *   @type int      $paged           Page number. Default 1.
 *   @type string   $sort            Sort key: name_asc|name_desc|start_date_desc|department_asc. Default 'name_asc'.
 *   @type string   $letter          First letter of display_name to filter by (A–Z). Overrides $search.
 *   @type string[] $role__in        Limit to specific roles; intersects with settings roles when both are set.
 *   @type bool     $new_hires_only  When true, only return users whose start date is within the new_hire_days window.
 * }
 * @return WP_User_Query
 */
function employee_dir_get_employee_query( array $args = [] ) {
	$settings = employee_dir_get_settings();

	$args = wp_parse_args( $args, [
		'search'         => '',
		'department'     => '',
		'per_page'       => $settings['per_page'],
		'paged'          => 1,
		'sort'           => 'name_asc',
		'letter'         => '',
		'role__in'       => [],
		'new_hires_only' => false,
	] );

	// Resolve sort to WP_User_Query orderby/order/meta_key args.
	$sort_map = [
		'name_asc'        => [ 'orderby' => 'display_name', 'order' => 'ASC' ],
		'name_desc'       => [ 'orderby' => 'display_name', 'order' => 'DESC' ],
		'start_date_desc' => [ 'orderby' => 'meta_value',   'order' => 'DESC', 'meta_key' => 'employee_dir_start_date' ], // phpcs:ignore WordPress.DB.SlowDBQuery
		'department_asc'  => [ 'orderby' => 'meta_value',   'order' => 'ASC',  'meta_key' => 'employee_dir_department' ],  // phpcs:ignore WordPress.DB.SlowDBQuery
	];
	$sort_args = $sort_map[ $args['sort'] ] ?? $sort_map['name_asc'];

	$query_args = array_merge(
		[
			'number'      => absint( $args['per_page'] ),
			'paged'       => absint( $args['paged'] ),
			'count_total' => true,
		],
		$sort_args
	);

	// Role filter: settings roles are the base; shortcode role__in narrows further.
	$settings_roles = ! empty( $settings['roles'] ) ? $settings['roles'] : [];
	$arg_roles      = array_filter( array_map( 'sanitize_key', (array) $args['role__in'] ) );
	if ( $arg_roles ) {
		$effective_roles = $settings_roles ? array_values( array_intersect( $settings_roles, $arg_roles ) ) : $arg_roles;
	} else {
		$effective_roles = $settings_roles;
	}
	if ( ! empty( $effective_roles ) ) {
		$query_args['role__in'] = $effective_roles;
	}

	// Letter filter takes priority over text search (mutual exclusion).
	if ( ! empty( $args['letter'] ) ) {
		$letter = strtoupper( substr( sanitize_text_field( $args['letter'] ), 0, 1 ) );
		if ( ctype_alpha( $letter ) ) {
			$query_args['search']         = $letter . '*';
			$query_args['search_columns'] = [ 'display_name' ];
		}
	} elseif ( ! empty( $args['search'] ) ) {
		$query_args['search']         = '*' . sanitize_text_field( $args['search'] ) . '*';
		$query_args['search_columns'] = [ 'display_name', 'user_email', 'user_login' ];
	}

	if ( ! empty( $args['department'] ) ) {
		$query_args['meta_query'] = [ // phpcs:ignore WordPress.DB.SlowDBQuery
			[
				'key'     => 'employee_dir_department',
				'value'   => sanitize_text_field( $args['department'] ),
				'compare' => '=',
			],
		];
	}

	// New-hires filter: restrict to users whose start date is within the configured window.
	if ( ! empty( $args['new_hires_only'] ) ) {
		$new_hire_days = absint( $settings['new_hire_days'] );
		if ( $new_hire_days > 0 ) {
			// Advance to the first day of the next month so the cutoff aligns with how
			// profile-card.php computes the badge (start_date normalized to YYYY-MM-01).
			// e.g. 90 days back = Nov 30 → 'first day of next month' = Dec 1 → '2025-12',
			// matching the card badge which treats '2025-12' as Dec 1 (89 days, within window).
			$cutoff = ( new DateTime( 'today' ) )
				->modify( "-{$new_hire_days} days" )
				->modify( 'first day of next month' )
				->format( 'Y-m' );
			$new_hire_clause = [ // phpcs:ignore WordPress.DB.SlowDBQuery
				'key'     => 'employee_dir_start_date',
				'value'   => $cutoff,
				'compare' => '>=',
				'type'    => 'CHAR',
			];
			// Merge with any existing meta_query (e.g. department filter).
			if ( isset( $query_args['meta_query'] ) ) {
				$query_args['meta_query'][] = $new_hire_clause;
			} else {
				$query_args['meta_query'] = [ $new_hire_clause ]; // phpcs:ignore WordPress.DB.SlowDBQuery
			}
		}
	}

	// Exclude blocked users from every query.
	$blocked = array_filter( array_map( 'absint', (array) $settings['blocked_users'] ) );
	if ( ! empty( $blocked ) ) {
		$query_args['exclude'] = $blocked;
	}

	/**
	 * Filters the WP_User_Query arguments before the employee query runs.
	 *
	 * @param array $query_args Arguments passed to WP_User_Query.
	 * @param array $args       Normalised args passed to employee_dir_get_employee_query().
	 */
	$query_args = apply_filters( 'employee_dir_query_args', $query_args, $args );

	return new WP_User_Query( $query_args );
}

/**
 * Query employees and return an array of WP_User objects.
 * Thin wrapper around employee_dir_get_employee_query() for callers that
 * only need results, not the total count.
 *
 * @param array $args See employee_dir_get_employee_query() for accepted keys.
 * @return WP_User[]
 */
function employee_dir_get_employees( array $args = [] ) {
	return employee_dir_get_employee_query( $args )->get_results();
}

// ---------------------------------------------------------------------------
// Pagination helper
// ---------------------------------------------------------------------------

/**
 * Generate pagination nav HTML.
 *
 * Renders Prev, numbered page buttons with ellipsis compression, and Next.
 * Each button carries a data-page attribute; JS handles clicks via AJAX.
 *
 * @param int $total_pages
 * @param int $current_page
 * @return string HTML string (empty when there is only one page).
 */
function employee_dir_pagination_html( $total_pages, $current_page ) {
	if ( $total_pages <= 1 ) {
		return '';
	}

	$current_page = max( 1, min( $total_pages, (int) $current_page ) );

	$html  = '<nav class="ed-pagination" id="ed-pagination" aria-label="' . esc_attr__( 'Directory pages', 'internal-staff-directory' ) . '">';

	// Previous button.
	$prev_disabled = ( 1 === $current_page ) ? ' disabled aria-disabled="true"' : '';
	$html .= '<button type="button" class="ed-pagination__btn ed-pagination__prev"'
		. $prev_disabled
		. ' data-page="' . ( $current_page - 1 ) . '"'
		. ' aria-label="' . esc_attr__( 'Previous page', 'internal-staff-directory' ) . '">'
		. '&laquo;</button>';

	// Numbered pages with ellipsis: always show first, last, current ±1.
	$pages_to_show = [];
	for ( $i = 1; $i <= $total_pages; $i++ ) {
		if ( $i === 1 || $i === $total_pages || abs( $i - $current_page ) <= 1 ) {
			$pages_to_show[] = $i;
		}
	}

	$prev_shown = null;
	foreach ( $pages_to_show as $page ) {
		if ( null !== $prev_shown && $page - $prev_shown > 1 ) {
			$html .= '<span class="ed-pagination__ellipsis" aria-hidden="true">&hellip;</span>';
		}
		$is_current  = ( $page === $current_page );
		$aria_current = $is_current ? ' aria-current="page"' : '';
		$html .= '<button type="button"'
			. ' class="ed-pagination__btn' . ( $is_current ? ' is-current' : '' ) . '"'
			. ' data-page="' . $page . '"'
			. $aria_current . '>'
			. $page
			. '</button>';
		$prev_shown = $page;
	}

	// Next button.
	$next_disabled = ( $total_pages === $current_page ) ? ' disabled aria-disabled="true"' : '';
	$html .= '<button type="button" class="ed-pagination__btn ed-pagination__next"'
		. $next_disabled
		. ' data-page="' . ( $current_page + 1 ) . '"'
		. ' aria-label="' . esc_attr__( 'Next page', 'internal-staff-directory' ) . '">'
		. '&raquo;</button>';

	$html .= '</nav>';

	return $html;
}

// ---------------------------------------------------------------------------
// Shortcode
// ---------------------------------------------------------------------------

/**
 * [employee_directory] shortcode.
 * Renders the full directory with search form on page load.
 *
 * @return string HTML output.
 */
function employee_dir_shortcode( $atts ) {
	$atts = shortcode_atts( [
		'department' => '',
		'per_page'   => 0,   // 0 = use plugin settings default
		'role'       => '',
	], $atts, 'employee_directory' );

	$locked_department = sanitize_text_field( $atts['department'] );
	$locked_per_page   = absint( $atts['per_page'] );
	$locked_role       = sanitize_text_field( $atts['role'] );

	$settings = employee_dir_get_settings();

	if ( $settings['require_login'] && ! is_user_logged_in() ) {
		return '<p class="ed-no-results">' . esc_html__( 'You must be logged in to view the staff directory.', 'internal-staff-directory' ) . '</p>';
	}

	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	$search = isset( $_GET['ed_search'] ) ? sanitize_text_field( wp_unslash( $_GET['ed_search'] ) ) : '';
	// If a department is locked via shortcode attribute, ignore the URL param.
	$department = $locked_department ?: ( isset( $_GET['ed_dept'] ) ? sanitize_text_field( wp_unslash( $_GET['ed_dept'] ) ) : '' );
	$paged      = isset( $_GET['ed_page'] ) ? max( 1, absint( $_GET['ed_page'] ) ) : 1;
	// phpcs:enable

	$query_args = compact( 'search', 'department', 'paged' );
	if ( $locked_per_page > 0 ) {
		$query_args['per_page'] = $locked_per_page;
	}
	if ( '' !== $locked_role ) {
		$query_args['role__in'] = [ $locked_role ];
	}

	$query    = employee_dir_get_employee_query( $query_args );
	$per_page = $locked_per_page > 0 ? $locked_per_page : $settings['per_page'];

	$employees   = $query->get_results();
	$total_pages = ( $per_page > 0 )
		? (int) ceil( $query->get_total() / $per_page )
		: 1;
	$departments = employee_dir_get_departments();
	$pagination  = employee_dir_pagination_html( $total_pages, $paged );

	// Pass pagination state to JS so the first AJAX request knows the right page.
	wp_localize_script( 'internal-staff-directory', 'employeeDirPage', [
		'currentPage' => $paged,
		'totalPages'  => $total_pages,
	] );

	// Pass locked shortcode constraints so JS always sends them with every AJAX request.
	wp_localize_script( 'internal-staff-directory', 'employeeDirLocked', [
		'department' => $locked_department,
		'perPage'    => $locked_per_page,
		'role'       => $locked_role,
	] );

	ob_start();
	include EMPLOYEE_DIR_PLUGIN_DIR . 'templates/directory.php';
	return ob_get_clean();
}
add_shortcode( 'employee_directory', 'employee_dir_shortcode' );

/**
 * [employee_new_hires] shortcode.
 * Renders a card grid of employees whose start date falls within the
 * new_hire_days window configured in Settings → Internal Staff Directory.
 * No search or filter controls — intended as a spotlight widget.
 *
 * Attributes:
 *   per_page (int)    – Max cards to show. Default: plugin settings value.
 *   role     (string) – Restrict to a single WP role slug.
 *
 * @param array $atts Shortcode attributes.
 * @return string HTML output.
 */
function employee_dir_new_hires_shortcode( $atts ) {
	$atts = shortcode_atts( [
		'per_page' => 0,
		'role'     => '',
	], $atts, 'employee_new_hires' );

	$locked_per_page = absint( $atts['per_page'] );
	$locked_role     = sanitize_text_field( $atts['role'] );

	$settings = employee_dir_get_settings();

	if ( $settings['require_login'] && ! is_user_logged_in() ) {
		return '<p class="ed-no-results">' . esc_html__( 'You must be logged in to view the staff directory.', 'internal-staff-directory' ) . '</p>';
	}

	$query_args = [
		'new_hires_only' => true,
		'sort'           => 'start_date_desc',
	];
	if ( $locked_per_page > 0 ) {
		$query_args['per_page'] = $locked_per_page;
	}
	if ( '' !== $locked_role ) {
		$query_args['role__in'] = [ $locked_role ];
	}

	$employees      = employee_dir_get_employees( $query_args );
	$visible_fields = $settings['new_hire_visible_fields'];

	ob_start();
	include EMPLOYEE_DIR_PLUGIN_DIR . 'templates/new-hires.php';
	return ob_get_clean();
}
add_shortcode( 'employee_new_hires', 'employee_dir_new_hires_shortcode' );

/**
 * [employee_birthdays] shortcode.
 * Renders a card grid of employees whose birthday (month + day) falls within
 * the configured window relative to today. No year — privacy-safe.
 *
 * Attributes:
 *   days_before (int)    – Days before today to include. Default: plugin setting.
 *   days_after  (int)    – Days after today to include. Default: plugin setting.
 *   role        (string) – Restrict to a single WP role slug.
 *
 * @param array $atts Shortcode attributes.
 * @return string HTML output.
 */
function employee_dir_birthdays_shortcode( $atts ) {
	$atts = shortcode_atts( [
		'days_before' => '',
		'days_after'  => '',
		'role'        => '',
	], $atts, 'employee_birthdays' );

	$settings    = employee_dir_get_settings();
	$locked_role = sanitize_text_field( $atts['role'] );

	if ( $settings['require_login'] && ! is_user_logged_in() ) {
		return '<p class="ed-no-results">' . esc_html__( 'You must be logged in to view the staff directory.', 'internal-staff-directory' ) . '</p>';
	}

	// Use per-shortcode overrides when provided; fall back to settings defaults.
	$days_before = '' !== $atts['days_before']
		? min( 30, absint( $atts['days_before'] ) )
		: absint( $settings['birthday_days_before'] );
	$days_after  = '' !== $atts['days_after']
		? min( 30, absint( $atts['days_after'] ) )
		: absint( $settings['birthday_days_after'] );

	$extra_args = [];
	if ( '' !== $locked_role ) {
		$extra_args['role__in'] = [ $locked_role ];
	}

	$birthday_entries = employee_dir_get_birthday_employees( $days_before, $days_after, $extra_args );
	$visible_fields   = $settings['visible_fields'];

	ob_start();
	include EMPLOYEE_DIR_PLUGIN_DIR . 'templates/birthdays.php';
	return ob_get_clean();
}
add_shortcode( 'employee_birthdays', 'employee_dir_birthdays_shortcode' );

// ---------------------------------------------------------------------------
// AJAX handler
// ---------------------------------------------------------------------------

/**
 * AJAX handler: returns filtered employee card HTML + updated pagination.
 * Used by the JS search/filter/pagination UI.
 */
function employee_dir_ajax_search() {
	check_ajax_referer( 'employee_dir_search', 'nonce' );

	$settings = employee_dir_get_settings();

	if ( $settings['require_login'] && ! is_user_logged_in() ) {
		wp_send_json_error( [ 'message' => __( 'You must be logged in to view the staff directory.', 'internal-staff-directory' ) ] );
	}

	$search     = isset( $_POST['search'] )     ? sanitize_text_field( wp_unslash( $_POST['search'] ) )     : '';
	$department = isset( $_POST['department'] ) ? sanitize_text_field( wp_unslash( $_POST['department'] ) ) : '';
	$paged      = isset( $_POST['paged'] )      ? max( 1, absint( $_POST['paged'] ) )                       : 1;
	$sort       = isset( $_POST['sort'] )       ? sanitize_key( wp_unslash( $_POST['sort'] ) )               : 'name_asc';
	$letter     = isset( $_POST['letter'] )     ? sanitize_text_field( wp_unslash( $_POST['letter'] ) )     : '';
	$role__in   = ( isset( $_POST['role'] ) && '' !== $_POST['role'] )
		? [ sanitize_key( wp_unslash( $_POST['role'] ) ) ]
		: [];

	$per_page = absint( isset( $_POST['per_page'] ) ? $_POST['per_page'] : 0 ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

	$query_args = compact( 'search', 'department', 'paged', 'sort', 'letter', 'role__in' );
	if ( $per_page > 0 ) {
		$query_args['per_page'] = $per_page;
	}

	$query       = employee_dir_get_employee_query( $query_args );
	$employees   = $query->get_results();
	$effective_per_page = $per_page > 0 ? $per_page : $settings['per_page'];
	$total_pages = ( $effective_per_page > 0 )
		? (int) ceil( $query->get_total() / $effective_per_page )
		: 1;

	$visible_fields = $settings['visible_fields'];

	ob_start();
	foreach ( $employees as $user ) {
		$profile = employee_dir_get_profile( $user->ID );
		include EMPLOYEE_DIR_PLUGIN_DIR . 'templates/profile-card.php';
	}
	$html = ob_get_clean();

	if ( empty( trim( $html ) ) ) {
		$html = '<p class="ed-no-results">' . esc_html__( 'No employees found.', 'internal-staff-directory' ) . '</p>';
	}

	wp_send_json_success( [
		'html'        => $html,
		'pagination'  => employee_dir_pagination_html( $total_pages, $paged ),
		'totalPages'  => $total_pages,
		'currentPage' => $paged,
	] );
}
add_action( 'wp_ajax_employee_dir_search',        'employee_dir_ajax_search' );
add_action( 'wp_ajax_nopriv_employee_dir_search', 'employee_dir_ajax_search' );

// ---------------------------------------------------------------------------
// Asset enqueueing
// ---------------------------------------------------------------------------

/**
 * Enqueue front-end assets on pages that contain the shortcode or the
 * individual profile page (ed_profile query var).
 */
function employee_dir_enqueue_assets() {
	global $post;

	$is_directory = is_a( $post, 'WP_Post' ) && (
		has_shortcode( $post->post_content, 'employee_directory' ) ||
		has_shortcode( $post->post_content, 'employee_new_hires' ) ||
		has_shortcode( $post->post_content, 'employee_birthdays' )
	);
	$is_profile   = (bool) get_query_var( 'ed_profile' );

	if ( ! $is_directory && ! $is_profile ) {
		return;
	}

	wp_enqueue_style(
		'internal-staff-directory',
		EMPLOYEE_DIR_PLUGIN_URL . 'assets/directory.css',
		[],
		EMPLOYEE_DIR_VERSION
	);

	wp_enqueue_script(
		'internal-staff-directory',
		EMPLOYEE_DIR_PLUGIN_URL . 'assets/directory.js',
		[ 'jquery' ],
		EMPLOYEE_DIR_VERSION,
		true
	);

	wp_localize_script( 'internal-staff-directory', 'employeeDir', [
		'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		'nonce'   => wp_create_nonce( 'employee_dir_search' ),
		'action'  => 'employee_dir_search',
	] );
}
add_action( 'wp_enqueue_scripts', 'employee_dir_enqueue_assets' );
