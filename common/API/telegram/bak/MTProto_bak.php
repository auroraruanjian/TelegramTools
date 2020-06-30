<?php

namespace Common\API\telegram;
class MTProtoBak extends \danog\MadelineProto\MTProto
{
//    use \danog\MadelineProto\MTProtoTools\AuthKeyHandler;
//    use \danog\MadelineProto\MTProtoTools\UpdateHandler;
//    use \danog\MadelineProto\SecretChats\AuthKeyHandler;
//    use \danog\MadelineProto\VoIP\AuthKeyHandler;
//    use \danog\MadelineProto\Wrappers\Events;
//    use \danog\MadelineProto\Wrappers\Loop;

    /**
     * Config array.
     *
     * @var array
     */
    private $config = ['expires' => -1];
    /**
     * TOS info.
     *
     * @var array
     */
    private $tos = ['expires' => 0, 'accepted' => true];
    /**
     * Whether we're initing authorization.
     *
     * @var boolean
     */
    private $initing_authorization = false;
    /**
     * RSA keys.
     *
     * @var array<RSA>
     */
    private $rsa_keys = [];
    /**
     * CDN RSA keys.
     *
     * @var array
     */
    private $cdn_rsa_keys = [];
    /**
     * Diffie-hellman config.
     *
     * @var array
     */
    private $dh_config = ['version' => 0];
    /**
     * Version integer for upgrades.
     *
     * @var integer
     */
    private $v = 0;
    /**
     * Cached getdialogs params.
     *
     * @var array
     */
    private $dialog_params = ['limit' => 0, 'offset_date' => 0, 'offset_id' => 0, 'offset_peer' => ['_' => 'inputPeerEmpty'], 'count' => 0];
    /**
     * Support user ID.
     *
     * @var integer
     */
    private $supportUser = 0;
    /**
     * Call checker loop.
     *
     * @var \danog\MadelineProto\Loop\Update\PeriodicLoop
     */
    private $callCheckerLoop;
    /**
     * Autoserialization loop.
     *
     * @var \danog\MadelineProto\Loop\Update\PeriodicLoop
     */
    private $serializeLoop;

    public function web_echo_async($message = '')
    {
        $stdout = getOutputBufferStream();
        $type = request()->get('type');

        switch ($this->authorized) {
            case self::NOT_LOGGED_IN:
                if (!empty($type)) {
                    if ($type === 'phone') {
                        yield $stdout->write($this->_web_json_echo(-5,'请输入手机号码！'.$message));
                    } else {
                        yield $stdout->write($this->_web_json_echo(-6,'请输入机器人Token！'.$message));
                    }
                } else {
                    yield $stdout->write($this->_web_json_echo(-7,'请选择用户还是机器人！'.$message));
                }
                break;

            case self::WAITING_CODE:
                yield $stdout->write($this->_web_json_echo(-8,'请输入手机验证码！'.$message));
                break;

            case self::WAITING_PASSWORD:
                yield $stdout->write($this->_web_json_echo(-9,'请输入密码！'.$message));
                break;

            case self::WAITING_SIGNUP:
                yield $stdout->write($this->_web_json_echo(-10,'您的号码未注册，请输入姓名注册！'.$message));
                break;
        }
    }

    private function _web_json_echo( $code , $message ,$data = [] )
    {
        return json_encode([
            'code'      => $code,
            'message'   => $message,
            'data'      => $data
        ]);
    }
}
