<?php

namespace xyg;

class CloudDisk
{
    // 创建私有静态的数组保存该类对象组
    private static $handle = [];

    // 防止使用new直接创建对象
    private function __construct() {}

    // 防止使用clone克隆对象
    private function __clone() {}

    // 驱动器句柄
    public static function drive($type = 'AliyunDrive')
    {
        if ( PHP_VERSION_ID < 50600 ) {
            throw new \Exception('PHP版本要>=5.6.0');
        }
        if ( empty( self::$handle[$type] ) ) {
            $class = "\\xyg\\" . $type;
            if ( !class_exists($class) ) {
                throw new \Exception("{$type}类库不存在");
            }
            self::$handle[$type] = new $class();
        }
        return self::$handle[$type];
    }

}

// 文件大小格式化
function file_size_format($size, $dec = 2)
{
    $unit = ['B', 'KB', 'MB', 'GB', 'TB'];
    $pos = 0;
    while ( $size >= 1024 ) {
        $size /= 1024;
        $pos++;
    }
    return round($size, $dec) . $unit[$pos];
}

// 文件图标格式化
function file_ico_format($extension)
{
    $exts['txt']    = ['txt'];
    $exts['exe']    = ['exe', 'msi'];
    $exts['ps']     = ['psd'];
    $exts['image']  = ['ico', 'bmp', 'gif', 'jpe', 'jpeg', 'jpg', 'png', 'webp', 'jfif', 'tif', 'tiff', 'heic'];
    $exts['audio']  = ['m4a', 'mp3', 'ogg', 'wav', 'wma', 'flac', 'ape'];
    $exts['video']  = ['mov', 'blv', 'flv', 'asf', 'avi', 'mkv', 'mp4', 'mpeg', 'mpg', 'rm', 'rmvb', 'wmv', 'webm', 'ts', 'm3u8'];
    $exts['word']   = ['doc', 'docx'];
    $exts['excel']  = ['xls', 'xlsx'];
    $exts['ppt']    = ['ppt', 'pptx'];
    $exts['rar']    = ['rar', 'zip', '7z', 'gz', 'tar'];
    $exts['code']   = ['bat', 'asp', 'aspx', 'css', 'java', 'js', 'md', 'php', 'py', 'sh', 'go', 'json', 'c', 'cpp', 'omf', 'htm', 'html', 'shtml'];
    $exts['web']    = [];
    $exts['apple']  = ['ipa', 'pxl', 'deb'];
    $exts['android']= ['apk'];
    $exts['ai']     = ['ai'];
    $exts['bt']     = ['torrent'];
    if ( in_array($extension, $exts['txt']) ) {
        return 'txt';
    } elseif ( in_array($extension, $exts['exe']) ) {
        return 'exe';
    } elseif ( in_array($extension, $exts['ps']) ) {
        return 'ps';
    } elseif ( in_array($extension, $exts['image']) ) {
        return 'image';
    } elseif ( in_array($extension, $exts['audio']) ) {
        return 'audio';
    } elseif ( in_array($extension, $exts['video']) ) {
        return 'video';
    } elseif ( in_array($extension, $exts['word']) ) {
        return 'word';
    } elseif ( in_array($extension, $exts['excel']) ) {
        return 'excel';
    } elseif ( in_array($extension, $exts['ppt']) ) {
        return 'ppt';
    } elseif ( in_array($extension, $exts['rar']) ) {
        return 'rar';
    } elseif ( in_array($extension, $exts['code']) ) {
        return 'code';
    } elseif ( in_array($extension, $exts['web']) ) {
        return 'web';
    } elseif ( in_array($extension, $exts['apple']) ) {
        return 'apple';
    } elseif ( in_array($extension, $exts['android']) ) {
        return 'android';
    } elseif ( in_array($extension, $exts['ai']) ) {
        return 'ai';
    } elseif ( in_array($extension, $exts['bt']) ) {
        return 'bt';
    } elseif ( $extension == 'folder' ) {
        return 'folder';
    } else {
        return 'mix';
    }
}