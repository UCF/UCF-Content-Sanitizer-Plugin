<?php
/**
 * Runs all the configured tasks.
 */
if ( ! class_exists( 'UCF_Sanitizer_Command_Sanitize_All' ) ) {
	class UCF_Sanitizer_Command_Sanitize_All {
		private
			/**
			 * @var array Set of posts to process
			 */
			$posts;


		public function __construct( $args=array() ) {
			$this->posts = UCF_Sanitizer_Common::get_posts_for_sanitization();
		}

		/**
		 * Runs all filtering commands
		 *
		 * ## EXAMPLES
		 *
		 *     wp ucfsanitizer sanitize all
		 *
		 * @when after_wp_load
		 */
		public function __invoke( $args ) {
			$filter_content = new UCF_Sanitizer_Command_Sanitize_Content( array(
				'posts' => $this->posts
			) );
			$filter_content->__invoke( $args );

			WP_CLI::success( 'Finished running all tasks.' );
		}
	}
}
