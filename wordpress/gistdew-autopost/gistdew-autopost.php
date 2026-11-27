<?php
/**
 * Plugin Name: GistDew Autopost
 * Description: Imports GistDew posts (with cover images and categories) from the bundled launch pack and from your daily GitHub content feed.
 * Version:     1.1.0
 * Author:      GistDew
 * License:     GPL-2.0-or-later
 * Text Domain: gistdew-autopost
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GDAP_VERSION', '1.1.0' );
define( 'GDAP_DIR', plugin_dir_path( __FILE__ ) );
define( 'GDAP_MAX_PER_RUN', 12 );

require_once GDAP_DIR . 'pages.php';

/* ------------------------------------------------------------------ *
 * Settings
 * ------------------------------------------------------------------ */
function gdap_settings() {
	$o = get_option( 'gdap_settings', array() );
	return wp_parse_args(
		is_array( $o ) ? $o : array(),
		array(
			'feed_url' => '',
			'status'   => 'publish',
			'author'   => 0,
			'enabled'  => 1,
		)
	);
}

function gdap_categories() {
	return array(
		'japa-visas'    => array( 'Japa & Visas', 'Visa news, immigration rules and relocation guides for the UK, Canada, the US and beyond.' ),
		'money'         => array( 'Money', 'Exchange rates, sending money home, banking and building your finances abroad.' ),
		'naija-news'    => array( 'Naija News', 'The biggest stories from home, explained for Nigerians abroad.' ),
		'entertainment' => array( 'Entertainment', 'Afrobeats, Nollywood, celebrity gist and the culture.' ),
		'diaspora-life' => array( 'Diaspora Life', 'Living abroad: food, family, faith, community and survival tips.' ),
		'jobs-careers'  => array( 'Jobs & Careers', 'Finding work, CVs, interviews and growing your career abroad.' ),
		'sports'        => array( 'Sports', 'Super Eagles, Nigerian stars abroad and the sports everyone is talking about.' ),
	);
}

/* ------------------------------------------------------------------ *
 * Activation: categories + schedule
 * ------------------------------------------------------------------ */
register_activation_hook( __FILE__, 'gdap_activate' );
function gdap_activate() {
	foreach ( gdap_categories() as $slug => $c ) {
		if ( ! term_exists( $slug, 'category' ) ) {
			wp_insert_term( $c[0], 'category', array( 'slug' => $slug, 'description' => $c[1] ) );
		}
	}
	if ( ! wp_next_scheduled( 'gdap_check_feed' ) ) {
		wp_schedule_event( time() + 300, 'hourly', 'gdap_check_feed' );
	}
}

register_deactivation_hook( __FILE__, 'gdap_deactivate' );
function gdap_deactivate() {
	wp_clear_scheduled_hook( 'gdap_check_feed' );
}

/* ------------------------------------------------------------------ *
 * Import engine
 * ------------------------------------------------------------------ */

/**
 * Import posts from a decoded feed.
 *
 * @param array  $feed   Decoded posts.json.
 * @param string $base   Base URL (remote feed) or absolute directory (launch pack) for images.
 * @param bool   $local  Whether $base is a local directory.
 * @param string $status Post status for new posts.
 * @return array|WP_Error Counts.
 */
