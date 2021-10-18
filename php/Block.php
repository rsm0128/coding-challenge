<?php
/**
 * Block class.
 *
 * @package SiteCounts
 */

namespace XWP\SiteCounts;

use WP_Block;
use WP_Query;

/**
 * The Site Counts dynamic block.
 *
 * Registers and renders the dynamic block.
 */
class Block {

	/**
	 * The Plugin instance.
	 *
	 * @var Plugin
	 */
	protected $plugin;

	/**
	 * Instantiates the class.
	 *
	 * @param Plugin $plugin The plugin object.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Adds the action to register the block.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'init', [ $this, 'register_block' ] );
	}

	/**
	 * Registers the block.
	 */
	public function register_block() {
		register_block_type_from_metadata(
			$this->plugin->dir(),
			[
				'render_callback' => [ $this, 'render_callback' ],
			]
		);
	}

	/**
	 * Renders the block.
	 *
	 * @param array    $attributes The attributes for the block.
	 * @param string   $content    The block content, if any.
	 * @param WP_Block $block      The instance of this block.
	 * @return string The markup of the block.
	 */
	public function render_callback( $attributes, $content, $block ) {
		// Init variables.
		$current_post_id = get_the_ID();
		$post_types      = get_post_types( [ 'public' => true ], 'objects' );
		$class_name      = isset( $attributes['className'] ) ? $attributes['className'] : '';

		ob_start();
		?>
		<div class="<?php echo esc_attr( $class_name ); ?>">
			<h2><?php esc_html_e( 'Post Counts', 'site-counts' ); ?></h2>
			<ul>
				<?php
				foreach ( $post_types as $post_type_object ) :
					$post_count      = wp_count_posts( $post_type_object->name );
					$published_count = $post_count->publish;

					echo '<li>';
					if ( $published_count > 0 ) :
						echo esc_html(
							sprintf(
								/* translators: 1: Number of posts 2: Post type name */
								_n( 'There is %1$d %2$s.', 'There are %1$d %2$s.', $published_count, 'site-counts' ),
								$published_count,
								( 1 > $published_count ) ? $post_type_object->labels->name : $post_type_object->labels->singular_name
							)
						);
					else :
						/* translators: %s: The post type plural name. */
						echo esc_html( sprintf( __( 'There are no %s.', 'site-counts' ), $post_type_object->labels->name ) );
					endif;
					echo '</li>';

				endforeach;
				?>
			</ul>
			<p>
				<?php /* translators: %d: The current post ID. */ ?>
				<?php echo esc_html( sprintf( __( 'The current post ID is %d', 'site-counts' ), $current_post_id ) ); ?>
			</p>

			<?php
			$foo_baz_query = new WP_Query(
				[
					'post_type'              => 'post',
					'posts_per_page'         => 6,
					'post_status'            => 'publish',
					'date_query'             => [
						[
							'hour'    => 9,
							'compare' => '>=',
						],
						[
							'hour'    => 17,
							'compare' => '<=',
						],
					],
					'tag'                    => 'foo',
					'category_name'          => 'baz',
					// 'meta_value'             => 'Accepted', // This should be updated with better mechanism.
					'no_found_rows'          => true,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				]
			);

			if ( $foo_baz_query->have_posts() ) :
				$post_list_body = '<ul>';
				$post_count     = 0;
				while ( $foo_baz_query->have_posts() && $post_count < 5 ) :
					$foo_baz_query->the_post();
					if ( get_the_ID() !== $current_post_id ) {
						$post_count++;
						$post_list_body .= sprintf( '<li>%s</li>', get_the_title() );
					}
				endwhile;
				wp_reset_postdata();

				$post_list_body .= '</ul>';

				/* translators: %d: The count of posts to show. */
				echo '<h2>' . esc_html( sprintf( __( 'Any %d posts with the tag of foo and the category of baz', 'site-counts' ), $post_count ) ) . '</h2>';
				echo $post_list_body;
			endif;
			?>
		</div>
		<?php

		return ob_get_clean();
	}
}
