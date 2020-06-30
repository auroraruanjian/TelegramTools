<?php

namespace Amp\Websocket;

final class Code
{
    const NORMAL_CLOSE = 1000;
    const GOING_AWAY = 1001;
    const PROTOCOL_ERROR = 1002;
    const UNACCEPTABLE_TYPE = 1003;
    // 1004 reserved and unused.
    const NONE = 1005;
    const ABNORMAL_CLOSE = 1006;
    const INCONSISTENT_FRAME_DATA_TYPE = 1007;
    const POLICY_VIOLATION = 1008;
    const MESSAGE_TOO_LARGE = 1009;
    const EXPECTED_EXTENSION_MISSING = 1010;
    const UNEXPECTED_SERVER_ERROR = 1011;
    const SERVICE_RESTARTING = 1012;
    const TRY_AGAIN_LATER = 1013;
    const BAD_GATEWAY = 1014;
    const TLS_HANDSHAKE_FAILURE = 1015;
    /**
     * @codeCoverageIgnore Class cannot be instigated.
     */
    private function __construct()
    {
        // no instances allowed
    }
}