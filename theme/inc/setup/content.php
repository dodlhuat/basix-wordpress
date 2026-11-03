<?php
/**
 * Sample content for the starter sites: block helpers, a small library of posts, and the sample events.
 *
 * Page text comes from the theme's patterns, so it is in the site's language. The sample posts are English demo text.
 *
 * @package basix
 */

/**
 * A paragraph block.
 *
 * @param string $text Plain text.
 * @return string
 */
function basix_sc_p( string $text ): string {
	return "<!-- wp:paragraph -->\n<p>" . esc_html( $text ) . "</p>\n<!-- /wp:paragraph -->";
}

/**
 * A heading block.
 *
 * @param string $text  Plain text.
 * @param int    $level Level, 2 to 4.
 * @return string
 */
function basix_sc_h( string $text, int $level = 2 ): string {
	$attrs = 2 === $level ? '' : ' {"level":' . $level . '}';
	return '<!-- wp:heading' . $attrs . " -->\n<h" . $level . ' class="wp-block-heading">' . esc_html( $text ) . '</h' . $level . ">\n<!-- /wp:heading -->";
}

/**
 * A bulleted list block.
 *
 * @param string[] $items Plain text items.
 * @return string
 */
function basix_sc_list( array $items ): string {
	$lis = array();
	foreach ( $items as $item ) {
		$lis[] = "<!-- wp:list-item -->\n<li>" . esc_html( $item ) . "</li>\n<!-- /wp:list-item -->";
	}
	return "<!-- wp:list -->\n<ul class=\"wp-block-list\">" . implode( "\n\n", $lis ) . "</ul>\n<!-- /wp:list -->";
}

/**
 * The content of a registered pattern, optionally with some of its texts swapped for demo copy.
 *
 * $texts maps a text of the pattern (in English, as written in the pattern file) to its replacement. A replacement
 * can be a list; then each time the text occurs in the pattern the next item is used (for texts that repeat, such as
 * the three answers of a FAQ). The replacements are demo text and are not translated.
 *
 * @param string                      $slug  Pattern name, for example basix/features-grid.
 * @param array<string, string|array> $texts Texts to replace.
 * @return string
 */
function basix_sc_pattern( string $slug, array $texts = array() ): string {
	$seen   = array();
	$filter = static function ( $translation, $text ) use ( $texts, &$seen ) {
		if ( ! isset( $texts[ $text ] ) ) {
			return $translation;
		}
		$swap = $texts[ $text ];
		if ( is_array( $swap ) ) {
			$seen[ $text ] = ( $seen[ $text ] ?? -1 ) + 1;
			return $swap[ $seen[ $text ] % count( $swap ) ];
		}
		return $swap;
	};
	if ( $texts ) {
		add_filter( 'gettext_with_context', $filter, 10, 2 );
	}
	$pattern = WP_Block_Patterns_Registry::get_instance()->get_registered( $slug );
	$content = $pattern ? (string) $pattern['content'] : '';
	if ( $texts ) {
		remove_filter( 'gettext_with_context', $filter, 10 );
	}
	return $content;
}

/**
 * The sample posts: key => title, category, tags, picture (a file in assets/images), age in days and the blocks.
 *
 * @return array<string, array{title: string, category: string, tags: string[], image: string, days: int, content: string}>
 */
