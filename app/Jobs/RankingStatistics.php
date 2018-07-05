<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Redis;
use App\Region;
use App\User;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Request;

class RankingStatistics implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {

    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //总排行榜-地区人数总榜
        Redis::del('regionTotalRanking');
        $users = User::groupBy('county')->where('county', '<>', '')->where('deleted_at',null)->selectRaw('county,count(id) as total')->get();
        $region = new Region();
        foreach ($users as $key => $value) {
            Redis::zadd('regionTotalRanking', $value->total, $region->getFullName($value->county));
        }
        //总排行榜-地区分数总榜
        Redis::del('regionScoreRanking');
        $regionScores = \DB::select('SELECT county,SUM(total) as total FROM `user` INNER JOIN (SELECT user_id,SUM(score) AS total FROM user_game GROUP BY user_id) AS score_table 
ON user.id = score_table.user_id where deleted_at is null GROUP BY user.county');
        $region = new Region();
        foreach ($regionScores as $key => $regionScore) {
            Redis::zadd('regionScoreRanking', $regionScore->total, $region->getFullName($regionScore->county));
        }
        //总排行榜-用户分数总榜
        Redis::del('userScoreRanking');
        $userScores = \DB::select('SELECT user_id,SUM(score) AS total FROM user_game ug INNER JOIN USER u ON u.id = ug.user_id WHERE u.deleted_at IS NULL GROUP BY user_id');
        foreach ($userScores as $userScore) {
            Redis::zadd('userScoreRanking', $userScore->total, $userScore->user_id);
        }
        //总排行榜-游戏人数总榜
        Redis::del('gameTotalRanking');
        $gameTotals = \DB::select('SELECT game_id,COUNT(DISTINCT(user_id)) AS total FROM user_game ug 
INNER JOIN USER u ON u.id = ug.user_id 
INNER JOIN game g ON ug.game_id = g.id WHERE u.deleted_at IS NULL AND g.deleted_at IS NULL GROUP BY game_id');
        foreach ($gameTotals as $gameTotal) {
            Redis::zadd('gameTotalRanking', $gameTotal->total, $gameTotal->game_id);
        }
        //后台-游戏分数榜
        Redis::del('gameScoreRanking');
        $gameScores = \DB::select('SELECT game_id,SUM(score) AS score FROM user_game ug INNER JOIN USER u ON u.id = ug.user_id WHERE deleted_at IS NULL GROUP BY game_id');
        foreach ($gameScores as $gameScore) {
            Redis::zadd('gameScoreRanking', $gameScore->score, $gameScore->game_id);
        }
        //活动人数总榜
        Redis::del('activityTotalRanking');
        $activityTotals = \DB::select('SELECT activity_id,COUNT(DISTINCT(user_id)) AS total FROM user_activity ua INNER JOIN USER u ON ua.`user_id` = u.id WHERE u.`deleted_at` IS NULL GROUP BY activity_id');
        foreach ($activityTotals as $activityTotal){
            Redis::zadd('activityTotalRanking',$activityTotal->total,$activityTotal->activity_id);
        }



    }
}
