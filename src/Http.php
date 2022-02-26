<?php

namespace xyg;

class Http
{
	// 创建私有静态的变量保存该类对象
	private static $instance = null;
	// TCP三次握手超时时间，校验目标服务器的过载，下线，或崩溃等状况
	private $connectTimeout = 5;
	// 接收缓冲完成前需要等待的时间，如果目标是个大文件，则调高数值
	private $readTimeout = 5;

	// 防止使用new直接创建对象
    private function __construct() {}

    // 防止使用clone克隆对象
    private function __clone() {}

    public static function instance()
    {
    	if ( self::$instance === null ) {
    		self::$instance = new self();
        }
        return self::$instance;
    }

	public function curl($url, $data = null, $header = [], $showResponseHeader = false, $location = false)
	{
		// 初始化CURL会话
	    $ch = curl_init();
	    curl_setopt($ch, CURLOPT_URL, $url);
	    if ( $data != null ) {
	    	curl_setopt($ch, CURLOPT_POST, 1);
	    	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		}
	    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout);
	    curl_setopt($ch, CURLOPT_TIMEOUT, $this->readTimeout);
	    // 设置获取的数据以文件流的形式返回（0 => 直接输出）
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    // 是否https
		if( strlen($url) > 5 && strtolower(substr($url, 0, 5)) == 'https' ) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		}
	    // 设置请求header项
		if ( !empty($header) ) {
			if ( isset($header['Referer']) ) {
		    	curl_setopt($ch, CURLOPT_REFERER, $header['Referer']);
		    }
		    $requestHeader = [];
		    foreach ( $header as $headerName => $headerValue ) {
		        $requestHeader[] = $headerName . ': ' . $headerValue;
		    }
		    curl_setopt($ch, CURLOPT_HTTPHEADER, $requestHeader);
		}
		// 是否输出响应头信息
	    curl_setopt($ch, CURLOPT_HEADER, $showResponseHeader);
	    if ( $showResponseHeader ) {
	        list($responseHeader, $response['body']) = explode("\r\n\r\n", curl_exec($ch));
	        foreach ( explode("\r\n", $responseHeader) as $head ) {
	            $tmp = explode(': ', $head);
	            $heads[$tmp[0]] = isset($tmp[1]) ? $tmp[1] : null;
	        }
	        $response['responseHeader'] = $heads;
	    } else {
	        $response['body'] = curl_exec($ch);
	    }
	    // 是否跟踪重定向页面
	    if ( $location ) {
	    	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	    }
	    $response['httpCode'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	    curl_close($ch);
	    return $response;
	}

}