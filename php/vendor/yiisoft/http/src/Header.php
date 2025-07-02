<?php

declare(strict_types=1);

namespace Yiisoft\Http;

/**
 * HTTP headers
 *
 * @link https://developer.mozilla.org/docs/Web/HTTP/Headers
 */
final class Header
{
    // Authentication

    /**
     * Defines the authentication method that should be used to access a resource.
     *
     * @link https://tools.ietf.org/html/rfc7235#section-4.1
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/WWW-Authenticate
     */
    public const WWW_AUTHENTICATE = 'WWW-Authenticate';
    /**
     * Contains the credentials to authenticate a user-agent with a server.
     *
     * @link https://tools.ietf.org/html/rfc7235#section-4.2
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Authorization
     */
    public const AUTHORIZATION = 'Authorization';
    /**
     * Defines the authentication method that should be used to access a resource behind a proxy server.
     *
     * @link https://tools.ietf.org/html/rfc7235#section-4.3
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Proxy-Authenticate
     */
    public const PROXY_AUTHENTICATE = 'Proxy-Authenticate';
    /**
     * Contains the credentials to authenticate a user agent with a proxy server.
     *
     * @link https://tools.ietf.org/html/rfc7235#section-4.4
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Proxy-Authorization
     */
    public const PROXY_AUTHORIZATION = 'Proxy-Authorization';

    // Caching

    /**
     * The time, in seconds, that the object has been in a proxy cache.
     *
     * @link https://tools.ietf.org/html/rfc7234#section-5.1
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Age
     */
    public const AGE = 'Age';
    /**
     * Directives for caching mechanisms in both requests and responses.
     *
     * @link https://tools.ietf.org/html/rfc7234#section-5.2
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Cache-Control
     */
    public const CACHE_CONTROL = 'Cache-Control';
    /**
     * Clears browsing data (e.g. cookies, storage, cache) associated with the requesting website.
     *
     * @link https://w3c.github.io/webappsec-clear-site-data Working Draft
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Clear-Site-Data
     */
    public const CLEAR_SITE_DATA = 'Clear-Site-Data';
    /**
     * The date/time after which the response is considered stale.
     *
     * @link https://tools.ietf.org/html/rfc7234#section-5.3
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Expires
     */
    public const EXPIRES = 'Expires';
    /**
     * Implementation-specific header that may have various effects anywhere along the request-response chain.
     * Used for backwards compatibility with HTTP/1.0 caches where the Cache-Control header is not yet present.
     *
     * @link https://tools.ietf.org/html/rfc7234#section-5.4
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Pragma
     */
    public const PRAGMA = 'Pragma';
    /**
     * General warning information about possible problems.
     *
     * @link https://tools.ietf.org/html/rfc7234#section-5.5
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Warning
     */
    public const WARNING = 'Warning';

    // Conditionals

