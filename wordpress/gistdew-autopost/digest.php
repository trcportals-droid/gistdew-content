<?php
/**
 * Daily email digest through SendFox.
 *
 * Every morning (default 07:30 UK time) the site builds an email from the
 * posts published in the last 24 hours and schedules it in SendFox to the
 * GistDew list. If the daily writing agent has left a digest.json next to
 * posts.json in the GitHub repo for today, its subject line and intro are
 * used; otherwise a plain subject and intro are written automatically.
 *
 * @package GistDew
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function gdap_digest_settings() {
	$o = get_option( 'gdap_digest', array() );
	return wp_parse_args(
		is_array( $o ) ? $o : array(),
		array(
			'enabled'    => 0,
			'token'      => '',
			'list_id'    => '',
			'from_name'  => 'GistDew',
			'from_email' => '',
			'time'       => '07:30',
		)
	);
}

/* ---------- SendFox API ---------- */
function gdap_sendfox( $method, $path, $body = null ) {
	$s = gdap_digest_settings();
	if ( ! $s['token'] ) {
		return new WP_Error( 'gdap_sf', 'Add your SendFox API token first.' );
	}
	$args = array(
		'method'  => $method,
		'timeout' => 25,
		'headers' => array(
			'Authorization' => 'Bearer ' . $s['token'],
			'Accept'        => 'application/json',
			'Content-Type'  => 'application/json',
		),
	);
	if ( null !== $body ) {
		$args['body'] = wp_json_encode( $body );
	}
	$res = wp_remote_request( 'https://api.sendfox.com' . $path, $args );
	if ( is_wp_error( $res ) ) {
		return $res;
	}
	$code = (int) wp_remote_retrieve_response_code( $res );
	$data = json_decode( wp_remote_retrieve_body( $res ), true );
	if ( $code < 200 || $code >= 300 ) {
		$msg = is_array( $data ) && ! empty( $data['message'] ) ? $data['message'] : 'HTTP ' . $code;
		return new WP_Error( 'gdap_sf', 'SendFox: ' . $msg );
	}
	return $data;
}

/* ---------- Building the email ---------- */
function gdap_digest_posts() {
	$last = (int) get_option( 'gdap_digest_last_ts', 0 );
	$from = max( $last, time() - DAY_IN_SECONDS );
	return get_posts(
		array(
			'post_type'   => 'post',
			'post_status' => 'publish',
			'numberposts' => 8,
			'date_query'  => array( array( 'after' => gmdate( 'Y-m-d H:i:s', $from ), 'column' => 'post_date_gmt' ) ),
		)
	);
}

/** Today's copy from the writing agent (digest.json next to posts.json), if any. */
function gdap_digest_copy() {
	$feed = gdap_settings()['feed_url'];
	if ( ! $feed ) {
		return array();
	}
	$url = substr( $feed, 0, strrpos( $feed, '/' ) ) . '/digest.json';
	$res = wp_remote_get( add_query_arg( 't', time(), $url ), array( 'timeout' => 15 ) );
	if ( is_wp_error( $res ) || 200 !== (int) wp_remote_retrieve_response_code( $res ) ) {
		return array();
	}
	$d = json_decode( wp_remote_retrieve_body( $res ), true );
	if ( ! is_array( $d ) || ( $d['date'] ?? '' ) !== wp_date( 'Y-m-d' ) ) {
		return array(); // stale copy from another day
	}
	return array(
		'subject' => sanitize_text_field( $d['subject'] ?? '' ),
		'preview' => sanitize_text_field( $d['preview'] ?? '' ),
		'intro'   => sanitize_textarea_field( $d['intro'] ?? '' ),
		'signoff' => sanitize_text_field( $d['signoff'] ?? '' ),
	);
}

