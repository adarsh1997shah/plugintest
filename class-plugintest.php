<?php
/**
 * This is a demo plugin test class.
 *
 * @since 1.0.0
 * @package PluginTest
 */
class Plugintest {

	/**
	 * For post type.
	 *
	 * @var post_type
	 */
	public static $post_type = 'movie';

	public $post_types = array();

	/**
	 * This is a constructor class for adding shortcode and enqueueing scripts.
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		add_shortcode( 'demo-test', array( $this, 'demo_shortcode' ) );

		add_action( 'init', array( $this, 'register_post_type' ) );

		add_action( 'init', array( $this, 'generate_custom_post_type' ) );

		//add_action( 'init', array( $this, 'rewrite_rules' ) );

		add_action( 'template_include', array( $this, 'custom_post_type_archive' ) );

		add_action( 'template_include', array( $this, 'custom_post_type_archive_template' ) );

		add_filter( 'single_template', array( $this, 'get_custom_post_type_template' ) );

		add_action( 'admin_menu', array( $this, 'custom_menu_page' ) );

		add_action( 'admin_init', array( $this, 'add_options' ) );

		add_action( 'init', array( $this, 'generate_post_type' ) );

		add_filter( 'post_type_link', array( $this, 'update_permalinks' ), 10, 2 );
		add_filter( 'post_type_link', array( $this, 'update_post_permalinks' ), 10, 2 );

		add_filter('term_link', array( $this, 'update_term_link' ) );

	 add_action( 'wp_footer', function() {
			global $wp_query;
			//print_r( $wp_query );
			print_r($this->post_types);
		});
	}
	/**
	 * Function to enque scripts.
	 */
	public function enqueue_scripts() {
		wp_enqueue_style( 'pt-style', plugins_url( 'assets/css/style.css', __FILE__ ), array(), '1.0.0', false );

		wp_enqueue_script( 'pt-script', plugins_url( 'assets/js/script.js', __FILE__ ), array(), '1.0.0', true );
	}


	/**
	 * Function to add admin menu page
	 */
	public function custom_menu_page() {
		add_menu_page(
			__( 'Options', 'plugintest' ),
			__( 'Options', 'plugintest' ),
			'manage_options',
			'options',
			array( $this, 'view_option_page' )
		);
	}
	/**
	 * Function for view admin menu page
	 */
	public function view_option_page() {
		include_once plugin_dir_path( __FILE__ ) . 'options.php';
	}


