<?php

namespace LiteMvc\Core\Controller;

use LiteMvc\Core\Controller\BaseController;
use denisok94\helper\Helper as H;

/**
 * Для общения по формату json.
 * 
 * @example Пример:
 * ```php
 * // получить все входные данные
 * $message = $this->data; // array
 * // получить параметр из данных
 * $phone = $this->get('phone'); // phone or null
 * 
 * // return ['code', 'status', 'message', 'data'];
 * return $this->sendSuccess(); // http status code 200
 * return $this->sendSuccess($data); // 200
 * return $this->sendResponse($data); // 200
 * return $this->sendError(); // 400
 * return $this->sendError($message); // 400
 * return $this->sendError($message, $data, 999); // 999
 * 
 * // return ['code', 'status', 'message'];
 * return $this->sendBadRequest(); // 400
 * return $this->sendUnauthorized(); // 401
 * return $this->sendForbidden(); // 403
 * return $this->sendNotFound(); // 404
 * return $this->sendInternalServerError(); // 500
 * 
 * if (!$this->data) {
 *  return $this->sendBadRequest("Request is null"); // 400
 * }
 * 
 * try {
 *  //code...
 * } catch (\Exception $e) {
 *  return $this->sendInternalServerError($e->getMessage()); // 500
 * }
 * 
 * // Собственный формат ответа / Custom responses
 * return $this->sendResponse($data, $message); // 200
 * return $this->sendResponse($data, $message, $status, 999); // 999
 * 
 * return $this->send([]); // 200
 * return $this->send(['code' => 204]); // 204
 * return $this->send(['code' => 201, 'data' => $data]); // 201
 */
class ApiController extends BaseController
{
    /**
     * @var array
     */
    public $data = [];
    public const CODE_OK = 200;
    public const CODE_CREATED = 201;
    public const CODE_NO_CONTENT = 204;
    public const CODE_BAD_REQUEST = 400;
    public const CODE_UNAUTHORIZED = 401;
    public const CODE_FORBIDDEN = 403;
    public const CODE_NOT_FOUND = 404;
    public const CODE_INTERNAL_SERVER_ERROR = 500;

    /**
     * @param string $action
     * @return bool
     */
    public function beforeAction($action): bool
    {
        header('Access-Control-Allow-Origin: *');
        header('Cache-Control: no-cache');
        header('Content-Type: application/json; charset=utf-8');

        $this->layout = null;
        $this->data = H::toArray($this->request->rawBody);

        return parent::beforeAction($action);
    }

    /**
     * Получть значение входящего параметра
     * @param string $path
     * @param bool $nullValue
     */
    public function get(string $path, $nullValue = null)
    {
        return H::get($this->data, $path, $nullValue);
    }

    /**
     * @param string $action
     * @param array $result
     */
    public function afterAction($action, &$result)
    {
        if (isset($result['code'])) {
            http_response_code((int) $result['code']);
        } else {
            http_response_code(ApiController::CODE_OK);
        }
        $result = H::toJson($result);
        return parent::afterAction($action, $result);
    }

    //-------------------------------

    /**
     * Custom responses
     * @param mixed $data
     * @param string $message
     * @param string $status
     * @param integer $code
     * @return array
     * 
     * @example Пример:
     * ```php
     * return $this->sendResponse($data); // 200
     * return $this->sendResponse($data, $message); // 200
     * return $this->sendResponse($data, $message, $status, 999); // 999
     * ```
     */
    protected function sendResponse($data = [], string $message = '', string $status = 'OK', int $code = ApiController::CODE_OK)
    {
        return [
            'code' => $code,
            'status' => $status,
            'message' => $message,
            'data' => $data
        ];
    }

    /**
     * 200
     * @param mixed $data
     * @return array
     */
    protected function sendSuccess($data = [])
    {
        return [
            'code' => self::CODE_OK,
            'status' => 'OK',
            'data' => $data
        ];
    }

    /**
     * @param string $message
     * @param mixed $data
     * @param integer $code
     * @return array
     */
    protected function sendError(string $message = 'Error', $data = [], int $code = ApiController::CODE_BAD_REQUEST)
    {
        return [
            'code' => $code,
            'status' => 'FAIL',
            'message' => $message,
            'data' => $data
        ];
    }

    /**
     * 400
     * @param string $message
     * @return array
     */
    protected function sendBadRequest(string $message = 'Bad Request')
    {
        return [
            'code' => self::CODE_BAD_REQUEST,
            'status' => 'FAIL',
            'message' => $message
        ];
    }

    /**
     * 401
     * @param string $message
     * @return array
     */
    protected function sendUnauthorized(string $message = "User not Authentication")
    {
        return [
            'code' => self::CODE_UNAUTHORIZED,
            'status' => 'FAIL',
            'message' => $message
        ];
    }

    /**
     * 403
     * @param string $message
     * @return array
     */
    protected function sendForbidden(string $message = 'Forbidden')
    {
        return [
            'code' => self::CODE_FORBIDDEN,
            'status' => 'FAIL',
            'message' => $message
        ];
    }

    /**
     * 404
     * @param string $message
     * @return array
     */
    protected function sendNotFound(string $message = 'Not Found')
    {
        return [
            'code' => self::CODE_NOT_FOUND,
            'status' => 'FAIL',
            'message' => $message
        ];
    }

    /**
     * 500
     * @param string $message
     * @return array
     * 
     * @example Пример:
     * ```php
     * try {
     *  //code...
     * } catch (\Exception $e) {
     *  return $this->sendInternalServerError($e->getMessage()); // 500
     * }
     * ```
     */
    protected function sendInternalServerError(string $message = 'Internal Server Error')
    {
        return [
            'code' => self::CODE_INTERNAL_SERVER_ERROR,
            'status' => 'FAIL',
            'message' => $message
        ];
    }

    // end send
    //-------------------------------

    /**
     * custom responses
     * @param mixed $data
     * ```php
     * $responses = [];
     * // ...
     * return $this->send($responses);
     * ```
     * @author vitaliy-pashkov 
     */
    protected function send($data = [])
    {
        return $data;
    }
}
