<?php

namespace App\Http\Controllers;

use Common\API\telegram\MadeLineAPI;
use Common\API\telegram\TelegramClient;
use Common\Models\TelegramAccounts;
use Common\Models\TelegramUsers;
use Illuminate\Http\Request;
use DB;

class TelegramController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        set_time_limit(0);
    }

    public function getIndex(Request $request)
    {
        $telegram_accounts = TelegramAccounts::select([
            'id',
            'phone',
            DB::raw("extra"),
        ])
            ->where('user_id','=',auth()->user()->id)
            ->get();

        $telegram_accounts = $telegram_accounts->isEmpty()?[]:$telegram_accounts->toArray();

        foreach($telegram_accounts as &$account){
            $account['extra'] = json_decode($account['extra'],true);
            $account['nickname'] = ($account['extra']['first_name']??'') . ($account['extra']['last_name']??'');
            $account['status'] = !empty($account['extra'])?1:0;
            // 检查账户状态
        }

        return $this->response(1,'success',[
            'accounts'  => $telegram_accounts
        ]);
    }

    /**
     * 登录用户
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function postAuth(Request $request)
    {
        $user           = auth()->user();

        $user_id        = $user->id;
        $phone_number   = $request->get('phone_number');
        $code           = $request->get('code');
        $phone_code     = $request->get('phone_code');


        $telegram_model = new TelegramClient($user_id , $phone_number);

        // 获取或创建记录
        $telegram_account = TelegramAccounts::firstOrCreate(
            [
                'user_id'       => $user->id,
                'phone'         => $phone_number
            ],
            [
                'madeline_file' => $telegram_model->madeline_path
            ]
        );

        // 创建TG对象
        $MadelineProto = $telegram_model->getClient();

        if( isset($MadelineProto->API->authorized) ){
            if( $MadelineProto->API->authorized == $MadelineProto->API::WAITING_CODE && empty($_POST['phone_code'])){
                return $this->response(-8, '请输入手机验证码');
            }
        }

        $MadelineProto->start();

        // 第一步输入手机号码
        $_user_self = $MadelineProto->getSelf();
        if( is_array($_user_self) && $_user_self['_'] == 'user')
        {
            $telegram_account->extra = $_user_self;
            $telegram_account->save();

            return $this->response(1,'登录成功',$_user_self);
        }
    }


    /**
     * 获取所有对话
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDialogs(Request $request)
    {
        // 0不获取，1获取
        $account_id = (int)$request->get('account_id');
        $get_photo  = (int)$request->get('get_phone');

        if( empty($account_id) ){
            return $this->response(0,'账户ID不能为空！');
        }

        $phone_number = TelegramAccounts::select(['phone'])->where([
            ['user_id','=',auth()->user()->id],
            ['id','=',$account_id]
        ])
            ->value('phone');

        if( empty($phone_number) ){
            return $this->response(0,'对不起，该账户不存在！');
        }

        $telegram_model = new TelegramClient(auth()->user()->id , $phone_number);

        // 创建TG对象
        $MadelineProto = $telegram_model->getClient();
        //$MadelineProto->parseSettings($telegram_model->settings);

        // 获取所有对话 getFullDialogs getDialogs
        $dialogs = $MadelineProto->getFullDialogs();

        $dialog_list = [];
        foreach ($dialogs as $dialog) {
            // 获取会话详情，包含聊天成员列表
            //$pwr_chat = $MadelineProto->getPwrChat($dialog);
            $info = $MadelineProto->getInfo($dialog);
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
            if( $get_photo ){
                $photo_peer = '';
                if( $info['type']=='user' && isset($info['User']['photo']) ){
                    $photo_peer = $info['User']['photo'];
                }else if($info['type']=='channel' && isset($info['Chat']['photo'])){
                    $photo_peer = $info['Chat']['photo'];
                }

                if( !empty($photo_peer) ){
                    $info['photo_path'] = $MadelineProto->downloadToDir($photo_peer, storage_path('public/photo'));
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

        return $this->response(1,'success',$dialog_list);
    }

    public function postMessages(Request $request)
    {
        $account_id  = (int)$request->get('account_id');
        $input_peer  = $request->get('input_peer');

        if( empty($account_id) ){
            return $this->response(0,'账户ID不能为空！');
        }

        $phone_number = TelegramAccounts::select(['phone'])->where([
            ['user_id','=',auth()->user()->id],
            ['id','=',$account_id]
        ])
            ->value('phone');

        if( empty($phone_number) ){
            return $this->response(0,'对不起，该账户不存在！');
        }

        $telegram_model = new TelegramClient(auth()->user()->id , $phone_number);

        // 创建TG对象
        $MadelineProto = $telegram_model->getClient();

        $info = $MadelineProto->getInfo($input_peer['user_id']);

        $messages = $MadelineProto->messages->getHistory([
            'peer' => $info['InputPeer'],
            'offset_id' => 0,
            'offset_date' => 0,
            'add_offset' => 0,
            'limit' => 100,
            'max_id' => 0,
            'min_id' => 0,
            //'hash' => [int, int],
        ]);

        return $this->response(1,'success',$messages);
    }
}
