=== Media File Renamer: Rename Files (Manual, Auto & AI) ===
Contributors: TigrouMeow
Tags: rename, file, attach, media, move, seo, files, renamer, optimize, library, slug, change, modify
Donate link: https://meowapps.com/donation/
Requires at least: 6.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 5.7.7

Rename and move files directly from the dashboard, either individually or in bulk. You can even set it to automatically rename your files for you! Nicer SEO, tidier WordPress, better life.

== Description ==
Rename and move files directly from the dashboard, either individually or in bulk. You can even set it to automatically rename your files for you! Nicer SEO, tidier WordPress, better life. For more information, please visit the official website: [Media File Renamer](https://meowapps.com/media-file-renamer/).

=== HOW IT WORKS ===
By default, it automatically renames your media filenames based on their titles every time you modify them. But you can also manually rename files and update references to them throughout your site, including posts, pages, custom post types, and metadata. The best way to use the plugin is through the sleek and dynamic Renamer Dashboard, which makes it easy to work efficiently and effectively. 

[youtube https://youtu.be/XPbKE8pq0i0]

Please have a look at the [tutorial](https://meowapps.com/media-file-renamer/tutorial/).

=== COMPATIBILITY ===
Media File Renamer works seamlessly with many features of WordPress and other plugins, including Retina files, WebP, rescaled images (since WP 5.3), PDF Thumbnails, UTF8 files, optimized images, and more. It can handle a wide variety of encoding cases, making it a reliable tool for organizing your media library.

There are a few page builders, like Avia Layout Builder, that currently do not allow Media File Renamer to rename images used in their posts due to encryption. However, we are actively seeking out solutions to this issue and are committed to providing users with the ability to rename these images if they desire.

=== PRO VERSION ===
In the [Pro Version](https://meowapps.com/media-file-renamer/), you'll find many exciting features.

- Automatically rename files based on attached posts, products, or ALT texts
- AI Suggestions (via AI Engine and OpenAI)
- Anonymize your files with anonymous filenames
- Move files to different directories in bulk
- Sync metadata like ALT texts and titles
- Number your files to allow for similar filenames
- Attach media entries to the posts or pages they're used in
- Use the Force Rename feature to re-link broken media entries to your files
- Advanced transliteration handles accents, emoticons, umlauts, cyrillic, and more

=== IMPORTANT ===
Renaming or moving files can be a risky process, which is why it's important to take precautions. Before renaming your files in bulk, try renaming them one by one to make sure the references in your pages are updated properly. It's worth noting that some plugins may use unconventional methods to encode file usage, which could cause issues with the renaming process. To ensure the safety of your files and database, **it is crucial to make a backup before using Media File Renamer** to its full extent. Protect your valuable media by taking these precautionary measures.

If you notice any issues with your website after renaming your media files, **try clearing your cache**. Cached HTML can often hold onto old references, so this simple step can often resolve any issues. If you're still experiencing problems, you can use the Undo feature to roll back to the previous filenames. If you're having trouble updating references or have any other questions, please check out the support threads on our website. We're always working to cover more use cases and improve the plugin. You will find more here: [Questions & Issues](https://meowapps.com/media-file-renamer/issues/).

=== FOR DEVELOPERS ===
The plugin can be tweaked in many ways, there are many actions and filters available. Through them, for example, you can customize the automatic renaming to your liking. There is even a little API that you can call. More about this [here](https://meowapps.com/media-file-renamer/issues/).

=== A SIMPLER PLUGIN ===
If you only need an simple field in order to modify the filename, you can also try [Phoenix Media Rename](https://wordpress.org/plugins/phoenix-media-rename). It's simpler, and just does that. Yes, we are friends!

== Installation ==

1. Upload the plugin to your WordPress.
2. Activate the plugin through the 'Plugins' menu.
3. Try it with one file first! :)

== Upgrade Notice ==

1. Replace the plugin with the new one.
2. Nothing else is required! :)

== Screenshots ==

1. Type in the name of your media, that is all.
2. Special screen for bulk actions.
3. This needs to be renamed.
4. The little lock and unlock icons.
5. Options for the automatic renaming (there are more options than just this).

== Changelog ==

= 5.7.7 (2023/12/05) =
* Update: Enhanced UI, clarified options, unified things.
* Add: Bulk Rename using AI Vision.
* Add: AI Vision on Upload.
* Add: Not Renamed filter in Dashboard.
* ⭐️ Don't hesitate to join our [Discord Channel](https://discord.gg/bHDGh38).
* 🌴 Please share some love [here](https://wordpress.org/support/plugin/media-file-renamer/reviews/?rate=5#new-post). Thank you!

= 5.7.4 (2023/11/25) =
* Update: Removed "mfrh_sync_media_meta" filter and added "mfrh_rewrite_title". Enhanced code quality with cleaning and added more logs for sync functions.
* Add: Introduced Table filters (for media with no alt text, no description, no title) and improved display for empty metadata.
* Add: Added "mfrh_clean_upload" filter to customize the clean upload value.
* Fix: Removed "disabled" status on NekoModal to prevent log spamming.
* Update: Removed busyOverlay and upgraded the description field to a textarea for better input handling.
* Fix: Resolved an undefined function call issue in the API.

= 5.7.3 (2023/11/20) =
* Add: Sync only for selected items and mfrh_sync_media_meta filter for post modification during syncing.
* Update: New modal for thumbnails with an "open in new tab" button, and enhanced auto-attach warning message.
* Fix: Adjusted the default behavior of sync functionality.

= 5.7.2 (2023/11/17) =
* Add: AI suggestion now utilizes Vision for enhanced accuracy.
* Add: New "Clean Uploads" feature for efficient media management.
* Add: Magic Wand for Metadata Fields.
* Update: "Auto-Attach Media" feature now allows selection of target media entries.
* Update: Created Meow_MFRH_Engine class, consolidating renaming-related code.
* Update: Conducted various non-code related updates for improved performance.
* Fix: Resolved extension-related errors in thumbnails for better reliability.

= 5.7.1 (2023/10/19) =
* Fix: The action_update_postmeta filter was not working properly.
* Fix: Missing buttons in the modals.

= 5.7.0 (2023/10/10) =
* Update: For better confidentiality, the logs file is now randomly generated.
* Fix: Support of Windows servers.

= 5.6.9 (2023/09/21) =
* Update: The Auto-Attach feature is a bit more robust when using Media Cleaner data.

= 5.6.8 (2023/09/14) =
* Add: Auto-Attach feature now use the data from [Media Cleaner](https://wordpress.org/plugins/media-cleaner/) (if available), which is extremely accurate!
* Fix: Random issues related to metadata not existing.
* Fix: Optimize the way the move feature works.
* Fix: Move didn't handle the WebP and AVIF files properly.
* Fix: Was not possible to completely delete the filename to type it from scratch.
* Fix: When uninstalled, all the data used by the plugin is now removed properly.

= 5.6.6 (2023/07/21) =
* Fix: Avoid warnings when the metadata isn't found.
* Fix: Better handling of metadata synchronization.
* Update: Enhanced the UI of the Renamer Field.

= 5.6.5 (2023/06/21) =
* Fix: Issue when automatic renaming was used with the related auto-lock.
* Update: Latest version of the UI.

= 5.6.4 (2023/06/02) =
* Fix: Removed a few warnings.
* Fix: The paging issue.

= 5.6.3 (2023/05/30) =
* Update: Trying to improve the UI based on your feedback. It might not please everyone, but I am trying to make it better. Please let me know if you have any idea.
* Fix: There were a few warning issues.
* Fix: There were some inconsistencies in the UI.

= 5.6.2 (2023/05/13) =
* Add: Some issues with spacing in some buttons.
* 🎵 I am struggling a bit to make the Dashboard UI nicer, if you have any idea, don't hesitate to let me know via the [Support Forums](https://wordpress.org/support/plugin/media-file-renamer/).

= 5.6.1 (2023/05/06) =
* Add: We can now edit the ALT Text.

= 5.6.0 (2023/05/02) =
* Add: 'Attached To' column is now hideable.
* Add: 'ALT Text' data now available if enabled in the options.
* Update: Minimized the size of the bundle.

= 5.5.9 (2023/03/18) =
* Fix: Various fixes in the UI.
* Update: Latest UI framework.

= 5.5.8 (2023/03/13) =
* Add: AI filename suggestions.
* Update: Added Unlocked instead of Pending (which was slowing-down the process and was not really useful). Let me know if you preferred it the other way.

= 5.5.7 (2023/02/09) =
* Add: New option to disable the Dashboard.
* Note: A bit late on the support, it's unusual, but very busy these days. I am also trying to gather the feedback/issues to fix them all at once in a good way. Thank you for your patience!

= 5.5.5 (2023/02/01) =
* Update: Clean the dashboard a bit, depending on the options.
* Fix: Issue in the Media Library with the Renamer field.
* Fix: The Edit Title modal wasn't working on ENTER.

= 5.5.4 (2023/01/29) =
* Fix: Titting enter in the Edit Title modal wasn't update with the new title.

= 5.5.3 (2023/01/27) =
* Update: Better move features and cleaner UI.

= 5.5.2 (2023/01/06) =
* Update: Slowly (but surely) separating the Rename mode from the Move mode. I will make the UI better and more adapted to the chosen mode. You will find the switch in the Renamer Dashboard.

= 5.5.1 (2022/12/24) =
* Update: Enhanced the hooks (filters).

= 5.5.0 (2022/11/12) =
* Fix: Enhanced the behavior of the UI.
= 5.4.9 (2022/10/30) =
* Fix: The link to the Dashboard was broken.

= 5.4.8 (2022/10/24) =
* Fix: There was an issue with WP-CLI in the latest versions.

= 5.4.7 (2022/10/12) =
* Add: Consider WebP as an "Image" (which it is 😏).
* Fix: The 'Featured Only' and 'Images Only' were not working perfectly.
* Update: Optimized the way options are updated and retrieved.
* Update: Some refactoring to simplify the code.

= 5.4.5 (2022/09/27) =
* Add: Auto-retry on failure, up to 10 times.
* Fix: Typos.

= 5.4.3 (2022/08/11) =
* Add: Handle errors gracefully (with retry, skip or cancel).

= 5.4.1 (2022/08/03) =
* Fix: Tiny UI bug in Safari.

= 5.4.0 (2022/07/05) =
* Add: Support for Elementor (update the metadata and CSS).
* Update: Use the default WordPress font (to avoid loading data from Google Fonts) and a few UI enhancements.

= 5.3.9 (2022/06/16) =
* Fix: The WebP files weren't not renamed perfectly.

= 5.3.8 (2022/03/29) =
* Fix: Support for WebP.
* Fix: Anonymize (MD5) on upload now works fine.
* Fix: Decode HTML entities (in the meta, title) when renaming is based on it.
* Update: I am trying to enhance the UI (the rename field and the actions) depending on the size of the browser. I'll try to make this better and better, but don't hesitate to give me some feedback.

= 5.3.6 (2022/02/01) =
* Update: Fresh build and support for WordPress 5.9.

= 5.3.5 (2021/11/10) =
* Fix: Renaming of WebP uploaded directly to WordPress.
* Add: The possibility of locking files automatically after a manual rename (which was always the case previously), and/or after a automatic rename (that was not possible previously). With this last option, users having trouble to "Rename All" will be given the choice to do it on any kind of server. You will find those options in the Advanced tab.
* Add: "Delay" option, to give a break and a reset to the server between asynchronous requests! Default to 100ms. That will avoid the server to time out, or to slow down on purpose.

= 5.3.3 (2021/11/09) =
* Fix: Avoid renaming when the URLs (before/after) are empty.
* Add: New option to update URLs in the excerpts (no need to use it for most users).
* Update: Avoid double call to the mfrh_url_renamed (seemed to be completely useless).
* Update: Added a new 'size' argument to the mfrh_url_renamed action.
* Update: Optimized queries.
* Add: We can change the page (in the dashboard) by typing it.

= 5.3.2 (2021/10/16) =
* Add: AVIF support.
* Fix: Avoid the double renaming when different registered sizes actually use the same file.

= 5.3.0 (2021/10/09) =
* Add: Better Force Rename.
* Add: Featured Images Only option.
* Fix: Auto-attach feature wasn't working properly with Featured Image when attached to Product.

= 5.2.9 (2021/09/23) =
* Add: Manual Sanitize Option. If the option is checked, the rename feature uses the new_filename function. If not, use the filename user input as it is.

= 5.2.8 (2021/09/07) =
* Add: Option to clean the plugin data on uninstall.
* Add: Manual Rename now goes through the cleaning flow to make sure everything is clean and nice.

= 5.2.7 (2021/09/03) =
* Fix: Security update: access controls to the REST API and the options enforced.
* Updated: Dependencies update.

= 5.2.5 (2021/08/25) =
* Fix: Search feature was not always working well.
* Update: Better technical architecture.

= 5.2.4 (2021/06/13) =
* Add: Remember the number of entries per page (dashboard).
* Fix: Limit the length of the manual filename.

= 5.2.3 (2021/05/29) =
* Fix: The 'Move' feature now also works with the original image (in case it has been scaled by WP).

= 5.2.2 (2021/05/18) =
* Fix: Better Windows support.

= 5.2.0 (2021/05/15) =
* Add: Move button (this was mainly added for tests, so it's a beta feature, it will be perfected over time).
* Add: Images Only option.
* Fix: Vulnerability report, a standard user access could potentially modify a media title with custom requests.

= 5.1.9 (2021/04/09) =
* Fix: The Synchronize Alt option wasn't working logically.

= 5.1.8 (2021/03/04) =
* Add: Search.
* Add: Quick rename the title from the dashboard.

= 5.1.7 (2021/02/21) =
* Fix: The Synchronize Media Title option wasn't working logically.

= 5.1.6 (2021/02/12) =
* Fix: References for moved files were not updated.
* Add: Sanitize filename after they have been through the mfrh_new_filename filter.

= 5.1.3 (2021/02/06)  =
* Add: Greek support.
* Fix: Better sensitive file check.
* Fix: Manual rename with WP CLI.

= 5.1.2 (2021/01/10) =
* Add: Auto attach feature.
* Add: Added Locked in the filters.
* Update: Icons position.

= 5.1.1 (2021/01/05) =
* Fix: Issue with roles overriding and WP-CLI.
* Fix: Issue with REST in the Common Dashboard.

= 5.1.0 (2021/01/01) =
* Add: Support overriding roles.
* Fix: The layout of the dashboard was broken by WPBakery.
