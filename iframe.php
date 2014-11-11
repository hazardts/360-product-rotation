<?php
/**
 * Generate iframe.html file when cloud option rotoatetool.js is set
 *
 */

if (!defined('YOFLA_PLAYER_URL')) define('YOFLA_PLAYER_URL', 'https://www.yofla.com/3d-rotate/app/cdn/get/rotatetool.js');

//construct $path variable
$path = '';

if(isset($_GET['product_url'])){
    $path = urldecode($_GET['product_url']);
}

//default template variables
$ga_enabled = "";
$ga_label = "";
$ga_category = "";
$ga_tracking_id = "";

//initiate google analytics event tracking values
if(isset($_GET['ga_tracking_id']) && strlen($_GET['ga_tracking_id']) > 10){
    $ga_enabled = "true";
    $ga_tracking_id = (isset($_GET['ga_tracking_id']))?$_GET['ga_tracking_id']:"";
    $ga_label = (isset($_GET['ga_label']))?$_GET['ga_label']:"";
    $ga_category = (isset($_GET['ga_category']))?$_GET['ga_category']:"";
}
else{
    $ga_enabled = "false";
}

//construct rotatetool.js path
$rotatetool_js_src = YOFLA_PLAYER_URL;
if(isset($_GET['license_id'])) $rotatetool_js_src = $rotatetool_js_src.'?id='.$_GET['license_id'];

//load template
$template_file_path = 'includes/template-iframe.tpl';
$template_string = file_get_contents($template_file_path);

$values = array(
  '{rotatetool_js_src}' => $rotatetool_js_src,
  '{path}' => $path,
  '{ga_enabled}' => $ga_enabled,
  '{ga_tracking_id}' => $ga_tracking_id,
  '{ga_label}' => $ga_label,
  '{ga_category}' => $ga_category,
);
$new_html = strtr($template_string,$values);

echo $new_html;
