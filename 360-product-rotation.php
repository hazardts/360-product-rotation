<?php
/**
 * @package 360 Product Rotation
 * @version 1.0.1
 */
/*
Plugin Name: 360&deg; Product Rotation
Plugin URI: http://www.yofla.com/3d-rotate/wordpress-plugin-360-product-rotation/
Description: Plugin for easier integration of the 360 product rotation created by the 3D Rotate Tool Setup Utility.
Author: YoFLA.com
Version: 1.0.1
Last Modified: 01/2014
Author URI: http://www.yofla.com/
License: GPLv2
*/

//define constants
if (!defined('YOFLA_PLAYER_URL')) define('YOFLA_PLAYER_URL', 'https://www.yofla.com/3d-rotate/app/cdn/get/rotatetool.js');
if (!defined('YOFLA_LICENSE_ID_CHECK_URL')) define('YOFLA_LICENSE_ID_CHECK_URL', 'http://www.yofla.com/3d-rotate/app/check/licenseid/');
if (!defined('YOFLA_360_VERSION_KEY')) define('YOFLA_360_VERSION_KEY', 'yofla_360_version');
if (!defined('YOFLA_360_VERSION_NUM')) define('YOFLA_360_VERSION_NUM', '1.0.1');
if (!defined('YOFLA_360_PATH'))  define('YOFLA_360_PATH', plugin_dir_path(__FILE__));
if (!defined('YOFLA_360_URL'))  define('YOFLA_360_URL', plugin_dir_url(__FILE__));

//store plugin version to db
add_option(YOFLA_360_VERSION_KEY, YOFLA_360_VERSION_NUM);

//check if upgrading...
if (get_option(YOFLA_360_VERSION_KEY) != YOFLA_360_VERSION_NUM) {
    // Execute your upgrade logic here
    // Then update the version value
    update_option(YOFLA_360_VERSION_KEY, YOFLA_360_VERSION_NUM);
}

//settings page
if( is_admin() ){
    require YOFLA_360_PATH.'includes/plugin-settings.php';
    new Yofla_360_product_rotation_settings();
}

//define shortcode and callback
add_shortcode('360', 'yofla_360_embed_shortcode');



function yofla_360_embed_shortcode($attributes, $content = null) {

    //process attributes
    $attributes = yofla_360_process_attributes($attributes);

    $output_legacy_version = false;

    //exit on error
    if(isset($attributes['error'])) return $attributes['error'];


    //get uploads folder
    $wp_uploads = wp_upload_dir();
    if($wp_uploads['error']) {
        $html = yofla_360_format_error('uploads directory not accessible!');
        return $html;
    }

    //init path variables
    $uploads_url       = $wp_uploads["baseurl"];
    $product_url       = $uploads_url.$attributes['src'];
    $product_path      = $wp_uploads['basedir'].$attributes['src'];
    $file_path_config  = $product_path.'config.js';
    $file_path_config_xml  = $product_path.'config.xml';
    $rotatetool_js_url = $uploads_url.$attributes['src'].'rotatetool.js';

    if(file_exists($file_path_config_xml)){
       $output_legacy_version = true;
    }

    if(!file_exists($file_path_config) && $output_legacy_version == false){
        $html = yofla_360_format_error('Config file not readable, are paths set correctly? Path: '.$file_path_config);
        return $html;
    }

    //start html output
    $html = "\n".'<!-- 360 Product Rotation Plugin v.'.YOFLA_360_VERSION_NUM.' by www.yofla.com  Begin -->'."\n";


    if($output_legacy_version){
        $code = yofla_360_output_legacy($attributes,$product_url,$product_path);
    }
    else{
        $code = yofla_360_output_element($attributes,$file_path_config,$product_url,$rotatetool_js_url);
    }

    $html .= $code;

    $html .= "\n".'<!-- 360 Product Rotation Plugin v.'.YOFLA_360_VERSION_NUM.' by www.yofla.com  End -->'."\n";

    return $html;
}


/**
 * Outputs the code as html element and not iframe
 *
 * @param $attributes
 * @param $file_path_config
 * @param $product_url
 * @param $rotatetool_js_url
 * @return string
 */
