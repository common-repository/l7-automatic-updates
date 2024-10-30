<?php
/*
 * Plugin Name: L7 Automatic Updates
 * Plugin URI: http://layer7web.com/projects/l7-automatic-updates
 * Description: Options for setting Wordpress's Automatic updates.
 * Author: Jeffrey S. Mattson
 * Version: 2.0.0
 * Author URI: http://layer7web.com
 * License: GPL2+
 */

/*
Copyright 2016 Jeffrey S. Mattson (email : plugins@layer7web.com)
This program is free software; you can redistribute it and/ or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.

See the GNU General Public License for more details. You should
have received a copy of the GNU General Public License along with this
program; if not, write to the Free Software Foundation, Inc.,
51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
*/

if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

if ( ! class_exists( 'L7wAutomaticUpdates' ) ){
	class L7wAutomaticUpdates{

		/**
		 * Init action hooks to create menu and register settings
		 */
		function __construct(){
			if ( is_admin() ){
				add_action( 'admin_menu', array( $this, 'l7wau_add_settings_menu' ) );
				add_action( 'admin_init', array( $this, 'l7wau_register_settings' ) );
			}

			// Action hook for the update filters,
			// callback also contains the email filter.
			add_action( 'init', array( $this, 'l7wau_set_auto_updates' ) );

			// Add action for admin enqueue scripts
			add_action( 'admin_enqueue_scripts', array( $this, 'jsm_custom_login_options_enqueue_scripts' ), 1 );

			// Error notices
			add_action( 'admin_notices', array( $this, 'l7wau_error_notice' ) );

			// Filter the background update notification email.
			add_filter( 'auto_core_update_email', array( $this, 'l7w_filter_auto_core_update_email' ), 100);
		}

		/**
		 * Error notice for outdated wordpress install.
		 */
		function l7wau_error_notice() {
			if ( version_compare( get_bloginfo( 'version' ), '3.8.2' ) == -1 ){
				$class = 'error';
				$message = 'The L7 Automatic Updates plugin will not work with this version on Wordpress.<br />Your current version of Wordpress is: ' . get_bloginfo( 'version' ) . '.  This plugin requires version 3.8.2 and above.<br />Please update your version before using this plugin.';
				echo '<div class=\"' . esc_attr( $class ) . '\"> <p>' . esc_html( $message ) . '</p></div>';
			}
		}

		/**
		 * Enqueue the checkbox bootstrap js only on the settings page.
		 */
		function jsm_custom_login_options_enqueue_scripts() {
			$url = explode( '/', plugin_basename( __FILE__ ) );
			$plugin_name = $url[0];
			$settings_page_url = 'settings_page_' . $plugin_name;
			wp_register_script( 'l7wau-bootstrap-checkbox', plugins_url( 'assets/js/bootstrap-checkbox.min.js', __FILE__ ), array('jquery') );
			wp_register_script( 'l7wau-main-js', plugins_url( 'assets/js/main.js', __FILE__ ), array('jquery') );
			wp_register_style( 'l7wau-bootstrap-css', plugins_url( 'assets/css/bootstrap.css', __FILE__ ) );
			if ( $settings_page_url == get_current_screen()->id ) {
				wp_enqueue_script( 'l7wau-bootstrap-checkbox' );
				wp_enqueue_script( 'l7wau-main-js' );
				wp_enqueue_style( 'l7wau-bootstrap-css' );
			}
		}

		/**
		 * The add settings menu function
		 */
		function l7wau_add_settings_menu(){
			add_options_page( 'L7 Automatic Updates', 'L7 Auto Updates', 'manage_options', 'l7-automatic-updates',  array( $this, 'l7wau_settings' ) );
		}

		/**
		 * The settings page display.
		 */
		function l7wau_settings(){
			$content = '';
			ob_start();
			?>
			<div class="wrap">
				<div class="row">
					<div class="col-md-8">
						<form action="options.php" method="post" >
						<?php
							settings_fields( 'l7wau_settings_group' );
							do_settings_sections( 'l7-automatic-updates' );
							echo '<hr>';
							echo '<div id="l7wau-hide-js">';
							do_settings_sections( 'l7-automatic-plugin-updates' );
							echo '</div>';
						?>
							<input name="Submit" class="button button-primary" type="submit" value="Save Changes" />
						</form>
					</div>
					<div class="col-md-4">
					</div>
				</div>
				<div class="row">
				 	<div class="col-md-12" style="text-align:center;">
					 Comments? - <a href="mail:plugins@layer7web.com">Layer 7 Web</a>
					</div>
				</div>
			</div>
			<?php 
			$content .= ob_get_contents();
			ob_end_clean();
			echo $content;
		}

		/**
		 * Register settings
		 */
		function l7wau_register_settings(){
			register_setting( 'l7wau_settings_group', 'l7wau_settings_group', array( $this, 'l7wau_setting_sanitiz') );

			/**
			 * Main Settings at the Top
			 */
			add_settings_section( 'l7wau_top_section', 'L7 Automatic Updates', array( $this, 'l7wau_wordpress_section' ), 'l7-automatic-updates' );
			add_settings_field( 'l7wau_major_releases', 'Major Releases', array( $this, 'l7wau_major_releases_field' ), 'l7-automatic-updates', 'l7wau_top_section', array( 'label_for' => 'l7wau_major_releases' ) );
			add_settings_field( 'l7wau_minor_releases', 'Minor Releases', array( $this, 'l7wau_minor_releases_field' ), 'l7-automatic-updates', 'l7wau_top_section', array( 'label_for' => 'l7wau_minor_releases' ) );
			add_settings_field( 'l7wau_themes', 'Themes', array( $this, 'l7wau_themes_field' ), 'l7-automatic-updates', 'l7wau_top_section', array( 'label_for' => 'l7wau_themes' ) );
			add_settings_field( 'l7wau_plugins', 'All Plugins', array( $this, 'l7wau_plugins_field' ), 'l7-automatic-updates', 'l7wau_top_section', array( 'label_for' => 'l7wau_plugins' ) );
			add_settings_field( 'l7wau_email', 'Send Email When Update is Completed', array( $this, 'l7wau_email' ), 'l7-automatic-updates', 'l7wau_top_section', array( 'label_for' => 'l7wau_email_field' ) );
			add_settings_field( 'l7wau_version', 'Update When Version Control is Detected?', array( $this, 'l7wau_version' ), 'l7-automatic-updates', 'l7wau_top_section', array( 'label_for' => 'l7wau_version' ) );

			// Email setting
			add_settings_field( 'l7wau_notification_email', 'Send Notification Email To', array( $this, 'l7wau_notification_email' ), 'l7-automatic-updates', 'l7wau_top_section', array( 'label_for' => 'l7wau_version' ) );

			/**
			 * Add the plugin checkboxes 
			 */
			add_settings_section( 'l7wau_plugins', 'Individual Plugins', array( $this, 'l7wau_wordpress_plugin_section' ), 'l7-automatic-plugin-updates' );

			$array_slugs = $this->l7wau_get_slugs();
			$this->lw7au_create_checkbox_input( $array_slugs );
		}

		/**
		 * Plugins Top Description
		 */
		function l7wau_wordpress_section(){
			echo 'Settings';
		}

		/**
		 * Plugins Description
		 */
		function l7wau_wordpress_plugin_section(){
			echo '<p>Choose the Plugins to Automaticaly Update<br /><em>Switch All Plugins to "No" to enable.</p>';
		}

		/**
		 * Major Releases Field
		 */
		function l7wau_major_releases_field(){
			$text_string = $this->l7wau_default_settings( 'major_releases' );
			'1' == $text_string ? $checked = 'checked': $checked = '';
			$output = '<input type="checkbox" value="1" id="l7wau_major_releases" name="l7wau_settings_group[major_releases]"' . $checked . ' >';
			echo $output;
		}

		/**
		 * Minor Releases Field
		 */
		function l7wau_minor_releases_field(){
			$text_string = $this->l7wau_default_settings( 'minor_releases' );
			'1' == $text_string ? $checked = 'checked': $checked = '';
			$output = '<input type="checkbox" value="1" id="l7wau_minor_releases" name="l7wau_settings_group[minor_releases]"' . $checked . ' >';
			echo $output;
		}

		/**
		 * Themes Field
		 */
		function l7wau_themes_field(){
			$text_string = $this->l7wau_default_settings( 'themes' );
			'1' == $text_string ? $checked = 'checked': $checked = '';
			$output = '<input type="checkbox" value="1" id="l7wau_themes" name="l7wau_settings_group[themes]"' . $checked . ' >';
			echo $output;
		}

		/**
		 * plugins Field
		 */
		function l7wau_plugins_field(){
			$text_string = $this->l7wau_default_settings( 'plugins' );
			'1' == $text_string ? $checked = 'checked': $checked = '';
			$output = '<input type="checkbox" value="1" id="l7wau_plugins" name="l7wau_settings_group[plugins]"' . $checked . ' >';
			echo $output;
		}

		/**
		 * Send email notification
		 */
		function l7wau_email(){
			$text_string = $this->l7wau_default_settings( 'email' );
			'1' == $text_string ? $checked = 'checked': $checked = '';
			$output = '<input type="checkbox" value="1" id="l7wau_email" name="l7wau_settings_group[email]"' . $checked . ' >';
			echo $output;
		}

		/**
		 * Is there Version control? Don't update/do update
		 */
		function l7wau_version(){
			$text_string = $this->l7wau_default_settings( 'version' );
			'1' == $text_string ? $checked = 'checked': $checked = '';
			$output = '<input type="checkbox" value="1" id="l7wau_version" name="l7wau_settings_group[version]"' . $checked . ' >';
			echo $output;
		}

		/**
		 * Enter email address field
		 */
		function l7wau_notification_email(){
			$text_string = $this->l7wau_default_settings( 'l7wau_notification_email' );
			$content = "<div class='input-group'><span class='input-group-addon'><i></i></span><input id='l7wau_notification_email' name='l7wau_settings_group[l7wau_notification_email]' type='text' value='" . esc_attr( $text_string ) . "' class='form-control' style='width:40%;' /></div><i>Leave blank to send to admin's email. Set in settings -> general.</i>";
			echo $content;
		}

		/**
		 * Takes the option setting key and returns the value if it is set. If it
		 * is not set then it returns the default value. If the default key is not
		 * in the array it returns false.
		 * 
		 * @param  string $key the key value of the setting
		 * @return string/boolean     
		 */
		function l7wau_default_settings( $key ) {
			$options = get_option( 'l7wau_settings_group' );

			// Array of the plugin slugs installed
			$array_slugs = $this->l7wau_get_slugs();
			$num_elements = count( $array_slugs );

			// Create empty zero array
			$zero_array = array();
			for ( $x = 0; $x < $num_elements; $x++ ) {
				$zero_array[] = '';
			}

			// Merge the two arrays so they are name => '0'
			$defaults_array = array_combine( $array_slugs, $zero_array );
			$defaults = array(
					'major_releases' 	=> '',
					'minor_releases' 	=> '',
					'themes' 			=> '',
					'email' 			=> '',
					'plugins'			=> '',
					'version'			=> '',
			);

			// Merge new array with the default so we have our 0 default values for all plugins
			$defaults = array_merge( $defaults, $defaults_array );

			$options_defaults = wp_parse_args( $options, $defaults );

			if ( isset( $options_defaults[$key] ) ) {
				return $options_defaults[$key];
			}
			else {
				return false;
			}
		}

		/**
		 * Sanitize
		 */
		function l7wau_setting_sanitiz( $input ){
			return $input;
		}

		/**
		 * Auto update filters
		 */
		function l7wau_set_auto_updates(){

			// Filter the background update notification email.
			add_filter( 'auto_core_update_email', array( $this, 'l7w_filter_auto_core_update_email' ), 100);

			/**
			 * Major Core Updates
			 */
			if ( $this->l7wau_default_settings( 'major_releases' ) ){
				add_filter( 'allow_major_auto_core_updates', '__return_true', 1 );
			}
			else {
				add_filter( 'allow_major_auto_core_updates', '__return_false', 1 );
			}

			/**
			 * Minor Core Updates
			 */
			if ( $this->l7wau_default_settings( 'minor_releases' ) ){
				add_filter( 'allow_minor_auto_core_updates', '__return_true', 1 );
			}
			else {
				add_filter( 'allow_minor_auto_core_updates', '__return_false', 1 );
			}

			/**
			 * Themes Updates
			 */
			if ( $this->l7wau_default_settings( 'themes' ) ){
				add_filter( 'auto_update_theme', '__return_true', 1 );
			}
			else {
				add_filter( 'auto_update_theme', '__return_false', 1 );
			}

			/**
			 * All plugins auto update set or select plugins to update.
			 */
			if ( $this->l7wau_default_settings( 'plugins' ) ){
				add_filter( 'auto_update_plugin', '__return_true', 1 );
			}
			else {

				/**
				 * Filter the auto updates to only the ones we want.
				 */
				add_filter( 'auto_update_plugin', array( $this, 'l7wau_update_specific_plugins' ), 10, 2 );
			}

			/**
			 * Send email when an update takes place
			 */
			if ( $this->l7wau_default_settings( 'email' ) ){
				add_filter( 'automatic_updates_send_debug_email', '__return_true', 1 );
			}
			else {
				add_filter( 'automatic_updates_send_debug_email', '__return_false', 1 );
			}

			/**
			 * Version Control Test
			 */
			if ( $this->l7wau_default_settings( 'version' ) ){
				add_filter( 'automatic_updates_is_vcs_checkout', '__return_false', 1 );
			}
			else {
				add_filter( 'automatic_updates_is_vcs_checkout', '__return_true', 1 );
			}
		}

		/**
		 * Get the slug of installed plugins.
		 *
		 * @return  array returns an array of slugs.
		 */
		function l7wau_get_slugs(){
			$plugin_array = array();
			if ( function_exists( 'get_plugins' ) ){
				$plugin_array = get_plugins();
			}

			// Check if empty
			if ( empty( $plugin_array ) ) {
				return $plugin_array;
			}

			// Declare Array and loop through it.
			$slugs = array();
			foreach ( $plugin_array as $plugin_slug => $values ){
				$slugs[] = strtolower( basename( $plugin_slug, '.php' ) );
			}
			return $slugs;
		}

		/**
		 * Create a checkbox input item with string value names.
		 */
		function lw7au_create_checkbox_input( $array_of_slugs ){
			ob_start();
			foreach ( $array_of_slugs as $slug ){

				/**
				 * We're using this variable to hold an anonymous function.
				 * "use" is basically how we pass variables to an anonymous function.
				 */
				${$slug} = function() use ( $slug ) {
					$options_value = L7wAutomaticUpdates::input_setup( $slug );
					echo "<input type='checkbox' id='". esc_attr( $slug ) . "' name='l7wau_settings_group[" . esc_attr( $slug ) . "]' value='1' " . checked( 1, isset( $options_value ) ? $options_value : 0, false ) .'/>';
				};

				/*
				 * We're using the dynamically generated variable to reference the anonymous function above.
				 */
				add_settings_field( $slug, $slug, ${$slug}, 'l7-automatic-plugin-updates' , 'l7wau_plugins', array( 'label_for' => $slug ) );
			}
			$content = ob_get_contents();
			ob_end_clean();
			return $content;
		}

		/**
		 * Used to set the 'checked' value to the checkboxes.
		 * 
		 * @param  string $name 
		 */
		function input_setup( $name ){
			$options = get_option( 'l7wau_settings_group' );
			$value = ( isset( $options[$name] ) ? $options[$name] : '');
			return $value;
		}

		/**
		 * Update specific plugins. Got this from Wordpress codex.
		 */
		function l7wau_update_specific_plugins( $update, $item ) {

			/**
		     * Get the array of slugs to set to auto-update
		     */
			$plugins = array();
			$plugins = $this->l7wau_get_array_plugins_to_update();
			if ( in_array( $item->slug, $plugins ) ) {
				return true; // Always update plugins in this array
			} else {
				return $update; // Else, use the normal API response to decide whether to update or not
			}
		}

		/**
		 * Returns an array with the plugin names to update.
		 * 
		 * @return array names of plugins to update.
		 */
		public function l7wau_get_array_plugins_to_update(){
			$options = get_option( 'l7wau_settings_group' );
			$return_array = array();

			// Array of plugin slugs.
			$plugin_slugs = array();
			$plugin_slugs = $this->l7wau_get_slugs();

			/**
			 * Check the slugs against the settings. 
			 * Put the checked settings into the array.
			 */
			foreach ( $plugin_slugs as $slug ){
				$temp = $this->l7wau_default_settings( $slug );
				if ( '1' === $temp ){
					$return_array[] = $slug;
					$temp = 0;
				}
			}
			return $return_array;
		}

		/**
		 * Filter the background update notification email
		 *
		 * @since 1.0.2
		 *
		 * @param array $email Array of email arguments that will be passed to wp_mail().
		 * @return array Modified array containing the new email address.
		 */
		public function l7w_filter_auto_core_update_email( $email ) {

			// Get plugin settings.
			$options = get_option( 'l7wau_settings_group' );

			// If an email address has been set, override the WordPress default.
			if ( isset( $options['l7wau_notification_email'] ) && ! empty( $options['l7wau_notification_email'] ) ) {
				$email['to'] = $options['l7wau_notification_email'];
			}

			return $email;
		}
	}
}

global $lw7au_plugin_object;
if ( ! isset( $lw7au_plugin_object ) && ( class_exists( 'L7wAutomaticUpdates' ) ) ){
	$lw7au_plugin_object = new L7wAutomaticUpdates;
}