<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use App\Game;
use App\Region;


class GameStatistics implements ShouldQueue
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
        $game = new Game();
        $region = new Region();
        $gameList = $game->getList();
        foreach ($gameList as $key => $value) {
            //用户分数
            $gameUserScores = \DB::select('SELECT user_id,SUM(score) as score FROM user_game  ug INNER JOIN user u ON u.id = ug.user_id WHERE deleted_at IS NULL GROUP BY user_id,game_id HAVING game_id =? ORDER BY score DESC,user_id ASC', [$value->id]);
            //Log::info('s',['arr'=>$gameUserScores]);
            Redis::del('gameUserScoreRanking_'.$value->id);
            foreach ($gameUserScores as $k=>$gameUserScore){
                Redis::zadd('gameUserScoreRanking_'.$value->id,$gameUserScore->score,$gameUserScore->user_id);
            }
            //地区人数
            $gameRegionTotals = \DB::select('SELECT county,COUNT(DISTINCT(user.id)) AS total FROM user INNER JOIN user_game ON user.id = user_game.`user_id` WHERE deleted_at IS NULL GROUP BY county,game_id HAVING game_id=?',[$value->id]);
            Redis::del('gameRegionTotalRanking_'.$value->id);
            foreach ($gameRegionTotals as $gameRegionTotal){
                Redis::zadd('gameRegionTotalRanking_'.$value->id,$gameRegionTotal->total,$region->getFullName($gameRegionTotal->county));
            }
            //地区分数
            $gameRegionScores = \DB::select('SELECT county,SUM(total) as total FROM `user` 
            INNER JOIN (SELECT user_id,SUM(score) AS total FROM user_game GROUP BY user_id,game_id HAVING game_id=?) AS score_table 
            ON user.id = score_table.user_id where deleted_at is null GROUP BY user.county ',[$value->id]);
            Redis::del('$gameRegionScoreRanking_'.$value->id);
            foreach ($gameRegionScores as $gameRegionScore){
                Redis::zadd('$gameRegionScoreRanking_'.$value->id,$gameRegionScore->total,$region->getFullName($gameRegionScore->county));
            }
        }
    }
}
