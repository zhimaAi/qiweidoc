<?php

namespace PHPSTORM_META {

    expectedArguments(
        \Psr\Http\Message\MessageInterface::hasHeader(),
        0,
        argumentsSet('\Yiisoft\Http\Header::HEADERS'),
    );

    expectedArguments(
        \Psr\Http\Message\MessageInterface::getHeader(),
        0,
        argumentsSet('\Yiisoft\Http\Header::HEADERS'),
    );

    expectedArguments(
        \Psr\Http\Message\MessageInterface::getHeaderLine(),
        0,
        argumentsSet('\Yiisoft\Http\Header::HEADERS'),
    );

    expectedArguments(
        \Psr\Http\Message\MessageInterface::withHeader(),
        0,
        argumentsSet('\Yiisoft\Http\Header::HEADERS'),
    );

    expectedArguments(
        \Psr\Http\Message\MessageInterface::withAddedHeader(),
        0,
        argumentsSet('\Yiisoft\Http\Header::HEADERS'),
    );

    expectedArguments(
        \Psr\Http\Message\MessageInterface::withoutHeader(),
        0,
        argumentsSet('\Yiisoft\Http\Header::HEADERS'),
    );

    registerArgumentsSet(
        '\Yiisoft\Http\Header::HEADERS',
        // Authentication
        \Yiisoft\Http\Header::WWW_AUTHENTICATE,
        \Yiisoft\Http\Header::AUTHORIZATION,
        \Yiisoft\Http\Header::PROXY_AUTHENTICATE,
        \Yiisoft\Http\Header::PROXY_AUTHORIZATION,
        // Caching
        \Yiisoft\Http\Header::AGE,
        \Yiisoft\Http\Header::CACHE_CONTROL,
        \Yiisoft\Http\Header::CLEAR_SITE_DATA,
        \Yiisoft\Http\Header::EXPIRES,
        \Yiisoft\Http\Header::PRAGMA,
        \Yiisoft\Http\Header::WARNING,
        // Conditionals
        \Yiisoft\Http\Header::LAST_MODIFIED,
        \Yiisoft\Http\Header::ETAG,
        \Yiisoft\Http\Header::IF_MATCH,
        \Yiisoft\Http\Header::IF_NONE_MATCH,
        \Yiisoft\Http\Header::IF_MODIFIED_SINCE,
        \Yiisoft\Http\Header::IF_UNMODIFIED_SINCE,
        \Yiisoft\Http\Header::IF_RANGE,
        \Yiisoft\Http\Header::VARY,
        // Connection management
        \Yiisoft\Http\Header::CONNECTION,
        \Yiisoft\Http\Header::KEEP_ALIVE,
        // Content negotiation
        \Yiisoft\Http\Header::ACCEPT,
        \Yiisoft\Http\Header::ACCEPT_CHARSET,
        \Yiisoft\Http\Header::ACCEPT_ENCODING,
        \Yiisoft\Http\Header::ACCEPT_LANGUAGE,
        // Controls
        \Yiisoft\Http\Header::EXPECT,
        \Yiisoft\Http\Header::MAX_FORWARDS,
        // Cookies
        \Yiisoft\Http\Header::COOKIE,
        \Yiisoft\Http\Header::SET_COOKIE,
        // CORS
        \Yiisoft\Http\Header::ACCESS_CONTROL_ALLOW_ORIGIN,
        \Yiisoft\Http\Header::ACCESS_CONTROL_ALLOW_CREDENTIALS,
        \Yiisoft\Http\Header::ACCESS_CONTROL_ALLOW_HEADERS,
        \Yiisoft\Http\Header::ACCESS_CONTROL_ALLOW_METHODS,
        \Yiisoft\Http\Header::ACCESS_CONTROL_EXPOSE_HEADERS,
        \Yiisoft\Http\Header::ACCESS_CONTROL_MAX_AGE,
        \Yiisoft\Http\Header::ACCESS_CONTROL_REQUEST_HEADERS,
        \Yiisoft\Http\Header::ACCESS_CONTROL_REQUEST_METHOD,
        \Yiisoft\Http\Header::ORIGIN,
        \Yiisoft\Http\Header::TIMING_ALLOW_ORIGIN,
        // Do Not Track
        \Yiisoft\Http\Header::DNT,
        \Yiisoft\Http\Header::TK,
        // Message body information
        \Yiisoft\Http\Header::CONTENT_DISPOSITION,
        \Yiisoft\Http\Header::CONTENT_LENGTH,
        \Yiisoft\Http\Header::CONTENT_TYPE,
        \Yiisoft\Http\Header::CONTENT_ENCODING,
        \Yiisoft\Http\Header::CONTENT_LANGUAGE,
        \Yiisoft\Http\Header::CONTENT_LOCATION,
        // Proxies
        \Yiisoft\Http\Header::FORWARDED,
        \Yiisoft\Http\Header::VIA,
        // Redirects
        \Yiisoft\Http\Header::LOCATION,
        // Request context
        \Yiisoft\Http\Header::FROM,
        \Yiisoft\Http\Header::HOST,
        \Yiisoft\Http\Header::REFERER,
        \Yiisoft\Http\Header::REFERRER_POLICY,
        \Yiisoft\Http\Header::USER_AGENT,
        // Response context
        \Yiisoft\Http\Header::ALLOW,
        \Yiisoft\Http\Header::SERVER,
        // Range requests
        \Yiisoft\Http\Header::ACCEPT_RANGES,
        \Yiisoft\Http\Header::RANGE,
        \Yiisoft\Http\Header::CONTENT_RANGE,
        //Security
        \Yiisoft\Http\Header::CROSS_ORIGIN_EMBEDDER_POLICY,
        \Yiisoft\Http\Header::CROSS_ORIGIN_OPENER_POLICY,
        \Yiisoft\Http\Header::CROSS_ORIGIN_RESOURCE_POLICY,
        \Yiisoft\Http\Header::CONTENT_SECURITY_POLICY,
        \Yiisoft\Http\Header::CONTENT_SECURITY_POLICY_REPORT_ONLY,
        \Yiisoft\Http\Header::EXPECT_CT,
        \Yiisoft\Http\Header::FEATURE_POLICY,
        \Yiisoft\Http\Header::STRICT_TRANSPORT_SECURITY,
        \Yiisoft\Http\Header::UPGRADE_INSECURE_REQUESTS,
        \Yiisoft\Http\Header::X_CONTENT_TYPE_OPTIONS,
        \Yiisoft\Http\Header::X_FRAME_OPTIONS,
        \Yiisoft\Http\Header::X_PERMITTED_CROSS_DOMAIN_POLICIES,
        \Yiisoft\Http\Header::X_POWERED_BY,
        \Yiisoft\Http\Header::X_XSS_PROTECTION,
        \Yiisoft\Http\Header::SEC_FETCH_SITE,
        \Yiisoft\Http\Header::SEC_FETCH_MODE,
        \Yiisoft\Http\Header::SEC_FETCH_USER,
        \Yiisoft\Http\Header::SEC_FETCH_DEST,
        // Transfer coding
        \Yiisoft\Http\Header::TRANSFER_ENCODING,
        \Yiisoft\Http\Header::TE,
        \Yiisoft\Http\Header::TRAILER,
        // WebSockets
        \Yiisoft\Http\Header::SEC_WEBSOCKET_KEY,
        \Yiisoft\Http\Header::SEC_WEBSOCKET_EXTENSIONS,
        \Yiisoft\Http\Header::SEC_WEBSOCKET_ACCEPT,
        \Yiisoft\Http\Header::SEC_WEBSOCKET_PROTOCOL,
        \Yiisoft\Http\Header::SEC_WEBSOCKET_VERSION,
        // Other
        \Yiisoft\Http\Header::ALT_SVC,
        \Yiisoft\Http\Header::DATE,
        \Yiisoft\Http\Header::LARGE_ALLOCATION,
        \Yiisoft\Http\Header::LINK,
        \Yiisoft\Http\Header::RETRY_AFTER,
        \Yiisoft\Http\Header::SERVER_TIMING,
        \Yiisoft\Http\Header::SERVICE_WORKER_ALLOWED,
        \Yiisoft\Http\Header::SOURCEMAP,
        \Yiisoft\Http\Header::UPGRADE,
        \Yiisoft\Http\Header::X_DNS_PREFETCH_CONTROL,
        // Pagination
        \Yiisoft\Http\Header::X_PAGINATION_LIMIT,
        \Yiisoft\Http\Header::X_PAGINATION_CURRENT_PAGE,
        \Yiisoft\Http\Header::X_PAGINATION_TOTAL_PAGES,
        \Yiisoft\Http\Header::X_PAGINATION_TOTAL_COUNT
    );
}
