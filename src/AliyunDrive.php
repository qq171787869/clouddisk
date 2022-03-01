<?php

namespace xyg\clouddisk;

class AliyunDrive
{
    // 阿里云盘 auth API
    private $auth_url = 'https://auth.aliyundrive.com';
    // 阿里云盘 API
    private $api_url = 'https://api.aliyundrive.com';
    // 初始化显示的文件夹ID
    private $init_folder_id = 'root';
    // 需要隐藏的文件或文件夹ID
    private $hide_mix_id = [];
    // 需要密码访问的文件或文件夹ID，二位数组['file_id'='you_file_id', 'password'=>'file_password']
    private $need_pass_mix = [];
    // 公共Reuqest Headers
    private $request_header = [
        'Accept'            =>  'application/json, text/plain, */*',
        'Content-type'      =>  'application/json;charset=UTF-8',
        'Origin'            =>  'https://www.aliyundrive.com',
        'Referer'           =>  'https://www.aliyundrive.com/',
        'User-agent'        =>  'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/94.0.4606.81 Safari/537.36',
    ];

    // 设置类属性
    public function set($param, $value)
    {
        if ( isset($this->$param) ) {
            $this->$param = $value;
        }
    }

    // 获取配置文件里的refresh_token和access_token
    public function getToken()
    {
        if ( file_exists( CONF_PATH . 'xyg_aliyundrive.php' ) ) {
            $config = require CONF_PATH . 'xyg_aliyundrive.php';
            // 判断access_token是否存在 并且 是否过期
            if ( isset($config['access_token']) && $config['expire_time'] > time() ) {
                return $config;
            } else {
                return $this->refreshToken($config['refresh_token'], $config);
            }
        } else {
            throw new \Exception(CONF_PATH . '目录下xyg_aliyundrive.php配置文件不存在');
        }
    }

    // 刷新token
    public function refreshToken($refresh_token, $config_old)
    {
        $api = $this->auth_url . '/v2/account/token';
        $data['refresh_token'] = $refresh_token;
        $data['grant_type'] = 'refresh_token';
        $res = \xyg\http\Request::instance()->curl($api, json_encode($data), $this->request_header);
        if ( $res['httpCode'] === 200 ) {
            $config = $this->filterToken(json_decode($res['body'] , 1));
            $config = array_merge($config_old, $config);
            file_put_contents(CONF_PATH . 'xyg_aliyundrive.php', "<?php return " . var_export($config, true) . ";", FILE_FLAGS);
            return $config;
        } else {
            return ;
        }
    }

    // 过滤token杂项
    public function filterToken($token)
    {
        $token['expire_time'] = strtotime($token['expire_time']);
        if ( isset($token['user_name']) ) unset($token['user_name']);
        if ( isset($token['need_link']) ) unset($token['need_link']);
        if ( isset($token['pin_setup']) ) unset($token['pin_setup']);
        if ( isset($token['need_rp_verify']) ) unset($token['need_rp_verify']);
        if ( isset($token['avatar']) ) unset($token['avatar']);
        if ( isset($token['user_data']) ) unset($token['user_data']);
        if ( isset($token['is_first_login']) ) unset($token['is_first_login']);
        if ( isset($token['nick_name']) ) unset($token['nick_name']);
        // if ( isset($token['user_id']) ) unset($token['user_id']);
        if ( isset($token['exist_link']) ) unset($token['exist_link']);
        if ( isset($token['status']) ) unset($token['status']);
        return $token;
    }

    // 获取文件列表数据
    public function getListData($folder_id = 'root', $next_marker = '', $limit = 20, $order_by = 'name', $order_direction = 'ASC')
    {
        $token = $this->getToken();
        $api = $this->api_url . '/adrive/v3/file/list';
        $data = [
            'all'                       =>  false,
            'drive_id'                  =>  $token['default_drive_id'],
            'fields'                    =>  '*',
            'limit'                     =>  $limit,
            // 名称=>name 创建时间=>created_at 更新时间=>updated_at 文件大小=>size
            'order_by'                  =>  $order_by,
            // 降序=>DESC 升序=>ASC
            'order_direction'           =>  $order_direction,
            'parent_file_id'            =>  $folder_id,
            // 文件URL过期时间 默认1600秒
            'url_expire_sec'            =>  14400,
            'image_thumbnail_process'   =>  'image/resize,w_400/format,jpeg',
            'image_url_process'         =>  'image/resize,w_1920/format,jpeg',
            'video_thumbnail_process'   =>  'video/snapshot,t_1000,f_jpg,ar_auto,w_300',
            'marker'                    =>  $next_marker ? $next_marker : '',
        ];
        $header['Authorization'] = $token['token_type'] . ' ' . $token['access_token'];
        $res = \xyg\http\Request::instance()->curl($api, json_encode($data), array_merge($this->request_header, $header));
        if ( $res['httpCode'] === 200 ) {
            return $this->filterListData(json_decode($res['body'], 1));
        } else {
            return ;
        }
    }

