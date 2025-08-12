<?php
/**
 * @Author: [FENG] 
 * @Date:   2020-10-13T17:50:31+08:00
 * @Last Modified by:   jincon
 * @Last Modified time: 2025-08-09T18:35:29+08:00
 */
namespace jincon;

use Exception;

/**
 * 支付基类
 */
class Tinypay
{
    /**
     * $config 相关配置
     */
    protected static $config = [];

    /**
     * [__construct 构造函数]
     * @param [type] $config [传递支付相关配置]
     */
    public function __construct(array $config=[]){
        $config && self::$config = $config;
    }
	
	/**
     * 发送一个POST请求
     * @param string $url     请求URL
     * @param array  $params  请求参数
     * @param array  $options 扩展参数
     * @return mixed|string
     */
    public static function post($url, $params = [], $headers = [], $pem = [])
    {
        $result = self::httpRequest($url, 'POST', $params, $headers, $pem);
        return $result;
    }

    /**
     * 发送一个GET请求
     * @param string $url     请求URL
     * @param array  $params  请求参数
     * @param array  $options 扩展参数
     * @return mixed|string
     */
    public static function get($url, $params = [], $headers = [])
    {
        $result = self::httpRequest($url, 'GET', $params, $headers);
        return $result;
    }

    /**
     * [httpRequest CURL请求]
     * @param  [type] $url        [请求url地址]
     * @param  string $method     [请求方法 GET POST]
     * @param  [type] $params     [数据数组]
     * @param  array  $headers    [请求header信息]
     * @param  [type] $debug      [调试开启 默认false]
     * @param  [type] $timeout    [超时时间]
     * @return [type]             [description]
     */
    public static function httpRequest($url, $method="GET", $params=null, $headers=array(), $pem=array(), $debug = false, $timeout = 60)
    {
		$startTime = microtime(true);
        $method = strtoupper($method);
        $ci = curl_init();
        /* Curl settings */
        curl_setopt($ci, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($ci, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/85.0.4183.121 Safari/537.36");
        // curl_setopt($ci, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 用户访问代理 User-Agent
        curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, $timeout); /* 在发起连接前等待的时间，如果设置为0，则无限等待 */
        curl_setopt($ci, CURLOPT_TIMEOUT, 7); /* 设置cURL允许执行的最长秒数 */
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, TRUE);

        in_array($method, ['PUT', 'DELETE']) && $headers[] = 'Content-Type:application/json';
        if ($params && is_array($params) && array_intersect(['Content-Type:application/json', 'Content-Type: application/json'], $headers)) {
            $params = json_encode($params, JSON_UNESCAPED_UNICODE);
        }

        switch ($method) {
            case "POST":
                curl_setopt($ci, CURLOPT_POST, TRUE);
                if (!empty($params)) {
                    if (is_array($params)) {
                        foreach ($params as $k => $v) {
                            if(!is_array($v) && "@" == substr($v, 0, 1)) { //判断是不是文件上传（文件上传用multipart/form-data）
                                $postMultipart = true;
                                if(class_exists('\CURLFile')){
                                    $params[$k] = new \CURLFile(substr($v, 1));
                                    curl_setopt($ci, CURLOPT_SAFE_UPLOAD, true);
                                } else {
                                    if (defined('CURLOPT_SAFE_UPLOAD')) {
                                        curl_setopt($ci, CURLOPT_SAFE_UPLOAD, false);
                                    }
                                }
                            }
                        }
                    }
                    // $tmpdatastr = is_array($params) ? http_build_query($params) : $params;
                    // curl_setopt($ci, CURLOPT_POSTFIELDS, $tmpdatastr);
                    $postFields = (is_array($params) && empty($postMultipart)) ? http_build_query($params) : $params;
                    curl_setopt($ci, CURLOPT_POSTFIELDS, $postFields);
                }
                break;
            default:
                if (in_array($method, ['PUT', 'DELETE'])) {
                    curl_setopt($ci, CURLOPT_POSTFIELDS, $params);
                } else {
                    $query_string = is_array($params) ? http_build_query($params) : $params;
                    $url = $query_string ? $url . (stripos($url, "?") !== false ? "&" : "?") . $query_string : $url;
                }
                curl_setopt($ci, CURLOPT_CUSTOMREQUEST, $method); /* //设置请求方式 */
                break;
        }
        $ssl = preg_match('/^https:\/\//i', $url) ? TRUE : FALSE;
        curl_setopt($ci, CURLOPT_URL, $url);
        if ($ssl) {
            curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
            curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, FALSE); // 不从证书中检查SSL加密算法是否存在
        }
        if (isset($pem['cert']) && isset($pem['key'])) { // 设置证书
            // 使用证书：cert 与 key 分别属于两个.pem文件
            foreach ($pem as $key => $value) {
                // CERT    CURLOPT_SSLCERTTYPE    CURLOPT_SSLCERT
                // KEY    CURLOPT_SSLKEYTYPE    CURLOPT_SSLKEY
                curl_setopt($ci, constant('CURLOPT_SSL'.strtoupper($key).'TYPE'), 'PEM');
                curl_setopt($ci, constant('CURLOPT_SSL'.strtoupper($key)), $value);
            }
        }
        $debug && curl_setopt($ci, CURLOPT_HEADER, true); /*启用时会将头文件的信息作为数据流输出*/
        if (ini_get('open_basedir') == '' && ini_get('safe_mode' == 'Off')) {
            curl_setopt($ci, CURLOPT_FOLLOWLOCATION, 1);
        }
        curl_setopt($ci, CURLOPT_MAXREDIRS, 2);/*指定最多的HTTP重定向的数量，这个选项是和CURLOPT_FOLLOWLOCATION一起使用的*/
        curl_setopt($ci, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ci, CURLINFO_HEADER_OUT, true);
        /*curl_setopt($ci, CURLOPT_COOKIE, $Cookiestr); * *COOKIE带过去** */
        $response = curl_exec($ci);
		
