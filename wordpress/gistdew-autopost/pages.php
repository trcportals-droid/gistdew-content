<?php
/**
 * Standard site pages: About, Contact, Privacy, Cookies, Terms, Editorial policy.
 * {email} and {updated} are filled in when the pages are created.
 *
 * @package GistDew
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function gdap_page_templates() {
	return array(
		'about'            => array(
			'title'   => 'About GistDew',
			'content' => '
<p><strong>GistDew is a daily news and lifestyle site for Nigerians and Africans living abroad, and for anyone planning the move.</strong> We cover the stories that matter when home is in one country and life is in another: visas and immigration rules, money and exchange rates, jobs and careers, entertainment and the culture, and the everyday business of settling in.</p>

<h2>What we publish</h2>
<ul>
<li><strong>Japa &amp; Visas:</strong> changes to UK, Canadian, US and other immigration rules, explained in plain English.</li>
<li><strong>Money:</strong> the pound and dollar against the naira, sending money home, banking and credit abroad.</li>
<li><strong>Naija News:</strong> the big stories from Nigeria, with the context people abroad need.</li>
<li><strong>Entertainment:</strong> Afrobeats, Nollywood, awards and what everyone is talking about.</li>
<li><strong>Diaspora Life, Jobs &amp; Careers and Sports:</strong> practical guides, career tips and the Super Eagles.</li>
</ul>

<h2>How we work</h2>
<p>Every article is written for GistDew. We check facts against official sources such as GOV.UK, Immigration, Refugees and Citizenship Canada and the Central Bank of Nigeria, and we link to those sources at the end of each article so you can read them yourself. When a rule changes or we get something wrong, we update the article. You can read more in our <a href="/editorial-policy/">editorial policy</a>.</p>

<p>GistDew gives news and general information. It is not legal, immigration or financial advice. For decisions about your own visa or money, speak to a regulated adviser (in the UK, an OISC-registered immigration adviser or a solicitor).</p>

<h2>The daily digest</h2>
<p>Our free morning email brings the day\'s top gist to your inbox. You can sign up on any article and unsubscribe at any time with one click.</p>

<h2>Get in touch</h2>
<p>Story tips, corrections and partnership enquiries are welcome. Email <a href="mailto:{email}">{email}</a> or use our <a href="/contact/">contact page</a>.</p>
',
		),
		'contact'          => array(
			'title'   => 'Contact us',
			'content' => '
<p>We\'d love to hear from you, whether it\'s a story tip, a correction, feedback on an article or an advertising enquiry.</p>

<h2>Email</h2>
<p><a href="mailto:{email}">{email}</a></p>
<p>We aim to reply within two working days.</p>

<h2>Corrections</h2>
<p>If you think something in an article is wrong or out of date, email us with the article link and what needs fixing. We review every correction request and update the article where needed.</p>

<h2>Advertising and partnerships</h2>
<p>For sponsored content, partnerships or advertising, email us with "Partnership" in the subject line. Sponsored content is always clearly labelled.</p>

<h2>Please note</h2>
<p>We can\'t give advice on individual visa applications or money matters. For your own case, please contact a regulated immigration adviser, a solicitor or a financial adviser.</p>
',
		),
		'privacy-policy'   => array(
			'title'   => 'Privacy policy',
			'content' => '
<p><em>Last updated: {updated}</em></p>

<p>This policy explains what personal information GistDew ("we", "us") collects when you use gistdew.com, how we use it and the choices you have. We follow the UK General Data Protection Regulation (UK GDPR) and the Data Protection Act 2018. If you have any questions, email <a href="mailto:{email}">{email}</a>.</p>

<h2>Information we collect</h2>
<ul>
<li><strong>Email address</strong>, if you sign up for our daily digest. We use it only to send the digest and occasional updates about GistDew.</li>
<li><strong>Comments</strong>: if you leave a comment we collect your name, email address, the comment and your IP address to help spot spam. Your email address is never shown publicly.</li>
<li><strong>Messages</strong> you send us by email.</li>
<li><strong>Technical information</strong> such as your IP address, browser type and the pages you visit. This is collected by our hosting, security and advertising providers (see below).</li>
</ul>

<h2>How and why we use it</h2>
<ul>
<li>To send the daily digest you asked for (legal basis: your consent, which you can withdraw at any time).</li>
<li>To run, secure and improve the website, and to stop spam and abuse (legal basis: our legitimate interests).</li>
<li>To show advertising that pays for the site (legal basis: your consent where cookies are used for personalised ads, otherwise our legitimate interests).</li>
<li>To reply to your messages.</li>
</ul>

<h2>Advertising and Google AdSense</h2>
<p>We use Google AdSense to show ads. Google and its partners may use cookies and similar technologies to show ads based on your visits to this and other websites. Visitors in the UK, the European Economic Area and Switzerland are asked for consent before personalised ads are shown, and can change their choice at any time using the privacy settings link shown at the bottom of the page.</p>
<p>You can learn how Google uses information from sites that use its services at <a href="https://policies.google.com/technologies/partner-sites" rel="nofollow noopener" target="_blank">policies.google.com/technologies/partner-sites</a>, and you can turn off personalised ads at <a href="https://adssettings.google.com" rel="nofollow noopener" target="_blank">adssettings.google.com</a>. Third-party vendors, including Google, use cookies to serve ads based on your prior visits to this website or other websites.</p>

<h2>Who we share information with</h2>
<p>We never sell your personal information. We share it only with the service providers that help us run GistDew:</p>
<ul>
<li><strong>SendFox</strong> (email newsletter), to store your email address and send the digest.</li>
<li><strong>Google</strong> (AdSense), to show ads and measure them.</li>
<li><strong>Cloudflare</strong>, which protects the site from attacks and bots and speeds it up.</li>
<li><strong>Namecheap</strong>, our web host.</li>
</ul>
<p>Some of these providers process data outside the UK. Where they do, they use legal safeguards such as the UK International Data Transfer Addendum or Standard Contractual Clauses.</p>

<h2>Cookies</h2>
<p>See our <a href="/cookie-policy/">cookie policy</a> for the cookies we use and how to control them.</p>

<h2>How long we keep it</h2>
<ul>
<li>Digest subscribers: until you unsubscribe.</li>
<li>Comments: for as long as the comment is on the site.</li>
<li>Emails to us: up to two years, unless we need them longer for a legal reason.</li>
</ul>

<h2>Your rights</h2>
<p>You have the right to ask for a copy of your information, to have it corrected or deleted, to object to or restrict how we use it, and to withdraw consent at any time. Email <a href="mailto:{email}">{email}</a> and we will respond within one month. If you are unhappy with how we handle your information, you can complain to the Information Commissioner\'s Office at <a href="https://ico.org.uk/make-a-complaint/" rel="nofollow noopener" target="_blank">ico.org.uk</a>.</p>

<h2>Children</h2>
<p>GistDew is written for adults. We do not knowingly collect information from children under 13.</p>

<h2>Changes</h2>
<p>We may update this policy from time to time. The date at the top shows when it last changed.</p>
',
		),
		'cookie-policy'    => array(
			'title'   => 'Cookie policy',
			'content' => '
<p><em>Last updated: {updated}</em></p>

<p>Cookies are small text files that websites store on your device. Some are needed for a site to work; others help show ads. This page explains the ones used on gistdew.com.</p>

<h2>Strictly necessary</h2>
<p>These keep the site secure and working. They don\'t need your consent.</p>
<ul>
<li><strong>Cloudflare</strong> (<code>__cf_bm</code>, <code>cf_clearance</code>): tell real visitors apart from bots and remember that you passed a security check. They last between 30 minutes and a year.</li>
<li><strong>WordPress</strong> (<code>wordpress_*</code>, <code>wp-settings-*</code>): used only if you log in or leave a comment, to remember you.</li>
</ul>

<h2>Preferences (stored in your browser)</h2>
<ul>
<li><code>gd-theme</code>: remembers whether you chose the light or dark look.</li>
<li><code>gd-v-*</code>: stops an article being counted twice in our "Trending" list when you reload it. It holds no personal information.</li>
</ul>

<h2>Advertising</h2>
<p>We use Google AdSense. Google and its partners may set cookies (such as <code>__gads</code>, <code>__gpi</code> and <code>IDE</code>) to show ads, limit how often you see the same ad and measure results. In the UK, the EEA and Switzerland these are only used for personalised ads if you agree in the consent message that appears when you first visit. You can change your choice at any time through the privacy settings link at the bottom of the page.</p>
<p>Read how Google uses cookies at <a href="https://policies.google.com/technologies/ads" rel="nofollow noopener" target="_blank">policies.google.com/technologies/ads</a>.</p>

<h2>Email sign-up</h2>
<p>The digest sign-up form is provided by SendFox, which may set a cookie to protect the form from spam.</p>

<h2>Controlling cookies</h2>
<p>You can block or delete cookies in your browser settings. Blocking strictly necessary cookies may stop parts of the site working. You can also opt out of personalised ads at <a href="https://adssettings.google.com" rel="nofollow noopener" target="_blank">adssettings.google.com</a> or <a href="https://www.youronlinechoices.com/uk/" rel="nofollow noopener" target="_blank">youronlinechoices.com</a>.</p>

<p>Questions? Email <a href="mailto:{email}">{email}</a>.</p>
',
		),
		'terms'            => array(
			'title'   => 'Terms of use',
			'content' => '
<p><em>Last updated: {updated}</em></p>

<p>By using gistdew.com you agree to these terms. If you don\'t agree, please don\'t use the site.</p>

<h2>Information only, not advice</h2>
<p>Articles on GistDew are news and general information. They are not legal, immigration, financial or professional advice. Rules, fees and exchange rates change often, so always check the official source (we link to it) and speak to a regulated adviser before making decisions. We are not responsible for losses caused by relying on our content.</p>

<h2>Our content</h2>
<p>The articles, cover graphics and design of GistDew belong to us unless stated otherwise. You may share links and quote short extracts with a credit and link back to the original article. Please don\'t republish whole articles without written permission. Photos credited to other photographers are used under their stated licences.</p>

<h2>Comments</h2>
<p>Keep comments respectful and on topic. We remove spam, abuse, hate speech, scams and anything illegal, and we may block repeat offenders. You are responsible for what you post.</p>

<h2>Links and advertising</h2>
<p>We link to other websites and show ads from third parties. We don\'t control those sites or ads and aren\'t responsible for their content. Sponsored content is always labelled.</p>

<h2>Availability</h2>
<p>We try to keep the site running smoothly but can\'t promise it will always be available or error-free.</p>

<h2>Changes and law</h2>
<p>We may update these terms; the date above shows the latest version. These terms are governed by the laws of England and Wales.</p>

<p>Contact: <a href="mailto:{email}">{email}</a></p>
',
		),
		'editorial-policy' => array(
			'title'   => 'Editorial policy',
			'content' => '
<p>GistDew exists to give Nigerians and Africans abroad clear, accurate and useful news. These are the standards we hold ourselves to.</p>

<h2>Accuracy and sources</h2>
<p>We check facts against primary sources: government websites, official statements, court and regulator documents and reputable news organisations. Every article lists its sources at the end so readers can check them.</p>

<h2>Original writing</h2>
<p>Every article is written for GistDew. We don\'t copy or rewrite other outlets\' articles, and we credit others when we build on their reporting.</p>

<h2>Corrections</h2>
<p>If we get something wrong, we fix it quickly and, for significant errors, add a note to the article saying what changed. Send corrections to <a href="mailto:{email}">{email}</a>.</p>

<h2>Updates</h2>
<p>Immigration rules, fees and exchange rates change often. When they do, we update the relevant articles and show the date.</p>

<h2>Independence and advertising</h2>
<p>Advertisers have no say over what we write. Sponsored or affiliate content is always clearly labelled.</p>

<h2>Images</h2>
<p>We use our own cover graphics and free, properly licensed stock photos, credited to the photographer under the image. We don\'t use photos taken from news sites.</p>

<h2>Not advice</h2>
<p>Our guides explain the rules in general terms. They are not a substitute for advice on your own situation from a regulated immigration adviser, solicitor or financial adviser.</p>
',
		),
	);
}

/**
 * Create the pages (or fill in WordPress's draft privacy page), add them to the
 * footer menu, and point the privacy/cookie settings at them.
 * Existing pages with the same slug are left alone unless $overwrite is true.
 */
