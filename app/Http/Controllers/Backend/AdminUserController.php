<?php

namespace App\Http\Controllers\Backend;

use App\AdminUser;
use App\Department;
use App\Role;
use App\Log;
use Illuminate\Http\Request;
use Gregwar\Captcha\CaptchaBuilder;
use Gregwar\Captcha\PhraseBuilder;
use Session;
use Illuminate\Support\Facades\Cache as C;


class AdminUserController extends Controller
{
    function __construct(Request $request)
    {
        $this->pathArr = array('adminLogin', 'adminList', 'adminInfo', 'getVerifyCode', 'checkVerifyCode');
        parent::__construct($request);
    }

    //新建管理员
    function addAdmin(Request $request)
    {
        $uid = $request->input('uid');
        $username = $request->input('username');
        $password = $request->input('password');
        $realName = $request->input('real_name');
        $user = new AdminUser();
        if (!$uid) {
            $this->validate($request, [
                'username' => 'required',
                'password' => 'required',
                'email' => 'required',
                'real_name' => 'required',
                'mobile' => 'required',
                'rid' => 'required',
                'department_id' => 'required',
            ]);
            $description = '添加后台用户' . $realName;
            //判断账号重名
            $count = $user->checkName($username);
        } else {
            $description = '修改后台用户' . $user->getUsername($uid);
            //判断账号重名
            $count = $user->checkName($username, $uid);
        }
        if ($count > 0) {
            $msg['success'] = -1;
            $msg['msg'] = '账号重名';
            return json_encode($msg, JSON_UNESCAPED_UNICODE);
        }
        $fields = $request->all();
        $user = AdminUser::updateOrCreate(
            ['uid' => $uid],
            $fields
        );
        if(!$uid){
            $user->password = md5($password);
        }
        $flag = $user->save();
        if ($flag) {
            $msg['success'] = 1;
            $msg['msg'] = $description . '成功';
            $type = 1;
        } else {
            $msg['success'] = 0;
            $msg['msg'] = $description . '失败';
            $type = 0;
        }
        $log = new Log();
        $log->addLog($this->userId, $description, $msg['success'], $type);
        return json_encode($msg, JSON_UNESCAPED_UNICODE);
    }

    //管理员登录
    function login(Request $request)
    {
        $this->validate($request, [
            'username' => 'required',
            'password' => 'required',
            'verify_code' => 'required',
        ]);
        $username = $request->input('username');
        $password = $request->input('password');
        $verifyCode = $request->input('verify_code');
        $vfyCode = $request->session()->get('verify_code');
        if (strtolower($verifyCode) !== strtolower($vfyCode)) {
            $msg['msg'] = '验证码不正确';
            $msg['success'] = -1;
            return $msg;
        }
        $userInfo = AdminUser::where(['username' => $username, 'password' => md5($password)])->first();
        if (empty($userInfo) || $userInfo == null) {
            $msg['msg'] = '用户名或密码不正确';
            $msg['success'] = 0;
        } else {
            AdminUser::where('uid', $userInfo->uid)
                ->update(['last_ip' => $_SERVER["REMOTE_ADDR"], 'last_login' => date('Y-m-d H:i:s')]);
            $userAgent = $request->header('user_agent');
            $tokenStr = $userAgent . '|' . json_encode(array('user_id' => $userInfo->uid, 'username' => $userInfo->username, 'real_name' => $userInfo->real_name)) . '|' . env('APP_KEY');
            $token = base64_encode($tokenStr);
            C::put($userInfo->uid, $token, 4320);//3天
            $msg['data'] = $userInfo->uid;
            $msg['success'] = 1;
            $msg['token'] = $token;
            $msg['rid'] = $userInfo->rid;
            $msg['avatar'] = $userInfo->avatar;
            $msg['real_name'] = $userInfo->real_name;
            $description = '登录系统';
            $type = 1;
            $log = new Log();
            $log->addLog($userInfo->uid, $description, $msg['success'], $type);
        }
        return json_encode($msg, JSON_UNESCAPED_UNICODE);
    }


    //管理员列表
    function lists(Request $request)
    {
        $p = $request->input('p')??1;
        $perPage = $request->input('per_page')??15;
        $rid = $request->input('rid');
        $did = $request->input('department_id');
        $key = $request->input('key');
        $user = new AdminUser();
        $users = $user->where(function ($query) use ($rid) {
            $rid && $query->where('rid', '=', $rid);
        })->where(function ($query) use ($did) {
            $did && $query->where('department_id', '=', $did);
        })->where(function ($query) use ($key) {
            $key && $query->where('real_name', 'like', '%' . $key . '%')->orWhere('real_name', 'like', '%' . $key . '%');
        })->paginate($perPage, ['uid', 'username', 'real_name', 'email', 'mobile', 'rid', 'status', 'department_id'], 'p', $p);
        $role = new Role();
        $department = new Department();
        foreach ($users as $key => $value) {
            $users[$key]['role'] = $role->getRoleName($value->rid);
            $users[$key]['department'] = $department->getName($value->department_id);
        }
        return json_encode($users, JSON_UNESCAPED_UNICODE);
    }