function gdap_digest_html( $posts, $copy ) {
	$green = '#0e7a55';
	$intro = $copy['intro'] ?? '';
	if ( ! $intro ) {
		$intro = 'Good morning! Here is today\'s gist for Nigerians and Africans abroad: the stories worth your coffee break.';
	}
	$h  = '<div style="background:#f3f6f4;padding:24px 0;font-family:Arial,Helvetica,sans-serif;color:#1b2621">';
	$h .= '<div style="max-width:600px;margin:0 auto;background:#ffffff;border-radius:12px;overflow:hidden">';
	$h .= '<div style="background:' . $green . ';padding:20px 24px;color:#fff;font-size:26px;font-weight:800">Gist<span style="color:#9ff0c8">Dew</span> <span style="font-size:14px;font-weight:400;opacity:.9">Daily · ' . esc_html( wp_date( 'j F Y' ) ) . '</span></div>';
	$h .= '<div style="padding:22px 24px 6px;font-size:16px;line-height:1.55">' . wpautop( esc_html( $intro ) ) . '</div>';
	foreach ( $posts as $i => $p ) {
		$link = add_query_arg( array( 'utm_source' => 'sendfox', 'utm_medium' => 'email', 'utm_campaign' => 'daily-digest' ), get_permalink( $p ) );
		$cats = get_the_category( $p->ID );
		$cat  = $cats ? $cats[0]->name : '';
		$img  = function_exists( 'gistdew_share_image' ) ? gistdew_share_image( $p->ID ) : '';
		$img  = $img ? $img : get_the_post_thumbnail_url( $p, 'large' );
		$h   .= '<div style="padding:16px 24px;border-top:1px solid #e6ece8">';
		if ( $img && 0 === $i ) {
			$h .= '<a href="' . esc_url( $link ) . '"><img src="' . esc_url( $img ) . '" alt="" width="552" style="width:100%;height:auto;border-radius:8px;display:block;margin-bottom:12px"></a>';
		}
		if ( $cat ) {
			$h .= '<div style="font-size:12px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:' . $green . '">' . esc_html( $cat ) . '</div>';
		}
		$h .= '<a href="' . esc_url( $link ) . '" style="display:block;margin:4px 0 6px;font-size:19px;font-weight:700;color:#1b2621;text-decoration:none;line-height:1.3">' . esc_html( get_the_title( $p ) ) . '</a>';
		$h .= '<div style="font-size:15px;line-height:1.5;color:#45524b">' . esc_html( wp_html_excerpt( get_the_excerpt( $p ), 220, '…' ) ) . '</div>';
		$h .= '<a href="' . esc_url( $link ) . '" style="display:inline-block;margin-top:8px;font-size:14px;font-weight:700;color:' . $green . '">Read the full gist →</a>';
		$h .= '</div>';
	}
	$sign = ! empty( $copy['signoff'] ) ? $copy['signoff'] : 'See you tomorrow morning. Forward this to someone who needs the gist.';
	$h   .= '<div style="padding:18px 24px;border-top:1px solid #e6ece8;font-size:15px">' . esc_html( $sign ) . '<br><strong>The GistDew team</strong></div>';
	$h   .= '<div style="padding:14px 24px 22px;font-size:12px;color:#6b7a72;background:#fafcfb">You signed up at gistdew.com. News and general information only, not legal or financial advice. <a href="' . esc_url( home_url( '/' ) ) . '" style="color:#6b7a72">Visit GistDew</a></div>';
	$h   .= '</div></div>';
	return $h;
}

/**
 * Build and schedule today's digest.
 *
 * @param bool $dry_run Only build it (for the preview button).
 */
