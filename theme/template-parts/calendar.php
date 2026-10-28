<?php
/**
 * The posts calendar: markup for the calendar component, used by the page template, the block and the shortcode.
 *
 * Args: view (month, week or agenda).
 *
 * @package basix
 */

$basix_view = isset( $args['view'] ) ? $args['view'] : 'month';
?>
<div class="calendar-wrap" <?php echo basix_component_attrs( 'calendar', array_merge( basix_calendar_config(), array( 'view' => $basix_view ) ) ); ?>>
	<div class="calendar-status progress" hidden><div class="progress-bar animated accent"></div></div>
	<div class="calendar-skeleton" aria-hidden="true">
		<?php for ( $basix_cell = 0; $basix_cell < 42; $basix_cell++ ) : ?>
			<span class="skeleton"></span>
		<?php endfor; ?>
	</div>
	<div class="calendar-host" aria-live="polite"></div>

	<noscript>
		<?php
		$basix_month_posts = new WP_Query(
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
		?>
		<h2><?php echo esc_html( wp_date( 'F Y' ) ); ?></h2>
		<?php if ( $basix_month_posts->have_posts() ) : ?>
			<ul class="calendar-list">
				<?php
				while ( $basix_month_posts->have_posts() ) :
					$basix_month_posts->the_post();
					?>
					<li><time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time> <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></li>
				<?php endwhile; ?>
			</ul>
			<?php wp_reset_postdata(); ?>
		<?php else : ?>
			<p><?php esc_html_e( 'No posts this month.', 'basix' ); ?></p>
		<?php endif; ?>
	</noscript>
</div>
