<?php

namespace App\Http\Controllers\Backend;
use Illuminate\Http\Request;
use App\Banner;
use App\AdminUser;
use App\Log;

class BannerController extends Controller
{
    function __construct(Request $request)
    {
        $this->pathArr = array('bannerList');
        parent::__construct($request);
    }

    //banner列表
    function lists(Request $request)
    {
        $p = $request->input('p')??1;
        $perPage = $request->input('per_page')??15;
        $key = $request->input('key');
        $banner = new Banner();
        $bannerList = array();
        $banners = $banner->where(function ($query) use ($key) {
            $key && $query->where('name', 'like', '%' . $key . '%');
        })->orderBy('level','asc')->paginate($perPage, ['id', 'name', 'picture', 'url','created_at','click','uid'], 'p', $p);
        $admin = new AdminUser();
        foreach ($banners as $key => $banner){
            $banners[$key]['adminUser'] = $admin->getUsername($banner->uid);
        }
        echo json_encode($banners,JSON_UNESCAPED_UNICODE);
    }

    //广告详情
    function info(Request $request){
        $this->validate($request, [
            'id' => 'required',
        ]);
        $id = $request->input('id');
        $banner = new Banner();
        $info = $banner->getInfo($id);
        $admin = new AdminUser();
        $info['adminUser'] = $admin->getUsername($info->uid);
        return json_encode($info,JSON_UNESCAPED_UNICODE);
    }
    //新增/修改banner
    function add(Request $request)
    {
        $userId = $this->userId;
        $fields = $request->all();
        $id = $request->input('id');
        if(!$id){
            $this->validate($request, [
                'name' => 'required',
                'picture' => 'required',
            ]);
            $fields['uid'] = $userId;
            $description = '添加广告'.$request->input('name');
        }
        else{
            $banner = new Banner();
            $info = $banner->getInfo($id);
            $description = '修改广告'.$info->name;
        }
        $banner = Banner::updateOrCreate(
            ['id' => $id],
            $fields
        );
        if ($banner) {
            $msg['success'] = 1;
            $msg['data'] = $banner;
            $msg['msg'] = $description.'成功';
            $type = 1;
        } else {
            $msg['success'] = 0;
            $msg['msg'] = $description.'失败';
            $type = 0;
        }
        $log = new Log();
        $log->addLog($this->userId,$description,$msg['success'],$type);
        echo json_encode($msg, JSON_UNESCAPED_UNICODE);
    }

    //删除banner
    function del(Request $request){
        $this->validate($request, [
            'id' => 'required',
        ]);
        $id = $request->input('id');
        $banner = new Banner();
        $info = $banner->getInfo($id);
        $description = '删除广告'.$info->name;
        if($banner->del($id)){
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
        echo json_encode($msg,JSON_UNESCAPED_UNICODE);
    }

    //增加点击次数
    function addClick(Request $request){
        $this->validate($request, [
            'id' => 'required',
        ]);
        $id = $request->input('id');
        $banner = new Banner();
        if($banner->addClick($id)){
            $msg['success'] = 1;
            $msg['msg'] = '增加点击次数成功';
        }
        else{
            $msg['success'] = 0;
            $msg['msg'] = '增加点击次数失败';
        }
        return json_encode($msg,JSON_UNESCAPED_UNICODE);
    }

    //排序
    function changeLevel(Request $request){

        $this->validate($request, [
            'id' => 'required',
            'type'=>'required'
        ]);
        $id = $request->input('id');
        $type = $request->input('type');
        $banner = new Banner();
        if($banner->changeLevel($id,$type)){
            $msg['success'] = 1;
            $msg['msg'] = '排序成功';
            $type = 1;
        }
        else{
            $msg['success'] = 0;
            $msg['msg'] = '排序失败';
            $type = 0;
        }
        $description = '广告排序';
        $log = new Log();
        $log->addLog($this->userId,$description,$msg['success'],$type);
        return json_encode($msg,JSON_UNESCAPED_UNICODE);
    }

}
