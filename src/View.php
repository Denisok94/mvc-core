<?php

namespace LiteMvc\Core;

use Exception, Throwable;
use LiteMvc\Core\Config;
use LiteMvc\Core\AssetBundle;
use LiteMvc\Core\Component\Html;
use denisok94\helper\Helper as H;

/**
 *
 */
class View
{
    public $theme;
    public Config $config;
    public string $class;
    public string $title;
    public array $metaTags = [];
    public array $linkTags = [];
    public array $css = [];
    public array $cssFiles = [];
    public array $js = [];
    public array $jsFiles = [];

    public function __construct(Config $config, string $class)
    {
        $this->config = $config;
        $this->class = strtolower(str_replace('Controller', '', H::getClassName($class)));
    }

    public function getViewPath()
    {
        return $this->config->viewPath . DIRECTORY_SEPARATOR . $this->class . DIRECTORY_SEPARATOR;
    }

    public function getLayoutPath()
    {
        return $this->config->viewPath . DIRECTORY_SEPARATOR . "layouts" . DIRECTORY_SEPARATOR;
    }

    /**
     * @param string $view the view name.
     * @param array $params the parameters
     * @return string the rendering result
     * @throws Exception
     */
    public function render($view, $params = [], $context = null)
    {
        $viewFile = $this->findViewFile($view);
        return $this->renderFile($viewFile, $params, $context);
    }

    /**
     * @param string $view the view name
     * @return string the view file path. Note that the file may not exist.
     */
    protected function findViewFile($view)
    {
        if ($this->theme !== null) {
            $file = $this->getLayoutPath() . $view;
        } else {
            $file = $this->getViewPath() . $view;
        }

        if (pathinfo($file, PATHINFO_EXTENSION) !== '') {
            return $file;
        }
        $path = $file . '.php';

        return $path;
    }

    /**
     *
     * @param string $viewFile the view file
     * @param array $params the parameters 
     * @param object $context the context 
     * @return string the rendering result
     * @throws Exception
     */
    public function renderFile($viewFile, $params = [], $context = null)
    {
        $output = '';
        if (!is_file($viewFile)) {
            throw new Exception("The view file does not exist: $viewFile");
        }

        $output = $this->renderPhpFile($viewFile, $params);

        return $output;
    }

    /**
     * @param string $file the view file.
     * @param array $params the parameters
     * @return string the rendering result
     * @throws Exception
     */
    protected function renderPhpFile($file, $params = [])
    {
        $_obInitialLevel_ = ob_get_level();
        ob_start();
        ob_implicit_flush(false);
        extract($params, EXTR_OVERWRITE);
        try {
            require $file;
            return ob_get_clean();
        } catch (Exception $e) {
            while (ob_get_level() > $_obInitialLevel_) {
                if (!@ob_end_clean()) {
                    ob_clean();
                }
            }
            throw $e;
        } catch (Throwable $e) {
            while (ob_get_level() > $_obInitialLevel_) {
                if (!@ob_end_clean()) {
                    ob_clean();
                }
            }
            throw $e;
        }
    }

    //----------------------

    public bool $_isPageEnded;

    /**
     * @var AssetBundle[] 
     */
    public $assetBundles = [];

    /**
     */
    const PH_HEAD = '<![CDATA[BLOCK-HEAD]]>';
    /**
     */
    const PH_BODY_BEGIN = '<![CDATA[BLOCK-BODY-BEGIN]]>';
    /**
     */
    const PH_BODY_END = '<![CDATA[BLOCK-BODY-END]]>';
    /**
     */
    const POS_HEAD = 1;
    /**
     */
    const POS_BEGIN = 2;
    /**
     */
    const POS_END = 3;
    /**
     */
    const POS_READY = 4;
    /**
     */
    const POS_LOAD = 5;

    /**
     * Marks the position of an HTML head section.
     */
    public function head()
    {
        echo self::PH_HEAD;
    }

    /**
     * Marks the beginning of an HTML body section.
     */
    public function beginBody()
    {
        echo self::PH_BODY_BEGIN;
    }

    /**
     * Marks the ending of an HTML body section.
     */
    public function endBody()
    {
        echo self::PH_BODY_END;

        foreach (array_keys($this->assetBundles) as $bundle) {
            $this->registerAssetFiles($bundle);
        }
    }
    /**
     * Marks the beginning of a page.
     */
    public function beginPage()
    {
        ob_start();
        ob_implicit_flush(false);
    }

    /**
     * Marks the ending of an HTML page.
     * @param bool $ajaxMode whether the view is rendering in AJAX mode.
     * If true, the JS scripts registered at [[POS_READY]] and [[POS_LOAD]] positions
     * will be rendered at the end of the view like normal scripts.
     */
    public function endPage($ajaxMode = false)
    {
        $this->_isPageEnded = true;

        $content = ob_get_clean();

        echo strtr($content, [
            self::PH_HEAD => $this->renderHeadHtml(),
            self::PH_BODY_BEGIN => $this->renderBodyBeginHtml(),
            self::PH_BODY_END => $this->renderBodyEndHtml($ajaxMode),
        ]);

        $this->clear();
    }

