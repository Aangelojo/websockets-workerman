<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Handlers\WorkermanHandler;
use Workerman\Worker;

class WorkermanCommand extends Command
{
//    private $server;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wk {action}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start a Workerman server.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        global $argv;
        $arg = $this->argument('action');
        $argv[1] = $argv[2];
        $argv[2] = isset($argv[3]) ? "-{$argv[3]}" : '';
        switch ($arg) {
            case 'start':
                $this->start();
                break;
            case 'stop':
                break;
            case 'restart':
                break;
            case 'reload':
                break;
            case 'status':
                break;
            case 'connections':
                break;
        }
    }

    private function start()
    {
        // 创建一个Worker监听20002端口，不使用任何应用层协议
        $this->server = new Worker("websocket://172.16.100.64:20002");
        // 启动4个进程对外提供服务
        $this->server->count = 4;
        $handler = app(WorkermanHandler::class);
        // 连接时回调
        $this->server->onConnect = [$handler, 'onConnect'];
        // 收到客户端信息时回调
        $this->server->onMessage = [$handler, 'onMessage'];
        // 进程启动后的回调
        $this->server->onWorkerStart = [$handler, 'onWorkerStart'];
        // 断开时触发的回调
        $this->server->onClose = [$handler, 'onClose'];

//        global $text_worker;
//
//        $text_worker = new Worker("websocket://172.16.100.64:20002");
//        $text_worker->uidConnections = array();//在线用户连接对象
//        $text_worker->uidInfo = array();//在线用户的用户信息
//
//        // 启动4个进程对外提供服务
//        $text_worker->count = 4;
//
//        $handler = app(WorkermanHandler::class);
//
//        $text_worker->onConnect = array($handler,"onConnect");
//        $text_worker->onMessage = array($handler,"onMessage");
//        $text_worker->onClose = array($handler,"onClose");
//        $text_worker->onWorkerStart = array($handler,"onWorkerStart");

        // 运行worker
        Worker::runAll();
    }

}
