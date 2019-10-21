 <?php
define("HOT_RELOAD",false);
define("APP_PATH",__DIR__ );
define("LIB_PATH",__DIR__ . "/libs");

$http = new swoole_http_server("127.0.0.1", 9501);

$http->on("start", function ($server) {
    echo "Swoole http server is started at http://127.0.0.1:9501\n";
});
$http->on('WorkerStart', function ($server, $worker_id) {
    //注册自动加载函数
    require_once LIB_PATH."/autoloader.php"; 
    //var_dump(get_included_files()); //此数组中的文件表示进程启动前就加载了，所以无法reload    
    if(HOT_RELOAD===true)
        openHotReload($server,$worker_id);
});

$http->on("request", function ($request, $response)use ($http) {
    $response->header("Content-Type", "text/plain");

    //通过路由请求热更新 reload
	if(isset($request->get['reload']) && $request->get['reload']==1)  
        $http->reload();
    
    // 处理输出	
	startMVC($request, $response);
   
	$response->end();
});
//热更新,监视文件是否变动，执行$server->reload();
function openHotReload($server,$worker_id){
     //需要安装 inotify_init 使用inotify扩展监控文件或目录的变化
    if($worker_id == 0) {
        // 设置热更新目录
        $dir = APP_PATH;
        $list[] = $dir;
        foreach (array_diff(scandir($dir), array('.', '..')) as $item) {
                $list[] = $dir.'/'.$item;
        }

        $notify = inotify_init();
        foreach ($list as $item) {
                inotify_add_watch($notify, $item, IN_CREATE | IN_DELETE | IN_MODIFY);
        }
        swoole_event_add($notify, function () use ($notify,$server) {
                $events = inotify_read($notify);
                if (!empty($events)) {
                        // 执行swolle reload
                        $server->reload();
                }
        });
     }   
}
function startMVC($request, $response){
    $_GET = $request->get??[];
    $_POST = $request->post??[];
    $_COOKIE = $request->cookie??[];
    $_FILES = $request->files??[];
    $_SERVER = array_change_key_case($request->server??[],CASE_UPPER); 
  
    require_once "frzz.php";    
    $response->write("startMVC\n");  
    $response->write(frzzStart());    

}
$http->start();	


?>
