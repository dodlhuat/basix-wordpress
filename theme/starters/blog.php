<?php
/**
 * Starter site: a personal blog.
 *
 * @package basix
 */

return array(
	'title'       => __( 'Blog', 'basix' ),
	'description' => __( 'A classic blog: a welcome card, the latest posts in a grid, a sidebar, an About page and a Contact page.', 'basix' ),
	'look'        => 'basix',
	'mods'        => array(
		'basix_hero_style'       => 'card',
		'basix_card_layout'      => 'grid',
		'basix_sidebar_position' => 'right',
		'basix_header_layout'    => 'inline',
	),
	'front'       => 'posts',
	'sticky'      => 0,
	'pages'       => array(
		'about'   => array(
			'title'   => _x( 'About', 'Starter page title', 'basix' ),
			'content' => static function (): string {
				return basix_sc_pattern(
					'basix/about-split',
					array(
						'About us'                       => 'About this blog',
						'A small team with a clear idea' => 'Notes from someone who loves small details',
						'Tell your story in a few short paragraphs: why you started, what you care about and who you do it for.' => 'I write about design systems, interfaces and the web: what worked, what did not, and what I would do differently next time.',
						'Meet the team'                  => 'Read the latest posts',
					)
				) . "\n\n" . basix_sc_pattern( 'basix/values-three' );
			},
		),
		'contact' => array(
			'title'   => _x( 'Contact', 'Starter page title', 'basix' ),
			'content' => static function (): string {
				return basix_sc_pattern(
					'basix/contact-cta',
					array(
						'Questions? Write to us.' => 'Say hello',
						'We answer within one working day.' => 'Ideas, questions or a correction? I read every message.',
						'Send an email'           => 'Write to me',
					)
				) . "\n\n" . basix_sc_pattern(
					'basix/faq-columns',
					array(
						'Questions?'                   => 'Good to know',
						'The short answers to what people ask most.' => 'The short answers to what readers ask most.',
						'What is this about?'          => 'What do you write about?',
						'How do I get started?'        => 'Can I reuse your posts?',
						'Can I change my mind later?'  => 'How can I follow along?',
						'Give a short, direct answer.' => array(
							'Design systems, interfaces and the web platform, mostly the unglamorous parts that make the rest work.',
							'Please do, with a link back. Code samples are free to use.',
							'Subscribe through the feed, or just come back on Fridays.',
						),
					)
				);
			},
		),
	),
	'menus'       => array(
		'primary' => array( 'home', 'about', 'contact' ),
		'footer'  => array( 'about', 'contact' ),
	),
	'posts'       => array( 'tokens', 'dark-mode', 'rhythm', 'npm', 'type-scale', 'keyboard' ),
	'widgets'     => true,
);
