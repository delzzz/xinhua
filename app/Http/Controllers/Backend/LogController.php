<?php

namespace App\Http\Controllers\Backend;
use App\Log;
use Illuminate\Http\Request;

class LogController extends Controller
{
    //日志列表
    function lists(Request $request)
    {
        $p = $request->input('p')??1;
        $perPage = $request->input('per_page')??15;
        $type = $request->input('type');
        $key = $request->input('key');
        $log = new Log();
        $logs = $log->join('admin_user','admin_log.uid','admin_user.uid')->where(function ($query) use ($key) {
            $key && $query->where('real_name', 'like', '%' . $key . '%');
        })->where(function ($query) use ($type) {
            if($type!==null){
                $query->where('type',$type);
            }
        })->where('admin_log.created_at','>=',date('Y-m-d H:i:s',strtotime('-1 month')))->orderBy('admin_log.created_at','desc')->paginate($perPage, ['id', 'real_name', 'type','result', 'description','admin_log.created_at'], 'p', $p);
        return json_encode($logs,JSON_UNESCAPED_UNICODE);
    }

    function insertData(){
        for ($i=5;$i<=52;$i++){
            \DB::table('role_module')->insert(['rid'=>'1','mid'=>$i,'created_at'=>date('Y-m-d H:i:s')]);
        }
    }
}