function gdap_import( $feed, $base, $local, $status ) {
	if ( empty( $feed['posts'] ) || ! is_array( $feed['posts'] ) ) {
		return new WP_Error( 'gdap_empty', 'The feed has no posts.' );
	}
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$s       = gdap_settings();
	$author  = (int) $s['author'];
	if ( ! $author || ! get_userdata( $author ) ) {
		$admins = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) );
		$author = $admins ? (int) $admins[0] : 1;
	}

	$added = 0;
	$skipped = 0;
	$errors = array();

	// Oldest first, so the newest ends up on top.
	$posts = $feed['posts'];
	usort( $posts, fn( $a, $b ) => strcmp( (string) ( $a['date'] ?? '' ), (string) ( $b['date'] ?? '' ) ) );

	foreach ( $posts as $p ) {
		if ( $added >= GDAP_MAX_PER_RUN && ! $local ) {
			break;
		}
		$id    = sanitize_title( $p['id'] ?? ( $p['slug'] ?? '' ) );
		$title = sanitize_text_field( $p['title'] ?? '' );
		if ( ! $id || ! $title ) {
			continue;
		}
		$exists = get_posts( array( 'post_type' => 'post', 'post_status' => 'any', 'meta_key' => '_gdap_id', 'meta_value' => $id, 'fields' => 'ids', 'numberposts' => 1 ) );
		if ( $exists || get_page_by_path( sanitize_title( $p['slug'] ?? $id ), OBJECT, 'post' ) ) {
			$skipped++;
			continue;
		}

		// Category.
		$cat_ids = array();
		$cname   = sanitize_text_field( $p['category'] ?? '' );
		$cslug   = sanitize_title( $p['category_slug'] ?? $cname );
		if ( $cname ) {
			$term = term_exists( $cslug, 'category' );
			if ( ! $term ) {
				$term = wp_insert_term( $cname, 'category', array( 'slug' => $cslug ) );
			}
			if ( ! is_wp_error( $term ) ) {
				$cat_ids[] = (int) ( is_array( $term ) ? $term['term_id'] : $term );
			}
		}

		// Date: keep the feed date if it's in the past, otherwise now.
		$ts       = strtotime( (string) ( $p['date'] ?? '' ) );
		$ts       = ( $ts && $ts <= time() ) ? $ts : time();
		$date_gmt = gmdate( 'Y-m-d H:i:s', $ts );

		$post_id = wp_insert_post(
			array(
				'post_title'    => $title,
				'post_name'     => sanitize_title( $p['slug'] ?? $id ),
				'post_content'  => wp_kses_post( $p['content'] ?? '' ),
				'post_excerpt'  => sanitize_textarea_field( $p['excerpt'] ?? '' ),
				'post_status'   => in_array( $status, array( 'publish', 'draft', 'pending' ), true ) ? $status : 'draft',
				'post_author'   => $author,
				'post_category' => $cat_ids,
				'post_date_gmt' => $date_gmt,
				'post_date'     => get_date_from_gmt( $date_gmt ),
				'tags_input'    => array_map( 'sanitize_text_field', (array) ( $p['tags'] ?? array() ) ),
				'meta_input'    => array( '_gdap_id' => $id ),
			),
			true
		);
		if ( is_wp_error( $post_id ) ) {
			$errors[] = $title . ': ' . $post_id->get_error_message();
			continue;
		}

		// Stock photo first (Openverse), generated cover card as fallback.
		$photo_q = sanitize_text_field( $p['photo_query'] ?? '' );
		if ( $photo_q ) {
			$att = gdap_attach_photo( $photo_q, $post_id );
			if ( ! is_wp_error( $att ) ) {
				$added++;
				continue;
			}
			$errors[] = $title . ' (photo): ' . $att->get_error_message();
		}

		// Cover image.
		$img = (string) ( $p['image'] ?? '' );
		if ( $img && preg_match( '/^[A-Za-z0-9_\/.-]+\.(jpe?g|png|webp)$/i', $img ) && false === strpos( $img, '..' ) ) {
			$att = $local ? gdap_attach_local( trailingslashit( $base ) . $img, $post_id ) : gdap_attach_remote( trailingslashit( $base ) . $img, $post_id );
			if ( is_wp_error( $att ) ) {
				$errors[] = $title . ' (image): ' . $att->get_error_message();
			} else {
				set_post_thumbnail( $post_id, $att );
				update_post_meta( $att, '_wp_attachment_image_alt', sanitize_text_field( $p['image_alt'] ?? $title ) );
			}
		}
		$added++;
	}

	delete_transient( 'gd_trending_5' );
	return array( 'added' => $added, 'skipped' => $skipped, 'errors' => $errors );
}

function gdap_attach_local( $file, $post_id ) {
	if ( ! is_readable( $file ) ) {
		return new WP_Error( 'gdap_missing', 'Image file not found.' );
	}
	$bits = wp_upload_bits( basename( $file ), null, file_get_contents( $file ) ); // phpcs:ignore
	if ( ! empty( $bits['error'] ) ) {
		return new WP_Error( 'gdap_upload', $bits['error'] );
	}
	$type = wp_check_filetype( $bits['file'] );
	$att  = wp_insert_attachment(
		array(
			'post_mime_type' => $type['type'],
			'post_title'     => get_the_title( $post_id ),
			'post_status'    => 'inherit',
		),
		$bits['file'],
		$post_id
	);
	if ( is_wp_error( $att ) ) {
		return $att;
	}
	wp_update_attachment_metadata( $att, wp_generate_attachment_metadata( $att, $bits['file'] ) );
	return $att;
}

