<?php
/**
 * ------------------------------------------------------------------------------
 * Plugin Name: Loop Injection
 * Description: Inject data into loop at top, middle and bottom; perfect for adverts.
 * Version: 1.2.5
 * Author: azurecurve
 * Author URI: https://development.azurecurve.co.uk/classicpress-plugins/
 * Plugin URI: https://development.azurecurve.co.uk/classicpress-plugins/loop-injection/
 * Text Domain: loop-injection
 * Domain Path: /languages
 * ------------------------------------------------------------------------------
 * This is free software released under the terms of the General Public License,
 * version 2, or later. It is distributed WITHOUT ANY WARRANTY; without even the
 * implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. Full
 * text of the license is available at https://www.gnu.org/licenses/gpl-2.0.html.
 * ------------------------------------------------------------------------------
 */

// Prevent direct access.
if (!defined('ABSPATH')){
	die();
}

// include plugin menu
require_once(dirname(__FILE__).'/pluginmenu/menu.php');
add_action('admin_init', 'azrcrv_create_plugin_menu_li');

// include update client
require_once(dirname(__FILE__).'/libraries/updateclient/UpdateClient.class.php');

/**
 * Setup registration activation hook, actions, filters and shortcodes.
 *
 * @since 1.0.0
 *
 */
// add actions
add_action('admin_menu', 'azrcrv_li_create_admin_menu');
add_action('admin_post_azrcrv_li_save_options', 'azrcrv_li_save_options');
add_action('network_admin_menu', 'azrcrv_li_create_network_admin_menu');
add_action('network_admin_edit_azrcrv_li_save_network_options', 'azrcrv_li_save_network_options');
add_action( 'the_post', 'azrcrv_li_inject_adds_in_loop' );
add_action('plugins_loaded', 'azrcrv_li_load_languages');
add_action( 'loop_start', 'azrcrv_li_inject_adds_before_loop' );
add_action( 'loop_end', 'azrcrv_li_inject_adds_after_loop' );
add_action('wp_enqueue_scripts', 'azrcrv_li_load_css');

// add filters
add_filter('plugin_action_links', 'azrcrv_li_add_plugin_action_link', 10, 2);
add_filter('codepotent_update_manager_image_path', 'azrcrv_li_custom_image_path');
add_filter('codepotent_update_manager_image_url', 'azrcrv_li_custom_image_url');

/**
 * Load language files.
 *
 * @since 1.0.0
 *
 */
function azrcrv_li_load_languages() {
    $plugin_rel_path = basename(dirname(__FILE__)).'/languages';
    load_plugin_textdomain('loop-injection', false, $plugin_rel_path);
}

/**
 * Load CSS.
 *
 * @since 1.0.0
 *
 */
function azrcrv_li_load_css(){
	wp_enqueue_style('azrcrv-li', plugins_url('assets/css/style.css', __FILE__), '', '1.0.0');
}

/**
 * Custom plugin image path.
 *
 * @since 1.2.0
 *
 */
function azrcrv_li_custom_image_path($path){
    if (strpos($path, 'azrcrv-loop-injection') !== false){
        $path = plugin_dir_path(__FILE__).'assets/pluginimages';
    }
    return $path;
}

/**
 * Custom plugin image url.
 *
 * @since 1.2.0
 *
 */
function azrcrv_li_custom_image_url($url){
    if (strpos($url, 'azrcrv-loop-injection') !== false){
        $url = plugin_dir_url(__FILE__).'assets/pluginimages';
    }
    return $url;
}

/**
 * Get options including defaults.
 *
 * @since 1.2.0
 *
 */
function azrcrv_li_get_option($option_name){
 
	$defaults = array(
						'loop_before_active' => 1,
						'loop_within_active' => 1,
						'loop_after_active' => 1,
						'loop_within_position' => 5,
						'loop_before_advert' => '',
						'loop_within_advert' => '',
						'loop_after_advert' => '',
					);

	$options = get_option($option_name, $defaults);

	$options = wp_parse_args($options, $defaults);

	return $options;

}

/**
 * Add Loop Injection action link on plugins page.
 *
 * @since 1.0.0
 *
 */
function azrcrv_li_add_plugin_action_link($links, $file){
	static $this_plugin;

	if (!$this_plugin){
		$this_plugin = plugin_basename(__FILE__);
	}

	if ($file == $this_plugin){
		$settings_link = '<a href="'.admin_url('admin.php?page=azrcrv-li').'"><img src="'.plugins_url('/pluginmenu/images/logo.svg', __FILE__).'" style="padding-top: 2px; margin-right: -5px; height: 16px; width: 16px;" alt="azurecurve" />'.esc_html__('Settings' ,'loop-injection').'</a>';
		array_unshift($links, $settings_link);
	}

	return $links;
}

