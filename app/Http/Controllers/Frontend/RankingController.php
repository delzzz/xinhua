<?php

namespace App\Http\Controllers\Frontend;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use App\Jobs\RankingStatistics;
use App\Jobs\GameStatistics;
use App\Jobs\AdminRankingStatistics;
use App\User;
use App\Game;


class RankingController extends Controller
{

    //游戏总排行榜
    function setRanking()
    {
        $this->dispatch(new RankingStatistics());
    }

    //单个游戏排行榜
    function setGameRanking()
    {
        $this->dispatch(new GameStatistics());
    }

    //后台排行榜
    function setAdminRanking()
    {
        $this->dispatch(new AdminRankingStatistics());
    }

    //总排行榜-获取地区人数总榜
    function getRegionTotal(Request $request)
    {
        $name = $request->input('name');
        $p = $request->input('p')??1;
        $perPage = $request->input('per_page')??15;
        $i = 0;
        $j = 0;
        $ranking = array();
        if ($name !== null && $name !== '') {
            $totalList = Redis::Zrevrange('regionTotalRanking', 0, -1, 'WITHSCORES');
            foreach ($totalList as $regionName => $total) {
                if (strstr($regionName, $name) !== false) {
                    $ranking[$j]['rank'] = $i + 1;
                    $ranking[$j]['region'] = $regionName;
                    $ranking[$j]['total'] = $total;
                    $j++;
                }
                $i++;
            }
        } else {
            $totalList = Redis::Zrevrange('regionTotalRanking', 0, 14, 'WITHSCORES');
            foreach ($totalList as $regionName => $total) {
                $ranking[$i]['rank'] = $i + 1;
                $ranking[$i]['region'] = $regionName;
                $ranking[$i]['total'] = $total;
                $i++;
            }
        }
        $ranking = array_slice($ranking, ($p - 1) * $perPage, $perPage);
        echo json_encode($ranking, JSON_UNESCAPED_UNICODE);
    }

    //总排行榜-获取地区分数总榜
    function getRegionScore(Request $request)
    {
        $name = $request->input('name');
        $p = $request->input('p')??1;
        $perPage = $request->input('per_page')??15;
        $i = 0;
        $j = 0;
        $ranking = array();
        if ($name !== null && $name !== '') {
            $totalList = Redis::Zrevrange('regionScoreRanking', 0, -1, 'WITHSCORES');
            foreach ($totalList as $regionName => $score) {
                if (strstr($regionName, $name) !== false) {
                    $ranking[$j]['rank'] = $i + 1;
                    $ranking[$j]['region'] = $regionName;
                    $ranking[$j]['score'] = $score;
                    $j++;
                }
                $i++;
            }
        } else {
            $totalList = Redis::Zrevrange('regionScoreRanking', 0, 14, 'WITHSCORES');
            foreach ($totalList as $regionName => $score) {
                $ranking[$i]['rank'] = $i + 1;
                $ranking[$i]['region'] = $regionName;
                $ranking[$i]['score'] = $score;
                $i++;
            }
        }
        $ranking = array_slice($ranking, ($p - 1) * $perPage, $perPage);
        echo json_encode($ranking, JSON_UNESCAPED_UNICODE);
    }

    //总排行榜-获取用户分数总榜
    function getUserScore(Request $request)
    {
        $name = $request->input('name');
        $p = $request->input('p')??1;
        $perPage = $request->input('per_page')??15;
        $i = 0;
        $j = 0;
        $ranking = array();
        $user = new User();
        if ($name !== null && $name !== '') {
            $scoreList = Redis::Zrevrange('userScoreRanking', 0, -1, 'WITHSCORES');
            foreach ($scoreList as $userId => $score) {
                $info = $user->getInfo($userId);
                if (strstr($info->nickname, $name) !== false) {
                    $ranking[$j]['user_id'] = $userId;
                    $ranking[$j]['rank'] = $i + 1;
                    $ranking[$j]['score'] = $score;
                    $ranking[$j]['avatar'] = $info->avatar;
                    $ranking[$j]['nickname'] = $info->nickname;
                    $ranking[$j]['sex'] = $info->sex;
                    $j++;
                }
                $i++;
            }

        } else {
            $scoreList = Redis::Zrevrange('userScoreRanking', 0, 14, 'WITHSCORES');
            foreach ($scoreList as $userId => $score) {
                $info = $user->getInfo($userId);
                $ranking[$i]['user_id'] = $userId;
                $ranking[$i]['rank'] = $i + 1;
                $ranking[$i]['score'] = $score;
                $ranking[$i]['avatar'] = $info->avatar??'';
                $ranking[$i]['nickname'] = $info->nickname??'';
                $ranking[$i]['sex'] = $info->sex??'';
                $i++;
            }
        }
        $ranking = array_slice($ranking, ($p - 1) * $perPage, $perPage);
        echo json_encode($ranking, JSON_UNESCAPED_UNICODE);
    }