function gdap_attach_remote( $url, $post_id ) {
	if ( 0 !== stripos( $url, 'https://' ) ) {
		return new WP_Error( 'gdap_url', 'Images must be served over HTTPS.' );
	}
	$tmp = download_url( $url, 30 );
	if ( is_wp_error( $tmp ) ) {
		return $tmp;
	}
	$file = array( 'name' => basename( wp_parse_url( $url, PHP_URL_PATH ) ), 'tmp_name' => $tmp );
	$att  = media_handle_sideload( $file, $post_id, get_the_title( $post_id ) );
	if ( is_wp_error( $att ) ) {
		@unlink( $tmp ); // phpcs:ignore
	}
	return $att;
}

/* ------------------------------------------------------------------ *
 * Free stock photos from Openverse (CC0, public domain, CC BY only),
 * credited under the image.
 * ------------------------------------------------------------------ */
function gdap_find_photo( $query, $skip = array() ) {
	$url = add_query_arg(
		array(
			'q'            => rawurlencode( $query ),
			'license'      => 'cc0,pdm,by',
			'category'     => 'photograph',
			'aspect_ratio' => 'wide',
			'size'         => 'large',
			'mature'       => 'false',
			'page_size'    => 20,
		),
		'https://api.openverse.org/v1/images/'
	);
	$res = wp_remote_get( $url, array( 'timeout' => 20, 'headers' => array( 'User-Agent' => 'GistDew/' . GDAP_VERSION . ' (+' . home_url( '/' ) . ')' ) ) );
	if ( is_wp_error( $res ) ) {
		return $res;
	}
	if ( 200 !== (int) wp_remote_retrieve_response_code( $res ) ) {
		return new WP_Error( 'gdap_ov', 'Openverse returned HTTP ' . (int) wp_remote_retrieve_response_code( $res ) . '.' );
	}
	$data = json_decode( wp_remote_retrieve_body( $res ), true );
	foreach ( (array) ( $data['results'] ?? array() ) as $r ) {
		$u = (string) ( $r['url'] ?? '' );
		if ( 0 !== stripos( $u, 'https://' ) || in_array( $r['id'] ?? '', $skip, true ) ) {
			continue;
		}
		if ( ! preg_match( '/\.(jpe?g|png|webp)(\?.*)?$/i', $u ) && ! in_array( strtolower( (string) ( $r['filetype'] ?? '' ) ), array( 'jpg', 'jpeg', 'png', 'webp' ), true ) ) {
			continue;
		}
		if ( ! empty( $r['width'] ) && (int) $r['width'] < 1000 ) {
			continue;
		}
		return $r;
	}
	return new WP_Error( 'gdap_ov_none', 'No suitable free photo found for "' . $query . '".' );
}

function gdap_photo_credit( $r ) {
	$lic     = strtolower( (string) ( $r['license'] ?? '' ) );
	$lic_txt = 'cc0' === $lic ? 'CC0' : ( 'pdm' === $lic ? 'Public domain' : 'CC ' . strtoupper( $lic ) . ' ' . ( $r['license_version'] ?? '' ) );
	$lic_url = (string) ( $r['license_url'] ?? '' );
	$creator = trim( (string) ( $r['creator'] ?? '' ) );
	$landing = (string) ( $r['foreign_landing_url'] ?? '' );
	$out     = 'Photo';
	if ( $creator ) {
		$out .= ': ' . ( $landing ? '<a href="' . esc_url( $landing ) . '" rel="nofollow noopener" target="_blank">' . esc_html( $creator ) . '</a>' : esc_html( $creator ) );
	}
	$out .= ' (' . ( $lic_url ? '<a href="' . esc_url( $lic_url ) . '" rel="nofollow noopener" target="_blank">' . esc_html( trim( $lic_txt ) ) . '</a>' : esc_html( trim( $lic_txt ) ) ) . ')';
	return $out;
}