/**
 * Add to menu.
 *
 * @since 1.0.0
 *
 */
function azrcrv_li_create_admin_menu(){
	//global $admin_page_hooks;
	
	add_submenu_page("azrcrv-plugin-menu"
						,esc_html__("Loop Injection Settings", 'loop-injection')
						,esc_html__("Loop Injection", 'loop-injection')
						,'manage_options'
						,'azrcrv-li'
						,'azrcrv_li_display_options');
}

/**
 * Display Settings page.
 *
 * @since 1.0.0
 *
 */
function azrcrv_li_display_options(){
	if (!current_user_can('manage_options')){
        wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'loop-injection'));
    }
	
	// Retrieve plugin configuration options from database
	$options = azrcrv_li_get_option('azrcrv-li');
	?>
	<div id="azrcrv-li-general" class="wrap">
		<fieldset>
			<h1>
				<?php
					echo '<a href="https://development.azurecurve.co.uk/classicpress-plugins/"><img src="'.plugins_url('/pluginmenu/images/logo.svg', __FILE__).'" style="padding-right: 6px; height: 20px; width: 20px;" alt="azurecurve" /></a>';
					esc_html_e(get_admin_page_title());
				?>
			</h1>
			
			<?php if(isset($_GET['settings-updated'])){ ?>
				<div class="notice notice-success is-dismissible">
					<p><strong><?php esc_html_e('Settings have been saved.', 'loop-injection'); ?></strong></p>
				</div>
			<?php } ?>
			
			<form method="post" action="admin-post.php">
			
				<input type="hidden" name="action" value="azrcrv_li_save_options" />
				<input name="page_options" type="hidden" value="use_network_settings,loop_before_active,loop_before_advert,loop_within_active,loop_within_advert,loop_within_position,loop_after_active,loop_after_advert" />
				
				<!-- Adding security through hidden referrer field -->
				<?php wp_nonce_field('azrcrv-li', 'azrcrv-li-nonce'); ?>
				<table class="form-table">
				
					<tr><th scope="row" colspan=2>
						<?php esc_html_e('Loop Injection supports injection of adverts before, within and after the loop.', 'loop-injection'); ?>
					</th></tr>
					
					<?php if (function_exists('is_multisite') && is_multisite()){ ?>
						<tr><th scope="row"><?php esc_html("Use Network Settings", "tag-cloud"); ?></th><td>
							<fieldset><legend class="screen-reader-text"><span><?php esc_html_e('Use Network Settings', 'tag-cloud'); ?></span></legend>
							<label for="use_network_settings"><input name="use_network_settings" type="checkbox" id="use_network_settings" value="1" <?php checked('1', $options['use_network_settings']); ?> /><?php esc_html_e('Use Network Settings? The settings below will be ignored', 'tag-cloud'); ?></label>
							</fieldset>
						</td></tr>
					<?php } ?>
					
					<tr><th scope="row"><?php esc_html_e('Activate Before Loop', 'loop-injection'); ?></th><td>
						<fieldset><legend class="screen-reader-text"><span>Activate Within Loop</span></legend>
						<label for="loop_before_active"><input name="loop_before_active" type="checkbox" id="loop_before_active" value="1" <?php checked('1', $options['loop_before_active']); ?> /><?php esc_html_e('Activate advert injection before loop.', 'loop-injection'); ?></label>
						</fieldset>
					</td></tr>
					
					<tr><th scope="row"><?php esc_html_e('Advert Code Before loop', 'loop-injection'); ?></th><td>
						<textarea name="loop_before_advert" rows="10" cols="100" id="loop_before_advert" class="large-text code"><?php echo esc_textarea(stripslashes($options['loop_before_advert'])) ?></textarea>
						<p class="description"><?php esc_html_e('Advert code to inject before loop.', 'display-after-post-content'); ?></em>
						</p>
					</td></tr>
					
					<tr><th scope="row"><?php esc_html_e('Activate Within Loop', 'loop-injection'); ?></th><td>
						<fieldset><legend class="screen-reader-text"><span>Activate Within Loop</span></legend>
						<label for="loop_within_active"><input name="loop_within_active" type="checkbox" id="loop_within_active" value="1" <?php checked('1', $options['loop_within_active']); ?> /><?php esc_html_e('Activate advert injection within loop.', 'loop-injection'); ?></label>
						</fieldset>
					</td></tr>
					
					<tr><th scope="row"><label for="loop_within_position"><?php esc_html_e('Position Within Loop', 'loop-injection'); ?></label></th><td>
						<input type="text" name="loop_within_position" value="<?php echo esc_html(stripslashes($options['loop_within_position'])); ?>" class="small-text" />
						<p class="description"><?php esc_html_e('How many posts should be displayed before advert injection.', 'loop-injection'); ?></p>
					</td></tr>
					
					<tr><th scope="row"><?php esc_html_e('Advert Code Within loop', 'loop-injection'); ?></th><td>
						<textarea name="loop_within_advert" rows="10" cols="100" id="loop_within_advert" class="large-text code"><?php echo esc_textarea(stripslashes($options['loop_within_advert'])) ?></textarea>
						<p class="description"><?php esc_html_e('Advert code to inject within loop.', 'display-after-post-content'); ?></em>
						</p>
					</td></tr>
					
					<tr><th scope="row"><?php esc_html_e('Activate After Loop', 'loop-injection'); ?></th><td>
						<fieldset><legend class="screen-reader-text"><span>Activate After Loop</span></legend>
						<label for="loop_after_active"><input name="loop_after_active" type="checkbox" id="loop_after_active" value="1" <?php checked('1', $options['loop_after_active']); ?> /><?php esc_html_e('Activate advert injection after loop.', 'loop-injection'); ?></label>
						</fieldset>
					</td></tr>
					
					<tr><th scope="row"><?php esc_html_e('Advert Code After loop', 'loop-injection'); ?></th><td>
						<textarea name="loop_after_advert" rows="10" cols="100" id="loop_after_advert" class="large-text code"><?php echo esc_textarea(stripslashes($options['loop_after_advert'])) ?></textarea>
						<p class="description"><?php esc_html_e('Advert code to inject after loop.', 'display-after-post-content'); ?></em>
						</p>
					</td></tr>
				
				</table>
				<input type="submit" value="Save Changes" class="button-primary"/>
			</form>
		</fieldset>
	</div>
	<?php
}