    /**
     * The last modification date of the resource, used to compare several versions of the same resource. It is less
     * accurate than ETag, but easier to calculate in some environments. Conditional requests using If-Modified-Since
     * and If-Unmodified-Since use this value to change the behavior of the request.
     *
     * @link https://tools.ietf.org/html/rfc7232#section-2.2
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Last-Modified
     */
    public const LAST_MODIFIED = 'Last-Modified';
    /**
     * A unique string identifying the version of the resource. Conditional requests using If-Match and If-None-Match
     * use this value to change the behavior of the request.
     *
     * @link https://tools.ietf.org/html/rfc7232#section-2.3
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/ETag
     */
    public const ETAG = 'ETag';
    /**
     * Makes the request conditional, and applies the method only if the stored resource matches one of the given ETags.
     *
     * @link https://tools.ietf.org/html/rfc7232#section-3.1
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/If-Match
     */
    public const IF_MATCH = 'If-Match';
    /**
     * Makes the request conditional, and applies the method only if the stored resource doesn't match any of the given
     * ETags. This is used to update caches (for safe requests), or to prevent to upload a new resource when one already
     * exists.
     *
     * @link https://tools.ietf.org/html/rfc7232#section-3.2
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/If-None-Match
     */
    public const IF_NONE_MATCH = 'If-None-Match';
    /**
     * Makes the request conditional, and expects the entity to be transmitted only if it has been modified after the
     * given date. This is used to transmit data only when the cache is out of date.
     *
     * @link https://tools.ietf.org/html/rfc7232#section-3.3
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/If-Modified-Since
     */
    public const IF_MODIFIED_SINCE = 'If-Modified-Since';
    /**
     * Makes the request conditional, and expects the entity to be transmitted only if it has not been modified after
     * the given date. This ensures the coherence of a new fragment of a specific range with previous ones, or to
     * implement an optimistic concurrency control system when modifying existing documents.
     *
     * @link https://tools.ietf.org/html/rfc7232#section-3.4
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/If-Unmodified-Since
     */
    public const IF_UNMODIFIED_SINCE = 'If-Unmodified-Since';
    /**
     * Creates a conditional range request that is only fulfilled if the given etag or date matches the remote resource.
     * Used to prevent downloading two ranges from incompatible version of the resource.
     *
     * @link https://tools.ietf.org/html/rfc7232#section-3.5
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/If-Range
     */
    public const IF_RANGE = 'If-Range';
    /**
     * Determines how to match request headers to decide whether a cached response can be used rather than requesting a
     * fresh one from the origin server.
     *
     * @link https://tools.ietf.org/html/rfc7231#section-7.1.4
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Vary
     */
    public const VARY = 'Vary';

    // Connection management

    /**
     * Controls whether the network connection stays open after the current transaction finishes.
     *
     * @link https://tools.ietf.org/html/rfc7230#section-6.1
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Connection
     */
    public const CONNECTION = 'Connection';
    /**
     * Controls how long a persistent connection should stay open.
     *
     * @link https://tools.ietf.org/html/draft-thomson-hybi-http-timeout-03#section-2
     * @link https://tools.ietf.org/html/rfc7230#appendix-A.1.2
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Keep-Alive
     */
    public const KEEP_ALIVE = 'Keep-Alive';

    // Content negotiation

    /**
     * Informs the server about the types of data that can be sent back.
     *
     * @link https://tools.ietf.org/html/rfc7231#section-5.3.2
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Accept
     */
    public const ACCEPT = 'Accept';
    /**
     * Which character encodings the client understands.
     *
     * @link https://tools.ietf.org/html/rfc7231#section-5.3.3
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Accept-Charset
     */
    public const ACCEPT_CHARSET = 'Accept-Charset';
    /**
     * The encoding algorithm, usually a compression algorithm, that can be used on the resource sent back.
     *
     * @link https://tools.ietf.org/html/rfc7231#section-5.3.4
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Accept-Encoding
     */
    public const ACCEPT_ENCODING = 'Accept-Encoding';
    /**
     * Informs the server about the human language the server is expected to send back. This is a hint and is not
     * necessarily under the full control of the user: the server should always pay attention not to override an
     * explicit user choice (like selecting a language from a dropdown).
     *
     * @link https://tools.ietf.org/html/rfc7231#section-5.3.5
     * @link https://tools.ietf.org/html/bcp47
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Accept-Language
     */
    public const ACCEPT_LANGUAGE = 'Accept-Language';

    // Controls

    /**
     * Indicates expectations that need to be fulfilled by the server to properly handle the request.
     *
     * @link https://tools.ietf.org/html/rfc7231#section-5.1.1
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Expect
     */
    public const EXPECT = 'Expect';
    /**
     * Limit the number of times the message can be forwarded through proxies or gateways.
     *
     * @link https://tools.ietf.org/html/rfc7231#section-5.1.2
     */
    public const MAX_FORWARDS = 'Max-Forwards';

    // Cookies