function gdap_attach_photo( $query, $post_id, $skip = array() ) {
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$r = gdap_find_photo( $query, $skip );
	if ( is_wp_error( $r ) ) {
		return $r;
	}
	$tmp = download_url( $r['url'], 40 );
	if ( is_wp_error( $tmp ) ) {
		return $tmp;
	}
	$mime = wp_get_image_mime( $tmp );
	$ext  = array( 'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp' )[ $mime ] ?? '';
	if ( ! $ext ) {
		@unlink( $tmp ); // phpcs:ignore
		return new WP_Error( 'gdap_type', 'The photo was not a JPG, PNG or WebP.' );
	}
	$name = sanitize_title( get_post_field( 'post_name', $post_id ) ) . '-photo.' . $ext;
	$att  = media_handle_sideload( array( 'name' => $name, 'tmp_name' => $tmp ), $post_id, get_the_title( $post_id ) );
	if ( is_wp_error( $att ) ) {
		@unlink( $tmp ); // phpcs:ignore
		return $att;
	}
	wp_update_post( array( 'ID' => $att, 'post_excerpt' => gdap_photo_credit( $r ) ) );
	update_post_meta( $att, '_wp_attachment_image_alt', sanitize_text_field( (string) ( $r['title'] ?? $query ) ) );
	update_post_meta( $att, '_gdap_openverse_id', sanitize_text_field( (string) ( $r['id'] ?? '' ) ) );
	$old = (int) get_post_thumbnail_id( $post_id );
	set_post_thumbnail( $post_id, $att );
	if ( $old && $old !== $att && get_post_meta( $old, '_gdap_openverse_id', true ) ) {
		wp_delete_attachment( $old, true ); // Only removes a previous stock photo, never a cover card.
	}
	update_post_meta( $post_id, '_gdap_photo_q', $query );
	return $att;
}

/**
 * Give existing imported posts a stock photo. Uses the photo phrases in the
 * launch pack and the GitHub feed. A post is redone when its phrase changes,
 * or when $redo lists it (to try the next photo).
 */
function gdap_refresh_photos( $limit = 6, $redo = array() ) {
	$queries = array();
	$sources = array( GDAP_DIR . 'launch/posts.json' );
	foreach ( $sources as $f ) {
		$d = is_readable( $f ) ? json_decode( file_get_contents( $f ), true ) : null; // phpcs:ignore
		foreach ( (array) ( $d['posts'] ?? array() ) as $p ) {
			if ( ! empty( $p['photo_query'] ) ) {
				$queries[ sanitize_title( $p['id'] ) ] = sanitize_text_field( $p['photo_query'] );
			}
		}
	}
	$s = gdap_settings();
	if ( $s['feed_url'] ) {
		$res = wp_remote_get( add_query_arg( 't', time(), $s['feed_url'] ), array( 'timeout' => 20 ) );
		$d   = is_wp_error( $res ) ? null : json_decode( wp_remote_retrieve_body( $res ), true );
		foreach ( (array) ( $d['posts'] ?? array() ) as $p ) {
			if ( ! empty( $p['photo_query'] ) ) {
				$queries[ sanitize_title( $p['id'] ) ] = sanitize_text_field( $p['photo_query'] );
			}
		}
	}
	$done = 0;
	$left = 0;
	$errors = array();
	foreach ( get_posts( array( 'post_type' => 'post', 'post_status' => 'any', 'meta_key' => '_gdap_id', 'numberposts' => -1, 'fields' => 'ids' ) ) as $pid ) {
		$gid = get_post_meta( $pid, '_gdap_id', true );
		if ( empty( $queries[ $gid ] ) ) {
			continue;
		}
		$force = in_array( (int) $pid, $redo, true );
		if ( ! $force && get_post_meta( $pid, '_gdap_photo_q', true ) === $queries[ $gid ] ) {
			continue;
		}
		if ( $done >= $limit ) {
			$left++;
			continue;
		}
		$skip = array();
		if ( $force ) {
			$skip = array_filter( (array) get_post_meta( $pid, '_gdap_photo_skip', true ) );
			$cur  = get_post_meta( (int) get_post_thumbnail_id( $pid ), '_gdap_openverse_id', true );
			if ( $cur ) {
				$skip[] = $cur;
				update_post_meta( $pid, '_gdap_photo_skip', array_values( array_unique( $skip ) ) );
			}
		}
		$r = gdap_attach_photo( $queries[ $gid ], $pid, $skip );
		if ( is_wp_error( $r ) ) {
			$errors[] = get_the_title( $pid ) . ': ' . $r->get_error_message();
			update_post_meta( $pid, '_gdap_photo_q', $queries[ $gid ] ); // don't retry forever
		}
		$done++;
	}
	return array( 'added' => $done, 'skipped' => $left, 'errors' => $errors );
}

