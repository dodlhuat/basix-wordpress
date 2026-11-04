<?php
/**
 * Starter site: a small business.
 *
 * @package basix
 */

return array(
	'title'       => __( 'Business', 'basix-paperlight' ),
	'description' => __( 'A company site: a landing page with a hero, features, numbers, testimonials and prices, plus Services, Pricing, About, News and Contact pages.', 'basix-paperlight' ),
	'look'        => 'bold',
	'mods'        => array(
		'basix_hero_style'       => 'none',
		'basix_card_layout'      => 'grid',
		'basix_sidebar_position' => 'none',
		'basix_header_layout'    => 'inline',
	),
	'front'       => 'home',
	'blog'        => 'news',
	'sticky'      => 0,
	'pages'       => array(
		'home'     => array(
			'title'   => _x( 'Home', 'Starter page title', 'basix-paperlight' ),
			'content' => static function (): string {
				$plans = array(
					'Starter'                => 'Care',
					'Pro'                    => 'Growth',
					'Agency'                 => 'Partner',
					'$9'                     => '$49',
					'$29'                    => '$149',
					'$79'                    => '$399',
					'One site'               => 'One website',
					'Basic support'          => 'Email support',
					'Community access'       => 'A monthly report',
					'Five sites'             => 'Up to five websites',
					'Priority support'       => 'Priority support',
					'All patterns and looks' => 'A quarterly review',
					'Unlimited sites'        => 'Unlimited websites',
					'Dedicated support'      => 'A named contact',
					'White label'            => 'Custom work included',
					'Choose Starter'         => 'Choose Care',
					'Choose Pro'             => 'Choose Growth',
					'Choose Agency'          => 'Choose Partner',
				);
				return implode(
					"\n\n",
					array(
						basix_sc_pattern(
							'basix/hero-split',
							array(
								'Welcome'     => 'Design studio',
								'A clear headline for your site' => 'We build websites people enjoy using',
								'Say what you do in one or two sentences, and point people to the next step.' => 'Strategy, design and development for small teams that want a site that is fast, clear and easy to look after.',
								'Get started' => 'Start a project',
								'Learn more'  => 'See our services',
							)
						),
						basix_sc_pattern(
							'basix/features-grid',
							array(
								'Easy to use' => 'Easy to edit',
								'Supported'   => 'Looked after',
								'Describe the first benefit in a sentence or two.' => 'Pages load in a blink, on any connection.',
								'Describe the second benefit in a sentence or two.' => 'Editors get a clear, friendly writing experience.',
								'Describe the third benefit in a sentence or two.' => 'Solid parts that stay maintainable for years.',
								'Describe the fourth benefit in a sentence or two.' => 'We stay on after launch: updates, fixes and ideas.',
							)
						),
						basix_sc_pattern(
							'basix/stats-four',
							array(
								'12k'       => '140+',
								'Customers' => 'Projects launched',
								'48'        => '12',
								'Countries' => 'Years in business',
								'24/7'      => '48 h',
								'Support'   => 'Reply time',
							)
						),
						basix_sc_pattern( 'basix/logos-strip' ),
						basix_sc_pattern(
							'basix/testimonials-three',
							array(
								'Clear, fast and easy to read.' => 'They understood our brand in the first meeting.',
								'Sam Lee, Designer'    => 'Sam Lee, Head of Marketing',
								'The best starting point we have used.' => 'The new site loads instantly and our team edits it without help.',
								'Jo Baker, Developer'  => 'Jo Baker, Founder',
								'Our readers stay longer now.' => 'Enquiries doubled in the first quarter after launch.',
								'Kim Novak, Publisher' => 'Kim Novak, Director',
							)
						),
						basix_sc_pattern( 'basix/pricing-three', $plans ),
						basix_sc_pattern(
							'basix/cta-split',
							array(
								'Ready when you are' => 'Ready to start?',
								'One sentence that makes the next step easy.' => 'Tell us about your project and we will reply within two working days.',
								'Get started'        => 'Start a project',
								'Talk to us'         => 'Book a call',
							)
						),
					)
				);
			},
		),
		'services' => array(
			'title'   => _x( 'Services', 'Starter page title', 'basix-paperlight' ),
			'content' => static function (): string {
				return basix_sc_pattern(
					'basix/services-list',
					array(
						'What we do' => 'What we do',
						'Describe this service in a sentence.' => array(
							'Workshops and research that turn a vague idea into a clear plan.',
							'Interfaces and brand systems that are simple to use and simple to keep.',
							'Fast, accessible websites on WordPress, built to be edited by you.',
						),
					)
				) . "\n\n" . basix_sc_pattern( 'basix/process-four' ) . "\n\n" . basix_sc_pattern( 'basix/cta-band' );
			},
		),
		'pricing'  => array(
			'title'   => _x( 'Pricing', 'Starter page title', 'basix-paperlight' ),
			'content' => static function (): string {
				$plans = array(
					'Starter'                => 'Care',
					'Pro'                    => 'Growth',
					'Agency'                 => 'Partner',
					'$9'                     => '$49',
					'$29'                    => '$149',
					'$79'                    => '$399',
					'One site'               => 'One website',
					'Basic support'          => 'Email support',
					'Community access'       => 'A monthly report',
					'Five sites'             => 'Up to five websites',
					'Priority support'       => 'Priority support',
					'All patterns and looks' => 'A quarterly review',
					'Unlimited sites'        => 'Unlimited websites',
					'Dedicated support'      => 'A named contact',
					'White label'            => 'Custom work included',
					'Choose Starter'         => 'Choose Care',
					'Choose Pro'             => 'Choose Growth',
					'Choose Agency'          => 'Choose Partner',
				);
				return basix_sc_pattern( 'basix/pricing-three', $plans ) . "\n\n" . basix_sc_pattern(
					'basix/faq-columns',
					array(
						'Questions?'                   => 'Before you ask',
						'The short answers to what people ask most.' => 'The short answers to what clients ask most.',
						'What is this about?'          => 'How long does a project take?',
						'How do I get started?'        => 'Do you work with small budgets?',
						'Can I change my mind later?'  => 'Can I change plan later?',
						'Give a short, direct answer.' => array(
							'Most websites take six to ten weeks from the first call to launch.',
							'Yes. The Care plan is made for small teams, and we will tell you honestly if something does not fit.',
							'Any time. Plans change on the first of the next month.',
						),
					)
				);
			},
		),
		'about'    => array(
			'title'   => _x( 'About', 'Starter page title', 'basix-paperlight' ),
			'content' => static function (): string {
				return basix_sc_pattern(
					'basix/about-split',
					array(
						'A small team with a clear idea' => 'A small studio with a clear idea',
						'Tell your story in a few short paragraphs: why you started, what you care about and who you do it for.' => 'We are designers and developers who like plain language, fast pages and clients who stay for years.',
					)
				) . "\n\n" . basix_sc_pattern( 'basix/values-three' ) . "\n\n" . basix_sc_pattern(
					'basix/team-three',
					array(
						'Editor'    => 'Strategy',
						'Designer'  => 'Design',
						'Developer' => 'Development',
					)
				);
			},
		),
		'news'     => array(
			'title'   => _x( 'News', 'Starter page title', 'basix-paperlight' ),
			'content' => '',
		),
		'contact'  => array(
			'title'   => _x( 'Contact', 'Starter page title', 'basix-paperlight' ),
			'content' => static function (): string {
				return basix_sc_pattern( 'basix/contact-details' ) . "\n\n" . basix_sc_pattern( 'basix/contact-cta' );
			},
		),
	),
	'menus'       => array(
		'primary' => array( 'home', 'services', 'pricing', 'about', 'news', 'contact' ),
		'footer'  => array( 'about', 'contact' ),
	),
	'posts'       => array( 'budget', 'docs', 'small' ),
	'widgets'     => true,
);
