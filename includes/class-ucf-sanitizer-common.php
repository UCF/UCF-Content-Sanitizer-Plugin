<?php
/**
 * Utility functions
 */
if ( ! class_exists( 'UCF_Sanitizer_Common' ) ) {
	/**
	 * Common static functions used throughout the plugin
	 * @author Jo Dickson
	 * @since 1.0.0
	 */
	class UCF_Sanitizer_Common {

		/**
		 * Returns a content string with `<a>` href values sanitized
		 * using the provided callback.
		 *
		 * @since 1.0.0
		 * @author Jo Dickson
		 * @param string $content Arbitrary HTML content string
		 * @param mixed $callback Callable function to perform on each link href value in $content
		 * @return string Sanitized content string
		 */
		public static function sanitize_links( $content, $callback ) {
			if ( is_string( $content ) ) {
				$content = preg_replace_callback(
					'/href=(?P<quote>\'|\")(?P<href>(?:[^\'\"])*)(?P=quote)/i',
					function( $match ) use ( $callback ) {

						$link = $match[0];
						$href = isset( $match['href'] ) ? $match['href'] : '';
						if ( ! $href ) return $link;

						$href_clean = call_user_func_array( $callback, array( $href ) );

						if ( $href_clean !== $href ) {
							$link = str_replace( $href, $href_clean, $link );
						}

						return $link;

					},
					$content
				);
			}

			return $content;
		}

		/**
		 * Generic function that replaces a URL with a query param value
		 * based on specific search criteria in the URL.  Intended for
		 * picking out final URLs from domains that perform redirections.
		 *
		 * Example:
		 * ```
		 * strip_link_prefix(
		 *     'https://some-redirector-domain.com/?url=https://www.ucf.edu/',
		 *     '//^https\:\/\/some\-redirector-\domain\.com\//i/',
		 *     'url'
		 * );
		 * ```
		 * would return "https://www.ucf.edu/"
		 *
		 * @since 1.0.0
		 * @author Jo Dickson
		 * @param string $url The full URL to parse against
		 * @param string $search_regex Regex to perform against $url using `preg_match()`
		 * @param string $query_param Query parameter to isolate the desired URL from
		 * @return string The filtered URL value
		 */
		public static function strip_link_prefix( $url, $search_regex, $query_param ) {
			if ( preg_match( $search_regex, $url ) ) {
				$query_params = array();
				parse_str( parse_url( $url, PHP_URL_QUERY ), $query_params );
				if ( isset( $query_params[$query_param] ) ) {
					$url = urldecode( $query_params[$query_param] );
				}
			}

			return $url;
		}

		/**
		 * Replaces Outlook safelink URLs with the actual redirected URL.
		 *
		 * @since 1.0.0
		 * @author Jo Dickson
		 * @param string $url The full URL to parse against
		 * @return string The filtered URL value
		 */
		public static function strip_outlook_safelinks( $url ) {
			return self::strip_link_prefix(
				$url,
				'/^https\:\/\/(.*\.)safelinks\.protection\.outlook\.com\//i',
				'url'
			);
		}

		/**
		 * Replaces Postmaster redirects with the actual redirected URL.
		 *
		 * @since 1.0.0
		 * @author Jo Dickson
		 * @param string $url The full URL to parse against
		 * @return string The filtered URL value
		 */
		public static function strip_postmaster_redirects( $url ) {
			return self::strip_link_prefix(
				$url,
				'/^https\:\/\/postmaster\.smca\.ucf\.edu\//i',
				'url'
			);
		}

		/**
		 * Returns all eligible posts for sanitization when performing bulk
		 * sanitization tasks.
		 *
		 * @since 1.0.0
		 * @author Jo Dickson
		 * @return array Array of WP_Post objects
		 */
		public static function get_posts_for_sanitization() {
			return get_posts( array(
				'posts_per_page' => -1,
				'post_status'    => array( 'publish', 'pending', 'draft', 'future', 'private' ),
				'post_type'      => UCF_Sanitizer_Config::get_option_or_default( 'enabled_post_types' )
			) );
		}

		/**
		 * Runs all valid WP-CLI sanitizers on the given content string,
		 * depending on plugin settings/configuration.
		 *
		 * @since 1.0.0
		 * @author Jo Dickson
		 * @param string $content Arbitrary content to sanitize
		 * @return string Sanitized content
		 */
		public static function run_cli_sanitizers( $content ) {
			if ( UCF_Sanitizer_Config::get_option_or_default( 'cli_enable_safelink_filtering' ) === true ) {
				$content = self::sanitize_links( $content, array( 'UCF_Sanitizer_Common', 'strip_outlook_safelinks' ) );
			}
			if ( UCF_Sanitizer_Config::get_option_or_default( 'cli_enable_postmaster_filtering' ) === true ) {
				$content = self::sanitize_links( $content, array( 'UCF_Sanitizer_Common', 'strip_postmaster_redirects' ) );
			}
			return $content;
		}

		/**
		 * Runs all valid sanitizers on the given content string,
		 * depending on plugin settings/configuration for posts on-save.
		 *
		 * @since 1.0.0
		 * @author Jo Dickson
		 * @param string $content Arbitrary content to sanitize
		 * @return string Sanitized content
		 */
		public static function run_post_save_sanitizers( $content ) {
			if ( UCF_Sanitizer_Config::get_option_or_default( 'post_save_enable_safelink_filtering' ) === true ) {
				$content = self::sanitize_links( $content, array( 'UCF_Sanitizer_Common', 'strip_outlook_safelinks' ) );
			}
			if ( UCF_Sanitizer_Config::get_option_or_default( 'post_save_enable_postmaster_filtering' ) === true ) {
				$content = self::sanitize_links( $content, array( 'UCF_Sanitizer_Common', 'strip_postmaster_redirects' ) );
			}
			return $content;
		}

		/**
		 * Filter for `wp_insert_post_data` that performs sanitization
		 * on post content immediately before the post is saved.
		 *
		 * @since 1.0.0
		 * @author Jo Dickson
		 * @param array $data    An array of slashed post data.
		 * @param array $postarr An array of sanitized, but otherwise unmodified post data.
		 * @return array Modified, slashed post data
		 */
		public static function add_post_save_content_sanitizers( $data, $postarr ) {
			// Only perform sanitization on this post if
			// it's an enabled post type:
			if ( in_array( $data['post_type'], UCF_Sanitizer_Config::get_option_or_default( 'enabled_post_types' ) ) ) {
				// Values in $data are expected to be slashed.
				// Unslash all modified values, then re-slash them
				// before returning $data:
				$data['post_content'] = wp_slash( self::run_post_save_sanitizers( wp_unslash( $data['post_content'] ) ) );
			}
			return $data;
		}

	}
}
