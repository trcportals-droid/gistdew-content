<?php
/**
 * Daily WhatsApp Channel kit: emails the top 3 new posts, formatted for WhatsApp,
 * so they can be copied into the channel by hand (WhatsApp has no official Channels API).
 */

defined( 'ABSPATH' ) || exit;

function gdap_wa_settings() {
	return wp_parse_args(
		(array) get_option( 'gdap_wa', array() ),
		array(
			'enabled' => 1,
			'email'   => 'trcportals@gmail.com',
			'time'    => '08:00',
		)
	);
}

/** Newest 3 posts from the last 24 hours, topped up with the latest posts if there are fewer. */
function gdap_wa_posts() {
	$args  = array( 'post_type' => 'post', 'post_status' => 'publish', 'numberposts' => 3 );
	$posts = get_posts( $args + array( 'date_query' => array( array( 'after' => '24 hours ago', 'column' => 'post_date_gmt' ) ) ) );
	if ( count( $posts ) < 3 ) {
		$have = wp_list_pluck( $posts, 'ID' );
		$more = get_posts( array_merge( $args, array( 'numberposts' => 3 - count( $posts ), 'post__not_in' => $have ?: array( 0 ) ) ) );
		$posts = array_merge( $posts, $more );
	}
	return $posts;
}

/** One ready-to-paste WhatsApp message for a post (WhatsApp uses *bold*). */
function gdap_wa_message( $p ) {
	$cats  = get_the_category( $p->ID );
	$cat   = $cats ? $cats[0]->name : 'GistDew';
	$emoji = array(
		'Japa & Visas'   => '✈️',
		'Money'          => '💷',
		'Naija News'     => '🇳🇬',
		'Entertainment'  => '🎬',
		'Diaspora Life'  => '🏠',
		'Jobs & Careers' => '💼',
		'Sports'         => '⚽',
	)[ $cat ] ?? '📰';
	$link  = add_query_arg(
		array( 'utm_source' => 'whatsapp', 'utm_medium' => 'channel', 'utm_campaign' => 'daily' ),
		get_permalink( $p )
	);
	$hook  = wp_strip_all_tags( get_the_excerpt( $p ) );
	$title = html_entity_decode( get_the_title( $p ), ENT_QUOTES, 'UTF-8' );
	$hook  = html_entity_decode( $hook, ENT_QUOTES, 'UTF-8' );
	return "{$emoji} *{$title}*\n\n{$hook}\n\n👉 Read the full gist: {$link}";
}

function gdap_wa_email_html( $posts ) {
	$h  = '<div style="font-family:Arial,sans-serif;max-width:620px;margin:0 auto;color:#1d2b24">';
	$h .= '<h2 style="color:#0b6b3a;margin:0 0 6px">Today\'s WhatsApp posts</h2>';
	$h .= '<p style="margin:0 0 18px;color:#555">Copy each box into the GistDew channel as its own message. Spacing them out (morning, lunchtime, evening) gets more views. The link preview with the picture appears on its own once the link is in the message.</p>';
	foreach ( $posts as $i => $p ) {
		$msg   = gdap_wa_message( $p );
		$img   = get_the_post_thumbnail_url( $p, 'medium' );
		$share = 'https://wa.me/?text=' . rawurlencode( $msg );
		$h    .= '<div style="border:1px solid #dfe8e2;border-radius:10px;padding:14px;margin:0 0 16px">';
		$h    .= '<p style="margin:0 0 8px;font-size:12px;color:#888;text-transform:uppercase;letter-spacing:.06em">Post ' . ( $i + 1 ) . ' of ' . count( $posts ) . '</p>';
		if ( $img ) {
			$h .= '<img src="' . esc_url( $img ) . '" alt="" style="width:100%;max-width:300px;border-radius:8px;display:block;margin:0 0 10px">';
		}
		$h .= '<div style="white-space:pre-wrap;background:#f3faf6;border-radius:8px;padding:12px;font-size:15px;line-height:1.45">' . esc_html( $msg ) . '</div>';
		$h .= '<p style="margin:10px 0 0"><a href="' . esc_url( $share ) . '" style="background:#25d366;color:#fff;text-decoration:none;padding:8px 14px;border-radius:6px;font-weight:bold;display:inline-block">Open in WhatsApp</a></p>';
		$h .= '</div>';
	}
	$h .= '<p style="font-size:12px;color:#888">Sent by the GistDew Autopost plugin. Change the time or address in WordPress → Settings → GistDew Autopost.</p></div>';
	return $h;
}

