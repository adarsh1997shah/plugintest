<?php


get_header();

// Post condition and loop for displaying post.
if ( have_posts() ) {
	echo '<ul class="pt-posts">';
	while ( have_posts() ) {
		the_post();
		echo '<li class="pt-post-container">
				<p class="pt-post-title">
					<a class="pt-post-title-link" href="' . esc_html( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a>
				</p>
				<div class="pt-post">By&nbsp
					<p class="pt-post-author">
						<a class="pt-post-author-link" href="' . esc_html( get_author_posts_url( get_the_author_meta( 'ID' ) ) ) . '">' . esc_html( get_the_author_meta( 'user_nicename' ) ) . '</a>
					</p>
					<p class="pt-post-publish">' . get_the_date( 'M j, Y' ) . '</p>
				</div>
				<p class="pt-post-content">' . esc_html( get_the_excerpt() ) . '</p>
			</li>';
	}
	echo '</ul>';
} else {
	// internationalization.
	esc_html_e( 'No posts found.', 'plugintest' );
}

	// To restore global post variable to refer to the main query loop.
	wp_reset_postdata();
