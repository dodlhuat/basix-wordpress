<?php
/**
 * Starter content: what a brand-new site shows when the theme is first opened in the Customizer.
 *
 * WordPress only offers it on fresh sites, as a preview; nothing is saved until the owner publishes.
 *
 * @package basix
 */

add_action(
	'after_setup_theme',
	function () {
		$text    = static function ( string $content ): string {
			return "<!-- wp:paragraph -->\n<p>" . esc_html( $content ) . "</p>\n<!-- /wp:paragraph -->";
		};
		$heading = static function ( string $content ): string {
			return "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">" . esc_html( $content ) . "</h2>\n<!-- /wp:heading -->";
		};

		add_theme_support(
			'starter-content',
			array(
				'posts'     => array(
					'home'    => array(
						'post_type'    => 'page',
						'post_title'   => _x( 'Welcome', 'Starter content page title', 'basix' ),
						'post_content' => $heading( _x( 'Say hello', 'Starter content', 'basix' ) )
							. "\n\n" . $text( _x( 'This is your front page. Edit it to say what your site is about, or add one of the ready-made sections from the Basix patterns.', 'Starter content', 'basix' ) ),
					),
					'about'   => array(
						'post_type'    => 'page',
						'post_title'   => _x( 'About', 'Starter content page title', 'basix' ),
						'post_content' => $text( _x( 'Tell visitors who you are and what they will find here.', 'Starter content', 'basix' ) ),
					),
					'blog'    => array(
						'post_type'  => 'page',
						'post_title' => _x( 'Blog', 'Starter content page title', 'basix' ),
					),
					'contact' => array(
						'post_type'    => 'page',
						'post_title'   => _x( 'Contact', 'Starter content page title', 'basix' ),
						'post_content' => $text( _x( 'Say how people can reach you.', 'Starter content', 'basix' ) ),
					),
					'first'   => array(
						'post_type'    => 'post',
						'post_title'   => _x( 'Your first post', 'Starter content post title', 'basix' ),
						'post_content' => $text( _x( 'Write something worth reading. Headings, images, code and tables all have a place in this theme.', 'Starter content', 'basix' ) ),
					),
				),
				'options'   => array(
					'show_on_front'  => 'page',
					'page_on_front'  => '{{home}}',
					'page_for_posts' => '{{blog}}',
				),
				'nav_menus' => array(
					'primary' => array(
						'name'  => __( 'Primary menu', 'basix' ),
						'items' => array( 'page_home', 'page_about', 'page_blog', 'page_contact' ),
					),
					'footer'  => array(
						'name'  => __( 'Footer menu', 'basix' ),
						'items' => array( 'page_about', 'page_contact' ),
					),
				),
				'widgets'   => array(
					'sidebar-1' => array( 'search', 'recent-posts', 'categories' ),
					'footer-1'  => array(
						'text_about' => array(
							'text',
							array(
								'title' => _x( 'About', 'Starter content widget title', 'basix' ),
								'text'  => _x( 'A few words about this site.', 'Starter content', 'basix' ),
							),
						),
					),
				),
			)
		);
	}
);