function gdap_send_wa_email() {
	$s     = gdap_wa_settings();
	$posts = gdap_wa_posts();
	if ( ! $posts ) {
		return new WP_Error( 'gdap_wa', 'No posts to send.' );
	}
	$top     = html_entity_decode( get_the_title( $posts[0] ), ENT_QUOTES, 'UTF-8' );
	$subject = 'WhatsApp posts for ' . wp_date( 'D j M' ) . ': ' . wp_trim_words( $top, 8, '…' );
	$from    = 'gistdew@' . preg_replace( '/^www\./', '', wp_parse_url( home_url(), PHP_URL_HOST ) );
	$ok      = wp_mail(
		$s['email'],
		$subject,
		gdap_wa_email_html( $posts ),
		array( 'Content-Type: text/html; charset=UTF-8', 'From: GistDew <' . $from . '>' )
	);
	$msg = $ok ? count( $posts ) . ' posts emailed to ' . $s['email'] . '.' : 'The server could not send the email (wp_mail failed).';
	update_option( 'gdap_wa_log', array( 'time' => time(), 'msg' => $msg ), false );
	if ( ! $ok ) {
		return new WP_Error( 'gdap_wa', $msg );
	}
	update_option( 'gdap_wa_last_day', wp_date( 'Y-m-d' ), false );
	return array( 'added' => count( $posts ), 'skipped' => 0, 'errors' => array() );
}

/* Rides on the digest's 15-minute tick: once a day, after the set time. */
add_action(
	'gdap_digest_tick',
	function () {
		$s = gdap_wa_settings();
		if ( ! $s['enabled'] || get_option( 'gdap_wa_last_day' ) === wp_date( 'Y-m-d' ) || wp_date( 'H:i' ) < $s['time'] ) {
			return;
		}
		gdap_send_wa_email();
	},
	20
);

add_action(
	'admin_init',
	function () {
		register_setting(
			'gdap_wa',
			'gdap_wa',
			array(
				'sanitize_callback' => function ( $in ) {
					$in = is_array( $in ) ? $in : array();
					return array(
						'enabled' => empty( $in['enabled'] ) ? 0 : 1,
						'email'   => sanitize_email( $in['email'] ?? '' ) ?: 'trcportals@gmail.com',
						'time'    => preg_match( '/^([01]\d|2[0-3]):[0-5]\d$/', $in['time'] ?? '' ) ? $in['time'] : '08:00',
					);
				},
			)
		);
	}
);

add_action(
	'admin_post_gdap_wa_now',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Not allowed.' );
		}
		check_admin_referer( 'gdap_wa_now' );
		$r = gdap_send_wa_email();
		set_transient( 'gdap_notice', is_wp_error( $r ) ? 'err:' . $r->get_error_message() : 'ok:' . get_option( 'gdap_wa_log' )['msg'], 60 );
		wp_safe_redirect( admin_url( 'options-general.php?page=gistdew-autopost' ) );
		exit;
	}
);

function gdap_wa_admin_section() {
	$s   = gdap_wa_settings();
	$log = get_option( 'gdap_wa_log' );
	?>
	<h2 style="margin-top:30px">6. WhatsApp Channel posts (by email)</h2>
	<p>Every morning after the time below, the site emails you the top 3 new posts, written up for WhatsApp. Copy each one into your channel.</p>
	<?php if ( $log ) : ?><p><strong>Last email (<?php echo esc_html( wp_date( 'j M, H:i', $log['time'] ) ); ?>):</strong> <?php echo esc_html( $log['msg'] ); ?></p><?php endif; ?>
	<form method="post" action="options.php">
		<?php settings_fields( 'gdap_wa' ); ?>
		<table class="form-table" role="presentation">
			<tr><th scope="row">Send daily</th><td><label><input type="checkbox" name="gdap_wa[enabled]" value="1" <?php checked( $s['enabled'], 1 ); ?>> Email me the WhatsApp posts every morning</label></td></tr>
			<tr><th scope="row"><label for="gdap_wa_email">Send to</label></th><td><input type="email" class="regular-text" id="gdap_wa_email" name="gdap_wa[email]" value="<?php echo esc_attr( $s['email'] ); ?>"></td></tr>
			<tr><th scope="row"><label for="gdap_wa_time">Send after (UK time)</label></th><td><input type="time" id="gdap_wa_time" name="gdap_wa[time]" value="<?php echo esc_attr( $s['time'] ); ?>"></td></tr>
		</table>
		<?php submit_button( 'Save WhatsApp settings' ); ?>
	</form>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="gdap_wa_now"><?php wp_nonce_field( 'gdap_wa_now' ); ?>
		<?php submit_button( 'Email today\'s WhatsApp posts now', 'secondary', 'gdap_wa_send', false ); ?>
	</form>
	<?php
}