function basix_starter_posts(): array {
	$post = static function ( string $title, string $category, array $tags, string $image, int $days, array $blocks ): array {
		return array(
			'title'    => $title,
			'category' => $category,
			'tags'     => $tags,
			'image'    => $image,
			'days'     => $days,
			'content'  => implode( "\n\n", $blocks ),
		);
	};
	return array(
		'tokens'     => $post(
			'Design tokens are the contract',
			'Design',
			array( 'tokens', 'css' ),
			'square-1.jpg',
			1,
			array(
				basix_sc_p( 'A design system lives or dies by how well it survives a rebrand. When every colour, radius and spacing value is a named token, a redesign becomes a change in one file instead of a hunt through a hundred stylesheets.' ),
				basix_sc_h( 'Two layers are enough' ),
				basix_sc_p( 'Raw values on one side, meaning on the other. A palette says what colours exist; the semantic layer says which one is the page background, the text, the accent.' ),
				basix_sc_list( array( 'Name tokens after intent, not appearance.', 'Give every colour token a dark mode value from the start.', 'Let components fall back to the global token.' ) ),
				basix_sc_p( 'Start small, ship the tokens first, and let the components grow around them.' ),
			)
		),
		'dark-mode'  => $post(
			'Dark mode without the flash',
			'Engineering',
			array( 'dark-mode', 'javascript' ),
			'square-2.jpg',
			3,
			array(
				basix_sc_p( 'The most common dark mode bug is not ugly colours, it is the flash: the page paints light, then a script flips it. The fix is to decide before the first paint.' ),
				basix_sc_h( 'Decide in the head' ),
				basix_sc_p( 'A tiny inline script reads the saved choice, falls back to the visitor\'s device setting, and sets one attribute on the root element. Everything else is CSS that reads that attribute.' ),
				basix_sc_p( 'Keep the switch itself a real button with a label, so it works with a keyboard and a screen reader.' ),
			)
		),
		'rhythm'     => $post(
			'Spacing is a rhythm, not a number',
			'Design',
			array( 'spacing', 'layout' ),
			'square-3.jpg',
			6,
			array(
				basix_sc_p( 'Good spacing feels inevitable. It comes from a small scale used consistently, not from nudging boxes until they look right.' ),
				basix_sc_h( 'Pick a scale and stay on it' ),
				basix_sc_p( 'Four or five steps cover almost everything. When something does not fit, the answer is usually to change the structure, not to invent a new step.' ),
			)
		),
		'npm'        => $post(
			'Shipping a WordPress theme with npm',
			'Engineering',
			array( 'npm', 'WordPress' ),
			'square-5.jpg',
			9,
			array(
				basix_sc_p( 'A theme can depend on a component library without asking its users to run a build. Keep the dependency in package.json, copy the few files you need into the theme at install time, and commit nothing generated.' ),
				basix_sc_h( 'One script, one folder' ),
				basix_sc_p( 'A short sync script and a pinned version make the result reproducible. The zip you ship contains only what the theme uses.' ),
			)
		),
		'type-scale' => $post(
			'A type scale you can explain',
			'Typography',
			array( 'typography', 'tokens' ),
			'square-6.jpg',
			12,
			array(
				basix_sc_p( 'A type scale is a promise: every size on the page comes from the same short list. Readers feel the order even when they cannot name it.' ),
				basix_sc_h( 'Fewer sizes, clearer hierarchy' ),
				basix_sc_p( 'Five sizes are plenty for most sites. Use weight and colour for emphasis before reaching for another size.' ),
				basix_sc_list( array( 'Body text between 16 and 20 pixels.', 'Line length between 45 and 75 characters.', 'Headings tighter than body, never louder than they need to be.' ) ),
			)
		),
		'keyboard'   => $post(
			'Keyboard first, mouse second',
			'Notes',
			array( 'accessibility', 'components' ),
			'square-7.jpg',
			15,
			array(
				basix_sc_p( 'If you can use a page with the keyboard alone, most other assistive tools will work too. It is the cheapest accessibility test there is.' ),
				basix_sc_h( 'Three things to check' ),
				basix_sc_list( array( 'You can reach everything with Tab, in an order that makes sense.', 'You can always see where you are.', 'Nothing traps you, and Escape closes what opened.' ) ),
			)
		),
		'small'      => $post(
			'Small components, boring decisions',
			'Notes',
			array( 'components', 'design' ),
			'square-8.jpg',
			19,
			array(
				basix_sc_p( 'The best component libraries are dull on purpose. A button is a button; a card is a card. The interesting part is what you build with them.' ),
				basix_sc_p( 'When a component needs ten options, it is usually two components in a trench coat.' ),
			)
		),
		'budget'     => $post(
			'A performance budget for people',
			'Performance',
			array( 'performance', 'javascript' ),
			'square-4.jpg',
			23,
			array(
				basix_sc_p( 'A budget turns "make it fast" into a number the whole team can see. Pick a weight for the page, a time for it to become usable, and fail the build when either is missed.' ),
				basix_sc_h( 'Start with what readers feel' ),
				basix_sc_p( 'Fast first content, no jumping layout, and nothing that blocks a tap. Everything else is detail.' ),
			)
		),
		'docs'       => $post(
			'Write the docs you wish you had',
			'Notes',
			array( 'writing', 'docs' ),
			'square-2.jpg',
			28,
			array(
				basix_sc_p( 'Documentation fails when it starts from the code instead of the reader. Start with the question someone would type into a search box.' ),
				basix_sc_p( 'One page, one task, one example that really runs.' ),
			)
		),
		'contrast'   => $post(
			'Contrast is not a colour',
			'Design',
			array( 'accessibility', 'colour' ),
			'square-3.jpg',
			33,
			array(
				basix_sc_p( 'Contrast is a ratio between two colours, so a colour on its own is never accessible or inaccessible. Check pairs, in both light and dark mode.' ),
				basix_sc_h( 'Make the check automatic' ),
				basix_sc_p( 'A test that computes the ratio for every text and background pair catches what the eye misses on a good screen.' ),
			)
		),
	);
}

/**
 * The sample events of the events starter: title, days from today, start time, end time, place, text, category, repeat rule.
 *
 * @return array[]
 */
function basix_starter_events(): array {
	return array(
		array( 'Design systems meetup', 4, '18:30', '20:30', 'Studio 4, Vienna', 'An evening of short talks on tokens, components and keeping a system alive.', 'Meetups', null ),
		array( 'Accessibility clinic', 8, '10:00', '12:00', 'Online', 'Bring a page and we will audit it together, live.', 'Workshops', null ),
		array( 'Theme workshop', 13, '', '', 'Library hall', 'A full day of building a block-friendly theme from components.', 'Workshops', null ),
		array(
			'Open office hours',
			2,
			'15:00',
			'16:00',
			'Online',
			'Ask anything about the components.',
			'Meetups',
			array(
				'freq' => 'weekly',
				'end'  => array( 'type' => 'never' ),
			),
		),
		array(
			'Reading group',
			6,
			'17:30',
			'19:00',
			'Café Letter',
			'Every second week we read one short text and talk about it.',
			'Meetups',
			array(
				'freq'     => 'weekly',
				'interval' => 2,
				'end'      => array(
					'type'  => 'count',
					'count' => 8,
				),
			),
		),
		array( 'Conference day', 25, '', '', 'Congress Center', 'A day of talks and workshops about interface craft.', 'Conferences', null ),
	);
}
