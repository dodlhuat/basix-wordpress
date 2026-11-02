<?php
/**
 * Title: Latest posts
 * Slug: basix/blog-latest
 * Description: The three newest posts as cards.
 * Categories: basix, basix-blog
 * Keywords: blog, posts, latest, news, query
 */
?>
<!-- wp:query {"query":{"perPage":3,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false},"align":"wide","className":"latest-posts"} -->
<div class="wp-block-query alignwide latest-posts"><!-- wp:post-template {"layout":{"type":"grid","columnCount":3}} -->
<!-- wp:post-featured-image {"isLink":true} /-->

<!-- wp:post-title {"level":3,"isLink":true} /-->

<!-- wp:post-date /-->

<!-- wp:post-excerpt {"excerptLength":20} /-->
<!-- /wp:post-template --></div>
<!-- /wp:query -->
