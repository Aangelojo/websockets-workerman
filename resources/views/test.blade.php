<!DOCTYPE html>
<html>
<head>
    <title>HTML5</title>
    <meta charset="utf-8"/>
    <script src="{{ url('jquery-2.2.3.min.js') }}"></script>
    <script>
        $(function () {
            var socket;
            var readyState = ["connecting", "connected", "closing", "closed"];
            /* 打开连接事件 */
            $("button:eq(0)").click(function () {
                try {
                    /* 连接 */
                    socket = new WebSocket("ws://172.16.100.64:20002");

                    /* 绑定事件 */
                    socket.onopen = function () {
                        $("#msg").html("连接成功...");
                    };

                    socket.onmessage = function (e) {
                        $("#msg").html($("#msg").html() + "<br />" + e.data);

                        var jsonParseData = JSON.parse(e.data);

                        console.log(jsonParseData);
                        console.log(jsonParseData.type);

                        //心跳回应
                        if(jsonParseData.type == 'ping'){
                            console.log(jsonParseData.type);
                            socket.send('{"type": "pong"}');
                        }
                    };

                    socket.onclose = function () {
                        $("#msg").html($("#msg").html() + "<br />关闭连接...");
                    };
                } catch (exception) {
                    $("#msg").html($("#msg").html() + "<br />有错误发生");
                }
            });

            /* 发送数据事件 */
            $("button:eq(1)").click(function () {
                /* 检查文本框是否为空 */
                if ($("#data").val() == "") {
                    alert("请输入数据！");
                    return;
                }

                try {
                    socket.send($("#data").val());
                    $("#msg").html($("#msg").html() + "<br />发送数据：" + $("#data").val());
                } catch (exception) {
                    $("#msg").html($("#msg").html() + "<br />发送数据出错");
                }

                /* 清空文本框 */
                $("#data").val("");
            });

            /* 断开连接 */
            $("button:eq(2)").click(function () {
                socket.close();
            });
        });
    </script>
</head>

<body>

{{--<h1>WebSocket示例</h1>--}}
<input type="text" id="data" style="width: 30%; height: 60px;"/>

<div style="padding: 5px;">
    <button>打开连接</button>
    <button>发送数据</button>
    <button>关闭连接</button>
</div>

<p id="msg"></p>

</body>
</html>