<?php
/**
 * Adds admin styles, scripts, and other modifications to the
 * WordPress admin that aren't related to plugin settings
 */
if ( ! class_exists( 'UCF_Sanitizer_Admin' ) ) {
	class UCF_Sanitizer_Admin {
		/**
		 * Enqueues admin assets for this plugin. Intended for use
		 * with the `admin_enqueue_scripts` hook.
		 *
		 * @since 1.0.0
		 * @author Jo Dickson
		 * @param string $hook
		 * @return void
		 */
		public static function admin_enqueue_scripts( $hook ) {
			if ( ! in_array( $hook, array( 'post-new.php', 'post.php' ) ) ) {
				return;
			}

			$current_screen = get_current_screen();
			$post_type      = isset( $current_screen->post_type ) ? $current_screen->post_type : null;
			$plugin_data    = get_plugin_data( UCF_SANITIZER__PLUGIN_FILE, false, false );
			$version        = $plugin_data['Version'];

			if ( $post_type && in_array( $post_type, UCF_Sanitizer_Config::get_option_or_default( 'enabled_post_types' ) ) ) {
				wp_enqueue_script(
					'ucfsanitize_admin_post_edit',
					UCF_SANITIZER__JS_URL . '/admin-post-edit.min.js',
					array(),
					$version,
					true
				);

				wp_localize_script(
					'ucfsanitize_admin_post_edit',
					'UCFSanitizeAdminPostEdit',
					self::localize_options_admin_post_edit()
				);
			}
		}

		/**
		 * Returns an associative array that should be translated
		 * into a localization object for the `ucfsanitize_admin_post_edit`
		 * script.
		 *
		 * Plugin settings and other configuration options that need to be
		 * accessible in this script should be added here.
		 *
		 * @since 1.0.0
		 * @author Jo Dickson
		 * @return array
		 */
		public static function localize_options_admin_post_edit() {
			return array(
				'on_paste_enable_postmaster_filtering' => UCF_Sanitizer_Config::get_option_or_default( 'on_paste_enable_postmaster_filtering' ),
				'on_paste_enable_safelink_filtering' => UCF_Sanitizer_Config::get_option_or_default( 'on_paste_enable_safelink_filtering' )
			);
		}

		/**
		 * Customizes TinyMCE's configuration.
		 *
		 * Adds a paste_preprocess rule that sanitizes incoming
		 * copy+pasted content according to plugin settings.
		 *
		 * @since 1.0.0
		 * @author Jo Dickson
		 * @param array $in TinyMCE init config
		 * @return array TinyMCE init config
		 */
		public static function configure_tinymce( $in ) {
			ob_start();
		?>
			function (plugin, args) {
				// Return the clean HTML
				args.content = UCFSanitizerJSCommon.runSanitizers(args.content);
			}
		<?php
			$in['paste_preprocess'] = trim( ob_get_clean() );

			return $in;
		}
	}
}
