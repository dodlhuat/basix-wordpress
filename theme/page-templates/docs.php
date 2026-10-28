<?php
/**
 * Template Name: Documentation
 * Template Post Type: page
 *
 * A page with a navigation tree of its section (the top-level page and everything below it).
 *
 * @package basix
 */

get_header();

$basix_docs_tree = basix_docs_tree();
$basix_sprite    = get_theme_file_uri( BASIX_VENDOR . '/svg-icons/icons.svg' );
?>

<div class="sidebar-layout docs-layout" <?php echo basix_component_attrs( 'docs' ); ?>>
    <aside class="sidebar-nav docs-sidebar" id="docs-sidebar" aria-label="<?php esc_attr_e( 'Documentation', 'basix' ); ?>">
        <div class="sidebar-scroll">
            <p class="docs-nav-title"><a href="<?php echo esc_url( get_permalink( $basix_docs_tree['page'] ) ); ?>"><?php echo esc_html( get_the_title( $basix_docs_tree['page'] ) ); ?></a></p>
            <nav class="docs-nav">
                <?php
                if ( $basix_docs_tree['children'] ) {
                    basix_docs_print_nodes( $basix_docs_tree['children'] );
                }
                ?>
            </nav>
        </div>
    </aside>
    <div class="sidebar-backdrop"></div>

    <div class="sidebar-main docs-main">
        <button type="button" class="sidebar-toggle docs-toggle" aria-controls="docs-sidebar">
            <svg class="icon-svg" aria-hidden="true"><use href="<?php echo esc_url( $basix_sprite . '#menu' ); ?>"/></svg>
            <span><?php esc_html_e( 'Navigation', 'basix' ); ?></span>
        </button>

        <?php
        while ( have_posts() ) :
            the_post();
            ?>
            <article <?php post_class( 'entry' ); ?>>
                <header class="entry-header"><h1><?php the_title(); ?></h1></header>
                <div class="entry-content"><?php the_content(); ?></div>
            </article>
        <?php endwhile; ?>

        <?php $basix_neighbours = basix_docs_neighbours(); ?>
        <?php if ( $basix_neighbours['prev'] || $basix_neighbours['next'] ) : ?>
            <nav class="post-nav docs-pager" aria-label="<?php esc_attr_e( 'Documentation pages', 'basix' ); ?>">
                <div>
                    <?php if ( $basix_neighbours['prev'] ) : ?>
                        <a href="<?php echo esc_url( get_permalink( $basix_neighbours['prev'] ) ); ?>" rel="prev">&larr; <?php echo esc_html( get_the_title( $basix_neighbours['prev'] ) ); ?></a>
                    <?php endif; ?>
                </div>
                <div class="text-right">
                    <?php if ( $basix_neighbours['next'] ) : ?>
                        <a href="<?php echo esc_url( get_permalink( $basix_neighbours['next'] ) ); ?>" rel="next"><?php echo esc_html( get_the_title( $basix_neighbours['next'] ) ); ?> &rarr;</a>
                    <?php endif; ?>
                </div>
            </nav>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>
