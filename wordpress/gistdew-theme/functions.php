<?php
/**
 * GistDew theme functions.
 *
 * @package GistDew
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GISTDEW_VERSION', '1.0.2' );

/* ------------------------------------------------------------------ *
 * Setup
 * ------------------------------------------------------------------ */
add_action( 'after_setup_theme', 'gistdew_setup' );
function gistdew_setup() {
	load_theme_textdomain( 'gistdew', get_template_directory() . '/languages' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'custom-logo', array( 'height' => 104, 'width' => 400, 'flex-height' => true, 'flex-width' => true ) );
	add_image_size( 'gistdew-card', 640, 360, true );
	add_image_size( 'gistdew-hero', 1200, 675, true );
	register_nav_menus(
		array(
			'primary' => __( 'Main menu', 'gistdew' ),
			'footer'  => __( 'Footer menu', 'gistdew' ),
		)
	);
}

add_action( 'widgets_init', 'gistdew_widgets' );
function gistdew_widgets() {
	register_sidebar(
		array(
			'name'          => __( 'Sidebar', 'gistdew' ),
			'id'            => 'sidebar-1',
			'description'   => __( 'Shown below Trending and the digest signup.', 'gistdew' ),
			'before_widget' => '<section id="%1$s" class="widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h3 class="widget-title">',
			'after_title'   => '</h3>',
		)
	);
}

add_action( 'wp_enqueue_scripts', 'gistdew_assets' );
function gistdew_assets() {
	wp_enqueue_style( 'gistdew', get_stylesheet_uri(), array(), GISTDEW_VERSION );
	wp_enqueue_script( 'gistdew', get_template_directory_uri() . '/assets/js/main.js', array(), GISTDEW_VERSION, array( 'strategy' => 'defer', 'in_footer' => true ) );
	wp_localize_script(
		'gistdew',
		'GistDew',
		array(
			'viewUrl' => esc_url_raw( rest_url( 'gistdew/v1/view/' ) ),
			'postId'  => is_singular( 'post' ) ? (string) get_queried_object_id() : '0',
		)
	);
	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}

/* Preload the main fonts and set the colour mode before first paint. */
add_action( 'wp_head', 'gistdew_head', 1 );
function gistdew_head() {
	$base = get_template_directory_uri() . '/assets/fonts/';
	echo '<link rel="preload" href="' . esc_url( $base . 'figtree-latin-400-normal.woff2' ) . '" as="font" type="font/woff2" crossorigin>' . "\n";
	echo '<link rel="preload" href="' . esc_url( $base . 'bricolage-grotesque-latin-800-normal.woff2' ) . '" as="font" type="font/woff2" crossorigin>' . "\n";
	echo "<script>try{var t=localStorage.getItem('gd-theme');if(t)document.documentElement.setAttribute('data-theme',t);}catch(e){}</script>\n";
	echo '<meta name="theme-color" content="#0e7a55">' . "\n";
}

/* Small hardening: no version leak, no XML-RPC (blocks a common bot target). */
remove_action( 'wp_head', 'wp_generator' );
add_filter( 'xmlrpc_enabled', '__return_false' );

add_filter( 'excerpt_length', fn() => 28 );
add_filter( 'excerpt_more', fn() => '…' );

/* ------------------------------------------------------------------ *
 * Customizer: GistDew settings
 * ------------------------------------------------------------------ */
