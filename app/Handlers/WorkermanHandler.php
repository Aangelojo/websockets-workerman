<?php


namespace App\Handlers;

use Workerman\Lib\Timer;

// 心跳间隔10秒
define('HEARTBEAT_TIME', 60);

class WorkermanHandler
{

    // 处理客户端连接
    public function onConnect($connection)
    {
//        echo "new connection from ip " . $connection->getRemoteIp() . "\n";

        //判断是否设置了UID
        if(!isset($connection->uid)){
            //给用户分配一个UID
            $connection->uid = $this->random_string();
            //保存用户的uid
//            $text_worker->uidConnections["{$connection->uid}"] = $connection;
            $connection->uidConnections["{$connection->uid}"] = $connection;
            //向用户返回创建成功的信息
            $connection->send("用户:[{$connection->uid}] 创建成功");
            echo '--------------------'."\n";
            print_r(json($connection->uidConnections));
        }


    }

    // 处理客户端消息
    public function onMessage($connection, $data)
    {
        // 向客户端发送hello $data
//        $connection->send('Hello, your send message is: ' . $data);

        global $text_worker;

        $connection->lastMessageTime = time();
        $data_info=json_decode($data,true);
        if(!$data_info){
            return ;
        }

        //判断业务类型
        switch($data_info['type'])
        {
            case 'login':
                //判断用户信息是否存在
                if(empty($data_info['user_id'])){
                    $connection->send("{'type':'error','msg':'非法请求'}");
                    return $connection->close();
                }
                //判断用户是否已经登录了
                $user_ids=array_column($text_worker->uidInfo,"user_id");
                if(in_array($data_info['user_id'],$user_ids)){
                    $connection->send("{'type':'error','msg':'你在其它地方已登录'}");
                    return $connection->close();
                }
                //存储用户信息
                $text_worker->uidInfo["{$connection->uid}"]=array(
                    "user_id"=>$data_info['user_id'],
                    "user_name"=>htmlspecialchars($data_info['user_name']),
                    "user_header"=>$data_info['user_header'],
                    "create_time"=>date("Y-m-d H:i"),
                );
                //返回数据
                if($data_info['to_uid'] == "all"){
                    $return_data=array(
                        "type"=>"login",
                        "uid"=>$connection->uid,
                        "user_name"=>htmlspecialchars($data_info['user_name']),
                        "user_header"=>$data_info['user_header'],
                        "send_time"=>date("Y-m-d H:i",time()),
                        "user_lists"=>$text_worker->uidInfo
                    );
                    $curral_data=array(
                        "type"=>"login_uid",
                        "uid"=>$connection->uid,
                    );
                    $connection->send(json_encode($curral_data));
                    //给所有用户发送一条数据
                    foreach($text_worker->connections as $conn){
                        $conn->send(json_encode($return_data));
                    }
                }else{
                    return ;
                }
                return;
            //用户发消息
            case 'say':
                if(!isset($text_worker->uidInfo["{$connection->uid}"]) || empty($text_worker->uidInfo["{$connection->uid}"])){
                    $connection->send('{"type":"error","msg":"你已经掉线了"}');
                }
                //获取到当前用户的信息
                $user_info=$text_worker->uidInfo["{$connection->uid}"];

                //判断是私聊还是群聊
                if($data_info['to_uid'] != "all"){
                    //私聊
                    $return_data=array(
                        "type"=>"say",
                        "from_uid"=>$connection->uid,
                        "from_user_name"=>$user_info['user_name'],
                        "from_user_header"=>$user_info['user_header'],
                        "to_uid"=>$data_info['to_uid'],
                        "content"=>nl2br(htmlspecialchars($data_info['content'])),
                        "send_time"=>date("Y-m-d H:i")
                    );
                    if($data_info['to_uid'] == $connection->uid){
                        $connection->send(json_encode($return_data));
                        return;
                    }
                    //判断用户是否存在，并向对方发送数据
                    if(isset($text_worker->uidConnections["{$data_info['to_uid']}"])){
                        $to_connection=$text_worker->uidConnections["{$data_info['to_uid']}"];
                        $to_connection->send(json_encode($return_data));
                    }
                    //向你自己发送一条数据
                    $connection->send(json_encode($return_data));
                }else{
                    //群聊
                    $return_data=array(
                        "type"=>"say",
                        "from_uid"=>$connection->uid,
                        "from_user_name"=>$user_info['user_name'],
                        "from_user_header"=>$user_info['user_header'],
                        "to_uid"=>"all",
                        "content"=>nl2br(htmlspecialchars($data_info['content'])),
                        "send_time"=>date("Y-m-d H:i")
                    );
                    //向所有用户发送数据
                    foreach($text_worker->connections as $conn){
                        $conn->send(json_encode($return_data));
                    }
                }
                return;
            case "pong":
                return;
        }

    }

    // 处理客户端断开
    public function onClose($connection)
    {
//        echo "connection closed from ip {$connection->getRemoteIp()}\n";

        global $text_worker;
        $user_name=$text_worker->uidInfo[$connection->uid]['user_name'] ?? "";
        unset($text_worker->uidConnections["{$connection->uid}"]);
        unset($text_worker->uidInfo["{$connection->uid}"]);

        if(!empty($user_name)){
            $return_data=array(
                "type"=>"logout",
                "uid"=>$connection->uid,
                "user_name"=>$user_name,
                "create_time"=>date("Y-m-d H:i:s"),
            );
            foreach($text_worker->connections as $conn){
                $conn->send(json_encode($return_data));
            }
        }
    }

    public function onWorkerStart($worker)
    {
        Timer::add(1, function () use ($worker) {
            $time_now = time();
            foreach ($worker->connections as $connection) {
                // 有可能该connection还没收到过消息，则lastMessageTime设置为当前时间
                if (empty($connection->lastMessageTime)) {
                    $connection->lastMessageTime = $time_now;
                    continue;
                }
                // 上次通讯时间间隔大于心跳间隔，则认为客户端已经下线，关闭连接
                /*if ($time_now - $connection->lastMessageTime > HEARTBEAT_TIME) {
                    echo "Client ip {$connection->getRemoteIp()} timeout!!!\n";
                    $connection->close();
                }*/
            }
        });
        //每隔30秒就向客户端发送一条心跳验证
        Timer::add(50,function ()use ($worker){
            foreach ($worker->connections as $conn){
                $conn->send('{"type":"ping"}');
            }
        });
    }


    /*
     * 生成随机字符串
     * @param int $length 生成随机字符串的长度
     * @param string $char 组成随机字符串的字符串
     * @return string $string 生成的随机字符串
     */
    public function random_string($length = 32, $char = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ')
    {
        if (!is_int($length) || $length < 0) {
            return false;
        }

        $string = '';
        for ($i = $length; $i > 0; $i--) {
            $string .= $char[mt_rand(0, strlen($char) - 1)];
        }

        return $string;

    }

}