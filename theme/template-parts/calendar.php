<?php
/**
 * The posts calendar: markup for the calendar component, used by the page template, the block and the shortcode.
 *
 * Args: view (month, week or agenda) and source (events or posts).
 *
 * @package basix
 */

$basix_view   = isset( $args['view'] ) ? $args['view'] : 'month';
$basix_source = isset( $args['source'] ) ? $args['source'] : 'events';
?>
<div class="calendar-wrap" <?php echo basix_component_attrs( 'calendar', array_merge( basix_calendar_config(), array(
	'view'   => $basix_view,
	'source' => $basix_source,
) ) ); ?>>
	<div class="calendar-status progress" hidden><div class="progress-bar animated accent"></div></div>
	<div class="calendar-skeleton" aria-hidden="true">
		<?php for ( $basix_cell = 0; $basix_cell < 42; $basix_cell++ ) : ?>
			<span class="skeleton"></span>
		<?php endfor; ?>
	</div>
	<div class="calendar-host" aria-live="polite"></div>

	<noscript>
		<?php
		// Without JavaScript: a plain list for the current month.
		$basix_first = wp_date( 'Y-m-01' );
		$basix_last  = wp_date( 'Y-m-t' );
		if ( 'events' === $basix_source ) {
			$basix_items = basix_events_between( $basix_first, $basix_last, 100 );
		} else {
			$basix_items = get_posts(
				array(
					'post_type'           => 'post',
					'year'                => (int) wp_date( 'Y' ),
					'monthnum'            => (int) wp_date( 'n' ),
					'posts_per_page'      => 50,
					'orderby'             => 'date',
					'order'               => 'ASC',
					'ignore_sticky_posts' => true,
					'no_found_rows'       => true,
				)
			);
		}
		?>
		<h2><?php echo esc_html( wp_date( 'F Y' ) ); ?></h2>
		<?php if ( $basix_items ) : ?>
			<ul class="calendar-list">
				<?php foreach ( $basix_items as $basix_item ) : ?>
					<li>
						<?php if ( 'events' === $basix_source ) : ?>
							<?php echo esc_html( basix_event_when( $basix_item->ID ) ); ?>
						<?php else : ?>
							<time datetime="<?php echo esc_attr( get_the_date( DATE_W3C, $basix_item ) ); ?>"><?php echo esc_html( get_the_date( '', $basix_item ) ); ?></time>
						<?php endif; ?>
						<a href="<?php echo esc_url( get_permalink( $basix_item ) ); ?>"><?php echo esc_html( get_the_title( $basix_item ) ); ?></a>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<p><?php echo esc_html( 'events' === $basix_source ? __( 'No events this month.', 'basix' ) : __( 'No posts this month.', 'basix' ) ); ?></p>
		<?php endif; ?>
	</noscript>
</div>
