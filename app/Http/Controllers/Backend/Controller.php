<?php

namespace App\Http\Controllers\Backend;

use App\AdminUser;
use App\Module;
use App\Role;
use App\Log;
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
    public $userName;
    public $realName;
    public $userAgent;
    public $modules;
    protected $pathArr;
    public $modulesArr;
    public $rid;

    function __construct(Request $request)
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods:POST,GET,PUT,PATCH,DELETE");
        header('Access-Control-Allow-Headers:x-requested-with,content-type,token');
        $this->modulesArr['admin_user'] = array('adminInfo', 'addAdmin', 'changeStatus', 'editAdmin', 'delAdmin', 'editAdminPassword','changePassword');
        $this->modulesArr['role'] = array('roleInfo', 'addRole', 'delRole','roleModuleList');
        $this->modulesArr['department'] = array('departmentInfo', 'addDepartment', 'delDepartment');
        $this->modulesArr['user'] = array('userInfo', 'editUserPassword', 'changeUserStatus', 'delUser');
        $this->modulesArr['activity'] = array('activityInfo', 'addActivity', 'changeActivityStatus', 'delActivity', 'uploadImg');
        $this->modulesArr['game'] = array('gameInfo', 'addGame', 'changeGameStatus', 'delGame', 'uploadImg');
        $this->modulesArr['channel'] = array('channelInfo', 'addChannel', 'changeChannelLevel', 'delChannel', 'uploadImg');
        $this->modulesArr['topic'] = array('activities','topicInfo', 'addTopic', 'editTopic', 'delTopic', 'uploadImg','changeTopicStatus');
        $this->modulesArr['article'] = array('articleInfo', 'addArticle', 'delArticle', 'uploadImg');
        $this->modulesArr['banner'] = array('bannerInfo', 'addBanner', 'changeBannerLevel', 'delBanner', 'uploadImg');
        $this->modulesArr['userStatistics'] = array('userAttendTotal', 'newAddUser', 'userSource', 'gameStatistics', 'activityStatistics');
        $this->modulesArr['gameStatistics'] = array('regionScoreTotal', 'userScoreList', 'gameTotalList');
        $this->modulesArr['tag'] = array('addTag','delTag');

        if (!empty($this->pathArr) && !in_array($request->path(), $this->pathArr)) {
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
//                $msg['success'] = -2;
//                $msg['msg'] = '浏览器不一致';
//                echo json_encode($tokenArr, JSON_UNESCAPED_UNICODE);
//                echo json_encode($msg, JSON_UNESCAPED_UNICODE);
//                exit();
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
                $this->userName = $userArr->username;
                $this->realName = $userArr->real_name??'';
                $user = new AdminUser();
                $info = $user->info($this->userId);
                $rid = $info->rid;
                $this->rid = $rid;
                $role = new Role();
                $moduleList = $role->moduleList($rid);
                $mArr = array();
                foreach ($moduleList as $key => $value) {
                    if (array_key_exists($value['flag'], $this->modulesArr)) {
                        $mArr = array_merge($mArr, $this->modulesArr[$value['flag']]);
                    }
                }
                $url = $request->url();
                $urlArr = parse_url($url);
                $currentUrl = substr($urlArr['path'], 1, strlen($urlArr['path']) - 1);
                if (!in_array($currentUrl, $mArr)) {
                    $msg['success'] = -4;
                    $msg['msg'] = '无该操作权限';
                    $module = new Module();
                    $moduleInfo = $module->info($currentUrl);
                    if(!empty($moduleInfo)){
                        $log = new Log();
                        $log->addLog($this->userId,$user->getUsername($this->userId).$moduleInfo->mod_name,0,2);
                    }
                    echo json_encode($msg, JSON_UNESCAPED_UNICODE);
                    exit();
                }
            } else {
                $msg['msg'] = '请登录';
                $msg['success'] = -5;
                echo json_encode($msg, JSON_UNESCAPED_UNICODE);
                exit();
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