function yofla_360_output_element($attributes,$file_path_config,$product_url,$rotatetool_js_url){

    $html = '';

    //get unique productid
    $product_id = yofla_360_get_product_id($file_path_config);

    // set unique div id
    $div_id = "productRotation_".$product_id;

    // get width, height
    $width  = $attributes['width'];
    $height = $attributes['height'];

    $html .= "<div id='".$div_id."' style='width: ".$width."; height: ".$height."; border: 1px solid silver;'";
    $html .= " data-rotate-tool='{\"path\":\"".$product_url."\",\"id\":\"$product_id\"}' > </div>";

    //cloud based player url
    $yofla_360_settings = get_option( 'yofla_360_options' );
    if ( isset($yofla_360_settings['license_id']) ){
        if(strlen($yofla_360_settings['license_id']) > 0){
            $rotatetool_js_url = YOFLA_PLAYER_URL.'?id='.$yofla_360_settings['license_id'];
        }
    }

    wp_enqueue_script( 'yofla_360_player', $rotatetool_js_url);

    return $html;
}


/**
 * Outputs the Product Rotation code as iframe
 *
 * @param $attributes
 * @param $product_url
 * @param $product_path
 * @return string
 */
function yofla_360_output_legacy($attributes,$product_url,$product_path){

    //
    $html = '';

    // get width, height
    $width  = $attributes['width_original'];
    $height = $attributes['height_original'];

    $iframe_name = 'iframe'.$width.'x'.$height.'.html';

    $iframe_path = $product_path.$iframe_name;
    $iframe_url = $product_url.$iframe_name;

    if(file_exists($iframe_path)){
        //no action, iframe already generated
    }
    else{
        //load template
        $template_file_path = YOFLA_360_PATH.'includes/template-iframe-legacy.tpl';
        $template_string = file_get_contents($template_file_path);

        //modify template
        $search = array('{width}','{height}');
        $replace = array($width,$height);
        $new_html = str_replace($search,$replace,$template_string);

        //save file
        file_put_contents($iframe_path,$new_html);
    }

    //output iframe
    $html .= '<iframe
            width="'.$width.'"
            height="'.$height.'"
            src="'.$iframe_url.'"
            marginheight="0"
            marginwidth="0"
            scrolling="no"
            style="border:1px solid silver;" >';
    $html .= '</iframe>';


    return $html;
}


/**
 * Ads default values to attributes
 *
 * @param $attributes
 * @return mixed
 */
function yofla_360_process_attributes($attributes) {
    
    //defaults
    $defaults = array(
        'width' => '500',
        'height' => '375',
    );

    //enhance provided attributes with  defaults
    foreach ($defaults as $default => $value) {
        if (!array_key_exists($default, $attributes)) {
            $attributes[$default] = $value;
        }
    }
    
    //check src parameter
    if (!isset($attributes['src'])){
        $html = yofla_360_format_error('[src] parameter is not set!');
        $attributes['error'] = $html;
    }
    else{
        $attributes['src'] = yofla_360_format_provided_src_attribute($attributes['src']);
    }
    //cache original width,height values
    $attributes['width_original'] = $attributes['width'];
    $attributes['height_original'] = $attributes['height'];


    //enhance width/height with px or %
    if(substr($attributes['width'],-1) != 'x' || substr($attributes['width'],-1) != '%'){
        $attributes['width'] = $attributes['width']."px";
    }
    if(substr($attributes['height'],-1) != 'x' || substr($attributes['height'],-1) != '%'){
        $attributes['height'] = $attributes['height']."px";
    }
    
    
    return $attributes;
}


/**
 * Adds trailing and leading slash to string
 * 
 * @param $src
 * @return string
 */
function yofla_360_format_provided_src_attribute($src) {
    if (substr($src,-1) != '/') $src .= '/';
    if (substr($src,0,1) != '/') $src = '/'.$src;
    return $src;
}


/**
 * Adds html code to error message that is returned to page
 *
 * @param $msg
 * @return string
 */
function yofla_360_format_error($msg){
    $str = '<div><span style="color: red">360 Plugin Error: '.$msg.'!</span></div>';
    return $str;
}


/**
 * Extract unique product_id from config.js file.
 *
 * @param $file_path_config
 * @return mixed
 */
function yofla_360_get_product_id($file_path_config){
    $config_file = file_get_contents($file_path_config);
    $first_line =  strtok($config_file,"\n");
    preg_match('/_(\d*)/',$first_line,$matches);
    $product_id = $matches[1];
    return $product_id;
}
