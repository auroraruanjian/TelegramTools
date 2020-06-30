<?php

namespace Amp\Http\Client\Internal;

/** @internal */
final class HarAttributes
{
    use ForbidCloning;
    use ForbidSerialization;
    const STARTED_DATE_TIME = 'amp.http.client.har.startedDateTime';
    const SERVER_IP_ADDRESS = 'amp.http.client.har.serverIPAddress';
    const TIME_START = 'amp.http.client.har.timings.start';
    const TIME_SSL = 'amp.http.client.har.timings.ssl';
    const TIME_CONNECT = 'amp.http.client.har.timings.connect';
    const TIME_SEND = 'amp.http.client.har.timings.send';
    const TIME_WAIT = 'amp.http.client.har.timings.wait';
    const TIME_RECEIVE = 'amp.http.client.har.timings.receive';
    const TIME_COMPLETE = 'amp.http.client.har.timings.complete';
}