    /**
     * Contains stored HTTP cookies previously sent by the server with the Set-Cookie header.
     *
     * @link https://tools.ietf.org/html/rfc6265#section-5.4
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Cookie
     */
    public const COOKIE = 'Cookie';
    /**
     * Send cookies from the server to the user-agent.
     *
     * @link https://tools.ietf.org/html/rfc6265#section-4.1
     * @link https://tools.ietf.org/html/rfc6265#section-5.2
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Set-Cookie
     */
    public const SET_COOKIE = 'Set-Cookie';

    // CORS

    /**
     * Indicates whether the response can be shared.
     *
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Access-Control-Allow-Origin
     */
    public const ACCESS_CONTROL_ALLOW_ORIGIN = 'Access-Control-Allow-Origin';
    /**
     * Indicates whether the response to the request can be exposed when the credentials flag is true.
     *
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Access-Control-Allow-Credentials
     */
    public const ACCESS_CONTROL_ALLOW_CREDENTIALS = 'Access-Control-Allow-Credentials';
    /**
     * Used in response to a preflight request to indicate which HTTP headers can be used when making the actual
     * request.
     *
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Access-Control-Allow-Headers
     * @link https://developer.mozilla.org/docs/Glossary/Preflight_request
     */
    public const ACCESS_CONTROL_ALLOW_HEADERS = 'Access-Control-Allow-Headers';
    /**
     * Specifies the methods allowed when accessing the resource in response to a preflight request.
     *
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Access-Control-Allow-Methods
     */
    public const ACCESS_CONTROL_ALLOW_METHODS = 'Access-Control-Allow-Methods';
    /**
     * Indicates which headers can be exposed as part of the response by listing their names.
     *
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Access-Control-Expose-Headers
     */
    public const ACCESS_CONTROL_EXPOSE_HEADERS = 'Access-Control-Expose-Headers';
    /**
     * Indicates how long the results of a preflight request can be cached.
     *
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Access-Control-Max-Age
     */
    public const ACCESS_CONTROL_MAX_AGE = 'Access-Control-Max-Age';
    /**
     * Used when issuing a preflight request to let the server know which HTTP headers will be used when the actual
     * request is made.
     *
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Access-Control-Request-Headers
     */
    public const ACCESS_CONTROL_REQUEST_HEADERS = 'Access-Control-Request-Headers';
    /**
     * Used when issuing a preflight request to let the server know which HTTP method will be used when the actual
     * request is made.
     *
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Access-Control-Request-Method
     */
    public const ACCESS_CONTROL_REQUEST_METHOD = 'Access-Control-Request-Method';
    /**
     * Indicates where a fetch originates from.
     *
     * @link https://tools.ietf.org/html/rfc6454#section-7
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Origin
     */
    public const ORIGIN = 'Origin';
    /**
     * Specifies origins that are allowed to see values of attributes retrieved via features of the Resource Timing API,
     * which would otherwise be reported as zero due to cross-origin restrictions.
     *
     * @link https://w3c.github.io/resource-timing/#sec-timing-allow-origin Editor's Draft
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Timing-Allow-Origin
     */
    public const TIMING_ALLOW_ORIGIN = 'Timing-Allow-Origin';

    // Do Not Track

    /**
     * Expresses the user's tracking preference.
     *
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/DNT
     */
    public const DNT = 'DNT';
    /**
     * Indicates the tracking status of the corresponding response.
     *
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Tk
     */
    public const TK = 'TK';

    // Message body information