	/**
	 * Function for save options page to option table
	 */
	public function add_options() {
		$types = get_option( 'ibx_docs_type' );
		if ( ! empty( $types ) ) {
			$docs_option = maybe_unserialize( $types );
		} else {
			$docs_option = array();
		}

		if (
			! isset( $_POST['docs_generate_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['docs_generate_nonce'] ) ), 'docs__form_save' )
		) {
			return;
		}

		$post_title = '';
		$post_slug  = '';
		// process form data.
		if ( isset( $_POST['title'] ) ) {
			$post_title = sanitize_text_field( wp_unslash( $_POST['title'] ) );
			if ( isset( $_POST['slug'] ) && ! empty( $_POST['slug'] ) ) {
				$post_slug = sanitize_text_field( wp_unslash( $_POST['slug'] ) );
			} else {
				$post_slug = sanitize_title_with_dashes( $post_title );
			}
		}

		$docs_option[ $post_slug ] = array(
			'title' => $post_title,
			'slug'  => $post_slug,
		);

		update_option( 'ibx_docs_type', maybe_serialize( $docs_option ) );
	}

	/**
	 * Function for updating permalink after rewriting.
	 *
	 * @param mixed $url updated url after rewwriting.
	 * @param mixed $post updated url for particular post.
	 */
	public function update_permalinks( $url, $post ) {

		$doc_types = get_option( 'ibx_docs_type' );
		$doc_types = maybe_unserialize( $doc_types );

		if ( empty( $doc_types ) ) {
			return $url;
		}
		$post_type = get_post_type( $post );

		if ( in_array( $post_type, array_keys( $doc_types ), true ) ) {
			return home_url( '/ibx/' . $post_type . '/' . $post->post_name . '/' );
		}
		return $url;
	}

	/**
	 * Function for custom_post_type for options page.s
	 */
	public function generate_post_type() {
		$doc_types = get_option( 'ibx_docs_type' );
		$doc_types = maybe_unserialize( $doc_types );

		if ( empty( $doc_types ) ) {
			return;
		}

		$count = 1;

		foreach ( $doc_types as $option ) {
			$labels = array(
				'name'          => _x( $option['title'], 'Post type general name', 'plugintest' ),
				'singular_name' => _x( $option['title'], 'Post type singular name', 'plugintest' ),
			);

			$args = array(
				'labels'             => $labels,
				'public'             => true,
				'publicly_queryable' => true,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'query_var'          => true,
				'rewrite'            => array( 'slug' => $option['slug'] ),
				'capability_type'    => 'post',
				'hierarchical'       => false,
				'has_archive'        => true,
			);

			register_post_type( $option['slug'], $args );
			// add_rewrite_rule( '^docs/' . $option['slug'] . '/([^/]+)/?$', 'index.php?post_type=' . $option['slug'] . '&name=$matches[1]', 'top' );
			//add_rewrite_rule( '^docs/' . $option['slug'], 'index.php?post_type=' . $option['slug'], 'top' );
			add_rewrite_rule( 'ibx/' . $option['slug'] . '/(.*)/?$', 'index.php?' . $option['slug'] . '=$matches[1]', 'top' );
			add_rewrite_rule( 'ibx/' . $option['slug'], 'index.php?post_type=' . $option['slug'], 'top' );

			$category_labels = array(
				'name'          => esc_html__( 'Categories', 'plugintest' ),
				'singular_name' => esc_html__( 'Category', 'plugintest' ),
			);

			$category_args = array(
				'hierarchical'      => true,
				'public'            => true,
				'labels'            => $category_labels,
				'show_ui'           => true,
				'show_admin_column' => true,
				'has_archive'       => true,
				'rewrite'        => array( 'slug' => 'category-' . $count ),
			);

			register_taxonomy( 'cat-' . $option['slug'], $option['slug'], $category_args );
			add_rewrite_rule( 'ibx/cat-' . $option['slug'] . '/(.*)/?$', 'index.php?cat-' . $option['slug'] . '=$matches[1]', 'top' );

			$count++;
		}
		flush_rewrite_rules();
	}

	public function generate_custom_post_type() {

		$query_args = array(
			'post_type'   => 'movie',
			'post_status' => 'publish',
			'posts_per_page' => '-1',
		);

		$query = new WP_Query( $query_args );

		if ( $query->have_posts() ) {
			$post_types = array();
			while ( $query->have_posts() ) {
				$query->the_post();

				$post_details = array(
					'title' => get_the_title(),
					'id'    => get_the_ID(),
					'slug'  => 'doc',
				);

				$labels = array(
					'name'          => _x( $post_details['title'], 'Post type general name', 'plugintest' ),
					'singular_name' => _x( $post_details['title'], 'Post type singular name', 'plugintest' ),
				);

				$type = $post_details['slug'] . '-' . $post_details['id'];

				$this->post_types[ $post_details['id'] ] = $type;

				$args = array(
					'labels'             => $labels,
					'public'             => true,
					'publicly_queryable' => true,
					'show_ui'            => true,
					'show_in_menu'       => true,
					'query_var'          => true,
					'rewrite'            => array( 'slug' => $type ),
					'capability_type'    => 'post',
					'hierarchical'       => false,
					'has_archive'        => true,
				);

				register_post_type( $type, $args );

				add_rewrite_rule(
					'^movie/' . $post_details['id'] . '/([^/]+)/?$',
					'index.php?post_type=' . $type . '&name=$matches[1]',
					'top'
				);

				add_rewrite_rule( '^movie/' . $post_details['id'], 'index.php?post_type=' . $type, 'top' );

				$category_labels = array(
					'name'              => esc_html__( 'Categories', 'plugintest' ),
					'singular_name'     => esc_html__( 'Category', 'plugintest' ),
				);

				$category_args = array(
					'hierarchical'      => false,
					'public'            => true,
					'labels'            => $category_labels,
					'show_ui'           => true,
					'show_admin_column' => true,
					'has_archive'       => true,
					//'rewrite'         => array( 'slug' => 'category-' . $post_details['id'] ),
				);

				register_taxonomy( 'category-' .  $post_details['id'], $type, $category_args );

				add_rewrite_rule( '^movie/category-' . $post_details['id'] . '/([^/]+)/?$', 'index.php?category-' . $post_details['id'] . '=$matches[1]', 'top' );
			}	
		}
		//$this->rewrite_rules();
		flush_rewrite_rules();
	}

	/**
	 * Function for updating permalink after rewriting.
	 *
	 * @param mixed $url updated url after rewwriting.
	 * @param mixed $post updated url for particular post.
	 */
	public function update_post_permalinks( $url, $post ) {
		$query_args = array(
			'post_type'   => 'movie',
			'post_status' => 'publish',
			'posts_per_page' => '-1',
		);

		$doc_types = get_posts( $query_args );
		$slugs = array();

		if ( empty( $doc_types ) ) {
			return $url;
		}

		foreach ( $doc_types as $doc_type ) {
			$slugs[ 'doc-' . $doc_type->ID ] = $doc_type->ID;
		}

		$post_type = get_post_type( $post );

		if ( isset( $slugs[ $post_type ] ) ) {
			return home_url( '/ibxx/' . $slugs[ $post_type ] . '/' . $post->post_name . '/' );
		}
		return $url;
	}

	public function update_term_link( $content ) {
		if ( empty( $this->post_types ) ) {
			return $content;
		}

		foreach ( $this->post_types as $id => $type ) {
			$category = 'category-' . $id;

			$content = str_replace( $category, 'ibxx/' . $category, $content );
		}

		return $content;
	}

	public function rewrite_rules() {
		if ( is_post_type_archive( $this->post_types ) ) {
			$ids = array();
			foreach ( $this->post_types as $type ) {
				$id = explode( '-', $type )[1];
			}
			add_rewrite_rule( '^ibx/(d+)/?$', 'index.php?post_type=' . $matches[1], 'top' );
			echo 'test';
		}
	}


	/**
	 * Function for shortcode.
	 */
	public function demo_shortcode() {

		$args = array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => '-1',
			'tax_query'      => array(
				'relation' => 'AND',
				array(
					'taxonomy' => 'category',
					'field'    => 'slug',
					'terms'    => array( 'entertainment', 'sports' ),
				),
				array(
					'taxonomy' => 'post_tag',
					'field'    => 'slug',
					'terms'    => array( 'amazonprime', 'hotstar', 'champions-cup' ),
				),
			),
		);

		$query = new WP_Query( $args );

		if ( $query->have_posts() ) {
			echo '<ul class="pt-posts">';
			while ( $query->have_posts() ) {
				$query->the_post();

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
			esc_html_e( 'No posts found.', 'plugintest' );
		}

		// To restore global post variable to refer to the main query loop.
		wp_reset_postdata();
	}
	/**
	 * Function for custom post type (Movie).
	 */
	public function register_post_type() {
		$labels = array(
			'name'                  => _x( 'Movies', 'Post type general name', 'plugintest' ),
			'singular_name'         => _x( 'Movie', 'Post type singular name', 'plugintest' ),
			'menu_name'             => _x( 'Movies', 'Admin Menu text', 'plugintest' ),
			'name_admin_bar'        => _x( 'Movie', 'Admin Menu Toolbar text', 'plugintest' ),
			'add_new'               => __( 'Add New', 'plugintest' ),
			'add_new_item'          => __( 'Add New Movie', 'plugintest' ),
			'new_item'              => __( 'Aaaaaa', 'plugintest' ),
			'view_item'             => __( 'View Movie', 'plugintest' ),
			'edit_item'             => __( 'Edit Movie', 'plugintest' ),
			'all_items'             => __( 'All Movies', 'plugintest' ),
			'search_items'          => __( 'Search Movies', 'plugintest' ),
			'parent_item_colon'     => __( 'Parent Movies', 'plugintest' ),
			'not_found'             => __( 'No Movies found.', 'plugintest' ),
			'not_found_in_trash'    => __( 'No Movies found in Trash.', 'plugintest' ),
			'featured_image'        => _x( 'Movie Cover Image', 'Overrides the “Featured Image” phrase for this post type. Added in 4.3', 'plugintest' ),
			'set_featured_image'    => _x( 'Set cover image', 'Overrides the “Set featured image” phrase for this post type. Added in 4.3', 'plugintest' ),
			'remove_featured_image' => _x( 'Remove cover image', 'Overrides the “Remove featured image” phrase for this post type. Added in 4.3', 'plugintest' ),
			'use_featured_image'    => _x( 'Use as cover image', 'Overrides the “Use as featured image” phrase for this post type. Added in 4.3', 'plugintest' ),
			'item_published'        => __( 'New Movie Published.', 'plugintest' ),
			'item_updated'          => __( 'Movie post updated.', 'plugintest' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'movie' ),
			'capability_type'    => 'post',
			'hierarchical'       => false,
			'has_archive'        => true,
			'menu_position'      => null,
			'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments' ),
			'taxonomies'         => array( 'category', 'post_tag' ),   // Using wordpess category and tags.
		);

		register_post_type( 'movie', $args );
		flush_rewrite_rules();

		$category_labels = array(
			'name'              => esc_html__( 'Movies Categories', 'plugintest' ),
			'singular_name'     => esc_html__( 'Movie Category', 'plugintest' ),
			'all_items'         => esc_html__( 'Movies Categories', 'plugintest' ),
			'parent_item'       => null,
			'parent_item_colon' => null,
			'edit_item'         => esc_html__( 'Edit Category', 'plugintest' ),
			'update_item'       => esc_html__( 'Update Category', 'plugintest' ),
			'add_new_item'      => esc_html__( 'Add New Movie Category', 'plugintest' ),
			'new_item_name'     => esc_html__( 'New Movie Name', 'plugintest' ),
			'menu_name'         => esc_html__( 'Genre', 'plugintest' ),
			'search_items'      => esc_html__( 'Search Categories', 'plugintest' ),
		);

		$category_args = array(
			'hierarchical'      => false,
			'public'            => true,
			'labels'            => $category_labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'rewrite'           => array( 'slug' => 'genre' ),
		);

		register_taxonomy( 'genre', array( self::$post_type ), $category_args );
	}

	public function custom_post_type_archive_template( $template ) {

		$slug_arr = array();

		$query_args = array(
			'post_type'   => 'movie',
			'post_status' => 'publish',
			'posts_per_page' => '-1',
		);

		$query = new WP_Query( $query_args );

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();

				array_push( $slug_arr, 'doc-' . get_the_ID() );
			}
		}

		if ( is_post_type_archive( $slug_arr ) ) {
			$theme_files     = array( 'archive-cat.php', 'template/archive-cat.php' );
			$exists_in_theme = locate_template( $theme_files, false );

			if ( '' !== $exists_in_theme ) {
				return $exists_in_theme;
			} else {
				return plugin_dir_path( __FILE__ ) . 'template/archive-cat.php';
			}
		}
		return $template;
	}


	/**
	 * Function for cutom template for custom post type.
	 *
	 * @param mixed $template custom post type template.
	 */
	public function custom_post_type_archive( $template ) {
		if ( is_post_type_archive( 'movie' ) ) {
			$theme_files     = array( 'archive-movie.php', 'template/archive-movie.php' );
			$exists_in_theme = locate_template( $theme_files, false );

			if ( '' !== $exists_in_theme ) {
				return $exists_in_theme;
			} else {
				return plugin_dir_path( __FILE__ ) . 'template/archive-movie.php';
			}
		}
		return $template;
	}


	public function get_custom_post_type_template( $single ) {
		global $post;
		if ( 'movie' === $post->post_type ) {
			$single = plugin_dir_path( __FILE__ ) . 'template/single-movie.php';
		}

		return $single;
	}
}

$plugintest1 = new Plugintest();