/**
 * Save settings.
 *
 * @since 1.0.0
 *
 */
function azrcrv_li_save_options(){
	// Check that user has proper security level
	if (!current_user_can('manage_options')){
		wp_die(esc_html__('You do not have permissions to perform this action', 'loop-injection'));
	}
	// Check that nonce field created in configuration form is present
	if (! empty($_POST) && check_admin_referer('azrcrv-li', 'azrcrv-li-nonce')){
	
		// Retrieve original plugin options array
		$options = get_option('azrcrv-li');
		
		$allowed = azrcrv_li_get_allowed_tags();
		
		$option_name = 'use_network';
		if (isset($_POST[$option_name])){
			$options[$option_name] = 1;
		}else{
			$options[$option_name] = 0;
		}
		
		$option_name = 'loop_before_active';
		if (isset($_POST[$option_name])){
			$options[$option_name] = 1;
		}else{
			$options[$option_name] = 0;
		}
	
		$option_name = 'loop_before_advert';
		if (isset($_POST[$option_name])){
			$options[$option_name] = wp_kses(stripslashes($_POST[$option_name]), $allowed);
		}
		
		$option_name = 'loop_within_active';
		if (isset($_POST[$option_name])){
			$options[$option_name] = 1;
		}else{
			$options[$option_name] = 0;
		}
		
		$option_name = 'loop_within_position';
		if (isset($_POST[$option_name])){
			$options[$option_name] = sanitize_text_field(intval($_POST[$option_name]));
		}
	
		$option_name = 'loop_within_advert';
		if (isset($_POST[$option_name])){
			$options[$option_name] = wp_kses(stripslashes($_POST[$option_name]), $allowed);
		}
		
		$option_name = 'loop_after_active';
		if (isset($_POST[$option_name])){
			$options[$option_name] = 1;
		}else{
			$options[$option_name] = 0;
		}
	
		$option_name = 'loop_after_advert';
		if (isset($_POST[$option_name])){
			$options[$option_name] = wp_kses(stripslashes($_POST[$option_name]), $allowed);
		}
		
		// Store updated options array to database
		update_option('azrcrv-li', $options);
		
		// Redirect the page to the configuration form that was processed
		wp_redirect(add_query_arg('page', 'azrcrv-li&settings-updated', admin_url('admin.php')));
		exit;
	}
}

/**
 * Get allowed tags.
 *
 * @since 1.0.0
 *
 */
