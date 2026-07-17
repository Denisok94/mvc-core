<?php

namespace LiteMvc\Core;

use LiteMvc\Core\View;
use LiteMvc\Core\Config;
use denisok94\helper\Helper as H;

/**
 * 
 */
class AssetBundle
{
    /**
     * @var Config 
     */
    public $config;
    /**
     * @var string 
     */
    public $class = '';
    /**
     * @var string 
     */
    public $hashAsset = '';
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
     * файлы находятся в web папке
     * @var bool
     */
    public $is_web = false;

    /**
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->class = strtolower(str_replace('Asset', '', H::getClassName(get_class($this))));

        $this->hashAsset = hash('adler32', serialize([$this->class, $this->js, $this->css]), false);
    }

    /**
     * @return string
     */
    public function getWebAssetPath()
    {
        return $this->config->webPath . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR;
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
        $this->init();
        $this->recurse_copy($this->basePath . DIRECTORY_SEPARATOR . $this->sourcePath, $this->getWebAssetPath() . $this->hashAsset);

        foreach ($this->js as $js) {
            $view->registerJsFile($this->getAssetUrl($js), $this->jsOptions);
        }
        foreach ($this->css as $css) {
            $view->registerCssFile($this->getAssetUrl($css), $this->cssOptions);
        }
    }

    public function init() {}

    /**
     * @param string $asset
     * @return string
     */
    public function getAssetUrl(string $asset)
    {
        return $this->is_web ? "/$asset" : "/assets/" . $this->hashAsset . "/$asset";
    }

    /**
     *
     * @param string $from_asset
     * @param string $to_asset
     */
    public function recurse_copy(string $from_asset, string $to_asset)
    {
        if ($this->is_web) {
            return;
        }
        $dir = opendir($from_asset);
        if (!is_dir($to_asset)) {
            @mkdir($to_asset);
        }
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                $from = $from_asset . DIRECTORY_SEPARATOR . $file;
                $to = $to_asset . DIRECTORY_SEPARATOR . $file;
                if (is_dir($from)) {
                    $this->recurse_copy($from, $to);
                } else {
                    if (!file_exists($to)) {
                        copy($from, $to);
                    }
                }
            }
        }
        closedir($dir);
    }
}