/* Launch pack bundled with the plugin. */
function gdap_import_launch_pack() {
	$dir  = GDAP_DIR . 'launch';
	$json = $dir . '/posts.json';
	if ( ! is_readable( $json ) ) {
		return new WP_Error( 'gdap_nopack', 'Launch pack not found.' );
	}
	$feed = json_decode( file_get_contents( $json ), true ); // phpcs:ignore
	return gdap_import( $feed, $dir, true, 'publish' );
}

/* Daily feed from GitHub (checked hourly). */
add_action( 'gdap_check_feed', 'gdap_check_feed' );
function gdap_check_feed() {
	$s = gdap_settings();
	if ( empty( $s['enabled'] ) || empty( $s['feed_url'] ) ) {
		return null;
	}
	$url = $s['feed_url'];
	if ( 0 !== stripos( $url, 'https://' ) ) {
		return gdap_log_error( 'The feed URL must start with https://' );
	}
	$res = wp_remote_get( add_query_arg( 't', time(), $url ), array( 'timeout' => 25, 'limit_response_size' => 4 * MB_IN_BYTES ) );
	if ( is_wp_error( $res ) ) {
		return gdap_log_error( $res->get_error_message() );
	}
	if ( 200 !== (int) wp_remote_retrieve_response_code( $res ) ) {
		return gdap_log_error( 'Feed returned HTTP ' . (int) wp_remote_retrieve_response_code( $res ) . '.' );
	}
	$feed = json_decode( wp_remote_retrieve_body( $res ), true );
	if ( ! is_array( $feed ) ) {
		return gdap_log_error( 'The feed is not valid JSON.' );
	}
	$base   = substr( $url, 0, strrpos( $url, '/' ) );
	$result = gdap_import( $feed, $base, false, $s['status'] );
	if ( is_wp_error( $result ) ) {
		return gdap_log_error( $result->get_error_message() );
	}
	update_option( 'gdap_last_check', array( 'time' => time(), 'added' => $result['added'], 'errors' => $result['errors'] ), false );
	delete_option( 'gdap_last_error' );
	return $result;
}

function gdap_log_error( $msg ) {
	update_option( 'gdap_last_error', array( 'time' => time(), 'msg' => $msg ), false );
	return new WP_Error( 'gdap', $msg );
}

/* ------------------------------------------------------------------ *
 * Google AdSense: site-verification code in <head> and /ads.txt
 * ------------------------------------------------------------------ */
function gdap_pub_id() {
	$id = gdap_settings()['adsense'];
	return preg_match( '/^pub-\d{10,20}$/', $id ) ? $id : '';
}

add_action( 'wp_head', function () {
	$pub = gdap_pub_id();
	if ( ! $pub || is_admin() ) {
		return;
	}
	echo '<meta name="google-adsense-account" content="ca-' . esc_attr( $pub ) . '">' . "\n";
	echo '<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-' . esc_attr( $pub ) . '" crossorigin="anonymous"></script>' . "\n"; // phpcs:ignore
}, 2 );

add_action( 'init', function () {
	$path = strtok( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), '?' ); // phpcs:ignore
	if ( '/ads.txt' !== $path ) {
		return;
	}
	$pub = gdap_pub_id();
	if ( ! $pub ) {
		return; // Normal 404 until a publisher ID is saved.
	}
	header( 'Content-Type: text/plain; charset=utf-8' );
	header( 'Cache-Control: public, max-age=3600' );
	echo 'google.com, ' . esc_html( $pub ) . ", DIRECT, f08c47fec0942fa0\n";
	exit;
}, 0 );

/* ------------------------------------------------------------------ *
 * Admin page: Settings → GistDew Autopost
 * ------------------------------------------------------------------ */
add_action( 'admin_menu', fn() => add_options_page( 'GistDew Autopost', 'GistDew Autopost', 'manage_options', 'gistdew-autopost', 'gdap_page' ) );

add_action( 'admin_init', 'gdap_register' );
function gdap_register() {
	register_setting(
		'gdap',
		'gdap_settings',
		array(
			'sanitize_callback' => function ( $in ) {
				$in = is_array( $in ) ? $in : array();
				return array(
					'feed_url' => esc_url_raw( trim( $in['feed_url'] ?? '' ), array( 'https' ) ),
					'status'   => in_array( $in['status'] ?? '', array( 'publish', 'draft', 'pending' ), true ) ? $in['status'] : 'publish',
					'author'   => absint( $in['author'] ?? 0 ),
					'enabled'  => empty( $in['enabled'] ) ? 0 : 1,
					'adsense'  => preg_match( '/(pub-\d{10,20})/', (string) ( $in['adsense'] ?? '' ), $m ) ? $m[1] : '',
					'email'    => sanitize_email( $in['email'] ?? '' ),
				);
			},
		)
	);
}