function gdap_send_digest( $dry_run = false ) {
	$s = gdap_digest_settings();
	if ( ! $dry_run && ( ! $s['enabled'] || ! $s['token'] || ! $s['list_id'] ) ) {
		return new WP_Error( 'gdap_sf', 'Turn the digest on and add your SendFox token and list ID first.' );
	}
	if ( ! $dry_run && get_option( 'gdap_digest_last_day' ) === wp_date( 'Y-m-d' ) ) {
		return new WP_Error( 'gdap_sf', 'Today\'s digest has already been scheduled.' );
	}
	$posts = gdap_digest_posts();
	if ( ! $posts ) {
		return new WP_Error( 'gdap_sf', 'No new posts in the last 24 hours, so no email today.' );
	}
	$title   = function ( $p ) {
		return html_entity_decode( wp_strip_all_tags( get_the_title( $p ) ), ENT_QUOTES, 'UTF-8' );
	};
	$copy    = gdap_digest_copy();
	$subject = $copy['subject'] ?? '';
	$subject = $subject ? $subject : wp_html_excerpt( $title( $posts[0] ), 120, '…' ) . ' + more gist';
	$preview = $copy['preview'] ?? '';
	$preview = $preview ? $preview : wp_html_excerpt( implode( ' · ', array_map( $title, array_slice( $posts, 1, 3 ) ) ), 180, '…' );
	$html    = gdap_digest_html( $posts, $copy );
	if ( $dry_run ) {
		return array( 'subject' => $subject, 'preview' => $preview, 'html' => $html, 'count' => count( $posts ) );
	}
	$from = $s['from_email'];
	if ( ! $from ) {
		$me   = gdap_sendfox( 'GET', '/me' );
		$from = is_array( $me ) && ! empty( $me['email'] ) ? $me['email'] : '';
	}
	if ( ! $from ) {
		return new WP_Error( 'gdap_sf', 'Add the "From" email address that is verified in SendFox.' );
	}
	$camp = gdap_sendfox(
		'POST',
		'/campaigns',
		array(
			'title'        => 'GistDew Daily ' . wp_date( 'Y-m-d' ),
			'subject'      => $subject,
			'preview_text' => $preview,
			'html'         => $html,
			'from_name'    => $s['from_name'] ? $s['from_name'] : 'GistDew',
			'from_email'   => $from,
			'lists'        => array( (int) $s['list_id'] ),
			'scheduled_at' => gmdate( 'Y-m-d\TH:i:s\Z', time() + 5 * MINUTE_IN_SECONDS ),
		)
	);
	if ( is_wp_error( $camp ) ) {
		update_option( 'gdap_digest_log', array( 'time' => time(), 'msg' => $camp->get_error_message() ), false );
		return $camp;
	}
	update_option( 'gdap_digest_last_day', wp_date( 'Y-m-d' ), false );
	update_option( 'gdap_digest_last_ts', time(), false );
	update_option( 'gdap_digest_log', array( 'time' => time(), 'msg' => 'Scheduled "' . $subject . '" with ' . count( $posts ) . ' stories.' ), false );
	return array( 'added' => count( $posts ), 'skipped' => 0, 'errors' => array() );
}

/* ---------- Schedule: check every 15 minutes, send once a day after the set time ---------- */
add_filter(
	'cron_schedules',
	function ( $s ) {
		$s['gdap_15min'] = array( 'interval' => 15 * MINUTE_IN_SECONDS, 'display' => 'Every 15 minutes' );
		return $s;
	}
);
add_action(
	'init',
	function () {
		if ( ! wp_next_scheduled( 'gdap_digest_tick' ) ) {
			wp_schedule_event( time() + 120, 'gdap_15min', 'gdap_digest_tick' );
		}
	}
);
add_action(
	'gdap_digest_tick',
	function () {
		$s = gdap_digest_settings();
		if ( ! $s['enabled'] || get_option( 'gdap_digest_last_day' ) === wp_date( 'Y-m-d' ) ) {
			return;
		}
		if ( wp_date( 'H:i' ) < $s['time'] ) {
			return;
		}
		gdap_send_digest();
	}
);

/* ---------- Settings ---------- */
add_action(
	'admin_init',
	function () {
		register_setting(
			'gdap_digest',
			'gdap_digest',
			array(
				'sanitize_callback' => function ( $in ) {
					$old = gdap_digest_settings();
					$in  = is_array( $in ) ? $in : array();
					$tok = trim( (string) ( $in['token'] ?? '' ) );
					return array(
						'enabled'    => empty( $in['enabled'] ) ? 0 : 1,
						'token'      => '' === $tok ? $old['token'] : sanitize_text_field( $tok ), // blank = keep
						'list_id'    => preg_replace( '/\D/', '', (string) ( $in['list_id'] ?? '' ) ),
						'from_name'  => sanitize_text_field( $in['from_name'] ?? 'GistDew' ),
						'from_email' => sanitize_email( $in['from_email'] ?? '' ),
						'time'       => preg_match( '/^([01]\d|2[0-3]):[0-5]\d$/', $in['time'] ?? '' ) ? $in['time'] : '07:30',
					);
				},
			)
		);
	}
);