    /**
     * Indicates if the resource transmitted should be displayed inline (default behavior without the header), or if it
     * should be handled like a download and the browser should present a “Save As” dialog.
     *
     * @link https://tools.ietf.org/html/rfc2183
     * @link https://tools.ietf.org/html/rfc6266
     * @link https://tools.ietf.org/html/rfc7578
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Content-Disposition
     */
    public const CONTENT_DISPOSITION = 'Content-Disposition';
    /**
     * The size of the resource, in decimal number of bytes.
     *
     * @link https://tools.ietf.org/html/rfc7230#section-3.3.2
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Content-Length
     */
    public const CONTENT_LENGTH = 'Content-Length';
    /**
     * Indicates the media type of the resource.
     *
     * @link https://tools.ietf.org/html/rfc7231#section-3.1.1.5
     * @link https://tools.ietf.org/html/rfc7233#section-4.1 Content-Type in multipart
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Content-Type
     */
    public const CONTENT_TYPE = 'Content-Type';
    /**
     * Used to specify the compression algorithm.
     *
     * @link https://tools.ietf.org/html/rfc7231#section-3.1.2.2
     * @link https://tools.ietf.org/html/rfc2616#section-14.11
     * @link https://tools.ietf.org/html/rfc7932
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Content-Encoding
     */
    public const CONTENT_ENCODING = 'Content-Encoding';
    /**
     * Describes the human language(s) intended for the audience, so that it allows a user to differentiate according to
     * the users' own preferred language.
     *
     * @link https://tools.ietf.org/html/rfc7231#section-3.1.3.2
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Content-Language
     */
    public const CONTENT_LANGUAGE = 'Content-Language';
    /**
     * Indicates an alternate location for the returned data.
     *
     * @link https://tools.ietf.org/html/rfc7231#section-3.1.4.2
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Content-Location
     */
    public const CONTENT_LOCATION = 'Content-Location';

    // Proxies

    /**
     * Contains information from the client-facing side of proxy servers that is altered or lost when a proxy is
     * involved in the path of the request.
     *
     * @link https://tools.ietf.org/html/rfc7239#section-4
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Forwarded
     */
    public const FORWARDED = 'Forwarded';
    /**
     * Added by proxies, both forward and reverse proxies, and can appear in the request headers and the response
     * headers.
     *
     * @link https://tools.ietf.org/html/rfc7230#section-5.7.1
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Via
     */
    public const VIA = 'Via';

    // Redirects

    /**
     * Indicates the URL to redirect a page to.
     *
     * @link https://tools.ietf.org/html/rfc7231#section-7.1.2
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Location
     */
    public const LOCATION = 'Location';

    // Request context

    /**
     * Contains an Internet email address for a human user who controls the requesting user agent.
     *
     * @link https://tools.ietf.org/html/rfc7231#section-5.5.1
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/From
     */
    public const FROM = 'From';
    /**
     * Specifies the domain name of the server (for virtual hosting), and (optionally) the TCP port number on which the
     * server is listening.
     *
     * @link https://tools.ietf.org/html/rfc7230#section-5.4
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Host
     */
    public const HOST = 'Host';
    /**
     * The address of the previous web page from which a link to the currently requested page was followed.
     *
     * @link https://tools.ietf.org/html/rfc7231#section-5.5.2
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Referer
     */
    public const REFERER = 'Referer';
    /**
     * Governs which referrer information sent in the Referer header should be included with requests made.
     *
     * @see REFERER
     * @link https://w3c.github.io/webappsec-referrer-policy/#referrer-policy-header Editor's draft
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Referrer-Policy
     */
    public const REFERRER_POLICY = 'Referrer-Policy';
    /**
     * Contains a characteristic string that allows the network protocol peers to identify the application type,
     * operating system, software vendor or software version of the requesting software user agent.
     *
     * @link https://tools.ietf.org/html/rfc7231#section-5.5.3
     * @link https://tools.ietf.org/html/rfc2616#section-14.43
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/User-Agent
     */
    public const USER_AGENT = 'User-Agent';

    // Response context

    /**
     * Lists the set of HTTP request methods supported by a resource.
     *
     * @link https://tools.ietf.org/html/rfc7231#section-7.4.1
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Allow
     */
    public const ALLOW = 'Allow';
    /**
     * Contains information about the software used by the origin server to handle the request.
     *
     * @see X_POWERED_BY
     * @link https://tools.ietf.org/html/rfc7231#section-7.4.2
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Server
     */
    public const SERVER = 'Server';

