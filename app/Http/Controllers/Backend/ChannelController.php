<?php

namespace App\Http\Controllers\Backend;

use DemeterChain\C;
use Illuminate\Http\Request;
use App\Channel;
use App\Log;


class ChannelController extends Controller
{
    function __construct(Request $request)
    {
        $this->pathArr=array('channelList');
        parent::__construct($request);
    }

    //菜单列表
    function lists(Request $request)
    {
        $pid = $request->input('pid');
        $channel = new Channel();
        $channelList = $channel->lists($pid);
        return json_encode($channelList, JSON_UNESCAPED_UNICODE);
    }

    //菜单详情
    function info(Request $request){
        $this->validate($request, [
            'id' => 'required',
        ]);
        $id = $request->input('id');
        $channel = new Channel();
        return json_encode($channel->getInfo($id),JSON_UNESCAPED_UNICODE);
    }

    //新增/修改菜单
    function add(Request $request)
    {
        $id = $request->input('id');
        if(!$id){
            $this->validate($request, [
                'name' => 'required',
                'url' => 'required',
                'pid' => 'required',
            ]);
            $description = '添加菜单'.$request->input('name');
        }
        else{
            $channel = new Channel();
            $info = $channel->getInfo($id);
            $description = '修改菜单'.$info->name;
        }
        $channel = Channel::updateOrCreate(
            ['id' => $id],
            $request->all()
        );
        if ($channel) {
            $msg['success'] = 1;
            $msg['data'] = $channel;
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

    //删除菜单
    function del(Request $request){
        $this->validate($request, [
            'id' => 'required',
        ]);
        $id = $request->input('id');
        $channel = new Channel();
        $info = $channel->getInfo($id);
        $description = '删除菜单'.$info->name;
        if ($channel->del($id)){
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

    //上下移顺序
    function changeLevel(Request $request){
        $this->validate($request, [
            'id' => 'required',
            'type'=>'required'
        ]);
        $id = $request->input('id');
        $type = $request->input('type');
        $channel = new Channel();
        if($channel->changeLevel($id,$type)){
            $msg['success'] = 1;
            $msg['msg'] = '菜单排序成功';
            $type = 1;
        }
        else{
            $msg['success'] = 0;
            $msg['msg'] = '菜单排序失败';
            $type = 0;
        }
        $description = '菜单排序';
        $log = new Log();
        $log->addLog($this->userId,$description,$msg['success'],$type);
        return json_encode($msg,JSON_UNESCAPED_UNICODE);
    }
}
