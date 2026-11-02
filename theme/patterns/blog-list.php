<?php
/**
 * Title: Latest posts as a list
 * Slug: basix/blog-list
 * Description: The five newest posts as a simple list.
 * Categories: basix, basix-blog
 * Keywords: blog, posts, list, news, query
 */
?>
<!-- wp:query {"query":{"perPage":5,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false},"align":"wide","className":"latest-list"} -->
<div class="wp-block-query alignwide latest-list"><!-- wp:post-template -->
<!-- wp:post-title {"level":3,"isLink":true} /-->

<!-- wp:post-date /-->
<!-- /wp:post-template --></div>
<!-- /wp:query -->