    // Range requests

    /**
     * Indicates if the server supports range requests, and if so in which unit the range can be expressed.
     *
     * @link https://tools.ietf.org/html/rfc7233#section-2.3
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Accept-Ranges
     */
    public const ACCEPT_RANGES = 'Accept-Ranges';
    /**
     * Indicates the part of a document that the server should return.
     *
     * @link https://tools.ietf.org/html/rfc7233#section-3.1
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Range
     */
    public const RANGE = 'Range';
    /**
     * Indicates where in a full body message a partial message belongs.
     *
     * @link https://tools.ietf.org/html/rfc7233#section-4.2
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Content-Range
     */
    public const CONTENT_RANGE = 'Content-Range';

    //Security

    /**
     * Allows a server to declare an embedder policy for a given document.
     */
    public const CROSS_ORIGIN_EMBEDDER_POLICY = 'Cross-Origin-Embedder-Policy';
    /**
     * Prevents other domains from opening/controlling a window.
     */
    public const CROSS_ORIGIN_OPENER_POLICY = 'Cross-Origin-Opener-Policy';
    /**
     * Prevents other domains from reading the response of the resources to which this header is applied.
     *
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Cross-Origin-Resource-Policy
     */
    public const CROSS_ORIGIN_RESOURCE_POLICY = 'Cross-Origin-Resource-Policy';
    /**
     * Controls resources the user agent is allowed to load for a given page.
     *
     * @link https://www.w3.org/TR/CSP2/
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Content-Security-Policy
     * @link https://developer.mozilla.org/docs/Glossary/CSP
     */
    public const CONTENT_SECURITY_POLICY = 'Content-Security-Policy';
    /**
     * Allows web developers to experiment with policies by monitoring, but not enforcing, their effects. These
     * violation reports consist of JSON documents sent via an HTTP POST request to the specified URI.
     *
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Content-Security-Policy-Report-Only
     */
    public const CONTENT_SECURITY_POLICY_REPORT_ONLY = 'Content-Security-Policy-Report-Only';
    /**
     * Allows sites to opt in to reporting and/or enforcement of Certificate Transparency requirements, which prevents
     * the use of misissued certificates for that site from going unnoticed. When a site enables the Expect-CT header,
     * they are requesting that Chrome check that any certificate for that site appears in public CT logs.
     *
     * @link https://tools.ietf.org/html/draft-ietf-httpbis-expect-ct-08 Draft
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Expect-CT
     */
    public const EXPECT_CT = 'Expect-CT';
    /**
     * Provides a mechanism to allow and deny the use of browser features in its own frame, and in iframes that it embeds.
     *
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Feature-Policy
     */
    public const FEATURE_POLICY = 'Feature-Policy';
    /**
     * Force communication using HTTPS instead of HTTP.
     *
     * @link https://tools.ietf.org/html/rfc6797
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Strict-Transport-Security
     * @link https://developer.mozilla.org/docs/Glossary/HSTS
     */
    public const STRICT_TRANSPORT_SECURITY = 'Strict-Transport-Security';
    /**
     * Sends a signal to the server expressing the client’s preference for an encrypted and authenticated response, and
     * that it can successfully handle the upgrade-insecure-requests directive.
     *
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Upgrade-Insecure-Requests
     */
    public const UPGRADE_INSECURE_REQUESTS = 'Upgrade-Insecure-Requests';
    /**
     * Disables MIME sniffing and forces browser to use the type given in Content-Type.
     *
     * @see CONTENT_TYPE
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/X-Content-Type-Options
     */
    public const X_CONTENT_TYPE_OPTIONS = 'X-Content-Type-Options';
    /**
     * Indicates whether a browser should be allowed to render a page in a <frame>, <iframe>, <embed> or <object>.
     *
     * @link https://tools.ietf.org/html/rfc7034
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/X-Frame-Options
     */
    public const X_FRAME_OPTIONS = 'X-Frame-Options';
    /**
     * Specifies if a cross-domain policy file (crossdomain.xml) is allowed. The file may define a policy to grant
     * clients, such as Adobe's Flash Player, Adobe Acrobat, Microsoft Silverlight, or Apache Flex, permission to handle
     * data across domains that would otherwise be restricted due to the Same-Origin Policy.
     *
     * @link https://developer.mozilla.org/docs/Web/Security/Same-origin_policy Same-Origin Policy
     */
    public const X_PERMITTED_CROSS_DOMAIN_POLICIES = 'X-Permitted-Cross-Domain-Policies';
    /**
     * May be set by hosting environments or other frameworks and contains information about them while not providing
     * any usefulness to the application or its visitors. Unset this header to avoid exposing potential vulnerabilities.
     */
    public const X_POWERED_BY = 'X-Powered-By';
    /**
     * Enables cross-site scripting filtering.
     *
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/X-XSS-Protection
     */
    public const X_XSS_PROTECTION = 'X-XSS-Protection';
    /**
     * It is a request header that indicates the relationship between a request initiator's origin and its target's
     * origin. It is a Structured Header whose value is a token with possible values cross-site, same-origin, same-site,
     * and none.
     *
     * @link https://w3c.github.io/webappsec-fetch-metadata/#sec-fetch-site-header
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Sec-Fetch-Site
     */
    public const SEC_FETCH_SITE = 'Sec-Fetch-Site';
    /**
     * It is a request header that indicates the request's mode to a server. It is a Structured Header whose value is a
     * token with possible values cors, navigate, nested-navigate, no-cors, same-origin, and websocket.
     *
     * @link https://w3c.github.io/webappsec-fetch-metadata/#sec-fetch-mode-header
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Sec-Fetch-Mode
     */
    public const SEC_FETCH_MODE = 'Sec-Fetch-Mode';
    /**
     * It is a request header that indicates whether or not a navigation request was triggered by user activation. It is
     * a Structured Header whose value is a boolean so possible values are ?0 for false and ?1 for true.
     *
     * @link https://w3c.github.io/webappsec-fetch-metadata/#sec-fetch-user-header
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Sec-Fetch-User
     */
    public const SEC_FETCH_USER = 'Sec-Fetch-User';
    /**
     * It is a request header that indicates the request's destination to a server. It is a Structured Header whose
     * value is a token with possible values audio, audioworklet, document, embed, empty, font, image, manifest, object,
     * paintworklet, report, script, serviceworker, sharedworker, style, track, video, worker, xslt, and
     * nested-document.
     *
     * @link https://w3c.github.io/webappsec-fetch-metadata/#sec-fetch-dest-header
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Sec-Fetch-Dest
     */
    public const SEC_FETCH_DEST = 'Sec-Fetch-Dest';