    //管理员详情
    function info(Request $request)
    {
        $this->validate($request, [
            'uid' => 'required',
        ]);
        $uid = $request->input('uid');
        $user = new AdminUser();
        $role = new Role();
        $department = new Department();
        $info = $user->info($uid);
        $infoArr['username'] = $info->username;
        $infoArr['real_name'] = $info->real_name;
        $infoArr['email'] = $info->email;
        $infoArr['mobile'] = $info->mobile;
        $infoArr['rid'] = $info->rid;
        $infoArr['department_id'] = $info->department_id;
        $infoArr['moduleList'] = $role->moduleList($info->rid);
        return json_encode($infoArr, JSON_UNESCAPED_UNICODE);
    }

    //修改密码
    function editPassword(Request $request)
    {
        $this->validate($request, [
            'uid' => 'required',
            'password' => 'required',
        ]);
        $uid = $request->input('uid');
        $password = $request->input('password');
        $user = new AdminUser();
        $description = '重置后台用户' . $user->getUsername($uid) . '密码';
        if ($user->editPassword($uid, $password)) {
            $msg['success'] = 1;
            $msg['msg'] = $description . '成功';
            $type = 1;
        } else {
            $msg['success'] = 0;
            $msg['msg'] = $description . '修改失败';
            $type = 0;
        }
        $log = new Log();
        $log->addLog($this->userId, $description, $msg['success'], $type);
        return json_encode($msg, JSON_UNESCAPED_UNICODE);
    }

    //管理员修改自己密码
    function changePassword(Request $request)
    {
        $userId = $this->userId;
        $this->validate($request, [
            'password' => 'required',
        ]);
        $password = $request->input('password');
        $admin = new AdminUser();
        $description = $admin->getUsername($userId) . '修改密码';
        if ($admin->editPassword($userId, $password)) {
            $msg['success'] = 1;
            $msg['msg'] = $description . '成功';
            $type = 1;
        } else {
            $msg['success'] = 0;
            $msg['msg'] = $description . '失败';
            $type = 0;
        }
        $log = new Log();
        $log->addLog($this->userId, $description, $msg['success'], $type);
        return json_encode($msg, JSON_UNESCAPED_UNICODE);
    }

    //启用禁用状态
    function changeStatus(Request $request)
    {
        $this->validate($request, [
            'uid' => 'required',
            'status' => 'required',
        ]);
        $uid = $request->input('uid');
        $status = $request->input('status');
        $user = new AdminUser();
        $flag = $user->changeStatus($uid, $status);
        if ($status == 1) {
            $description = '启用用户' . $user->getUsername($uid);
        } else {
            $description = '禁用用户' . $user->getUsername($uid);
        }
        if ($flag) {
            $msg['success'] = 1;
            $msg['msg'] = $description . '成功';
            $type = 1;
        } else {
            $msg['success'] = 0;
            $msg['msg'] = $description . '失败';
            $type = 0;
        }
        $log = new Log();
        $log->addLog($this->userId, $description, $msg['success'], $type);
        return json_encode($msg, JSON_UNESCAPED_UNICODE);
    }

    //删除管理员
    function del(Request $request)
    {
        $this->validate($request, [
            'uid' => 'required',
        ]);
        $id = $request->input('uid');
        $user = new AdminUser();
        if ($user->del($id)) {
            $msg['success'] = 1;
            $msg['msg'] = '删除成功';
            $type = 1;
        } else {
            $msg['success'] = 0;
            $msg['msg'] = '删除失败';
            $type = 0;
        }
        $description = '删除后台用户' . $user->getUsername($id);
        $log = new Log();
        $log->addLog($this->userId, $description, $msg['success'], $type);
        return json_encode($msg, JSON_UNESCAPED_UNICODE);
    }


    //获取验证码
    function getVerifyCode(Request $request)
    {
        $phraseBuilder = new PhraseBuilder(4);
        $builder = new CaptchaBuilder(null, $phraseBuilder);
        $builder->build();
        //  return $builder->get();
        $request->session()->flash('verify_code', $builder->getPhrase());
        return response($builder->output())->header('Content-type', 'image/jpeg');
    }

    //登出
    function logOut(Request $request)
    {
        $userId = $this->userId;
        $admin = new AdminUser();
        $description = $admin->getUsername($userId) . '登出系统';
        $type = 1;
        $log = new Log();
        $log->addLog($this->userId, $description, 1, $type);
    }
}