add_action( 'customize_register', 'gistdew_customize' );
function gistdew_customize( $wp_customize ) {
	$wp_customize->add_section( 'gistdew', array( 'title' => __( 'GistDew settings', 'gistdew' ), 'priority' => 30 ) );

	$fields = array(
		'gd_ticker'       => array( __( 'Show breaking-news ticker', 'gistdew' ), 'checkbox', 1, 'absint' ),
		'gd_ticker_label' => array( __( 'Ticker label', 'gistdew' ), 'text', 'Fresh gist', 'sanitize_text_field' ),
		'gd_sendfox'      => array( __( 'SendFox form ID for the daily digest (the value of data-form)', 'gistdew' ), 'text', '', 'gistdew_sanitize_slug' ),
		'gd_digest_title' => array( __( 'Digest box title', 'gistdew' ), 'text', 'Get the GistDew Daily', 'sanitize_text_field' ),
		'gd_digest_text'  => array( __( 'Digest box text', 'gistdew' ), 'text', 'The top diaspora gist, visa news and money updates in your inbox every morning. Free.', 'sanitize_text_field' ),
		'gd_privacy_url'  => array( __( 'Privacy policy link', 'gistdew' ), 'url', '', 'esc_url_raw' ),
		'gd_footer_about' => array( __( 'Footer "about" text', 'gistdew' ), 'textarea', 'GistDew brings you fresh gist for Nigerians and Africans abroad: japa news, money, jobs, culture and entertainment, every day.', 'sanitize_textarea_field' ),
		'gd_facebook'     => array( 'Facebook URL', 'url', '', 'esc_url_raw' ),
		'gd_instagram'    => array( 'Instagram URL', 'url', '', 'esc_url_raw' ),
		'gd_x'            => array( 'X (Twitter) URL', 'url', '', 'esc_url_raw' ),
		'gd_tiktok'       => array( 'TikTok URL', 'url', '', 'esc_url_raw' ),
		'gd_youtube'      => array( 'YouTube URL', 'url', '', 'esc_url_raw' ),
		'gd_whatsapp'     => array( 'WhatsApp Channel URL', 'url', '', 'esc_url_raw' ),
		'gd_ad_header'    => array( __( 'Ad code: header (728×90)', 'gistdew' ), 'textarea', '', 'gistdew_sanitize_code' ),
		'gd_ad_article'   => array( __( 'Ad code: inside articles', 'gistdew' ), 'textarea', '', 'gistdew_sanitize_code' ),
		'gd_ad_sidebar'   => array( __( 'Ad code: sidebar', 'gistdew' ), 'textarea', '', 'gistdew_sanitize_code' ),
	);
	foreach ( $fields as $id => $f ) {
		$wp_customize->add_setting( $id, array( 'default' => $f[2], 'sanitize_callback' => $f[3] ) );
		$wp_customize->add_control( $id, array( 'label' => $f[0], 'section' => 'gistdew', 'type' => $f[1] ) );
	}
}

function gistdew_sanitize_slug( $v ) {
	$v = trim( (string) $v );
	return preg_match( '/^[A-Za-z0-9_-]{1,64}$/', $v ) ? $v : '';
}

/* Ad code may contain scripts: only keep it for users allowed to post unfiltered HTML. */
function gistdew_sanitize_code( $v ) {
	return current_user_can( 'unfiltered_html' ) ? (string) $v : wp_kses_post( $v );
}

/* ------------------------------------------------------------------ *
 * Helpers
 * ------------------------------------------------------------------ */
function gistdew_cat_colors() {
	return apply_filters(
		'gistdew_cat_colors',
		array(
			'japa-visas'    => '#0e7a55',
			'money'         => '#c2410c',
			'jobs-careers'  => '#1d4ed8',
			'diaspora-life' => '#7c3aed',
			'entertainment' => '#db2777',
			'sports'        => '#0891b2',
			'naija-news'    => '#b91c1c',
		)
	);
}

function gistdew_cat_color( $term ) {
	if ( ! $term ) {
		return '#0e7a55';
	}
	$map = gistdew_cat_colors();
	if ( isset( $map[ $term->slug ] ) ) {
		return $map[ $term->slug ];
	}
	$palette = array( '#0e7a55', '#c2410c', '#1d4ed8', '#7c3aed', '#db2777', '#0891b2', '#b91c1c', '#4d7c0f' );
	return $palette[ abs( crc32( $term->slug ) ) % count( $palette ) ];
}

function gistdew_primary_cat( $post_id = null ) {
	$cats = get_the_category( $post_id );
	if ( empty( $cats ) ) {
		return null;
	}
	foreach ( $cats as $c ) {
		if ( 'uncategorized' !== $c->slug ) {
			return $c;
		}
	}
	return $cats[0];
}

function gistdew_cat_pill( $post_id = null, $class = 'cat-pill' ) {
	$cat = gistdew_primary_cat( $post_id );
	if ( ! $cat ) {
		return '';
	}
	return sprintf(
		'<a class="%s" style="--c:%s" href="%s">%s</a>',
		esc_attr( $class ),
		esc_attr( gistdew_cat_color( $cat ) ),
		esc_url( get_category_link( $cat ) ),
		esc_html( $cat->name )
	);
}