    // Transfer coding

    /**
     * Specifies the form of encoding used to safely transfer the entity to the user.
     *
     * @link https://tools.ietf.org/html/rfc7230#section-3.3.1
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Transfer-Encoding
     */
    public const TRANSFER_ENCODING = 'Transfer-Encoding';
    /**
     * Specifies the transfer encodings the user agent is willing to accept.
     *
     * @link https://tools.ietf.org/html/rfc7230#section-4.3
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/TE
     */
    public const TE = 'TE';
    /**
     * Allows the sender to include additional fields at the end of chunked message.
     *
     * @link https://tools.ietf.org/html/rfc7230#section-4.4
     * @link https://tools.ietf.org/html/rfc7230#section-4.1.2 Chunked trailer part
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Trailer
     */
    public const TRAILER = 'Trailer';

    // WebSockets

    /**
     * @link https://tools.ietf.org/html/rfc6455#section-11.3.1
     */
    public const SEC_WEBSOCKET_KEY = 'Sec-WebSocket-Key';
    /**
     * @link https://tools.ietf.org/html/rfc6455#section-11.3.2
     */
    public const SEC_WEBSOCKET_EXTENSIONS = 'Sec-WebSocket-Extensions';
    /**
     * @link https://tools.ietf.org/html/rfc6455#section-11.3.3
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Sec-WebSocket-Accept
     */
    public const SEC_WEBSOCKET_ACCEPT = 'Sec-WebSocket-Accept';
    /**
     * @link https://tools.ietf.org/html/rfc6455#section-11.3.4
     */
    public const SEC_WEBSOCKET_PROTOCOL = 'Sec-WebSocket-Protocol';
    /**
     * @link https://tools.ietf.org/html/rfc6455#section-11.3.5
     */
    public const SEC_WEBSOCKET_VERSION = 'Sec-WebSocket-Version';

