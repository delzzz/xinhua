<?php

namespace App\Http\Controllers\Backend;

use App\AdminUser;
use App\Tag;
use Illuminate\Http\Request;
use App\Game;
use App\Post;
use App\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Redis;


class GameController extends Controller
{
    function __construct(Request $request)
    {
        $this->pathArr = array('gameList', 'allGameList');
        parent::__construct($request);
    }

    //游戏列表
    function lists(Request $request)
    {
        $p = $request->input('p')??1;
        $perPage = $request->input('per_page')??15;
        $key = $request->input('key');
        $game = new Game();
        $games = $game->where(function ($query) use ($key) {
            $key && $query->where('name', 'like', '%' . $key . '%');
        })->paginate($perPage, ['id', 'name', 'picture', 'url', 'created_at', 'description', 'uid', 'status'], 'p', $p);
        $admin = new AdminUser();
        foreach ($games as $key => $game) {
            $games[$key]['adminUser'] = $admin->getUsername($game->uid);
        }
        return json_encode($games, JSON_UNESCAPED_UNICODE);
    }

    //游戏详情
    function info(Request $request)
    {
        $this->validate($request, [
            'id' => 'required']);
        $id = $request->input('id');
        $game = new Game();
        return json_encode($game->getInfo($id), JSON_UNESCAPED_UNICODE);
    }


    //新增/修改游戏
    function add(Request $request)
    {
        $userId = $this->userId;
        $fields = $request->all();
        $id = $request->input('id');
        $appId = $request->input('appid');
        $game = new Game();
        if (!$id) {
            $this->validate($request, [
                'name' => 'required',
                'picture' => 'required',
                'url' => 'required',
                'appid' => 'required',
                'appsecret' => 'required'
            ]);
            //appId重复
            if ($game->checkAppID($appId)) {
                $msg['success'] = -1;
                $msg['msg'] = 'appID重复';
                return json_encode($msg, JSON_UNESCAPED_UNICODE);
            }
            $fields['uid'] = $userId;
            $description = '添加游戏' . $request->input('name');
        } else {
            if (!empty($appId)) {
                //appId重复
                if ($game->checkAppID($appId, $id)) {
                    $msg['success'] = -1;
                    $msg['msg'] = 'appID重复';
                    return json_encode($msg, JSON_UNESCAPED_UNICODE);
                }
            }
            $info = $game->getInfo($id);
            $description = '修改游戏' . $info->name;
        }
        $game = Game::updateOrCreate(
            ['id' => $id],
            $fields
        );
        if ($game->save()) {
            $msg['success'] = 1;
            $msg['msg'] = $description . '成功';
            $type = 1;
        } else {
            $msg['success'] = 0;
            $type = 0;
            $msg['msg'] = $description . '失败';
        }
        $log = new Log();
        $log->addLog($this->userId, $description, $msg['success'], $type);
        return json_encode($msg, JSON_UNESCAPED_UNICODE);
    }

    //改变状态
    function changeStatus(Request $request)
    {
        $this->validate($request, [
            'id' => 'required',
            'status' => 'required',
        ]);
        $id = $request->input('id');
        $status = $request->input('status');
        $game = new Game();
        $info = $game->getInfo($id);
        if ($status == 1) {
            $description = '上架游戏' . $info->name;
        } else {
            $description = '下架游戏' . $info->name;
        }
        if ($game->changeStatus($id, $status)) {
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

    //删除游戏
    function del(Request $request)
    {
        $id = $request->input('id');
        $game = new Game();
        $info = $game->getInfo($id);
        $description = '删除游戏' . $info->name;
        if ($game->del($id)) {
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

    //所有游戏列表
    function allList()
    {
        $game = new Game();
        $gameList = array();
        $games = $game->getList();
        $tag = new Tag();
        //$admin = new AdminUser();
        foreach ($games as $key => $game) {
            $gameList[$key]['id'] = $game->id;
            $gameList[$key]['name'] = $game->name;
            $gameList[$key]['picture'] = $game->picture;
            $gameList[$key]['url'] = $game->url;
            $gameList[$key]['description'] = $game->description;
            $gameList[$key]['tag_info'] = $tag->getTagNames($game->tag_id);
            //$gameList[$key]['adminUser'] = $admin->getUsername($game->uid);
        }
        echo json_encode($gameList, JSON_UNESCAPED_UNICODE);
    }
}