function gistdew_reading_time( $post_id = null ) {
	$words = str_word_count( wp_strip_all_tags( get_post_field( 'post_content', $post_id ) ) );
	$mins  = max( 1, (int) round( $words / 220 ) );
	/* translators: %d: minutes */
	return sprintf( _n( '%d min read', '%d min read', $mins, 'gistdew' ), $mins );
}

function gistdew_thumb( $size = 'gistdew-card', $with_pill = false, $post_id = null ) {
	$post_id = $post_id ? $post_id : get_the_ID();
	$img     = has_post_thumbnail( $post_id )
		? get_the_post_thumbnail( $post_id, $size, array( 'loading' => 'lazy', 'alt' => esc_attr( get_the_title( $post_id ) ) ) )
		: '<img src="' . esc_url( get_template_directory_uri() . '/assets/placeholder.svg' ) . '" alt="" loading="lazy">';
	$pill    = $with_pill ? gistdew_cat_pill( $post_id ) : '';
	return '<div class="thumb-wrap" style="position:relative"><a class="thumb" href="' . esc_url( get_permalink( $post_id ) ) . '" tabindex="-1" aria-hidden="true">' . $img . '</a>' . ( $pill ? '<span style="position:absolute;left:10px;top:10px">' . $pill . '</span>' : '' ) . '</div>';
}

function gistdew_meta( $post_id = null, $author = true ) {
	$out = '<div class="meta">';
	if ( $author ) {
		$out .= '<span>' . esc_html( get_the_author_meta( 'display_name', get_post_field( 'post_author', $post_id ) ) ) . '</span>';
	}
	$out .= '<time datetime="' . esc_attr( get_the_date( 'c', $post_id ) ) . '">' . esc_html( get_the_date( '', $post_id ) ) . '</time>';
	$out .= '<span>' . esc_html( gistdew_reading_time( $post_id ) ) . '</span>';
	$out .= '</div>';
	return $out;
}

/* ------------------------------------------------------------------ *
 * Views (for Trending). Counted with a small REST call so it works
 * with page caching (LiteSpeed Cache, Cloudflare).
 * ------------------------------------------------------------------ */
add_action( 'rest_api_init', 'gistdew_rest' );
function gistdew_rest() {
	register_rest_route(
		'gistdew/v1',
		'/view/(?P<id>\d+)',
		array(
			'methods'             => 'POST',
			'permission_callback' => '__return_true',
			'callback'            => function ( $req ) {
				$id = (int) $req['id'];
				if ( 'post' !== get_post_type( $id ) || 'publish' !== get_post_status( $id ) ) {
					return new WP_REST_Response( array( 'ok' => false ), 404 );
				}
				$views = (int) get_post_meta( $id, 'gd_views', true );
				update_post_meta( $id, 'gd_views', $views + 1 );
				return array( 'ok' => true );
			},
		)
	);
}

function gistdew_trending( $count = 5 ) {
	$key = 'gd_trending_' . $count;
	$ids = get_transient( $key );
	if ( false === $ids ) {
		$q   = new WP_Query(
			array(
				'posts_per_page'      => $count,
				'meta_key'            => 'gd_views',
				'orderby'             => 'meta_value_num',
				'order'               => 'DESC',
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
				'fields'              => 'ids',
				'date_query'          => array( array( 'after' => '21 days ago' ) ),
			)
		);
		$ids = $q->posts;
		if ( count( $ids ) < $count ) {
			$more = get_posts(
				array(
					'numberposts'  => $count,
					'fields'       => 'ids',
					'post__not_in' => $ids,
				)
			);
			$ids  = array_slice( array_merge( $ids, $more ), 0, $count );
		}
		set_transient( $key, $ids, 30 * MINUTE_IN_SECONDS );
	}
	return $ids;
}

/* ------------------------------------------------------------------ *
 * Digest signup (SendFox) and ads
 * ------------------------------------------------------------------ */
