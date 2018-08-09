<?php

namespace App\Http\Controllers\Backend;

use App\AdminUser;
use Illuminate\Http\Request;
use App\Tag;
use App\Log;


class TagController extends Controller
{
    function __construct(Request $request)
    {
        $this->pathArr=array('tagList','tagInfo');
        parent::__construct($request);
    }

    //标签列表
    function lists(Request $request)
    {
        $tag = new Tag();
        $tagList = $tag->lists();
        $admin = new AdminUser();
        foreach ($tagList as $key => $tag){
            $tagList[$key]['adminUser'] = $admin->getUsername($tag->uid);
        }
        return json_encode($tagList, JSON_UNESCAPED_UNICODE);
    }

    //新增/修改标签
    function add(Request $request)
    {
        $id = $request->input('id');
        $fields = $request->all();
        $fields['uid'] = $this->userId;
        if(!$id){
            $this->validate($request, [
                'tag_name' => 'required',
                'color' => 'required',
            ]);
            $description = '添加标签'.$request->input('tag_name');
        }
        else{
            $tag = new Tag();
            $info = $tag->getInfo($id);
            $description = '修改标签'.$info->tag_name;
        }
        $tag = Tag::updateOrCreate(
            ['id' => $id],
            $fields
        );
        if ($tag) {
            $msg['success'] = 1;
            $msg['data'] = $tag;
            $msg['msg'] = $description.'成功';
            $type = 1;
        } else {
            $msg['success'] = 0;
            $msg['msg'] = $description.'失败';
            $type = 0;
        }
        $log = new Log();
        $log->addLog($this->userId,$description,$msg['success'],$type);
        return json_encode($msg, JSON_UNESCAPED_UNICODE);
    }

    //删除标签
    function del(Request $request){
        $this->validate($request, [
            'id' => 'required',
        ]);
        $id = $request->input('id');
        $tag = new Tag();
        $info = $tag->getInfo($id);
        $description = '删除菜单'.$info->tag_name;
        if ($tag->del($id)){
            $msg['success'] = 1;
            $msg['msg'] = $description.'成功';
            $type = 1;
        }
        else{
            $msg['success'] = 0;
            $msg['msg'] = $description.'失败';
            $type = 0;
        }
        $log = new Log();
        $log->addLog($this->userId,$description,$msg['success'],$type);
        return json_encode($msg,JSON_UNESCAPED_UNICODE);
    }

    //标签信息
    function info(Request $request){
        $this->validate($request, [
            'id' => 'required',
        ]);
        $id = $request->input('id');
        $tag = new Tag();
        return $tag->getInfo($id);
    }
}
