<?php

namespace App\Http\Controllers;

use danog\MadelineProto\Tools;
use Illuminate\Http\Request;
use danog\MadelineProto\Stream\MTProtoTransport\ObfuscatedStream;

class Test2Controller extends Controller
{
    use Tools;

    public function __construct()
    {

    }

    /**
     * 测试API
     */
    public function getIndex(Request $request)
    {
        return $this->index($request);
    }

    /**
     * 测试API
     */
    public function postIndex(Request $request)
    {
        return $this->index($request);
    }

    public function index(Request $request)
    {
        set_time_limit(0);

        $phone_number = $_POST['phone_number'] ?? '';
        $step = $request->get('step', 0);
        if (empty($phone_number)) {
            //return $this->response(-10,'手机号码为空');
        }
        if ($step != 1) {
            unset($_POST['phone_number']);
        }

        $settings = [
            'connection_settings'   => [
                'all'               => [
                    /*
                    'proxy'         => ObfuscatedStream::getName(),
                    'proxy_extra'   => [
                        'address' => '52.187.63.202',
                        'port' => '543',
                        'secret' => 'ee000000000000000000000000000000017777772e62616964752e636f6d'
                    ],
                    */
                    'ipv6'          => 'ipv4'
                ]
            ],
            'connection' => [
                'main' => [
                    // Main datacenters
                    'ipv4' => [
                        // ipv4 addresses
                        2 => [
                            // The rest will be fetched using help.getConfig
                            'ip_address' => '149.154.167.50',
                            'port' => 443,
                            'media_only' => false,
                            'tcpo_only' => false,
                        ],
                    ],
                ],
            ],
            /*
            'app_info' => [
                'api_id' => '123',
                'api_hash' => '11sda'
            ],
            */
            'serialization' => [
                'serialization_interval' => 30,
                'cleanup_before_serialization' => true
            ],
            'logger' => [
                'logger_param' => storage_path('public/telegram/3/') . $phone_number . '.log'
            ],
            'db' => [
                'mysql' => '',
            ]
        ];

        $MadelineProto = new \danog\MadelineProto\API(storage_path('public/telegram/3/') . $phone_number . '.madeline', $settings);

        //unset($settings['app_info']);
        //$MadelineProto->parse_settings($settings);

        //$MadelineProto->start();
        Tools::wait($this->run($MadelineProto, $settings));
    }

    private function run( $MadelineProto , $settings )
    {

        if (!isset($MadelineProto->my_telegram_org_wrapper)) {
            if (!empty($_POST['phone_number'])) {
                yield $this->_login( null,$settings );
            } else {
                yield $this->response(-2, '请输入手机号码！');
            }
        } elseif (!$MadelineProto->my_telegram_org_wrapper->logged_in()) {
            if (isset($_POST['code'])) {
                Tools::wait($this->_complete($MadelineProto->my_telegram_org_wrapper));

                if (yield $MadelineProto->my_telegram_org_wrapper->has_app_async()) {
                    return yield $MadelineProto->my_telegram_org_wrapper->get_app_async();
                }

                if (!$MadelineProto->my_telegram_org_wrapper->logged_in()) {
                    yield $this->response(-4, '请输入Code');
                } else {
                    yield $this->response(-5, 'Enter the API info');
                }
            }else if (!empty($_POST['phone_number'])) {
                Tools::wait($this->_login( $MadelineProto->my_telegram_org_wrapper,$settings ));
            }else{
                yield $this->response(-2, '请输入手机号码！');
            }
        }

        dd($MadelineProto->authorized);
        if ($MadelineProto->authorized === self::NOT_LOGGED_IN) {
            if (isset($_POST['phone_number'])) {
                //yield $this->web_phone_login_async();
            } elseif (isset($_POST['token'])) {
                //yield $this->web_bot_login_async();
            } else {
                //yield $this->web_echo_async();
            }
        } elseif ($MadelineProto->authorized === self::WAITING_CODE) {
            if (isset($_POST['phone_code'])) {
                //yield $this->web_complete_phone_login_async();
            } else {
                //yield $this->web_echo_async("You didn't provide a phone code!");
            }
        } elseif ($MadelineProto->authorized === self::WAITING_PASSWORD) {
            if (isset($_POST['password'])) {
                //yield $this->web_complete_2fa_login_async();
            } else {
                //yield $this->web_echo_async("You didn't provide the password!");
            }
        } elseif ($MadelineProto->authorized === self::WAITING_SIGNUP) {
            if (isset($_POST['first_name'])) {
                //yield $this->web_complete_signup_async();
            } else {
                //yield $this->web_echo_async("You didn't provide the first name!");
            }
        }
        if ($MadelineProto->authorized === self::LOGGED_IN) {

        }
    }

    private function _login($wrapper, $settings)
    {
        try {
            if( is_null($wrapper) ){
                $wrapper = new \danog\MadelineProto\MyTelegramOrgWrapper($settings);
            }
            yield $wrapper->login_async($_POST['phone_number']);
            yield $this->response(-1, '请输入Code');
        } catch (\Throwable $e) {

            yield $this->response(-1, 'ERROR: ' . $e->getMessage() . '. Try again.');
        }
    }

    private function _complete( $wrapper )
    {
        try {
            return yield $wrapper->complete_login_async($_POST['code']);
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            return yield $this->response(-3,$e->getMessage());
        } catch (\danog\MadelineProto\Exception $e) {
            return yield $this->response(-4,$e->getMessage());
        }
    }

    public function response(int $code ,string $msg = '',array $data = [] )
    {
        echo json_encode([
            'code'  => $code,
            'msg'   => $msg,
            'data'  => $data,
        ]);
    }
}