function gistdew_digest_box( $extra_class = '' ) {
	$form = get_theme_mod( 'gd_sendfox', '' );
	if ( ! $form && ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}
	$privacy = get_theme_mod( 'gd_privacy_url', '' );
	if ( ! $privacy ) {
		$privacy = get_privacy_policy_url();
	}
	?>
	<section class="widget digest <?php echo esc_attr( $extra_class ); ?>" aria-label="<?php esc_attr_e( 'Daily digest signup', 'gistdew' ); ?>">
		<h3>📬 <?php echo esc_html( get_theme_mod( 'gd_digest_title', 'Get the GistDew Daily' ) ); ?></h3>
		<p><?php echo esc_html( get_theme_mod( 'gd_digest_text', 'The top diaspora gist, visa news and money updates in your inbox every morning. Free.' ) ); ?></p>
		<div class="sf-wrap">
			<?php if ( $form ) : ?>
				<script src="https://sendfox.com/js/embed.js" data-form="<?php echo esc_attr( $form ); ?>" data-api="https://sendfox.com" async></script>
			<?php else : ?>
				<p style="margin:0;padding:6px;color:#3f4d46;font-size:14px"><?php esc_html_e( 'Admin note: add your SendFox form ID in Appearance → Customize → GistDew settings. Visitors won\'t see this box until you do.', 'gistdew' ); ?></p>
			<?php endif; ?>
		</div>
		<small><?php esc_html_e( 'No spam. Unsubscribe anytime.', 'gistdew' ); ?>
			<?php if ( $privacy ) : ?>
				<a style="color:#fff" href="<?php echo esc_url( $privacy ); ?>"><?php esc_html_e( 'Privacy policy', 'gistdew' ); ?></a>
			<?php endif; ?>
		</small>
	</section>
	<?php
}

function gistdew_ad( $slot ) {
	$code = get_theme_mod( 'gd_ad_' . $slot, '' );
	if ( ! $code ) {
		return '';
	}
	return '<div class="ad-wrap"><div class="ad-label">' . esc_html__( 'Advertisement', 'gistdew' ) . '</div><div class="ad-slot">' . $code . '</div></div>';
}

/* Insert the in-article ad after the second paragraph. */
add_filter( 'the_content', 'gistdew_article_ad', 20 );
function gistdew_article_ad( $content ) {
	if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	$ad = gistdew_ad( 'article' );
	if ( ! $ad ) {
		return $content;
	}
	$parts = explode( '</p>', $content );
	if ( count( $parts ) < 4 ) {
		return $content . $ad;
	}
	$parts[1] .= '</p>' . $ad;
	return implode( '</p>', $parts );
}

/* ------------------------------------------------------------------ *
 * Icons and menu fallback
 * ------------------------------------------------------------------ */
function gistdew_icon( $name ) {
	$icons = array(
		'search' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>',
		'menu'   => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>',
		'close'  => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>',
		'sun'    => '<svg class="sun" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>',
		'moon'   => '<svg class="moon" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>',
	);
	return isset( $icons[ $name ] ) ? $icons[ $name ] : '';
}

