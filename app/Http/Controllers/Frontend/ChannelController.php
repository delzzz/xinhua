<?php

namespace App\Http\Controllers\Frontend;

use Illuminate\Http\Request;
use App\Channel;


class ChannelController extends Controller
{
    function __construct(Request $request)
    {
        //$this->pathArr=array('channelList','delChannel');
        //parent::__construct($request);
    }

    //菜单列表
    function lists(Request $request)
    {
        $pid = $request->input('pid');
        $channel = new Channel();
        $channelList = $channel->lists($pid);
        echo json_encode($channelList, JSON_UNESCAPED_UNICODE);
    }

}
