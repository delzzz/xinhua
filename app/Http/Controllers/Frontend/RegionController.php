<?php

namespace App\Http\Controllers\Frontend;
use Illuminate\Http\Request;
use App\User;
use App\Region;


class RegionController extends Controller
{
    //根据parent_id查询地区列表
    function lists(Request $request)
    {
        $parentId = $request->input('parent_id');
        $region = Region::where('parent_id',$parentId)->get();
        echo json_encode($region,JSON_UNESCAPED_UNICODE);
    }




}