add_action( 'admin_post_gdap_action', 'gdap_handle_action' );
function gdap_handle_action() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Not allowed.' );
	}
	check_admin_referer( 'gdap_action' );
	$what = sanitize_key( $_POST['gdap_do'] ?? '' );
	if ( 'launch' === $what ) {
		$r = gdap_import_launch_pack();
	} elseif ( 'photos' === $what ) {
		$r = gdap_refresh_photos( 6 );
	} elseif ( 'redo' === $what ) {
		$r = gdap_refresh_photos( 1, array( absint( $_POST['gdap_post'] ?? 0 ) ) );
	} elseif ( 'pages' === $what ) {
		$email = gdap_settings()['email'] ? gdap_settings()['email'] : get_option( 'admin_email' );
		$r     = gdap_create_pages( $email, ! empty( $_POST['gdap_overwrite'] ) );
	} else {
		$r = gdap_check_feed();
		if ( null === $r ) {
			$r = new WP_Error( 'gdap', 'Add your feed URL first.' );
		}
	}
	$fmt = array(
		'photos' => 'ok:%d posts got a new photo, %d still to do (click again).',
		'redo'   => 'ok:%d post got a different photo.%.0s',
		'pages'  => 'ok:%d pages created or updated, %d already there.',
	)[ $what ] ?? 'ok:%d new posts imported, %d already on the site.';
	$msg = is_wp_error( $r ) ? 'err:' . $r->get_error_message() : sprintf( $fmt, $r['added'], $r['skipped'] ) . ( $r['errors'] ? ' Problems: ' . implode( ' | ', array_slice( $r['errors'], 0, 3 ) ) : '' );
	set_transient( 'gdap_notice', $msg, 60 );
	wp_safe_redirect( admin_url( 'options-general.php?page=gistdew-autopost' ) );
	exit;
}

