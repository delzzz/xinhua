<?php

namespace App\Http\Controllers\Frontend;

use Illuminate\Http\Request;
use App\Game;
use App\Post;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Redis;


class GameController extends Controller
{
    function __construct(Request $request)
    {
        $this->pathArr = array('gameList', 'indexList');
        parent::__construct($request);
    }

    //游戏列表
    function lists(Request $request)
    {
        $p = $request->input('p')??1;
        $perPage = $request->input('per_page')??15;
        $games = Game::with('users')->where(['status' => 1])->orderBy('level', 'asc')->orderBy('created_at', 'desc')->paginate($perPage, ['id', 'name', 'picture','url'], 'p', $p);
        foreach ($games as $key => $game) {
            $gameList[$key]['id'] = $game->id;
            $gameList[$key]['name'] = $game->name;
            $gameList[$key]['picture'] = $game->picture;
            $gameList[$key]['url'] = $game->url;
            $gameTotalList = Redis::Zrange('gameTotalRanking', 0, -1, 'WITHSCORES');
            $gameList[$key]['total'] = $gameTotalList[$game->id]??0;
        }
        echo json_encode($gameList??array(), JSON_UNESCAPED_UNICODE);
    }

    //首页游戏/活动列表
    function indexList(Request $request)
    {
        $p = $request->input('p')??1;
        $perPage = $request->input('per_page')??15;
        $post = new Post();
        $lists = $post->lists($p, $perPage);
        foreach ($lists as $key => $value) {
            $indexList[$key]['id'] = $value->itemid;
            $indexList[$key]['name'] = $value->name;
            $indexList[$key]['picture'] = $value->picture;
            $indexList[$key]['url'] = $value->url;
            $indexList[$key]['type'] = $value->type;
            if ($value->type == 'game') {
                $gameList = Redis::Zrange('gameTotalRanking', 0, -1, 'WITHSCORES');
                $total = $gameList[$value->itemid]??0;
            }
            if ($value->type == 'activity') {
                $activityList = Redis::Zrange('activityTotalRanking', 0, -1, 'WITHSCORES');
                $total = $activityList[$value->itemid]??0;
            }
            $indexList[$key]['total'] = $total;
        }
        echo json_encode($indexList??array(), JSON_UNESCAPED_UNICODE);
    }


}
