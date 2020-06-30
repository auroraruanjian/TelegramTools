<?php
namespace Common\API;

use Carbon\Carbon;
use Common\API\telegram\TelegramClient;
use Common\Models\TelegramAccounts;
use Common\Models\TelegramUsers;
use danog\MadelineProto\MyTelegramOrgWrapper;
use danog\MadelineProto\API;
use Exception;
use Traversable;

class Websocket
{
    /**
     * Command命令行对象
     * @var \Illuminate\Console\Command
     */
    private $_command;

    /**
     * 用户ID对应socket连接
     * @var array
     */
    private $user_id2socket_id = [];

    public function __construct(\Illuminate\Console\Command $command)
    {
        $this->_command = $command;

        $this->_command->info("Webscocket服务启动中...");

        $_swoole = new \swoole_websocket_server('0.0.0.0', 6001);

        $socketioHandler = SocketIOParser::getInstance();

        //register socketio events
        $socketioHandler->on('connection', [$this,'onConnection']);
        $socketioHandler->on('disconnect', [$this,'onDisconnect']);
        $socketioHandler->on('message', [$this,'onMessage']);
        $socketioHandler->on('telegram_login', [$this,'onTelegramLogin']);
        $socketioHandler->on('telegram_update', [$this,'onTelegramUpdate']);
        $socketioHandler->on('telegram_get_dialogs', [$this,'onTelegramGetDialogs']);
        $socketioHandler->on('telegram_get_dialog_messages', [$this,'onTelegramGetDialogMessages']);
        $socketioHandler->on('telegram_send_messages', [$this,'onTelegramSendMessages']);
        $socketioHandler->on('telegram_add_all_group', [$this,'onTelegramAddAllGroup']);
        $socketioHandler->on('telegram_collect_user', [$this,'onCollectUser']);

        //start websocket server
        $socketioHandler->bindEngine($_swoole);

        $_swoole->start();
    }

    public function onConnection( $socket )
    {
        echo 'connection:'.$socket->id.PHP_EOL;
        //dump($socket->id);
        //dump($socket->request->get);
        // 保存socket连接
        $this->user_id2socket_id[$socket->request->get['user_id']] = $socket;
    }

    public function onDisconnect( $socket )
    {
        //dump($socket->request);
        echo 'disconnected:'.$socket->id.PHP_EOL;
        //dump($socket->id.'_____'.__LINE__);
        //$socket->close();
        //unset($this->user_id2socket_id[$socket->request->get['user_id']]);
        //$socket->in
    }

    public function onMessage( $socket , $data )
    {
        echo 'message:'.PHP_EOL; print_r($data);

        $res = $socket->madeline->messages->sendMessage(['peer' => '@xintiao', 'message' => $data]);
        //dump($res);
        $socket->emit('message', ['hello' => 'message received']);
    }

    /**
     * Telegram登录
     * @param $socket
     * @param $data     {user_id:用户ID,account_id:账户ID}
     */
    public function onTelegramLogin($socket,$data)
    {
        echo 'telegram_login:'.PHP_EOL; print_r($data);

        $phone_number = TelegramAccounts::select(['phone'])->where([
            ['user_id','=',$data['user_id']],
            ['id','=',$data['account_id']]
        ])
            ->value('phone');

        //dump($socket->madeline);
        if( empty($phone_number) ){
            $socket->emit('telegram_login_status',['status'=>false]);
        }else{
            //if( empty($socket->madeline) ){
                $telegram_model = new TelegramClient( $data['user_id'] , $phone_number );

                //$socket->madeline[$data['user_id']][$phone_number] = $telegram_model->getClient();
                $socket->madeline = $telegram_model->getClient();
                //$socket->madeline->parseSettings($socket->madeline->settings);
            //}

            //$socket->madeline->messages->sendMessage(['peer' => '@xintiao', 'message' => (string)Carbon::now()]);
            $myself = $socket->madeline->getSelf();
            //dump($socket);
            if( is_array($myself) ){
                $socket->emit('telegram_login_status',['status'=>true,'self'=>$myself]);
            }else{
                $socket->emit('telegram_login_status',['status'=>false]);
            }
        }
    }

    /**
     * Telegram消息更新
     * @param $socket
     * @param $data
     */
    public function onTelegramUpdate( $socket,$data )
    {
        echo 'telegram_update:'.PHP_EOL; print_r($data);

        $socket->madeline->API->startUpdateSystem();
        foreach ($socket->madeline->API->updates as $update) {
            dump($update);
        }
    }

    /**
     * 获取所有对话
     * @param $socket
     * @param $data     {get_photo:是否获取头像}
     */
    public function onTelegramGetDialogs( $socket,$data )
    {
        echo 'telegram_get_dialogs:'.PHP_EOL; print_r($data);

        // 获取所有对话 getFullDialogs getDialogs
        $dialogs = $socket->madeline->getFullDialogs();

        $dialog_list = [];
        foreach ($dialogs as $dialog) {
            // 获取会话详情，包含聊天成员列表
            //$pwr_chat = $MadelineProto->getPwrChat($dialog);
            $info = $socket->madeline->getInfo($dialog);
            $info['photo_path'] = '';
            //dd($info['InputPeer']);
            /*
             * 获取对话消息
            dump($MadelineProto->messages->getHistory([
                'peer' => $info['InputPeer'],
                'offset_id' => 0,
                'offset_date' => 0,
                'add_offset' => 0,
                'limit' => 100,
                'max_id' => 0,
                'min_id' => 0,
                //'hash' => [int, int],
            ]));
            */

            // 是否获取头像
            if( $data['get_photo'] > 0 ){
                $photo_peer = '';
                if( $info['type']=='user' && isset($info['User']['photo']) ){
                    $photo_peer = $info['User']['photo'];
                }else if($info['type']=='channel' && isset($info['Chat']['photo'])){
                    $photo_peer = $info['Chat']['photo'];
                }

                if( !empty($photo_peer) ){
                    $info['photo_path'] = $socket->madeline->downloadToDir($photo_peer, storage_path('public/photo'));
                }
            }
            //dump($pwr_chat);
            if( $info['type'] == 'user' ){
                TelegramUsers::updateOrCreate([
                    'user_id'   => $info['User']['id']
                ],[
                    'phone'     => $info['User']['phone'] ?? '',
                    'photo'     => $info['photo_path']
                ]);
            }

            $dialog['dialog_detail'] = $info;
            $dialog_list[] = $dialog;
        }

        $socket->emit('get_dialogs',$dialog_list);
    }