function gistdew_social_links() {
	$nets = array(
		'gd_facebook'  => array( 'Facebook', '<path d="M14 9V7c0-1 .5-1.5 1.5-1.5H17V2h-2.8C11.3 2 10 3.7 10 6.4V9H7v3.5h3V22h4v-9.5h2.8L17.3 9z"/>' ),
		'gd_instagram' => array( 'Instagram', '<path d="M12 7.3A4.7 4.7 0 1 0 12 16.7 4.7 4.7 0 0 0 12 7.3zm0 7.7a3 3 0 1 1 0-6 3 3 0 0 1 0 6zm6-7.9a1.1 1.1 0 1 1-2.2 0 1.1 1.1 0 0 1 2.2 0zM12 3.6c2.7 0 3 0 4.1.1 2.7.1 4 1.4 4.1 4.1.1 1.1.1 1.4.1 4.1s0 3-.1 4.1c-.1 2.7-1.4 4-4.1 4.1-1.1.1-1.4.1-4.1.1s-3 0-4.1-.1c-2.7-.1-4-1.4-4.1-4.1C3.7 15 3.6 14.7 3.6 12s0-3 .1-4.1C3.8 5.2 5.1 3.9 7.9 3.8 9 3.7 9.3 3.6 12 3.6z"/>' ),
		'gd_x'         => array( 'X', '<path d="M17.8 3h3.1l-6.8 7.7L22 21h-6.2l-4.9-6.4L5.3 21H2.2l7.3-8.3L2 3h6.4l4.4 5.8zm-1.1 16.2h1.7L7.4 4.7H5.6z"/>' ),
		'gd_tiktok'    => array( 'TikTok', '<path d="M16.6 5.8A4.3 4.3 0 0 1 15.5 3h-3.1v12.4a2.6 2.6 0 1 1-1.8-2.5V9.7a5.7 5.7 0 1 0 4.9 5.7V9.1a7.3 7.3 0 0 0 4.3 1.4V7.4a4.3 4.3 0 0 1-3.2-1.6z"/>' ),
		'gd_youtube'   => array( 'YouTube', '<path d="M21.6 7.2a2.5 2.5 0 0 0-1.8-1.8C18.2 5 12 5 12 5s-6.2 0-7.8.4A2.5 2.5 0 0 0 2.4 7.2 26 26 0 0 0 2 12a26 26 0 0 0 .4 4.8 2.5 2.5 0 0 0 1.8 1.8C5.8 19 12 19 12 19s6.2 0 7.8-.4a2.5 2.5 0 0 0 1.8-1.8A26 26 0 0 0 22 12a26 26 0 0 0-.4-4.8zM10 15V9l5.2 3z"/>' ),
		'gd_whatsapp'  => array( 'WhatsApp', '<path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm5.3 14.1c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .1-3.2-.8-2.7-1.1-4.4-3.8-4.5-4-.1-.2-1.1-1.4-1.1-2.7s.7-1.9.9-2.2c.2-.3.5-.3.7-.3h.5c.2 0 .4 0 .6.5l.8 2c.1.2.1.3 0 .5l-.4.6c-.1.2-.3.3-.1.6.1.3.7 1.1 1.4 1.8.9.8 1.7 1.1 2 1.2.3.1.4.1.6-.1l.8-1c.2-.3.4-.2.6-.1l1.9.9c.3.1.5.2.5.3.1.2.1.8-.1 1.4z"/>' ),
	);
	$out = '';
	foreach ( $nets as $key => $n ) {
		$url = get_theme_mod( $key, '' );
		if ( $url ) {
			$out .= '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener" aria-label="' . esc_attr( $n[0] ) . '"><svg viewBox="0 0 24 24" aria-hidden="true">' . $n[1] . '</svg></a>';
		}
	}
	return $out;
}

/* If no menu is set, list categories that have posts. */
function gistdew_menu_fallback() {
	$cats = get_categories( array( 'hide_empty' => true, 'exclude' => get_option( 'default_category' ), 'orderby' => 'count', 'order' => 'DESC', 'number' => 8 ) );
	echo '<ul>';
	echo '<li' . ( is_front_page() ? ' class="current-menu-item"' : '' ) . '><a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Home', 'gistdew' ) . '</a></li>';
	foreach ( $cats as $c ) {
		$cur = is_category( $c->term_id ) ? ' class="current-cat"' : '';
		echo '<li' . $cur . '><a href="' . esc_url( get_category_link( $c ) ) . '">' . esc_html( $c->name ) . '</a></li>'; // phpcs:ignore
	}
	echo '</ul>';
}

/* Categories shown as blocks on the home page, in this order (empty ones are skipped). */
function gistdew_home_categories() {
	$order = array( 'japa-visas', 'money', 'naija-news', 'entertainment', 'diaspora-life', 'jobs-careers', 'sports' );
	$out   = array();
	foreach ( $order as $slug ) {
		$t = get_category_by_slug( $slug );
		if ( $t && $t->count > 0 ) {
			$out[] = $t;
		}
	}
	$others = get_categories( array( 'hide_empty' => true, 'exclude' => array_merge( wp_list_pluck( $out, 'term_id' ), array( (int) get_option( 'default_category' ) ) ) ) );
	return array_merge( $out, $others );
}

/* ------------------------------------------------------------------ *
 * Share previews (WhatsApp, Facebook, X, Telegram, LinkedIn).
 * Each post gets a 1200x630 JPG made from its featured image, because
 * some apps won't show WebP or very large images.
 * ------------------------------------------------------------------ */
function gistdew_share_image( $post_id ) {
	$att = (int) get_post_thumbnail_id( $post_id );
	if ( ! $att ) {
		return '';
	}
	$up   = wp_upload_dir();
	$dir  = trailingslashit( $up['basedir'] ) . 'gistdew-share';
	$name = $att . '-' . (int) get_post_modified_time( 'U', true, $att ) . '.jpg';
	if ( file_exists( "$dir/$name" ) ) {
		return trailingslashit( $up['baseurl'] ) . 'gistdew-share/' . $name;
	}
	$src = get_attached_file( $att );
	if ( ! $src || ! file_exists( $src ) ) {
		return '';
	}
	wp_mkdir_p( $dir );
	$ed = wp_get_image_editor( $src );
	if ( is_wp_error( $ed ) ) {
		return '';
	}
	$ed->resize( 1200, 630, true );
	$ed->set_quality( 80 );
	$saved = $ed->save( "$dir/$name", 'image/jpeg' );
	if ( is_wp_error( $saved ) ) {
		return '';
	}
	return trailingslashit( $up['baseurl'] ) . 'gistdew-share/' . $name;
}

