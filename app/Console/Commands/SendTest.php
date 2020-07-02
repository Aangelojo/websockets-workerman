<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Workerman\Connection\AsyncTcpConnection;
use Workerman\Worker;
use Workerman\Lib\Timer;

class SendTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:SendTest';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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

        $worker = new Worker('websocket://172.16.100.64:20002');
        // 进程启动后定时推送数据给客户端
        $worker->onWorkerStart = function($worker){
            Timer::add(1, function()use($worker){
                foreach($worker->connections as $connection) {
                    $connection->send('hello');
                    echo 'send success';
                }
            });
        };
        Worker::runAll();

    }
}
