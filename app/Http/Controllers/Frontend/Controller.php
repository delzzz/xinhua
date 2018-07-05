<?php

namespace App\Http\Controllers\Frontend;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache as C;


class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public $userId;
    public $mobile;
    public $userAgent;
    protected $pathArr;

    function __construct(Request $request)
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods:POST,GET,PUT,PATCH,DELETE");
        header('Access-Control-Allow-Headers:x-requested-with,content-type,token');
        if (!empty($this->pathArr)&&!in_array($request->path(), $this->pathArr)) {
            $token = $request->header('token');
            if (empty($token) || $token == NULL) {
                $msg['msg'] = 'token为空';
                $msg['success'] = -5;
                echo json_encode($msg, JSON_UNESCAPED_UNICODE);
                exit();
            }
            $tokenStr = base64_decode($token);
            $tokenArr = explode('|', $tokenStr);
            try {
                $userArr = json_decode($tokenArr[1]);
            } catch (\Exception $e) {
                $msg['msg'] = 'token异常';
                $msg['success'] = -5;
                echo json_encode($msg, JSON_UNESCAPED_UNICODE);
                exit();
            }
            if ($tokenArr[0] !== $request->header('user_agent')) {
                $msg['success'] = -2;
                $msg['msg'] = '浏览器不一致';
                echo json_encode($msg, JSON_UNESCAPED_UNICODE);
                exit();
            } else {
                $this->userAgent = $tokenArr[0];
            }
            if (!empty($userArr->user_id)) {
                $cache_token = C::get($userArr->user_id);
                if(!$cache_token){
                    $msg['msg'] = 'token过期';
                    $msg['success'] = -5;
                    echo json_encode($msg, JSON_UNESCAPED_UNICODE);
                    exit();
                }
                $this->userId = $userArr->user_id;
            }
            else{
                $msg['msg'] = '请登录';
                $msg['success'] = 0;
                echo json_encode($msg, JSON_UNESCAPED_UNICODE);
                exit();
            }
            if(!empty($userArr->mobile)){
                $this->mobile = $userArr->mobile;
            }

            if ($tokenArr[2] !== env('APP_KEY')) {
                $msg['success'] = -3;
                $msg['msg'] = 'APP_KEY不一致';
                echo json_encode($msg, JSON_UNESCAPED_UNICODE);
                exit();
            }
        }

    }

}