function gdap_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$s      = gdap_settings();
	$last   = get_option( 'gdap_last_check' );
	$err    = get_option( 'gdap_last_error' );
	$notice = get_transient( 'gdap_notice' );
	delete_transient( 'gdap_notice' );
	$count  = (int) ( new WP_Query( array( 'post_type' => 'post', 'post_status' => 'any', 'meta_key' => '_gdap_id', 'fields' => 'ids', 'posts_per_page' => 1 ) ) )->found_posts;
	?>
	<div class="wrap">
		<h1>GistDew Autopost</h1>
		<?php if ( $notice ) : ?>
			<div class="notice notice-<?php echo 0 === strpos( $notice, 'ok:' ) ? 'success' : 'error'; ?> is-dismissible"><p><?php echo esc_html( substr( $notice, strpos( $notice, ':' ) + 1 ) ); ?></p></div>
		<?php endif; ?>

		<h2>1. Launch posts</h2>
		<p>Imports the 20 launch posts with their cover images and categories. Safe to run more than once: posts already on the site are skipped.</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="gdap_action"><input type="hidden" name="gdap_do" value="launch">
			<?php wp_nonce_field( 'gdap_action' ); ?>
			<?php submit_button( 'Import launch posts', 'primary', 'gdap_launch', false ); ?>
		</form>

		<h2 style="margin-top:30px">2. Daily posts from GitHub</h2>
		<p>Posts imported by this plugin so far: <strong><?php echo (int) $count; ?></strong>.
		<?php if ( $last ) : ?>Last check: <?php echo esc_html( wp_date( 'j M Y, H:i', $last['time'] ) ); ?> (<?php echo (int) $last['added']; ?> new).<?php endif; ?>
		<?php if ( $err ) : ?><br><span style="color:#b32d2e">Last problem (<?php echo esc_html( wp_date( 'j M, H:i', $err['time'] ) ); ?>): <?php echo esc_html( $err['msg'] ); ?></span><?php endif; ?></p>

		<form method="post" action="options.php">
			<?php settings_fields( 'gdap' ); ?>
			<table class="form-table" role="presentation">
				<tr><th scope="row"><label for="gdap_url">Feed URL</label></th>
					<td><input type="url" class="regular-text code" id="gdap_url" name="gdap_settings[feed_url]" value="<?php echo esc_attr( $s['feed_url'] ); ?>" placeholder="https://raw.githubusercontent.com/you/gistdew-content/main/posts.json">
					<p class="description">The raw GitHub link to <code>posts.json</code>. The site checks it every hour and imports anything new.</p></td></tr>
				<tr><th scope="row">New posts are</th>
					<td><select name="gdap_settings[status]">
						<option value="publish" <?php selected( $s['status'], 'publish' ); ?>>Published straight away</option>
						<option value="draft" <?php selected( $s['status'], 'draft' ); ?>>Saved as drafts for me to review</option>
					</select></td></tr>
				<tr><th scope="row">Author</th>
					<td><?php wp_dropdown_users( array( 'name' => 'gdap_settings[author]', 'selected' => $s['author'], 'role__in' => array( 'administrator', 'editor', 'author' ), 'show_option_none' => 'First administrator', 'option_none_value' => 0 ) ); ?></td></tr>
				<tr><th scope="row"><label for="gdap_ads">AdSense publisher ID</label></th>
					<td><input type="text" class="regular-text code" id="gdap_ads" name="gdap_settings[adsense]" value="<?php echo esc_attr( $s['adsense'] ); ?>" placeholder="pub-1234567890123456">
					<p class="description">Adds the AdSense code to every page and serves <a href="<?php echo esc_url( home_url( '/ads.txt' ) ); ?>" target="_blank">/ads.txt</a>. Leave empty to switch both off.</p></td></tr>
				<tr><th scope="row"><label for="gdap_email">Contact email</label></th>
					<td><input type="email" class="regular-text" id="gdap_email" name="gdap_settings[email]" value="<?php echo esc_attr( $s['email'] ); ?>" placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>">
					<p class="description">Shown on the Contact, About and policy pages.</p></td></tr>
				<tr><th scope="row">Automatic checks</th>
					<td><label><input type="checkbox" name="gdap_settings[enabled]" value="1" <?php checked( $s['enabled'], 1 ); ?>> Check the feed every hour</label></td></tr>
			</table>
			<?php submit_button( 'Save settings' ); ?>
		</form>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="gdap_action"><input type="hidden" name="gdap_do" value="feed">
			<?php wp_nonce_field( 'gdap_action' ); ?>
			<?php submit_button( 'Check feed now', 'secondary', 'gdap_feed', false ); ?>
		</form>

		<h2 style="margin-top:30px">3. Stock photos</h2>
		<p>Swaps the cover cards for free, commercially licensed photos from <a href="https://openverse.org" target="_blank" rel="noopener">Openverse</a> (CC0, public domain or CC BY), credited under each photo. Does 6 posts per click.</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="gdap_action"><input type="hidden" name="gdap_do" value="photos">
			<?php wp_nonce_field( 'gdap_action' ); ?>
			<?php submit_button( 'Add stock photos', 'secondary', 'gdap_photos', false ); ?>
		</form>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:10px">
			<input type="hidden" name="action" value="gdap_action"><input type="hidden" name="gdap_do" value="redo">
			<?php wp_nonce_field( 'gdap_action' ); ?>
			Don't like a photo? Post ID <input type="number" name="gdap_post" min="1" style="width:90px">
			<?php submit_button( 'Try a different photo', 'secondary', 'gdap_redo', false ); ?>
		</form>

		<h2 style="margin-top:30px">4. Site pages</h2>
		<p>Creates About, Contact, Editorial policy, Privacy policy, Cookie policy and Terms of use, adds them to the footer and removes the Sample Page. Pages you've already published are left alone.</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="gdap_action"><input type="hidden" name="gdap_do" value="pages">
			<?php wp_nonce_field( 'gdap_action' ); ?>
			<label><input type="checkbox" name="gdap_overwrite" value="1"> Overwrite existing versions of these pages</label><br><br>
			<?php submit_button( 'Create site pages', 'secondary', 'gdap_pages', false ); ?>
		</form>
	</div>
	<?php
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), function ( $links ) {
	array_unshift( $links, '<a href="' . esc_url( admin_url( 'options-general.php?page=gistdew-autopost' ) ) . '">Settings</a>' );
	return $links;
} );