    // Other

    /**
     * Used to list alternate ways to reach this service.
     *
     * @link https://tools.ietf.org/html/rfc7838
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Alt-Svc
     */
    public const ALT_SVC = 'Alt-Svc';
    /**
     * Contains the date and time at which the message was originated.
     *
     * @link https://tools.ietf.org/html/rfc7231#section-7.1.1.2
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Date
     */
    public const DATE = 'Date';
    /**
     * Tells the browser that the page being loaded is going to want to perform a large allocation.
     *
     * @link https://gist.github.com/mystor/5739e222e398efc6c29108be55eb6fe3
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Large-Allocation
     */
    public const LARGE_ALLOCATION = 'Large-Allocation';
    /**
     * The Link entity-header field provides a means for serialising one or more links in HTTP headers. It is
     * semantically equivalent to the HTML <link> element.
     *
     * @link https://tools.ietf.org/html/rfc5988#section-5 The Link Header Field
     * @link https://tools.ietf.org/html/rfc8288#section-3 Link Serialisation in HTTP Headers
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Link
     */
    public const LINK = 'Link';
    /**
     * Indicates how long the user agent should wait before making a follow-up request.
     *
     * @link https://tools.ietf.org/html/rfc7231#section-7.1.3
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Retry-After
     */
    public const RETRY_AFTER = 'Retry-After';
    /**
     * Communicates one or more metrics and descriptions for the given request-response cycle.
     *
     * @link https://w3c.github.io/server-timing/#the-server-timing-header-field
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/Server-Timing
     */
    public const SERVER_TIMING = 'Server-Timing';
    /**
     * Used to remove the path restriction by including this header in the response of the Service Worker script.
     *
     * @link https://www.w3.org/TR/service-workers/#service-worker-allowed
     */
    public const SERVICE_WORKER_ALLOWED = 'Service-Worker-Allowed';
    /**
     * Links generated code to a source map.
     *
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/SourceMap
     */
    public const SOURCEMAP = 'SourceMap';
    /**
     * Establishes rules for upgrading or changing to a different protocol on the current client, server, transport
     * protocol connection.
     *
     * @link https://tools.ietf.org/html/rfc7230#section-6.7
     */
    public const UPGRADE = 'Upgrade';
    /**
     * Controls DNS prefetching, a feature by which browsers proactively perform domain name resolution on both links
     * that the user may choose to follow as well as URLs for items referenced by the document, including images, CSS,
     * JavaScript, and so forth.
     *
     * @link https://developer.mozilla.org/docs/Web/HTTP/Headers/X-DNS-Prefetch-Control
     */
    public const X_DNS_PREFETCH_CONTROL = 'X-DNS-Prefetch-Control';

    // Pagination

    /**
     * The per page limit.
     */
    public const X_PAGINATION_LIMIT = 'X-Pagination-Limit';
    /**
     * The current page.
     */
    public const X_PAGINATION_CURRENT_PAGE = 'X-Pagination-Current-Page';
    /**
     * The total number of pages in the result set.
     */
    public const X_PAGINATION_TOTAL_PAGES = 'X-Pagination-Total-Pages';
    /**
     * The total number of records across all pages.
     */
    public const X_PAGINATION_TOTAL_COUNT = 'X-Pagination-Total-Count';
}
