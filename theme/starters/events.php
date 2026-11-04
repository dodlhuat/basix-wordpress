<?php
/**
 * Starter site: events.
 *
 * @package basix
 */

return array(
	'title'       => __( 'Events', 'basix-paperlight' ),
	'description' => __( 'A site for gatherings: sample events (one repeating every week), an agenda and a calendar, a subscribe link, and About and Contact pages.', 'basix-paperlight' ),
	'look'        => 'calm',
	'mods'        => array(
		'basix_hero_style'       => 'none',
		'basix_card_layout'      => 'list',
		'basix_sidebar_position' => 'none',
		'basix_header_layout'    => 'inline',
	),
	'front'       => 'home',
	'sticky'      => 0,
	'pages'       => array(
		'home'     => array(
			'title'   => _x( 'Home', 'Starter page title', 'basix-paperlight' ),
			'content' => static function (): string {
				return basix_sc_pattern(
					'basix/hero-minimal',
					array(
						'Welcome'                        => 'Meet in person',
						'A clear headline for your site' => 'Small evenings for people who make things for the web',
						'Say what you do in one or two sentences, and point people to the next step.' => 'Talks, workshops and a weekly open table. Everyone is welcome, beginners most of all.',
						'Get started'                    => 'See what is on',
						'Learn more'                     => 'About us',
					)
				) . "\n\n"
					. basix_sc_h( _x( 'What is on', 'Starter content', 'basix-paperlight' ) ) . "\n\n"
					. "<!-- wp:shortcode -->\n[basix_calendar view=\"agenda\"]\n<!-- /wp:shortcode -->\n\n"
					. basix_sc_pattern(
						'basix/cta-newsletter',
						array(
							'Stay in the loop' => 'Never miss an evening',
							'One short email when something new is published. No spam.' => 'One short email a month with what is coming up.',
						)
					);
			},
		),
		'calendar' => array(
			'title'    => _x( 'Calendar', 'Starter page title', 'basix-paperlight' ),
			'template' => 'page-templates/calendar.php',
			'content'  => '',
		),
		'about'    => array(
			'title'   => _x( 'About', 'Starter page title', 'basix-paperlight' ),
			'content' => static function (): string {
				return basix_sc_pattern(
					'basix/about-split',
					array(
						'About us'                       => 'About the community',
						'A small team with a clear idea' => 'People who like meeting in person',
						'Tell your story in a few short paragraphs: why you started, what you care about and who you do it for.' => 'We organise small, friendly events for people who make things for the web. Nobody sells anything, and everyone is welcome.',
						'Meet the team'                  => 'See what is on',
					)
				) . "\n\n" . basix_sc_pattern(
					'basix/team-three',
					array(
						'Editor'    => 'Host',
						'Designer'  => 'Programme',
						'Developer' => 'Venue',
					)
				);
			},
		),
		'contact'  => array(
			'title'   => _x( 'Contact', 'Starter page title', 'basix-paperlight' ),
			'content' => static function (): string {
				return basix_sc_pattern( 'basix/contact-details' ) . "\n\n" . basix_sc_pattern(
					'basix/faq-columns',
					array(
						'Questions?'                   => 'Good to know',
						'The short answers to what people ask most.' => 'The short answers to what guests ask most.',
						'What is this about?'          => 'Do I need a ticket?',
						'How do I get started?'        => 'Can I bring a friend?',
						'Can I change my mind later?'  => 'Is there step-free access?',
						'Give a short, direct answer.' => array(
							'No. Just come along. For workshops we ask you to sign up so there is a seat and coffee for you.',
							'Please do. New faces are what keep an evening interesting.',
							'Yes, all our regular venues have it. Tell us if you need anything else and we will arrange it.',
						),
					)
				);
			},
		),
	),
	'menus'       => array(
		'primary' => array( 'home', 'events-archive', 'calendar', 'about', 'contact' ),
		'footer'  => array( 'about', 'contact' ),
	),
	'posts'       => array(),
	'events'      => true,
	'widgets'     => true,
);