function gdap_digest_admin_section() {
	$s   = gdap_digest_settings();
	$log = get_option( 'gdap_digest_log' );
	?>
	<h2 style="margin-top:30px">5. Daily email digest (SendFox)</h2>
	<p>Every morning after the time below, the site emails the last 24 hours of posts to your SendFox list. Subscribers start getting it the morning after they sign up.</p>
	<?php if ( $log ) : ?><p><strong>Last run (<?php echo esc_html( wp_date( 'j M, H:i', $log['time'] ) ); ?>):</strong> <?php echo esc_html( $log['msg'] ); ?></p><?php endif; ?>
	<form method="post" action="options.php">
		<?php settings_fields( 'gdap_digest' ); ?>
		<table class="form-table" role="presentation">
			<tr><th scope="row">Send the digest</th><td><label><input type="checkbox" name="gdap_digest[enabled]" value="1" <?php checked( $s['enabled'], 1 ); ?>> Send every morning</label></td></tr>
			<tr><th scope="row"><label for="gdap_tok">SendFox API token</label></th><td><input type="password" class="regular-text" id="gdap_tok" name="gdap_digest[token]" value="" autocomplete="off" placeholder="<?php echo $s['token'] ? esc_attr__( 'Saved. Leave blank to keep it.' ) : esc_attr__( 'Paste your Personal Access Token' ); ?>"><p class="description">SendFox → Settings → API → create a Personal Access Token.</p></td></tr>
			<tr><th scope="row"><label for="gdap_list">SendFox list ID</label></th><td><input type="text" class="small-text" id="gdap_list" name="gdap_digest[list_id]" value="<?php echo esc_attr( $s['list_id'] ); ?>"><p class="description">The number in the list's address in SendFox (Audience → Lists → GistDew Daily Digest).</p></td></tr>
			<tr><th scope="row"><label for="gdap_fn">From name</label></th><td><input type="text" class="regular-text" id="gdap_fn" name="gdap_digest[from_name]" value="<?php echo esc_attr( $s['from_name'] ); ?>"></td></tr>
			<tr><th scope="row"><label for="gdap_fe">From email</label></th><td><input type="email" class="regular-text" id="gdap_fe" name="gdap_digest[from_email]" value="<?php echo esc_attr( $s['from_email'] ); ?>"><p class="description">Must be a sender address verified in SendFox. Leave empty to use your SendFox account email.</p></td></tr>
			<tr><th scope="row"><label for="gdap_time">Send after (UK time)</label></th><td><input type="time" id="gdap_time" name="gdap_digest[time]" value="<?php echo esc_attr( $s['time'] ); ?>"></td></tr>
		</table>
		<?php submit_button( 'Save digest settings' ); ?>
	</form>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;margin-right:8px">
		<input type="hidden" name="action" value="gdap_digest_preview"><?php wp_nonce_field( 'gdap_digest_preview' ); ?>
		<?php submit_button( 'Preview today\'s email', 'secondary', 'gdap_prev', false ); ?>
	</form>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block">
		<input type="hidden" name="action" value="gdap_action"><input type="hidden" name="gdap_do" value="digest"><?php wp_nonce_field( 'gdap_action' ); ?>
		<?php submit_button( 'Send today\'s digest now', 'secondary', 'gdap_digest_now', false ); ?>
	</form>
	<?php
}

add_action(
	'admin_post_gdap_digest_preview',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Not allowed.' );
		}
		check_admin_referer( 'gdap_digest_preview' );
		$d = gdap_send_digest( true );
		if ( is_wp_error( $d ) ) {
			wp_die( esc_html( $d->get_error_message() ) . ' <a href="' . esc_url( admin_url( 'options-general.php?page=gistdew-autopost' ) ) . '">Back</a>' );
		}
		echo '<!doctype html><meta charset="utf-8"><title>Digest preview</title><div style="font:14px Arial;padding:12px;background:#fff8e1"><strong>Subject:</strong> ' . esc_html( $d['subject'] ) . '<br><strong>Preview text:</strong> ' . esc_html( $d['preview'] ) . '<br>' . (int) $d['count'] . ' stories · <a href="' . esc_url( admin_url( 'options-general.php?page=gistdew-autopost' ) ) . '">Back to settings</a></div>' . $d['html']; // phpcs:ignore
		exit;
	}
);
