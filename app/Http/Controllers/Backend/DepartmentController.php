<?php

namespace App\Http\Controllers\Backend;

use App\Department;
use App\Log;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    function __construct(Request $request)
    {
        $this->pathArr = array('departmentList');
        parent::__construct($request);
    }

    //部门列表
    function lists(Request $request)
    {
        $department = new Department();
        $departmentList = $department->lists();
        return json_encode($departmentList, JSON_UNESCAPED_UNICODE);
    }

    //部门添加/修改
    function add(Request $request)
    {
        $did = $request->input('department_id');
        $dname = $request->input('dname');
        $fields = $request->all();
        $department = new Department();
        if (!$did) {
            $this->validate($request, [
                'dname' => 'required',
            ]);
            $description = '添加部门'.$dname;
        } else {
            $department = new Department();
            $description = '修改部门'.$department->getName($did);
        }
        if ($department->checkName($dname,$did)) {
            $msg['success'] = -1;
            $msg['msg'] = '部门名已存在';
            return json_encode($msg, JSON_UNESCAPED_UNICODE);
        } else {
            $flag = $department->add($did, $fields);
            if ($flag) {
                $msg['success'] = 1;
                $msg['msg'] = $description.'成功';
                $type = 1;
            } else {
                $msg['success'] = 0;
                $msg['msg'] = $description.'失败';
                $type = 0;
            }
        }
        $log = new Log();
        $log->addLog($this->userId, $description, $msg['success'], $type);
        return json_encode($msg, JSON_UNESCAPED_UNICODE);
    }

    //部门删除
    function del(Request $request)
    {
        $this->validate($request, [
            'department_id' => 'required',
        ]);
        $did = $request->input('department_id');
        $department = new Department();
        $description = '删除部门'.$department->getName($did);
        if ($department->del($did)) {
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

    function info(Request $request)
    {
        $this->validate($request, [
            'department_id' => 'required',
        ]);
        $did = $request->input('department_id');
        $department = new Department();
        $info = $department->info($did);
        return json_encode($info, JSON_UNESCAPED_UNICODE);
    }

}
