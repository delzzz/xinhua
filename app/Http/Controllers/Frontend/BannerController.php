<?php

namespace App\Http\Controllers\Frontend;
use Illuminate\Http\Request;
use App\Banner;


class BannerController extends Controller
{
    //banner列表
    function lists(Request $request)
    {
        $banner = new Banner();
        $bannerList = $banner->lists();
        echo json_encode($bannerList??array(),JSON_UNESCAPED_UNICODE);
    }

}