    // 过滤文件列表数据
    public function filterListData($list)
    {
        $res = [
            'items'         =>  [],
            'next_marker'   =>  $list['next_marker'],
        ];
        foreach ( $list['items'] as $value ) {
            if ( !in_array($value['file_id'], $this->hide_mix_id, true) ) {
                $res['items'][] = [
                    'file_id'           =>  $value['file_id'],
                    'parent_file_id'    =>  $value['parent_file_id'],
                    'name'              =>  $value['name'],
                    'type'              =>  $value['type'],
                    'create_time'       =>  date('Y-m-d H:i:s', strtotime($value['created_at'])),
                    'update_time'       =>  date('Y-m-d H:i:s', strtotime($value['updated_at'])),
                    'file_extension'    =>  isset($value['file_extension']) ? $value['file_extension'] : '',
                    'ico'               =>  $value['type'] == 'folder' ? 'folder' : file_ico_format($value['file_extension']),
                    'size'              =>  isset($value['size']) ? file_size_format($value['size']) : '',
                    // 'url'               =>  isset($value['url']) ? $value['url'] : '',
                    'download_url'      =>  isset($value['download_url']) ? $value['download_url'] : '',
                    'need_pass_mix'    =>  in_array($value['file_id'], $this->need_pass_mix) ? 'yes' : 'no',
                ];
            }
        }
        return $res;
    }

    // 获取文件数据（多余）
    public function getFileData($file_id)
    {
        $token = $this->getToken();
        $api = $this->api_url . '/v2/file/get';
        $data = [
            'drive_id'        => $token['default_drive_id'],
            'file_id'         => $file_id,
            // 文件URL过期时间 默认1600秒
            'url_expire_sec'  =>  14400,
        ];
        $header['Authorization'] = $token['token_type'] . ' ' . $token['access_token'];
        $res = \xyg\http\Request::instance()->curl($api, json_encode($data), array_merge($this->request_header, $header));
        if ( $res['httpCode'] === 200 ) {
            return $this->filterFileData(json_decode($res['body'], 1));
        }
    }

    // 过滤文件数据（多余）
    public function filterFileData($file)
    {
        return [
            'file_id'           =>  $file['file_id'],
            'parent_file_id'    =>  $file['parent_file_id'],
            'name'              =>  $file['name'],
            'type'              =>  $file['type'],
            'created_at'        =>  date('Y-m-d H:i:s', strtotime($file['created_at'])),
            'updated_at'        =>  date('Y-m-d H:i:s', strtotime($file['updated_at'])),
            'file_extension'    =>  isset($file['file_extension']) ? $file['file_extension'] : '',
            'ico'               =>  $file['type'] == 'folder' ? 'folder' : file_ico_format($file['file_extension']),
            'size'              =>  isset($file['size']) ? file_size_format($file['size']) : '',
            // 'url'               =>  isset($file['url']) ? $file['url'] : '',
            'download_url'      =>  isset($file['download_url']) ? $file['download_url'] : '',
        ];
    }

    // 获取视频播放信息
    public function getVideoPlayData($file_id)
    {
        $token = $this->getToken();
        $api = $this->api_url . '/v2/file/get_video_preview_play_info';
        $data = [
            'drive_id'      =>  $token['default_drive_id'],
            'category'      =>  'live_transcoding',
            'file_id'       =>  $file_id,
            'template_id'   =>  '',
            // 文件URL过期时间 默认1600秒
            'url_expire_sec'=>  14400,
        ];
        $header['Authorization'] = $token['token_type'] . ' ' . $token['access_token'];
        $res = \xyg\http\Request::instance()->curl($api, json_encode($data), array_merge($this->request_header, $header));
        if ( $res['httpCode'] === 200 ) {
            return $this->filterVideoPlayData(json_decode($res['body'], 1));
        }
    }

