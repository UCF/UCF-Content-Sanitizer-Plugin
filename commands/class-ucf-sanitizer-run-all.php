<?php
/**
 * Runs all the configured tasks.
 */
if ( ! class_exists( 'UCF_Sanitizer_Command_Sanitize_All' ) ) {
	class UCF_Sanitizer_Command_Sanitize_All {
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
			$filter_content = new UCF_Sanitizer_Command_Sanitize_Content();
			$filter_content->__invoke( $args );

			WP_CLI::success( 'Finished running all tasks.' );
		}
	}
}