function gdap_create_pages( $email, $overwrite = false ) {
	$made    = 0;
	$kept    = 0;
	$ids     = array();
	$updated = wp_date( 'j F Y' );
	foreach ( gdap_page_templates() as $slug => $t ) {
		$content = str_replace( array( '{email}', '{updated}' ), array( esc_html( $email ), $updated ), trim( $t['content'] ) );
		$page    = get_page_by_path( $slug, OBJECT, 'page' );
		if ( 'privacy-policy' === $slug && ! $page ) {
			$pid  = (int) get_option( 'wp_page_for_privacy_policy' );
			$page = $pid ? get_post( $pid ) : null;
		}
		if ( $page && 'trash' !== $page->post_status && ! $overwrite && 'publish' === $page->post_status ) {
			$ids[ $slug ] = $page->ID;
			$kept++;
			continue;
		}
		$args = array(
			'post_type'      => 'page',
			'post_title'     => $t['title'],
			'post_name'      => $slug,
			'post_content'   => $content,
			'post_status'    => 'publish',
			'comment_status' => 'closed',
		);
		if ( $page ) {
			$args['ID'] = $page->ID;
			$id         = wp_update_post( $args, true );
		} else {
			$id = wp_insert_post( $args, true );
		}
		if ( ! is_wp_error( $id ) ) {
			$ids[ $slug ] = (int) $id;
			$made++;
		}
	}
	if ( ! empty( $ids['privacy-policy'] ) ) {
		update_option( 'wp_page_for_privacy_policy', $ids['privacy-policy'] );
	}

	// Footer menu.
	$menu_name = 'GistDew footer';
	$menu      = wp_get_nav_menu_object( $menu_name );
	$menu_id   = $menu ? (int) $menu->term_id : (int) wp_create_nav_menu( $menu_name );
	if ( $menu_id && ! is_wp_error( $menu_id ) ) {
		$have = wp_list_pluck( (array) wp_get_nav_menu_items( $menu_id ), 'object_id' );
		foreach ( array( 'about', 'contact', 'editorial-policy', 'privacy-policy', 'cookie-policy', 'terms' ) as $slug ) {
			if ( empty( $ids[ $slug ] ) || in_array( (string) $ids[ $slug ], array_map( 'strval', $have ), true ) ) {
				continue;
			}
			wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-object-id' => $ids[ $slug ],
					'menu-item-object'    => 'page',
					'menu-item-type'      => 'post_type',
					'menu-item-status'    => 'publish',
				)
			);
		}
		$locs           = get_theme_mod( 'nav_menu_locations', array() );
		$locs['footer'] = $menu_id;
		set_theme_mod( 'nav_menu_locations', $locs );
	}

	// Remove the default "Sample Page" if it's untouched.
	$sample = get_page_by_path( 'sample-page', OBJECT, 'page' );
	if ( $sample && false !== strpos( $sample->post_content, 'This is an example page' ) ) {
		wp_trash_post( $sample->ID );
	}
	return array( 'added' => $made, 'skipped' => $kept, 'errors' => array() );
}
