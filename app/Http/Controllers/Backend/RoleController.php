<?php

namespace App\Http\Controllers\Backend;

use App\Role;
use App\Module;
use App\Log;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    function __construct(Request $request)
    {
        $this->pathArr = array('roleList');
        parent::__construct($request);
    }

    //角色列表
    function lists(Request $request)
    {
        $role = new Role();
        $roleList = $role->lists();
        return json_encode($roleList, JSON_UNESCAPED_UNICODE);
    }

    //角色详情
    function info(Request $request)
    {
        $this->validate($request, [
            'rid' => 'required',
        ]);
        $rid = $request->input('rid');
        $role = new Role();
        $info = $role->info($rid);
        $roleInfo['rid'] = $rid;
        $roleInfo['rname'] = $info['rname'];
        $roleInfo['description'] = $info['description'];
        $roleModuleList = $role->moduleList($rid);
        if(!empty($roleModuleList)){
            foreach ($roleModuleList as $key => $value){
                $modList[] = $value['mid'];
            }
        }
        $roleInfo['moduleList'] = $modList??[];
        return json_encode($roleInfo, JSON_UNESCAPED_UNICODE);
    }

    //角色添加/修改
    function add(Request $request)
    {
        $rid = $request->input('rid');
        $modules = $request->input('modules');
        $rname = $request->input('rname');
        $fields = $request->all();
        $role = new Role();
        if (!$rid) {
            $this->validate($request, [
                'rname' => 'required',
                'modules' => 'required'
            ]);
            $description = '添加岗位'.$rname;
        } else {
            $description = '修改岗位'.$role->getRoleName($rid);
        }
        if ($role->checkRoleName($rname,$rid)) {
            $msg['success'] = -1;
            $msg['msg'] = '岗位名已存在';
            return json_encode($msg, JSON_UNESCAPED_UNICODE);
        } else {
            $flag = $role->add($rid, $fields);
            if ($flag) {
                if ($modules !== '' && !empty($modules)) {
                    $role->addModule($flag['rid'], $modules);
                }
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
    }

    //角色删除
    function del(Request $request)
    {
        $this->validate($request, [
            'rid' => 'required',
        ]);
        $rid = $request->input('rid');
        $role = new Role();
        $description = '删除岗位'.$role->getRoleName($rid);
        if ($role->del($rid)) {
            $msg['success'] = 1;
            $msg['msg'] = $description.'成功';
            $type = 1;
        } else {
            $msg['success'] = 0;
            $msg['msg'] = $description.'失败';
            $type = 0;
        }
        $log = new Log();
        $log->addLog($this->userId, $description, $msg['success'], $type);
        return json_encode($msg, JSON_UNESCAPED_UNICODE);
    }

    //角色的模块列表
    function moduleList(Request $request)
    {
        $role = new Role();
        $moduleList = $role->moduleList($this->rid);
        return json_encode($moduleList, JSON_UNESCAPED_UNICODE);
    }


}