    /**
     * @param string $name 
     * @return AssetBundle the registered asset bundle instance
     */
    public function registerAssetBundle($name)
    {
        if (!isset($this->assetBundles[$name])) {
            $this->assetBundles[$name] = new $name($this->config);
        }
        $bundle = $this->assetBundles[$name];

        return $bundle;
    }

    /**
     * Registers all files provided by an asset bundle including depending bundles files.
     * Removes a bundle from [[assetBundles]] once files are registered.
     * @param string $name name of the bundle to register
     */
    protected function registerAssetFiles($name)
    {
        if (!isset($this->assetBundles[$name])) {
            return;
        }
        $bundle = $this->assetBundles[$name];
        if ($bundle) {
            $bundle->registerAssetFiles($this);
        }
        unset($this->assetBundles[$name]);
    }



    /**
     * Renders the content to be inserted in the head section.
     * The content is rendered using the registered meta tags, link tags, CSS/JS code blocks and files.
     * @return string the rendered content
     */
    protected function renderHeadHtml()
    {
        $lines = [];
        if (!empty($this->metaTags)) {
            $lines[] = implode("\n", $this->metaTags);
        }

        if (!empty($this->linkTags)) {
            $lines[] = implode("\n", $this->linkTags);
        }
        if (!empty($this->cssFiles)) {
            $lines[] = implode("\n", $this->cssFiles);
        }
        if (!empty($this->css)) {
            $lines[] = implode("\n", $this->css);
        }
        if (!empty($this->jsFiles[self::POS_HEAD])) {
            $lines[] = implode("\n", $this->jsFiles[self::POS_HEAD]);
        }
        if (!empty($this->js[self::POS_HEAD])) {
            $lines[] = Html::script(implode("\n", $this->js[self::POS_HEAD]));
        }

        return empty($lines) ? '' : implode("\n", $lines);
    }
    protected function renderBodyBeginHtml()
    {
        $lines = [];
        if (!empty($this->jsFiles[self::POS_BEGIN])) {
            $lines[] = implode("\n", $this->jsFiles[self::POS_BEGIN]);
        }
        if (!empty($this->js[self::POS_BEGIN])) {
            $lines[] = Html::script(implode("\n", $this->js[self::POS_BEGIN]));
        }

        return empty($lines) ? '' : implode("\n", $lines);
    }
    protected function renderBodyEndHtml($ajaxMode)
    {
        $lines = [];

        if (!empty($this->jsFiles[self::POS_END])) {
            $lines[] = implode("\n", $this->jsFiles[self::POS_END]);
        }

        if ($ajaxMode) {
            $scripts = [];
            if (!empty($this->js[self::POS_END])) {
                $scripts[] = implode("\n", $this->js[self::POS_END]);
            }
            if (!empty($this->js[self::POS_READY])) {
                $scripts[] = implode("\n", $this->js[self::POS_READY]);
            }
            if (!empty($this->js[self::POS_LOAD])) {
                $scripts[] = implode("\n", $this->js[self::POS_LOAD]);
            }
            if (!empty($scripts)) {
                $lines[] = Html::script(implode("\n", $scripts));
            }
        } else {
            if (!empty($this->js[self::POS_END])) {
                $lines[] = Html::script(implode("\n", $this->js[self::POS_END]));
            }
            if (!empty($this->js[self::POS_READY])) {
                $js = "jQuery(function ($) {\n" . implode("\n", $this->js[self::POS_READY]) . "\n});";
                $lines[] = Html::script($js);
            }
            if (!empty($this->js[self::POS_LOAD])) {
                $js = "jQuery(window).on('load', function () {\n" . implode("\n", $this->js[self::POS_LOAD]) . "\n});";
                $lines[] = Html::script($js);
            }
        }

        return empty($lines) ? '' : implode("\n", $lines);
    }
    public function clear()
    {
        $this->metaTags = [];
        $this->linkTags = [];
        $this->css = [];
        $this->cssFiles = [];
        $this->js = [];
        $this->jsFiles = [];
        $this->assetBundles = [];
    }

    public function registerJsFile($url, $options = [], $key = null)
    {
        $this->registerFile('js', $url, $options, $key);
    }
    public function registerCssFile($url, $options = [], $key = null)
    {
        $this->registerFile('css', $url, $options, $key);
    }

    /**
     * Registers a JS or CSS file.
     * @param string $url 
     * @param string $type
     * @param array $options
     * @param string 
     */
    private function registerFile($type, $url, $options = [], $key = null)
    {
        $key = $key ?: $url;
        $originalOptions = $options;
        $position = H::get($options, 'position', self::POS_END);

        if ($type === 'js') {
            $this->jsFiles[$position][$key] = Html::jsFile($url, $options);
        } else {
            $this->cssFiles[$key] = Html::cssFile($url, $options);
        }
    }
}
