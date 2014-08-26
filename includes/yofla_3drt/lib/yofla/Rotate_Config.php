<?php
/**
 * Generates the contest of the config.js file dynamically, based
 * on provided image list and settings. If no settings are provided,
 * defaults are used
 */

class Rotate_Config {

    public $images_list; //holds arrays: "images","imageslarge"
    public $products_path;
    public $products_url;
    public $settings;

    private $_config = array(); //config in form of array, to be exported e.g. in JSON

    /**
     *
     */
    function __construct() {
        $this->_init_config();
    }


    /**
     * Returns config in JSON format
     *
     */
    public function get_config_json()
    {
        $this->_make_config();
        $json_config = json_encode($this->_config);
        return 'var RotationData = '.$json_config.';';
    }

    /**
     * Steps required to create/modify config values
     *
     */
    private function _make_config()
    {
        $this->_use_settings();
        $this->_add_images_to_config();
    }//make_config


    /**
     * Initializes config variable with defaults
     *
     *
     */
    private function _init_config()
    {
        $this->_config = array();

        //settings
        $this->_config["settings"] = array(
            "control"=>array(
                "maxZoom"=>300,
                "dragSpeed"=>0.5,
                "reverseDrag"=>false,
            ),
            "userInterface"=>array(
                "showArrows"=>true,
                "showToggleFullscreenButton"=>false,
                "showZoombar"=>false,
                "showTogglePlayButton"=>true,
            ),
            "preloader"=>array(
                "color1"=>'#FF000',
                "type"=>'wave',
            ),
            "rotation"=>array(
                "rotatePeriod"=>3,
                "rotateDirection"=>1,
                "bounce"=>false,
                "rotate"=>'true',
            ),
        );

        //hotspots
        $this->_config["hotspots"] = array();

        //images
        $this->_config["images"] = array();

    }//_init_config()

    /**
     * Adds the images in the list to the _config array
     */
    private function _add_images_to_config()
    {
        //sort images
        $this->_sort_images();

        //add images to config
        for ($i=0; $i<sizeof($this->images_list["images"]); $i++)
        {
            $this->_add_image_to_config($this->_create_image_info($i));
        }

    }//add_images_to_config


    /**
     * Modifies the settings _config array with the provided settings
     */
    private function _use_settings()
    {
       //use defaults from "global" settings.ini
       if(isset($this->settings))
       {
           $config = $this->settings["config"];
           $this->_use_settings_config($config);
       }

       //used if images folder has own settings.ini
       if (isset($this->images_list["settings"]))
       {
           if (isset($this->images_list["settings"]["config"]))
           {
               $config = $this->images_list["settings"]["config"];
               $this->_use_settings_config($config);
           }
       }
    }

    /**
     * Converts the provided default or directory level settings into
     * a format suitable for _config
     *
     * @param $config
     */
    private function _use_settings_config($config)
    {
        //rotation
        if (isset($config["bounce"])) $this->_config["settings"]["rotation"]["bounce"] = ($config["bounce"] == TRUE);
        if (isset($config["rotate"])) $this->_config["settings"]["rotation"]["rotate"] = $config["rotate"];
        if (isset($config["rotatePeriod"])) $this->_config["settings"]["rotation"]["rotatePeriod"] = floatval($config["rotatePeriod"]);
        if (isset($config["rotateDirection"])) $this->_config["settings"]["rotation"]["rotateDirection"] = intval($config["rotateDirection"]);
        //control
        if (isset($config["maxZoom"])) $this->_config["settings"]["control"]["maxZoom"] = intval($config["maxZoom"]);
        if (isset($config["dragSpeed"])) $this->_config["settings"]["control"]["dragSpeed"] = floatval($config["dragSpeed"]);
        if (isset($config["reverseDrag"])) $this->_config["settings"]["control"]["reverseDrag"] = ($config["reverseDrag"] == TRUE);
        if (isset($config["rotateOnMouseHover"])) $this->_config["settings"]["control"]["rotateOnMouseHover"] = ($config["rotateOnMouseHover"] == TRUE);
        if (isset($config["clickUrl"])) $this->_config["settings"]["control"]["clickUrl"] = $config["clickUrl"];
        if (isset($config["clickUrlTarget"])) $this->_config["settings"]["control"]["clickUrlTarget"] = $config["clickUrlTarget"];

        //preloader
        if (isset($config["color1"])) $this->_config["settings"]["preloader"]["color1"] = $config["color1"];
        if (isset($config["type"])) $this->_config["settings"]["preloader"]["type"] = $config["type"];
        //userinterface
        if (isset($config["showToggleFullscreenButton"])) $this->_config["settings"]["userInterface"]["showToggleFullscreenButton"] = ($config["showToggleFullscreenButton"] == TRUE);
        if (isset($config["showZoombar"])) $this->_config["settings"]["userInterface"]["showZoombar"] = ($config["showZoombar"] == TRUE);
        if (isset($config["showArrows"])) $this->_config["settings"]["userInterface"]["showArrows"] = ($config["showArrows"] == TRUE);
        if (isset($config["showTogglePlayButton"])) $this->_config["settings"]["userInterface"]["showTogglePlayButton"] = ($config["showTogglePlayButton"] == TRUE);
    }


    /**
     * Sorts provided images (before they are written to config)
     */
    private function _sort_images()
    {
        if (is_array($this->images_list["images"])) usort($this->images_list["images"],array($this,"cmp"));
        if (is_array($this->images_list["imageslarge"])) usort($this->images_list["imageslarge"],array($this,"cmp"));
    }

    private function  cmp($a,$b)
    {
        return strnatcmp(basename($a), basename($b));
    }


    /**
     * Creates the images entry (in array format) in the exported config
     *
     * @param $image_id
     * @return array
     */
    private function _create_image_info($image_id)
    {
        $image_info = array();

        $image_path_normal = $this->images_list["images"][$image_id];
        $image_path_large = (isset($this->images_list["imageslarge"])) ? $this->images_list["imageslarge"][$image_id] : NULL;

        $src = $this->_get_image_url($image_path_normal);
        $image_info["src"] = $src;

        //add info on large image, if defined
        if($image_path_large)
        {
            $image_info["srcLarge"] = $this->_get_image_url($image_path_large);
        }

        return $image_info;
    }

    /**
     * Adds image to list in exported config
     *
     * @param $image_info
     */
    private function _add_image_to_config($image_info)
    {
        $this->_config["images"][] = $image_info;
    }

    /**
     * The path for images in the exported config must be valigenerating config.jss, adding comments
     *
     * @param $image_path
     * @return string
     */
    private function _get_image_url($image_path)
    {
        $path_relative = substr($image_path,strlen($this->products_path));
        $url = $this->products_url.$path_relative;
        return $url;
    }

}//class