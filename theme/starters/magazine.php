<?php
/**
 * Starter site: a magazine.
 *
 * @package basix
 */

return array(
	'title'       => __( 'Magazine', 'basix-paperlight' ),
	'description' => __( 'An editorial site: warm paper and serif type, a slider of featured stories, a large first story and a sidebar.', 'basix-paperlight' ),
	'look'        => 'editorial',
	'mods'        => array(
		'basix_hero_style'       => 'minimal',
		'basix_card_layout'      => 'magazine',
		'basix_sidebar_position' => 'right',
		'basix_header_layout'    => 'inline',
		'basix_show_featured'    => true,
	),
	'front'       => 'posts',
	'sticky'      => 3,
	'pages'       => array(
		'about'   => array(
			'title'   => _x( 'About', 'Starter page title', 'basix-paperlight' ),
			'content' => static function (): string {
				return basix_sc_pattern(
					'basix/about-split',
					array(
						'About us'                       => 'About the magazine',
						'A small team with a clear idea' => 'Stories on design and the web',
						'Tell your story in a few short paragraphs: why you started, what you care about and who you do it for.' => 'An independent magazine for people who make things for the web. Written by practitioners and edited with care.',
						'Meet the team'                  => 'Pitch a story',
					)
				) . "\n\n" . basix_sc_pattern(
					'basix/team-four',
					array(
						'Editor'    => 'Editor in chief',
						'Designer'  => 'Art director',
						'Developer' => 'Web editor',
					)
				);
			},
		),
		'contact' => array(
			'title'   => _x( 'Contact', 'Starter page title', 'basix-paperlight' ),
			'content' => static function (): string {
				return basix_sc_pattern( 'basix/contact-details' ) . "\n\n" . basix_sc_pattern(
					'basix/cta-newsletter',
					array(
						'Stay in the loop' => 'The weekly letter',
						'One short email when something new is published. No spam.' => 'The best of the week, in one short email.',
					)
				);
			},
		),
	),
	'menus'       => array(
		'primary' => array( 'home', 'about', 'contact' ),
		'footer'  => array( 'about', 'contact' ),
	),
	'posts'       => array( 'tokens', 'type-scale', 'dark-mode', 'contrast', 'rhythm', 'keyboard', 'small', 'docs', 'budget' ),
	'widgets'     => true,
);
