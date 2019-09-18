<?php
/**
 * Performs content sanitization all at once on post content,
 * depending on plugin settings.
 */
if ( ! class_exists( 'UCF_Sanitizer_Command_Sanitize_Content' ) ) {
	class UCF_Sanitizer_Command_Sanitize_Content {
		private
			/**
			 * @var array Set of posts to process
			 */
			$posts,
			/**
			 * @var object Progress bar object
			 */
			$progress,
			/**
			 * @var int Number of sanitized + saved posts
			 */
			$sanitized = 0;


		public function __construct( $args=array() ) {
			$this->posts = isset( $args['posts'] ) ? $args['posts'] : $this->get_posts();
		}

		/**
		 * Filters old post content to strip out undesirable tags.
		 *
		 * ## EXAMPLES
		 *
		 *     wp ucfsanitizer sanitize content
		 *
		 * @when after_wp_load
		 */
		public function __invoke( $args ) {
			global $wpdb;
			$count = count( $this->posts );

			$this->progress = WP_CLI\Utils\make_progress_bar(
				"Updating Post Content...",
				$count
			);

			foreach ( $this->posts as $post ) {
				$post_content = $post->post_content;
				$post_content = UCF_Sanitizer_Common::run_cli_sanitizers( $post_content );
				if ( $post->post_content !== $post_content ) {
					$update_status = $wpdb->update( $wpdb->posts, array( 'post_content' => $post_content ), array( 'ID' => $post->ID ) );
					if ( $update_status !== false ) {
						$this->sanitized++;
						clean_post_cache( $post->ID );
					}
				}
				$this->progress->tick();
			}

			$this->progress->finish();

			WP_CLI::success( "Updated post content within $this->sanitized posts out of $count processed posts." );
		}

		/**
		 * Returns all post objects to be processed during this command.
		 *
		 * @since 1.0.0
		 * @author Jo Dickson
		 * @return array Array of WP_Post objects
		 */
		private function get_posts() {
			if ( $this->posts ) {
				return $this->posts;
			}

			return UCF_Sanitizer_Common::get_posts_for_sanitization();
		}
	}
}
