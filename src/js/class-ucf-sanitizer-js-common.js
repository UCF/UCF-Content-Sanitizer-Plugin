/* global UCFSanitizeAdminPostEdit, sanitizeHtml */

/**
 * Common functions for sanitizing content with Javascript.
 */
class UCFSanitizerJSCommon { // eslint-disable-line no-unused-vars
  /**
   * Returns whether or not the given plugin setting for
   * a content sanitizer is enabled.
   *
   * @author Jo Dickson
   * @since 1.0.0
   * @param {string} sanitizer The setting name
   * @return {boolean} Whether or not the setting is enabled
   */
  static hasSanitizer(sanitizer) {
    return UCFSanitizeAdminPostEdit[sanitizer] === '1';
  }

  /**
   * Returns whether or not any link sanitizers are enabled.
   *
   * @author Jo Dickson
   * @since 1.0.0
   * @return {boolean} Whether or not any link sanitizers are enabled
   */
  static hasLinkSanitizers() {
    return this.hasSanitizer('on_paste_enable_postmaster_filtering') || this.hasSanitizer('on_paste_enable_safelink_filtering');
  }

  /**
   * Returns whether or not any tag transforms
   * (`transformTag` arg in sanitizeHtml lib) are enabled.
   *
   * @author Jo Dickson
   * @since 1.0.0
   * @return {boolean} Whether or not any tag transforms are enabled
   */
  static hasTagTransforms() {
    return this.hasLinkSanitizers();
  }

  /**
   * Generic function that replaces a URL with a query param value
   * based on specific search criteria in the URL.
   *
   * See PHP equivalent, `UCF_Sanitizer_Common::strip_link_prefix()`,
   * for more information.
   *
   * @author Jo Dickson
   * @since 1.0.0
   * @param {string} url The full URL to parse against
   * @param {RegExp} searchRegex Regex to perform against `url` using `url.search()`
   * @param {string} queryParam Query parameter to isolate the desired URL from
   * @return {boolean} Whether or not any link sanitizers are enabled
   */
  static stripLinkPrefix(url, searchRegex, queryParam) {
    if (url.search(searchRegex) !== -1) {
      const dummylink = document.createElement('a');
      dummylink.href = url;
      const query = dummylink.search;
      let updatedUrl = '';

      if (query.indexOf(`${queryParam}=`) !== -1) {
        // Get the query param
        updatedUrl = query.replace('?', '').split('&').filter((x) => {
          const kv = x.split('=');
          if (kv[0] === queryParam) {
            return true;
          }
          return false;
        }).shift().split('=')[1];

        // Decode special characters.
        // This is dumb, but colon (:) characters don't get
        // decoded properly without running decodeURIComponent()
        // on the string twice
        updatedUrl = decodeURIComponent(decodeURIComponent(updatedUrl));

        url = updatedUrl;
      }
    }

    return url;
  }

  /**
   * Replaces Outlook safelink URLs with the actual redirected URL.
   *
   * @author Jo Dickson
   * @since 1.0.0
   * @param {string} url The full URL to parse against
   * @return {string} The filtered URL value
   */
  static stripOutlookSafelinks(url) {
    return this.stripLinkPrefix(
      url,
      /^https:\/\/(.*\.)safelinks\.protection\.outlook\.com\//i,
      'url'
    );
  }

  /**
   * Replaces Postmaster redirects with the actual redirected URL.
   *
   * @author Jo Dickson
   * @since 1.0.0
   * @param {string} url The full URL to parse against
   * @return {string} The filtered URL value
   */
  static stripPostmasterRedirects(url) {
    return this.stripLinkPrefix(
      url,
      /^https:\/\/postmaster\.smca\.ucf\.edu\//i,
      'url'
    );
  }

  /**
   * Returns arguments to pass to the sanitizeHtml lib
   * based on plugin settings.
   *
   * @author Jo Dickson
   * @since 1.0.0
   * @return {Object} sanitizeHtml args
   */
  static getSanitizeHtmlArgs() {
    const self = this; // eslint-disable-line consistent-this
    const args = {};

    // Set up transformTags arg
    if (self.hasTagTransforms()) {
      args.transformTags = {};

      // Build transformTags settings
      if (self.hasLinkSanitizers()) {
        args.transformTags.a = function (tagName, attribs) {
          if (attribs.href) {
            let url = attribs.href;

            if (self.hasSanitizer('on_paste_enable_safelink_filtering')) {
              url = self.stripOutlookSafelinks(url);
            }

            if (self.hasSanitizer('on_paste_enable_postmaster_filtering')) {
              url = self.stripPostmasterRedirects(url);
            }

            if (url !== attribs.href) {
              attribs.href = url;
            }
          }

          return {
            tagName: tagName,
            attribs: attribs
          };
        };
      }
    }

    return args;
  }

  /**
   * Performs all enabled sanitizers on the given content string.
   *
   * @author Jo Dickson
   * @since 1.0.0
   * @param {string} content Arbitrary HTML content string
   * @return {string} Sanitized content string
   */
  static runSanitizers(content) {
    const args = this.getSanitizeHtmlArgs();
    if (args) {
      content = sanitizeHtml(content, args);
    }

    return content;
  }
}
