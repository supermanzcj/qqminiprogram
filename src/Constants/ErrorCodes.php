<?php

namespace Superzc\QQMiniprogram\Constants;

class ErrorCodes
{
    public const ERROR               = -10000;
    public const INVALID_PARAMS      = -10001;
    public const SERVICE_UNAVAILABLE = -10002;

    public static function getMessage($code)
    {
        $messages = [
            self::ERROR => '',
            self::INVALID_PARAMS => '',
            self::SERVICE_UNAVAILABLE => '',
        ];

        return $messages[$code] ?? 'Error';
    }
}
