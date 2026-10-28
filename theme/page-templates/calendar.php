<?php
/**
 * Template Name: Calendar
 * Template Post Type: page
 *
 * A month, week and agenda calendar of the site's posts by publish date. The posts for the visible range
 * are loaded from the REST API; without JavaScript the posts of the current month are listed instead.
 *
 * @package basix
 */

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<article <?php post_class( 'entry' ); ?>>
		<header class="entry-header"><h1><?php the_title(); ?></h1></header>
		<div class="entry-content"><?php the_content(); ?></div>

		<div class="calendar-wrap" <?php echo basix_component_attrs( 'calendar', basix_calendar_config() ); ?>>
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
	</article>
	<?php
endwhile;

get_footer();
