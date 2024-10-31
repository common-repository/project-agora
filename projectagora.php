<?php
/*
Plugin Name: Project Agora
Description: Easily implement the Project Agora header tag code on your site, without the need of a developer.
Version: 1.0.2
Author: Project Agora
Author URI: https://projectagora.com
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

define( 'PROJECTAGORA_SUPPORT_MAIL','pub_support@projectagora.com' );
define( 'PROJECTAGORA_PLUGIN_BASENAME', plugin_basename( __FILE__ ) ); // return:  projectagora/projectagora.php
define( 'PROJECTAGORA_SETTINGS_PAGE_SLUG', 'projectagora-settings');
define( 'PROJECTAGORA_LOGO_URL', plugin_dir_url( __FILE__ ) . 'images/logo.svg');

add_action( 'admin_init', 'projectagora_settings_init' );
add_action( 'admin_menu', 'projectagora_options_page' );
add_action( 'wp_enqueue_scripts', 'projectagora_headertag_script' );
add_filter( 'script_loader_tag', 'projectagora_prefix_add_async_attribute', 10, 2 );
add_filter( 'plugin_action_links_' . PROJECTAGORA_PLUGIN_BASENAME, 'projectagora_plugin_settings_link', 10, 1 );
add_action( 'update_option_projectagora_option__htscript_url','projectagora_send_mail_for_updated_option', 10, 3 );
add_action( 'add_option_projectagora_option__htscript_url','projectagora_send_mail_for_added_option', 10, 2 );

register_activation_hook( __FILE__, 'projectagora_plugin_activate' );   // plugin activation hook
register_deactivation_hook(__FILE__, 'projectagora_plugin_deactivate'); // plugin deactivation hook
register_uninstall_hook( __FILE__, 'projectagora_plugin_uninstall' );   // plugin uninstall hook



function projectagora_settings_init() {
    register_setting( 'projectagora_headertag_script_settings', 'projectagora_option__htscript_url' );
    add_settings_section( 'projectagora-headertag-script-section', 'Header Tag Settings', 'projectagora_headertag_script_section__cb', 'projectagora-settings-section' );
    add_settings_field( 'projectagora_option__htscript_url_field', 'Header Tag', 'projectagora_option__htscript_url_field__cb', 'projectagora-settings-section', 'projectagora-headertag-script-section' );
	
	register_setting( 'projectagora_headertag_script_settings', 'projectagora_option__notify_support_consent' );
	add_settings_field( 'projectagora_option__notify_support_consent', 'Notify Project Agora?', 'projectagora_option__notify_support_consent__cb', 'projectagora-settings-section', 'projectagora-headertag-script-section' );
	add_option( 'projectagora_option__notify_support_consent', 'on' );
}

function projectagora_headertag_script_section__cb() {
    echo "<p>Enter the Header Tag code you received from ProjectAgora.</p><br>If you don't have a code and wish to work with Project Agora, please, visit <a target='_blank' rel='noopener noreferrer' href='https://projectagora.com'> projectagora.com</a> and fill in the publisher form.";
}

function projectagora_option__htscript_url_field__cb() {
    $projectagora__script_url_escaped = esc_url( "https://" . get_option('projectagora_option__htscript_url') );
    echo '<input type="text" name="projectagora_option__htscript_url" value="' . explode("://", $projectagora__script_url_escaped)[1] . '" style="width: 300px;"/>';
}

function projectagora_option__notify_support_consent__cb() {
    $projectagora__notify_support_consent = get_option( 'projectagora_option__notify_support_consent' );
    echo '<input type="checkbox" id="projectagora_option__notify_support_consent" name="projectagora_option__notify_support_consent" ' . ($projectagora__notify_support_consent == "on" ? "checked" : "") . '/>';
	echo '<label for="projectagora_option__notify_support_consent">By checking this box you agree to send an automatic email notification to Project Agora regarding the above code implementation status.</label>';
}

function projectagora_options_page() {
    add_options_page( 'Project Agora Settings', 'Project Agora', 'manage_options', PROJECTAGORA_SETTINGS_PAGE_SLUG, 'projectagora_options_page_html' );
}

function projectagora_options_page_html() {
    ?>
    <div class="wrap">
        <!-- <h1>Project Agora Settings</h1> -->
		<img width="300" src="<?php echo esc_url(PROJECTAGORA_LOGO_URL) ?>"  alt="Project Agora Logo" decoding="async">
        <form method="post" action="options.php">
            <?php settings_fields( 'projectagora_headertag_script_settings' ); ?>
            <?php do_settings_sections( 'projectagora-settings-section' ); ?>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

function projectagora_headertag_script() {
    $projectagora__script_url_escaped = esc_url( "https://" . get_option( 'projectagora_option__htscript_url' ) );
    if ( $projectagora__script_url_escaped ) {
        wp_enqueue_script( 'projectagora-ht-script', explode(":", $projectagora__script_url_escaped)[1], array(), null, false );
    }
}

function projectagora_prefix_add_async_attribute($tag, $handle) {
   // add script handles to the array below
   $scripts_to_async = array('projectagora-ht-script');
   
   foreach($scripts_to_async as $async_script) {
      if ($async_script === $handle) {
         return str_replace(' src', ' async src', $tag);
      }
   }
   return $tag;
}

function projectagora_plugin_settings_link($links){
	$url = get_admin_url() . "options-general.php?page=" . PROJECTAGORA_SETTINGS_PAGE_SLUG;
    $settings_link = '<a href="' . esc_url($url) . '">' . 'Settings' . '</a>';
      
	// Adds the link to the end of the array.
	array_push($links, $settings_link);
	return $links;
}

function projectagora_send_mail_for_added_option($option, $value){
	//set $old_value to null indicates that the HT saved for the first time since installing the plugin.
	projectagora_send_mail_for_updated_option(null, $value, $option);
}


function projectagora_send_mail_for_updated_option($old_value, $value, $option){
	$projectagora_mail__siteurl = get_site_url();
	$projectagora_mail__to = PROJECTAGORA_SUPPORT_MAIL;
	
	$projectagora_mail__message = "Hi PA Support Team,\r\n\r\n";
	
	if($old_value == null){
		$projectagora_mail__subject = "🟩 New HT implementation on site: " . esc_url($projectagora_mail__siteurl);
		$projectagora_mail__message = projectagora_mail__message . "The HT has been added on the site: " . esc_url($projectagora_mail__siteurl) . " .\r\n\r\nHeader Tag added: {$value}.";
	} else {
		$projectagora_mail__subject = "🟪 HT updated on site: " . esc_url($projectagora_mail__siteurl);
		$projectagora_mail__message = $projectagora_mail__message . "The HT has been updated on the site: " . esc_url($projectagora_mail__siteurl) . " .\r\n\r\nOld Header Tag: {$old_value}\r\nNew Header Tag: {$value}.";
	}
	
	$projectagora_mail__message = "{$projectagora_mail__message}\r\n\r\n\r\nRegards,\r\nProject Agora WordPress Plugin";
	
	
    //send the email if the checkbox is checked
    if( get_option('projectagora_option__notify_support_consent') == 'on' ){
        wp_mail($projectagora_mail__to, $projectagora_mail__subject, $projectagora_mail__message);
    }
	

}

//--------------------------
function projectagora_plugin_activate(){
	
}

function projectagora_plugin_deactivate(){
	projectagora_send_mail_for_plugin_uninstall();
}

// And here goes the uninstallation function:
function projectagora_plugin_uninstall(){
	//	codes to perform during unistallation
    projectagora_send_mail_for_plugin_uninstall();
	delete_option('projectagora_option__htscript_url');
    delete_option('projectagora_option__notify_support_consent');
}

function projectagora_send_mail_for_plugin_uninstall(){
	$projectagora_mail__siteurl = get_site_url();
	$projectagora_mail__to = PROJECTAGORA_SUPPORT_MAIL;
	$projectagora_mail__subject = "🟥 HT REMOVED from: " . esc_url($projectagora_mail__siteurl);
	$projectagora_mail__message = "Hi PA Support Team,\r\n\r\nThe HT has been removed from the site: " . esc_url($projectagora_mail__siteurl) . " .\r\n\r\n\r\nRegards,\r\nProject Agora WordPress Plugin";
	

    //send the email if the checkbox is checked
    if( get_option('projectagora_option__notify_support_consent') == 'on' ){
        wp_mail($projectagora_mail__to, $projectagora_mail__subject, $projectagora_mail__message);
    }
}


?>