function azrcrv_li_get_allowed_tags() {
	
    $allowed_tags = wp_kses_allowed_html();
	
    $allowed_tags['table']['class'] = 1;
    $allowed_tags['table']['style'] = 1;
    $allowed_tags['tr']['class'] = 1;
    $allowed_tags['tr']['style'] = 1;
    $allowed_tags['th']['class'] = 1;
    $allowed_tags['th']['style'] = 1;
    $allowed_tags['td']['class'] = 1;
    $allowed_tags['td']['style'] = 1;
    $allowed_tags['p']['class'] = 1;
    $allowed_tags['p']['style'] = 1;
    $allowed_tags['ul']['class'] = 1;
    $allowed_tags['ul']['style'] = 1;
    $allowed_tags['ol']['class'] = 1;
    $allowed_tags['ol']['style'] = 1;
    $allowed_tags['li']['class'] = 1;
    $allowed_tags['li']['style'] = 1;
    $allowed_tags['div']['class'] = 1;
    $allowed_tags['div']['style'] = 1;
    $allowed_tags['div']['id'] = 1;
    $allowed_tags['div']['name'] = 1;
    $allowed_tags['span']['class'] = 1;
    $allowed_tags['span']['style'] = 1;
    $allowed_tags['span']['id'] = 1;
    $allowed_tags['span']['name'] = 1;
    $allowed_tags['script']['asynv'] = 1;
    $allowed_tags['script']['src'] = 1;
    $allowed_tags['ins']['class'] = 1;
    $allowed_tags['ins']['style'] = 1;
    $allowed_tags['ins']['data-ad-client'] = 1;
    $allowed_tags['ins']['data-ad-slot'] = 1;
    $allowed_tags['ins']['data-ad-format'] = 1;
	
    return $allowed_tags;
}

/**
 * Add to Network menu.
 *
 * @since 1.0.0
 *
 */
function azrcrv_li_create_network_admin_menu(){
	if (function_exists('is_multisite') && is_multisite()){
		add_submenu_page(
			'settings.php'
			,esc_html__("Loop Injection Settings", 'loop-injection')
			,esc_html__("Loop Injection", 'loop-injection')
			,'manage_network_options'
			,'azrcrv-li'
			,'azrcrv_li_network_settings'
			);
	}
}

/**
 * Display network settings.
 *
 * @since 1.0.0
 *
 */
function azrcrv_li_network_settings(){
	// Check that user has proper security level
	if(!current_user_can('manage_network_options')){
		wp_die(esc_html__('You do not have permissions to perform this action', 'loop-injection'));
	}

	?>
	<div id="azrcrv-li-general" class="wrap">
		<fieldset>
			<h1>
				<?php
					echo '<a href="https://development.azurecurve.co.uk/classicpress-plugins/"><img src="'.plugins_url('/pluginmenu/images/logo.svg', __FILE__).'" style="padding-right: 6px; height: 20px; width: 20px;" alt="azurecurve" /></a>';
					esc_html_e(get_admin_page_title());
				?>
			</h1>
				
			<table class="form-table">
			
				<tr><th scope="row" colspan=2>
					<?php esc_html_e('Loop Injection supports injection of adverts before, within and after the loop.', 'loop-injection'); ?>
				</th></tr>
			
				<tr><th scope="row" colspan=2>
					<?php esc_html_e('Settings are configured at the site level.'); ?>
				</th></tr>
			
			</table>
		</fieldset>
	</div>
	<?php
}

/**
 * Check if function active (included due to standard function failing due to order of load).
 *
 * @since 1.0.0
 *
 */
function azrcrv_li_is_plugin_active($plugin){
    return in_array($plugin, (array) get_option('active_plugins', array()));
}

/**
 * advert injection before loop.
 *
 * @since 1.0.0
 *
 */
function azrcrv_li_inject_adds_before_loop($query){
	
	$options = azrcrv_li_get_option('azrcrv-li');
	
	if ($options['loop_before_active'] == 1){
		if( $query->is_main_query() ){
			echo do_shortcode(stripslashes($options['loop_before_advert']));
		}
	}
}

/**
 * advert injection within loop.
 *
 * @since 1.0.0
 *
 */
function azrcrv_li_inject_adds_in_loop($post){
	global $wp_query;
	
	$options = azrcrv_li_get_option('azrcrv-li');
	
	if ($options['loop_within_active'] == 1){

		if ($wp_query->post != $post){ return; }

		if ($wp_query->current_post == $options['loop_within_position']){
			echo do_shortcode(stripslashes($options['loop_within_advert']));
		}

	}
}

/**
 * advert injection after loop.
 *
 * @since 1.0.0
 *
 */
function azrcrv_li_inject_adds_after_loop($query){
	
	$options = azrcrv_li_get_option('azrcrv-li');
	if ($options['loop_after_active'] == 1){
		if( $query->is_main_query() ){
			echo do_shortcode(stripslashes($options['loop_after_advert']));
		}
	}
}


?>