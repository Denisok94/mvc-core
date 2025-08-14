<?php

namespace LiteMvc\Core;

use denisok94\helper\Helper as H;

class AssetBundle
{
    /**
     * @var array 
     */
    public $config = [];
    /**
     * @var string 
     */
    public $class = [];
    /**
     * @var string
     */
    public $basePath;
    /**
     * @var string
     */
    public $sourcePath;
    /**
     * @var array 
     */
    public $js = [];
    /**
     * @var array
     */
    public $css = [];
    /**
     * @var array
     */
    public $jsOptions = [];
    /**
     * @var array
     */
    public $cssOptions = [];

    /**
     */
    public function __construct($config)
    {
        $this->config = $config;
        $this->class = strtolower(str_replace('Asset', '', H::getClassName(get_class($this))));
    }

    /**
     * @return string
     */
    public function getWebAssetPath()
    {
        return $this->config['webPath'] . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR;
    }

    /**
     * @param View $view the view to be registered with
     * @return static the registered asset bundle instance
     */
    public static function register(View $view)
    {
        return $view->registerAssetBundle(get_called_class());
    }

    /**
     * Registers the CSS and JS files with the given view.
     * @param View $view the view that the asset files are to be registered with.
     */
    public function registerAssetFiles(View $view)
    {
        $this->recurse_copy($this->basePath . DIRECTORY_SEPARATOR . $this->sourcePath, $this->getWebAssetPath() . $this->class);

        foreach ($this->js as $js) {
            $view->registerJsFile($this->getAssetUrl($js), $this->jsOptions);
        }
        foreach ($this->css as $css) {
            $view->registerCssFile($this->getAssetUrl($css), $this->cssOptions);
        }
    }

    /**
     * @param string $asset
     * @return string
     */
    public function getAssetUrl(string $asset)
    {
        return "/assets/" . $this->class . "/$asset";
    }

    /**
     *
     * @param string $src
     * @param string $dst
     */
    public function recurse_copy(string $src, string $dst)
    {
        $dir = opendir($src);
        @mkdir($dst);
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($src . '/' . $file)) {
                    $this->recurse_copy($src . '/' . $file, $dst . '/' . $file);
                } else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }
}