add_action( 'wp_head', 'gistdew_share_tags', 5 );
function gistdew_share_tags() {
	$site  = get_bloginfo( 'name' );
	$title = $site;
	$desc  = get_bloginfo( 'description' );
	$url   = home_url( '/' );
	$img   = get_template_directory_uri() . '/assets/share-default.jpg';
	$type  = 'website';
	if ( is_singular() ) {
		$id    = get_queried_object_id();
		$title = wp_strip_all_tags( get_the_title( $id ) );
		$ex    = get_the_excerpt( $id );
		$desc  = $ex ? wp_strip_all_tags( $ex ) : $desc;
		$url   = get_permalink( $id );
		$share = gistdew_share_image( $id );
		$img   = $share ? $share : $img;
		$type  = is_single() ? 'article' : 'website';
	} elseif ( is_category() ) {
		$t     = get_queried_object();
		$title = $t->name . ' – ' . $site;
		$desc  = $t->description ? $t->description : $desc;
		$url   = get_category_link( $t );
	}
	$desc = wp_html_excerpt( $desc, 200, '…' );
	$tags = array(
		'og:site_name'   => $site,
		'og:type'        => $type,
		'og:title'       => $title,
		'og:description' => $desc,
		'og:url'         => $url,
		'og:image'       => $img,
		'og:image:width' => '1200',
		'og:image:height' => '630',
		'og:locale'      => 'en_GB',
	);
	foreach ( $tags as $k => $v ) {
		echo '<meta property="' . esc_attr( $k ) . '" content="' . esc_attr( $v ) . '">' . "\n";
	}
	if ( is_single() ) {
		echo '<meta property="article:published_time" content="' . esc_attr( get_the_date( 'c' ) ) . '">' . "\n";
		echo '<meta property="article:modified_time" content="' . esc_attr( get_the_modified_date( 'c' ) ) . '">' . "\n";
	}
	echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
	echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '">' . "\n";
	echo '<meta name="twitter:description" content="' . esc_attr( $desc ) . '">' . "\n";
	echo '<meta name="twitter:image" content="' . esc_attr( $img ) . '">' . "\n";
	echo '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
}

/* ------------------------------------------------------------------ *
 * "About this article" box at the end of each post: who writes it,
 * how it's checked and when it was last updated (trust signals for
 * readers and for Google/AdSense reviewers).
 * ------------------------------------------------------------------ */
add_filter( 'the_content', 'gistdew_article_footer', 20 );
function gistdew_article_footer( $content ) {
	if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	$author  = get_the_author_meta( 'display_name' );
	$bio     = get_the_author_meta( 'description' );
	$bio     = $bio ? $bio : 'GistDew is written for Nigerians and Africans abroad. We check every story against official sources and update guides when rules change.';
	$edit    = get_page_by_path( 'editorial-policy' );
	$contact = get_page_by_path( 'contact' );
	$updated = ( get_the_modified_time( 'U' ) - get_the_time( 'U' ) > DAY_IN_SECONDS ) ? ' · Updated ' . esc_html( get_the_modified_date() ) : '';
	$out  = '<aside class="article-about" aria-label="About this article">';
	$out .= '<p class="aa-head"><strong>' . esc_html( $author ) . '</strong> · Published ' . esc_html( get_the_date() ) . $updated . '</p>';
	$out .= '<p>' . esc_html( $bio ) . '</p>';
	$links = array();
	if ( $edit ) {
		$links[] = '<a href="' . esc_url( get_permalink( $edit ) ) . '">How we check facts</a>';
	}
	if ( $contact ) {
		$links[] = '<a href="' . esc_url( get_permalink( $contact ) ) . '">Report an error</a>';
	}
	if ( $links ) {
		$out .= '<p class="aa-links">' . implode( ' · ', $links ) . '</p>';
	}
	$out .= '</aside>';
	return $content . $out;
}
