<?php
/**
 * Plugin Name: SB118 Private Sites
 * Description: Enforces login requirement on private multisite sub-sites (blog_public=0).
 *              Blocks REST API, RSS/Atom feeds, and direct page access for unauthenticated users.
 *              Replaces jonradio-private-site with a single must-use plugin.
 * Version: 1.0.0
 * Author: StarBase 118
 * Network: true
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check if the current site is marked as private (blog_public = 0).
 */
function sb118_is_private_site() {
	return '0' === get_option( 'blog_public' );
}

/**
 * Block REST API requests on private sites for unauthenticated users.
 */
add_filter( 'rest_authentication_errors', function ( $result ) {
	if ( ! sb118_is_private_site() ) {
		return $result;
	}

	if ( is_user_logged_in() ) {
		return $result;
	}

	return new WP_Error(
		'rest_login_required',
		__( 'This site is private. Authentication is required.' ),
		array( 'status' => 401 )
	);
}, 99 );

/**
 * Disable RSS/Atom feeds on private sites for unauthenticated users.
 */
add_action( 'do_feed', 'sb118_block_private_feeds', 1 );
add_action( 'do_feed_rdf', 'sb118_block_private_feeds', 1 );
add_action( 'do_feed_rss', 'sb118_block_private_feeds', 1 );
add_action( 'do_feed_rss2', 'sb118_block_private_feeds', 1 );
add_action( 'do_feed_atom', 'sb118_block_private_feeds', 1 );

function sb118_block_private_feeds() {
	if ( ! sb118_is_private_site() ) {
		return;
	}

	if ( is_user_logged_in() ) {
		return;
	}

	wp_die(
		__( 'This site is private. Please log in to access feeds.' ),
		__( 'Private Site' ),
		array( 'response' => 403 )
	);
}

/**
 * Redirect unauthenticated users to the login page on private sites.
 * Allows wp-login.php, wp-cron.php, and admin-ajax.php through.
 */
add_action( 'template_redirect', function () {
	if ( ! sb118_is_private_site() ) {
		return;
	}

	if ( is_user_logged_in() ) {
		return;
	}

	// Don't block login, cron, or AJAX
	$script = isset( $_SERVER['SCRIPT_NAME'] ) ? basename( $_SERVER['SCRIPT_NAME'] ) : '';
	$allowed = array( 'wp-login.php', 'wp-cron.php', 'admin-ajax.php', 'wp-activate.php' );
	if ( in_array( $script, $allowed, true ) ) {
		return;
	}

	wp_safe_redirect( wp_login_url( $_SERVER['REQUEST_URI'] ) );
	exit;
}, 0 );

/**
 * Remove feed links from <head> on private sites for unauthenticated users.
 */
add_action( 'wp', function () {
	if ( ! sb118_is_private_site() ) {
		return;
	}

	if ( is_user_logged_in() ) {
		return;
	}

	remove_action( 'wp_head', 'feed_links', 2 );
	remove_action( 'wp_head', 'feed_links_extra', 3 );
} );
