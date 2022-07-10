<?php

namespace LiteMvc\Core;

use LiteMvc\Core\Controller;
use denisok94\helper\Helper as H;

/**
 *
 */
class ApiController extends Controller
{
    /**
     * @var array
     */
    public $post = [];

    /**
     * @param string $action
     * @return bool
     */
    public function beforeAction($action)
    {
        header('Access-Control-Allow-Origin: *');
        header('Cache-Control: no-cache');

        $this->layout = null;
        $this->post = H::toArray($this->request->rawBody);

        return parent::beforeAction($action);
    }
    
    /**
     * 
     * @param string $path
     */
    public function getPost(string $path)
    {
        return H::get($this->post, $path);
    }

    /**
     * @param string $action
     */
    public function afterAction($action, &$result)
    {
        $result = H::toJson($result);
        return parent::afterAction($action, $result);
    }
}
