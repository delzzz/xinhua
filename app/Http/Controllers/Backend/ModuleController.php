<?php

namespace App\Http\Controllers\Backend;
use App\Module;
use Illuminate\Http\Request;

class ModuleController extends Controller
{
    //模块列表
    function lists(Request $request)
    {
        $module = new Module();
        $moduleList = $module->lists();
        echo json_encode($moduleList,JSON_UNESCAPED_UNICODE);
    }

    //模块添加/修改
    function add(Request $request){
        $mid = $request->input('mid');
        $fields = $request->all();
        if (!$mid){
            $this->validate($request, [
                'mod_name' => 'required',
                'mod_url' => 'required',
                'mod_path' => 'required',
                'all_path' => 'required',
            ]);
        }
        $module = new Module();
        if ($module->add($mid,$fields)) {
            $msg['success'] = 1;
            $msg['msg'] = '操作成功';
        } else {
            $msg['success'] = 0;
            $msg['msg'] = '操作失败';
        }
        echo json_encode($msg, JSON_UNESCAPED_UNICODE);
    }

    //模块删除
    function del(Request $request){
        $this->validate($request, [
            'mid' => 'required',
        ]);
        $mid = $request->input('mid');
        $module = new Module();
        if ($module->del($mid)){
            $msg['success'] = 1;
            $msg['msg'] = '删除成功';
        }
        else{
            $msg['success'] = 0;
            $msg['msg'] = '删除失败';
        }
        echo json_encode($msg,JSON_UNESCAPED_UNICODE);
    }


}