    /**
     * 获取对话消息内容
     * @param $socket
     * @param $data   {peer:对话的peer对象}
     */
    public function onTelegramGetDialogMessages( $socket,$data )
    {
        echo 'telegram_get_dialog_messages:'.PHP_EOL; print_r($data);

        /*
        $id = '';
        if( $data['input_peer']['_'] == 'inputPeerUser' ){
            $id = $data['input_peer']['user_id'];
        }else if( $data['input_peer']['_'] == 'inputPeerChannel' ){
            $id = $data['input_peer']['channel_id'];
        }

        $info = $socket->madeline->getInfo( $id );
        */
        unset($data['peer']['status']);

        $messages = $socket->madeline->messages->getHistory([
            'peer' => $data['peer'],
            'offset_id' => 0,
            'offset_date' => 0,
            'add_offset' => 0,
            'limit' => $data['limit'],
            'max_id' => $data['max_id']+1,
            'min_id' => -1,//$data['max_id']-$data['limit'],//,
            //'hash' => [int, int],
        ]);
        //dump($socket->id);
        if( $data['peer'] == "peerChannel" || $data['peer'] == "peerChat" ){
            $messages['online_user'] = $socket->madeline->messages->getOnlines([ 'peer' => $data['peer'] ]);
        }
        //dump($messages);

        $socket->emit('telegram_get_dialog_messages',$messages);
    }

    /**
     * 发送消息
     * @param $socket
     * @param $data   {peer：聊天对象的peer，message：消息内容}
     */
    public function onTelegramSendMessages($socket,$data)
    {
        echo 'telegram_send_messages:'.PHP_EOL; print_r($data);

        $updates = $socket->madeline->messages->sendMessage(['peer'=>$data['peer'],'message'=>$data['message']]);

        if( isset($updates['users']) ){
            $socket->emit('telegram_send_messages',['status'=>true,'users'=>$updates['users'],'updates'=>$updates['updates']]);
        }else{
            $socket->emit('telegram_send_messages',['status'=>false]);
        }

        //dump($updates);
        foreach($updates['updates'] as $update){
            if( $update['_'] == 'updateMessageID' ){

            }else if( $update['_'] == 'updateReadHistoryOutbox'){
                $this->onTelegramReceive($socket,['max_id'=>$update['max_id'],'peer'=>$update['peer']]);break;
            }
        }
    }

    //$MadelineProto->messages->receivedMessages(['max_id' => int, ]);
    public function onTelegramReceive($socket,$data)
    {
        echo 'telegram_receive:'.PHP_EOL; print_r($data);

        $socket->madeline->messages->receivedMessages(['max_id' => $data['max_id']]);
        $socket->madeline->messages->readHistory(['max_id' => $data['max_id'],'peer'=>$data['peer']]);

        echo 'onTelegramReceive:'.PHP_EOL; print_r($data);
    }

    /**
     * 添加群
     * @param $socket
     * @param $data
     */
    public function onTelegramAddAllGroup($socket,$data)
    {
        echo 'telegram_add_all_group:'.PHP_EOL; print_r($data);

        foreach($data as $group){
            try{
                $info = $socket->madeline->getFullInfo($group);
                $update = $socket->madeline->channels->joinChannel(['channel' => $info['InputChannel'] ]);
                dd($update);
            }catch(\Exception $e){
                // 群不存在
                if( strpos($e->getMessage(),'This peer is not present in the internal peer') >= 0 ){

                }
            }
        }
    }

    /**
     * 收集数据
     * @param $socket
     * @param $data
     */
    public function onCollectUser($socket,$data)
    {
        echo 'telegram_collect_user:'.PHP_EOL; print_r($data);
        foreach( $data as $peer ){
            go(function () use ($socket,$peer) {
                $info = $socket->madeline->getPwrChat( $peer );
                echo '数据获取成功'.PHP_EOL;
                foreach( $info['participants'] as $participants ){
                    //dump($participants['user']);

                    TelegramUsers::updateorcreate([
                        'user_id'   => $participants['user']['id']
                    ],[
                        'username'  => $participants['user']['username']??'',
                        'phone'     => $participants['user']['phone']??'',
                        'extra'     => json_encode($participants['user']),
                    ]);
                }
                //dump($info);//$inf['participants']
                /**
                array:9 [
                "id" => 585487789
                "type" => "user"
                "first_name" => "L.V"
                "username" => "IM520"
                "verified" => false
                "restricted" => false
                "status" => array:1 [
                "_" => "userStatusRecently"
                ]
                "access_hash" => 3533961688304619251
                "bot_nochats" => false
                ]
                 */
            });
        }
        $socket->emit('telegram_collect_user',['status'=>true]);
    }

    /*
    public function run()
    {
        $socketioHandler->on('message_with_callback', function ($socket, $data, $ack = '') {
            echo 'message_with_callback:'.PHP_EOL; print_r($data);
            $ack && $ack('hello there');
        });
    }
    */
}
