<?php
/**
 * Performs content sanitization all at once on post content,
 * depending on plugin settings.
 */
if ( ! class_exists( 'UCF_Sanitizer_Command_Sanitize_Content' ) ) {
	class UCF_Sanitizer_Command_Sanitize_Content {
		private
			$progress,
			$filtered = 0;

		/**
		 * Filters old post content to strip out undesirable tags.
		 *
		 * ## EXAMPLES
		 *
		 *     wp ucfsanitizer filter content
		 *
		 * @when after_wp_load
		 */
		public function __invoke( $args ) {
			// TODO filter by enabled post types
			$posts = get_posts( array(
				'posts_per_page' => -1,
				'post_status'    => array( 'publish', 'pending', 'draft', 'future', 'private' )
			) );

			$count = count( $posts );

			$this->progress = WP_CLI\Utils\make_progress_bar(
				"Updating Post Content...",
				$count
			);

			foreach ( $posts as $post ) {
				// TODO add Postmaster link filtering, if enabled
				// TODO add Outlook Safelink filtering, if enabled
				// TODO add on-save HTML tag filtering, if enabled
				// TODO add action for themes/plugins to hook into here
				$this->progress->tick();
			}

			$this->progress->finish();

			WP_CLI::success( "Updated post content within $this->filtered posts out of $count processed posts." );
		}
	}
}