		$httpCode = curl_getinfo($ci, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ci);
        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2); // 毫秒
        // 记录请求结果日志
        if (isset(self::$config['log_path']) && self::$config['log_path']) {
			$params = is_string($params) ? $params : json_encode($params); // 限制响应长度
			$responseForLog = is_string($response) ? $response : json_encode($response); // 限制响应长度
            if ($curlError) {
                self::writeLog('ERROR', 'HTTP请求失败', [
					'refer'=>(isset($_SERVER['REQUIRE_URI'])?$_SERVER['REQUIRE_URI']:""),
                    'params' => $params,
                    'method' => $method,
                    'url' => $url,
                    'duration_ms' => $duration,
                    'curl_error' => $curlError
                ]);
            } else {
                self::writeLog('INFO', 'HTTP请求完成', [
					'refer'=>(isset($_SERVER['REQUIRE_URI'])?$_SERVER['REQUIRE_URI']:""),
                    'params' => $params,
                    'method' => $method,
                    'url' => $url,
                    'http_code' => $httpCode,
                    'duration_ms' => $duration,
                    'response' => $responseForLog
                ]);
            }
        }
		
        if ($debug) {
            $requestinfo = curl_getinfo($ci);
            !empty($requestinfo['request_header']) && $requestinfo['request_header'] = self::httpParseHeaders($requestinfo['request_header']);
            $headerSize = curl_getinfo($ci, CURLINFO_HEADER_SIZE);
            $headerString = substr($response, 0, $headerSize);

            $response = substr($response, $headerSize);
            $response = [
                'params' => $params,
                'request_info' => $requestinfo,
                'response' => $response,
                'response_header' => self::httpParseHeaders($headerString),
            ];
        }
        curl_close($ci);
        return $response;
    }

    /**
     * [httpParseHeaders 解析header头]
     * @param  [type] $headerString [header头字符串]
     * @return [type]               [description]
     */
    private static function httpParseHeaders($headerString)
    {
        $headers = [];
        $lines = explode("\r\n", $headerString);
        foreach ($lines as $line) {
            if ($line = trim($line)) {
                $parts = explode(':', $line, 2);
                $key = trim($parts[0]);
                $value = isset($parts[1]) ? trim($parts[1]) : '';
                $headers[$key] = $value;
            }
        }
        return $headers;
    }


    /**
     * [writeLog 统一日志记录方法]
     * @param string $level 日志级别 (INFO, ERROR, DEBUG)
     * @param string $message 日志消息
     * @param array $context 上下文数据
     * @param string $paymentType 支付类型 (Alipay, Wechat, Baidu, Bytedance, Unionpay)
     * @param array $config 配置数组（可选，如果不传则使用静态配置）
     */
    public static function writeLog($level, $message, $context = [], $paymentType = 'Pay', $config = null)
    {
        // 使用传入的配置或静态配置
        $logConfig = $config ?: static::$config;
        
        if (empty($logConfig['log_path'])) {
            return;
        }

        $logDir = dirname($logConfig['log_path']);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' Context: ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $logMessage = "[{$timestamp}] [{$level}] [{$paymentType}] {$message}{$contextStr}" . PHP_EOL;
        
        file_put_contents($logConfig['log_path'], $logMessage, FILE_APPEND | LOCK_EX);
    }

    /**
     * [__callStatic 模式方法（当我们调用一个不存在的静态方法时,会自动调用 __callStatic()）]
     * @param  [type] $method [方法名]
     * @param  [type] $params [方法参数]
     * @return [type]         [description]
     */
    public static function __callStatic($method, $params)
    {
        $app = new self(...$params);
        return $app->create($method);
    }

    /**
     * [create 实例化命名空间]
     * @param  [type] $method [description]
     * @return [type]         [description]
     */
    protected static function create($method)
    {
        $method = ucfirst(strtolower($method));

        $className = __CLASS__ . '\\' . $method;
        if (!class_exists($className)) { // 当类不存在是自动加载
            // spl_autoload_register(function($method){
            //     $filename = dirname(__FILE__) . DIRECTORY_SEPARATOR . basename (__CLASS__) .DIRECTORY_SEPARATOR . $method . '.php';
            //     if (is_readable($filename)) {
            //         require $filename;
            //     }
            // }, true, true);
            spl_autoload_register(function($class) use ($method) {
                $filename = dirname(__FILE__) . DIRECTORY_SEPARATOR . basename (__CLASS__) .DIRECTORY_SEPARATOR . $method . '.php';
                if (is_readable($filename)) {
                    require $filename;
                }
            }, true, true);
        }

        if (class_exists($className)) {
            return new $className(self::$config);
        } else {
            throw new Exception("ClassName [{$className}] Not Exists");
        }
    }

}