    // 过滤视频播放流数据
    public function filterVideoPlayData($video)
    {
        $res = [];
        foreach ( $video['video_preview_play_info']['live_transcoding_task_list'] as $value ) {
            $res[] = [
                'name'  =>  $this->videoRateFormat($value['template_id']),
                'url'   =>  $value['url'],
            ];
        }
        return array_reverse($res);
    }

    // 视频流分辨率名称
    public function videoRateFormat($template_id)
    {
        switch ($template_id) {
            case 'LD':
                return '360P流畅';
            break;
            case 'SD':
                return '540P标清';
            break;
            case 'HD':
                return '720P高清';
            break;
            case 'FHD':
                return '1080P超清';
            break;
            default:
                return '普清';
            break;
        }
    }

    // 获取音频播放信息
    public function getAudioPlayData($file_id)
    {
        $token = $this->getToken();
        $api = $this->api_url . '/v2/databox/get_audio_play_info';
        $data = [
            'drive_id'      =>  $token['default_drive_id'],
            'file_id'       =>  $file_id,
            // 文件URL过期时间 默认1600秒
            'expire_sec'=>  14400,
        ];
        $header['Authorization'] = $token['token_type'] . ' ' . $token['access_token'];
        $res = \xyg\http\Request::instance()->curl($api, json_encode($data), array_merge($this->request_header, $header));
        if ( $res['httpCode'] === 200 ) {
            $audio = json_decode($res['body'], 1);
            return ['url' => $audio['template_list'][0]['url']];
        }
    }

    // 获取代码文件内容
    public function getCode($download_url)
    {
        $header['Content-type'] = '';
        $res = \xyg\http\Request::instance()->curl('get', $download_url, '', array_merge($this->request_header, $header));
        return $res;
    }

    // 获取文件的Path路径
    public function getFilePath($file_id)
    {
        $token = $this->getToken();
        $api = $this->api_url . '/adrive/v1/file/get_path';
        $data = [
            'drive_id'      =>  $token['default_drive_id'],
            'file_id'       =>  $file_id,
        ];
        $header['Authorization'] = $token['token_type'] . ' ' . $token['access_token'];
        $res = \xyg\http\Request::instance()->curl($api, json_encode($data), array_merge($this->request_header, $header));
        if ( $res['httpCode'] === 200 ) {
            return $this->filterFilePath(json_decode($res['body'], 1), $file_id);
        }
    }

    // 文件Path路径过滤
    public function filterFilePath($data, $file_id)
    {
        $res = [];
        foreach ( $data['items'] as $value ) {
            if ( $value['type'] == 'folder' ) {
                $res[] = [
                    'name'              =>  $value['name'],
                    'file_id'           =>  $value['file_id'],
                    'parent_file_id'    =>  $value['parent_file_id'],
                ];
            }
            // 如果当前文件ID == 初始化文件ID，文件path遍历终止循环
            if ( $value['file_id'] == $this->init_folder_id ) {
                break;
            }
        }
        return array_reverse($res);
    }

    // 文件搜索
    public function searchFile($keyword, $limit = 20, $order_by = 'updated_at DESC')
    {
        $token = $this->getToken();
        $api = $this->api_url . '/adrive/v3/file/search';
        $data = [
            'drive_id'                  =>  $token['default_drive_id'],
            'limit'                     =>  $limit,
            'order_by'                  =>  $order_by,
            'query'                     =>  'name match ###' . $keyword . '***',
            // 文件URL过期时间 默认1600秒
            'url_expire_sec'            =>  14400,
            'image_thumbnail_process'   =>  'image/resize,w_200/format,jpeg',
            'image_url_process'         =>  'image/resize,w_1920/format,jpeg',
            'video_thumbnail_process'   =>  'video/snapshot,t_1000,f_jpg,ar_auto,w_300',
        ];
        $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        $data = str_replace(['###', '***'], ['\"', '\"'], $data);
        $header['Authorization'] = $token['token_type'] . ' ' . $token['access_token'];
        $res = \xyg\http\Request::instance()->curl($api, $data, array_merge($this->request_header, $header));
        if ( $res['httpCode'] === 200 ) {
            return $this->filterListData(json_decode($res['body'], 1));
        }
    }

}