    //总排行榜-获取游戏人数总榜
    function getGameTotal(Request $request)
    {
        $name = $request->input('name');
        $p = $request->input('p')??1;
        $perPage = $request->input('per_page')??15;
        $i = 0;
        $j = 0;
        $gameTotalList = array();
        $game = new Game();
        if ($name !== null && $name !== '') {
            $totalList = Redis::Zrevrange('gameTotalRanking', 0, -1, 'WITHSCORES');
            foreach ($totalList as $gameId => $total) {
                $info = $game->getInfo($gameId);
                if (strstr($info->name, $name) !== false) {
                    $gameTotalList[$j]['game_id'] = $gameId;
                    $gameTotalList[$j]['rank'] = $i + 1;
                    $gameTotalList[$j]['name'] = $info->name;
                    $gameTotalList[$j]['total'] = $total;
                    $gameTotalList[$j]['picture'] = $info->picture;
                    $j++;
                }
                $i++;
            }

        } else {
            $totalList = Redis::Zrevrange('gameTotalRanking', 0, 14, 'WITHSCORES');
            $game = new Game();
            foreach ($totalList as $gameId => $total) {
                $info = $game->getInfo($gameId);
                $gameTotalList[$i]['game_id'] = $gameId;
                $gameTotalList[$i]['rank'] = $i + 1;
                $gameTotalList[$i]['name'] = $info->name;
                $gameTotalList[$i]['total'] = $total;
                $gameTotalList[$i]['picture'] = $info->picture;
                $i++;
            }
        }
        $gameTotalList = array_slice($gameTotalList, ($p - 1) * $perPage, $perPage);
        echo json_encode($gameTotalList, JSON_UNESCAPED_UNICODE);
    }


    //游戏排行榜-获取用户分数榜
    function getGameUserScore(Request $request)
    {
        $i = 0;
        $j = 0;
        $gameId = $request->input('game_id');
        $name = $request->input('name');
        $p = $request->input('p')??1;
        $perPage = $request->input('per_page')??15;
        $user = new User();
        $userScoreList = array();
        if ($name !== null && $name !== '') {
            $ranking = Redis::Zrevrange('gameUserScoreRanking_' . $gameId, 0, -1, 'WITHSCORES');
            foreach ($ranking as $userId => $score) {
                $info = $user->getInfo($userId);
                if (strstr($info->nickname, $name) !== false) {
                    $userScoreList[$j]['user_id'] = $userId;
                    $userScoreList[$j]['rank'] = $i + 1;
                    $userScoreList[$j]['score'] = $score;
                    $userScoreList[$j]['avatar'] = $info->avatar;
                    $userScoreList[$j]['nickname'] = $info->nickname;
                    $userScoreList[$j]['sex'] = $info->sex;
                    $j++;
                }
                $i++;
            }
        } else {
            $ranking = Redis::Zrevrange('gameUserScoreRanking_' . $gameId, 0, 14, 'WITHSCORES');
            foreach ($ranking as $userId => $score) {
                $info = $user->getInfo($userId);
                $userScoreList[$i]['user_id'] = $userId;
                $userScoreList[$i]['rank'] = $i + 1;
                $userScoreList[$i]['score'] = $score;
                $userScoreList[$i]['avatar'] = $info->avatar;
                $userScoreList[$i]['nickname'] = $info->nickname;
                $userScoreList[$i]['sex'] = $info->sex;
                $i++;
            }
        }
        $userScoreList = array_slice($userScoreList, ($p - 1) * $perPage, $perPage);
        echo json_encode($userScoreList, JSON_UNESCAPED_UNICODE);
    }

    //游戏排行榜-获取地区人数榜
    function getGameRegionTotal(Request $request)
    {
        $i = 0;
        $j = 0;
        $gameId = $request->input('game_id');
        $name = $request->input('name');
        $p = $request->input('p')??1;
        $perPage = $request->input('per_page')??15;
        $regionTotalList = array();
        if ($name !== null && $name !== '') {
            $ranking = Redis::Zrevrange('gameRegionTotalRanking_' . $gameId, 0, -1, 'WITHSCORES');
            foreach ($ranking as $region => $total) {
                if (strstr($region, $name) !== false) {
                    $regionTotalList[$j]['region'] = $region;
                    $regionTotalList[$j]['total'] = $total;
                    $regionTotalList[$j]['rank'] = $i + 1;
                    $j++;
                }
                $i++;
            }
        } else {
            $ranking = Redis::Zrevrange('gameRegionTotalRanking_' . $gameId, 0, 14, 'WITHSCORES');
            foreach ($ranking as $region => $total) {
                $regionTotalList[$i]['region'] = $region;
                $regionTotalList[$i]['total'] = $total;
                $regionTotalList[$i]['rank'] = $i + 1;
                $i++;
            }
        }
        $regionTotalList = array_slice($regionTotalList, ($p - 1) * $perPage, $perPage);
        echo json_encode($regionTotalList, JSON_UNESCAPED_UNICODE);
    }

    //游戏排行榜-获取地区分数榜
    function getGameRegionScore(Request $request)
    {
        $i = 0;
        $j = 0;
        $gameId = $request->input('game_id');
        $name = $request->input('name');
        $p = $request->input('p')??1;
        $perPage = $request->input('per_page')??15;
        $regionScoreList = array();
        if ($name !== null && $name !== '') {
            $ranking = Redis::Zrevrange('$gameRegionScoreRanking_' . $gameId, 0, -1, 'WITHSCORES');
            foreach ($ranking as $region => $score) {
                if (strstr($region, $name) !== false) {
                    $regionScoreList[$j]['region'] = $region;
                    $regionScoreList[$j]['score'] = $score;
                    $regionScoreList[$j]['rank'] = $i + 1;
                    $j++;
                }
                $i++;
            }
        } else {
            $ranking = Redis::Zrevrange('$gameRegionScoreRanking_' . $gameId, 0, 14, 'WITHSCORES');
            foreach ($ranking as $region => $score) {
                $regionScoreList[$i]['region'] = $region;
                $regionScoreList[$i]['score'] = $score;
                $regionScoreList[$i]['rank'] = $i + 1;
                $i++;
            }
        }
        $regionScoreList = array_slice($regionScoreList, ($p - 1) * $perPage, $perPage);
        echo json_encode($regionScoreList, JSON_UNESCAPED_UNICODE);
    }


}
