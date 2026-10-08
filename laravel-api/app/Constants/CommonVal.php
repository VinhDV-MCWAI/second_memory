<?php

declare(strict_types=1);

namespace App\Constants;

class CommonVal
{
    /**
     * Http status code: Information responses 100 - 199
     */
    public const HTTP_CONTINUE = 100;

    public const HTTP_SWITCHING_PROTOCOLS = 101;

    public const HTTP_PROCESSING = 102;

    public const HTTP_EARLY_HINTS = 103;

    /**
     * Http status code: Successful responses 200 - 299
     */
    public const HTTP_OK = 200;

    public const HTTP_CREATED = 201;

    public const HTTP_ACCEPTED = 202;

    public const HTTP_NON_AUTHORITATIVE_INFO = 203;

    public const HTTP_NO_CONTENT = 204;

    public const HTTP_RESET_CONTENT = 205;

    public const HTTP_PARTIAL_CONTENT = 206;

    public const HTTP_MULTI_STATUS = 207;

    public const HTTP_ALREADY_REPORT = 208;

    public const HTTP_IM_USED = 226;

    /**
     * Http status code: Redirection messages 300 - 399
     */
    public const HTTP_MULTIPLE_CHOICES = 300;

    public const HTTP_FOUND = 302;

    public const HTTP_SEE_OTHER = 303;

    public const HTTP_NOT_MODIFIED = 304;

    public const HTTP_TEMPORARY_REDIRECT = 307;

    public const HTTP_PERMANENT_REDIRECT = 308;

    /**
     * Http status code: Client error responses 400 - 499
     */
    public const HTTP_BAD_REQUEST = 400;

    public const HTTP_UNAUTHORIZED = 401;

    public const HTTP_PAYMENT_REQUIRED = 402;

    public const HTTP_FORBIDDEN = 403;

    public const HTTP_NOT_FOUND = 404;

    public const HTTP_METHOD_NOT_ALLOWED = 405;

    public const HTTP_NOT_ACCEPTABLE = 406;

    public const HTTP_PROXY_AUTHENTICATION_REQUIRED = 407;

    public const HTTP_REQUEST_TIMEOUT = 408;

    public const HTTP_CONFLICT = 409;

    public const HTTP_GONE = 410;

    public const HTTP_UNPROCESSABLE_CONTENT = 422;

    public const HTTP_TOO_MANY_REQUESTS = 429;

    /**
     * Http status code: Server error responses 500 - 599
     */
    public const HTTP_INTERNAL_SERVER_ERROR = 500;

    public const HTTP_NOT_IMPLEMENTED = 501;

    public const HTTP_BAD_GATEWAY = 502;

    public const HTTP_SERVICE_UNAVAILABLE = 503;

    public const DATE_FORMAT = 'd/m/Y';

    public const MIN_INTEGER = 0;

    public const MAX_INTEGER = 2147483647;

    public const MAX_BIG_INTEGER = 9223372036854775807;

    public const MIN_DATE = '1900-01-01';

    public const MAX_DATE = '2100-12-31';

    public const MIN_VARCHAR = 0;

    public const MAX_VARCHAR = 255;

    public const MAX_EMAIL = 254;

    public const MAX_PHONE_NUMBER = 20;

    public const MAX_TEXT = 65535;

    /**
     * Admin auth (Sanctum SPA session, ADR-0004)
     */
    public const ADMIN_GUARD = 'admin';

    public const GATE_WRITE = 'write';

    public const LOGIN_MAX_ATTEMPTS = 5;

    public const LOGIN_LOCK_SECONDS = 60 * 15; // 15 min

    /**
     * Admin master
     */
    public const ROOT = 'root';
}
