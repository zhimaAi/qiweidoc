<?php

declare(strict_types=1);

namespace Yiisoft\Http;

/**
 * HTTP response status codes
 *
 * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Status
 * @link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
 */
final class Status
{
    // Informational responses
    /**
     * This interim response indicates that everything so far is OK and that the client should continue the request,
     * or ignore the response if the request is already finished.
     *
     * @link https://tools.ietf.org/html/rfc7231#section-6.2.1
     */
    public const CONTINUE = 100;
    /**
     * This code is sent in response to an `Upgrade` request header from the client,
     * and indicates the protocol the server is switching to.
     *
     * @link https://tools.ietf.org/html/rfc7231#section-6.2.2
     */
    public const SWITCHING_PROTOCOLS = 101;
    /**
     * This code indicates that the server has received and is processing the request,
     * but no response is available yet.
     *
     * @link https://tools.ietf.org/html/rfc2518#section-10.1
     */
    public const PROCESSING = 102;
    /**
     * Indicates to the client that the server is likely to send a final response with the header fields included
     * in the informational response.
     *
     * @link https://tools.ietf.org/html/rfc8297#section-2
     */
    public const EARLY_HINTS = 103;

    // Successful responses
    /**
     * The request has succeeded. The meaning of the success depends on the HTTP method:
     *  - GET: The resource has been fetched and is transmitted in the message body.
     *  - HEAD: The entity headers are in the message body.
     *  - PUT or POST: The resource describing the result of the action is transmitted in the message body.
     *  - TRACE: The message body contains the request message as received by the server
     *
     * @link https://tools.ietf.org/html/rfc7231#section-6.3.1
     */
    public const OK = 200;
    /**
     * The request has succeeded and a new resource has been created as a result.
     * This is typically the response sent after POST requests, or some PUT requests.
     *
     * @link https://tools.ietf.org/html/rfc7231#section-6.3.2
     */
    public const CREATED = 201;
    /**
     * The request has been received but not yet acted upon. It is noncommittal, since there is no way in HTTP to later
     * send an asynchronous response indicating the outcome of the request.
     * It is intended for cases where another process or server handles the request, or for batch processing.
     *
     * @link https://tools.ietf.org/html/rfc7231#section-6.3.3
     */
    public const ACCEPTED = 202;
    /**
     * This response code means the returned meta-information is not exactly the same as is available
     * from the origin server, but is collected from a local or a third-party copy.
     * This is mostly used for mirrors or backups of another resource.
     * Except for that specific case, the "200 OK" response is preferred to this status.
     *
     * @link https://tools.ietf.org/html/rfc7231#section-6.3.4
     */
    public const NON_AUTHORITATIVE_INFORMATION = 203;
    /**
     * There is no content to send for this request, but the headers may be useful.
     * The user-agent may update its cached headers for this resource with the new ones.
     *
     * @link https://tools.ietf.org/html/rfc7231#section-6.3.5
     */
    public const NO_CONTENT = 204;
    /**
     * Tells the user-agent to reset the document which sent this request.
     *
     * @link https://tools.ietf.org/html/rfc7231#section-6.3.6
     */
    public const RESET_CONTENT = 205;
    /**
     * This response code is used when the Range header is sent from the client to request only part of a resource.
     *
     * @link https://tools.ietf.org/html/rfc7233#section-4.1
     */
    public const PARTIAL_CONTENT = 206;
    /**
     * Conveys information about multiple resources, for situations where multiple status codes might be appropriate.
     *
     * @link https://tools.ietf.org/html/rfc4918#section-11.1
     */
    public const MULTI_STATUS = 207;
    /**
     * Used inside a `<dav:propstat>` response element to avoid repeatedly enumerating
     * the internal members of multiple bindings to the same collection.
     *
     * @link https://tools.ietf.org/html/rfc5842#section-7.1     *
     */
    public const ALREADY_REPORTED = 208;
    /**
     * The server has fulfilled a GET request for the resource, and the response
     * is a representation of the result of one or more instance-manipulations applied to the current instance.
     *
     * @link https://tools.ietf.org/html/rfc3229#section-10.4.1
     */
    public const IM_USED = 226;

    // Redirects
    /**
     * The request has more than one possible response. The user-agent or user should choose one of them.
     * (There is no standardized way of choosing one of the responses, but HTML links to the possibilities are
     * recommended so the user can pick.)
     *
     * @link https://tools.ietf.org/html/rfc7231#section-6.4.1
     */
    public const MULTIPLE_CHOICES = 300;
    /**
     * The URL of the requested resource has been changed permanently. The new URL is given in the response.
     *
     * @link https://tools.ietf.org/html/rfc7231#section-6.4.2
     */
    public const MOVED_PERMANENTLY = 301;
    /**
     * This response code means that the URI of requested resource has been changed temporarily.
     * Further changes in the URI might be made in the future.
     * Therefore, this same URI should be used by the client in future requests.
     *
     * @link https://tools.ietf.org/html/rfc7231#section-6.4.3
     */
    public const FOUND = 302;
    /**
     * The server sent this response to direct the client to get
     * the requested resource at another URI with a GET request.
     *
     * @link https://tools.ietf.org/html/rfc7231#section-6.4.4
     */
    public const SEE_OTHER = 303;
    /**
     * This is used for caching purposes.
     * It tells the client that the response has not been modified, so the client can continue
     * to use the same cached version of the response.
     *
     * @link https://tools.ietf.org/html/rfc7232#section-4.1
     */
    public const NOT_MODIFIED = 304;
    /**
     * Defined in a previous version of the HTTP specification to indicate that a requested response
     * must be accessed by a proxy.
     * It has been deprecated due to security concerns regarding in-band configuration of a proxy.
     *
     * @link https://tools.ietf.org/html/rfc7231#section-6.4.5
     */
    public const USE_PROXY = 305;
    /**
     * The server sends this response to direct the client to get the requested resource at another URI with same method
     * that was used in the prior request. This has the same semantics as the 302 Found HTTP response code, with
     * the exception that the user agent must not change
     * the HTTP method used: If a POST was used in the first request, a POST must be used in the second request.
     *
     * @link https://tools.ietf.org/html/rfc7231#section-6.4.7
     */
    public const TEMPORARY_REDIRECT = 307;
    /**
     * This means that the resource is now permanently located at another URI, specified by
     * the Location: HTTP Response header. This has the same semantics as the 301 Moved Permanently HTTP response code,
     * with the exception that the user agent must not change
     * the HTTP method used: If a POST was used in the first request, a POST must be used in the second request.
     *
     * @link https://tools.ietf.org/html/rfc7238#section-3
     */
    public const PERMANENT_REDIRECT = 308;

    // Client errors
    /**
     * The server could not understand the request due to invalid syntax.
     *
     * @link https://tools.ietf.org/html/rfc7231#section-6.5.1
     */
    public const BAD_REQUEST = 400;
    /**
     * Although the HTTP standard specifies "unauthorized", semantically this response means "unauthenticated".
     * That is, the client must authenticate itself to get the requested response.
     *
     * @link https://tools.ietf.org/html/rfc7235#section-3.1
     */
    public const UNAUTHORIZED = 401;
    /**
     * This response code is reserved for future use.
     * The initial aim for creating this code was using it for digital payment systems, however this status code
     * is used very rarely and no standard convention exists.
     *
     * @link https://tools.ietf.org/html/rfc7231#section-6.5.2
     */
    public const PAYMENT_REQUIRED = 402;
    /**
     * The client does not have access rights to the content; that is, it is unauthorized, so the server is refusing
     * to give the requested resource. Unlike 401, the client's identity is known to the server.
     *
     * @link https://tools.ietf.org/html/rfc7231#section-6.5.3
     */
    public const FORBIDDEN = 403;
    /**
     * The server can not find requested resource. In the browser, this means the URL is not recognized.
     * In an API, this can also mean that the endpoint is valid but the resource itself does not exist.
     * Servers may also send this response instead of 403 to hide the existence of a resource from an unauthorized client.
     * This response code is probably the most famous one due to its frequent occurrence on the web.
     *
     * @link https://tools.ietf.org/html/rfc7231#section-6.5.4
     */
    public const NOT_FOUND = 404;
    /**
     * The request method is known by the server but has been disabled and cannot be used.
     * For example, an API may forbid DELETE-ing a resource.
     * The two mandatory methods, GET and HEAD, must never be disabled and should not return this error code.
     *
     * @link https://tools.ietf.org/html/rfc7231#section-6.5.5
     */
    public const METHOD_NOT_ALLOWED = 405;
    /**
     * This response is sent when the web server, after performing server-driven content negotiation,
     * doesn't find any content that conforms to the criteria given by the user agent.
     *
     * @link https://tools.ietf.org/html/rfc7231#section-6.5.6
     */
    public const NOT_ACCEPTABLE = 406;
    /**
     * This is similar to 401 but authentication is needed to be done by a proxy.
     *
     * @link https://tools.ietf.org/html/rfc7235#section-3.2
     */
    public const PROXY_AUTHENTICATION_REQUIRED = 407;
    /**
     * This response is sent on an idle connection by some servers, even without any previous request by the client.
     * It means that the server would like to shut down this unused connection.
     * This response is used much more since some browsers, like Chrome, Firefox 27+, or IE9,
     * use HTTP pre-connection mechanisms to speed up surfing.
     * Also note that some servers merely shut down the connection without sending this message.
     *
     * @link https://tools.ietf.org/html/rfc7231#section-6.5.7
     */
    public const REQUEST_TIMEOUT = 408;
    /**
     * This response is sent when a request conflicts with the current state of the server.
     *
     * @link https://tools.ietf.org/html/rfc7231#section-6.5.8
     */
    public const CONFLICT = 409;
    /**
     * This response is sent when the requested content has been permanently deleted from server,
     * with no forwarding address. Clients are expected to remove their caches and links to the resource.
     * The HTTP specification intends this status code to be used for "limited-time, promotional services".
     * APIs should not feel compelled to indicate resources that have been deleted with this status code.
     *
     * @link https://tools.ietf.org/html/rfc7231#section-6.5.9
     */
    public const GONE = 410;
    /**
     * Server rejected the request because the Content-Length header field is not defined and the server requires it.
     *
     * @link https://tools.ietf.org/html/rfc7231#section-6.5.10
     */
    public const LENGTH_REQUIRED = 411;
    /**
     * The client has indicated preconditions in its headers which the server does not meet.
     *
     * @link https://tools.ietf.org/html/rfc7232#section-4.2
     */
    public const PRECONDITION_FAILED = 412;
    /**
     * Request entity is larger than limits defined by server; the server might close
     * the connection or return an Retry-After header field.
     *
     * @link https://tools.ietf.org/html/rfc7231#section-6.5.11
     */
    public const PAYLOAD_TOO_LARGE = 413;
    /**
     * The URI requested by the client is longer than the server is willing to interpret.
     *
     * @link https://tools.ietf.org/html/rfc7231#section-6.5.12
     */
    public const URI_TOO_LONG = 414;
    /**
     * The media format of the requested data is not supported by the server, so the server is rejecting the request.
     *
     * @link https://tools.ietf.org/html/rfc7231#section-6.5.13
     */
    public const UNSUPPORTED_MEDIA_TYPE = 415;
    /**
     * The range specified by the Range header field in the request can't be fulfilled; it's possible that
     * the range is outside the size of the target URI's data.
     *
     * @link https://tools.ietf.org/html/rfc7233#section-4.4
     */
    public const RANGE_UNSATISFIABLE = 416;
    /**
     * This response code means the expectation indicated by the Expect request header field can't be met by the server.
     *
     * @link https://tools.ietf.org/html/rfc7231#section-6.5.14
     */
    public const EXPECTATION_FAILED = 417;
    /**
     * The server refuses the attempt to brew coffee with a teapot.
     *
     * @link https://tools.ietf.org/html/rfc2324#section-2.3.2
     */
    public const I_AM_A_TEAPOT = 418;
    /**
     * The request was directed at a server that is not able to produce a response. This can be sent by a server that is
     * not configured to produce responses for the combination of scheme and authority that are included in the request URI.
     *
     * @link https://tools.ietf.org/html/rfc7540#section-9.1.2
     */
    public const MISDIRECTED_REQUEST = 421;
    /**
     * The request was well-formed but was unable to be followed due to semantic errors.
     *
     * @link https://tools.ietf.org/html/rfc4918#section-11.2
     */
    public const UNPROCESSABLE_ENTITY = 422;
    /**
     * The resource that is being accessed is locked.
     *
     * @link https://tools.ietf.org/html/rfc4918#section-11.3
     */
    public const LOCKED = 423;
    /**
     * The request failed due to failure of a previous request.
     *
     * @link https://tools.ietf.org/html/rfc4918#section-11.4
     */
    public const FAILED_DEPENDENCY = 424;
    /**
     * Indicates that the server is unwilling to risk processing a request that might be replayed.
     *
     * @link https://tools.ietf.org/html/draft-ietf-httpbis-replay-04#section-5.2
     */
    public const TOO_EARLY = 425;
    /**
     * The server refuses to perform the request using the current protocol but might be willing to do so after
     * the client upgrades to a different protocol.
     * The server sends an Upgrade header in a 426 response to indicate the required protocol(s).
     *
     * @link https://tools.ietf.org/html/rfc7231#section-6.5.15
     */
    public const UPGRADE_REQUIRED = 426;
    /**
     * The origin server requires the request to be conditional. This response is intended to prevent
     * the 'lost update' problem, where a client GETs a resource's state, modifies it, and PUTs it back to the server,
     * when meanwhile a third party has modified the state on the server, leading to a conflict.
     *
     * @link https://tools.ietf.org/html/rfc6585#section-3
     */
    public const PRECONDITION_REQUIRED = 428;
    /**
     * The user has sent too many requests in a given amount of time ("rate limiting").
     *
     * @link https://tools.ietf.org/html/rfc6585#section-4
     */
    public const TOO_MANY_REQUESTS = 429;
    /**
     * The server is unwilling to process the request because its header fields are too large.
     * The request may be resubmitted after reducing the size of the request header fields.
     *
     * @link https://tools.ietf.org/html/rfc6585#section-5
     */
    public const REQUEST_HEADER_FIELDS_TOO_LARGE = 431;
    /**
     * The user-agent requested a resource that cannot legally be provided, such as a web page censored by a government.
     *
     * @link https://tools.ietf.org/html/rfc7725#section-3
     */
    public const UNAVAILABLE_FOR_LEGAL_REASONS = 451;

    // Server errors
    /**
     * The server has encountered a situation it doesn't know how to handle.
     *
     * @link https://tools.ietf.org/html/rfc7231#section-6.6.1
     */
    public const INTERNAL_SERVER_ERROR = 500;
    /**
     * The request method is not supported by the server and cannot be handled.
     * The only methods that servers are required to support (and therefore that must not return this code) are GET and HEAD.
     *
     * @link https://tools.ietf.org/html/rfc7231#section-6.6.2
     */
    public const NOT_IMPLEMENTED = 501;
    /**
     * This error response means that the server, while working as a gateway to get a response needed to handle
     * the request, got an invalid response.
     *
     * @link https://tools.ietf.org/html/rfc2616#section-10.5.3
     */
    public const BAD_GATEWAY = 502;
    /**
     * The server is not ready to handle the request. Common causes are a server that is down for maintenance or that is overloaded.
     * Note that together with this response, a user-friendly page explaining the problem should be sent.
     * This responses should be used for temporary conditions and the Retry-After: HTTP header should, if possible,
     * contain the estimated time before the recovery of the service.
     * The webmaster must also take care about the caching-related headers that are sent along with this response, as
     * these temporary condition responses should usually not be cached.
     *
     * @link https://tools.ietf.org/html/rfc7231#section-6.6.4
     */
    public const SERVICE_UNAVAILABLE = 503;
    /**
     * This error response is given when the server is acting as a gateway and cannot get a response in time.
     *
     * @link https://tools.ietf.org/html/rfc7231#section-6.6.5
     */
    public const GATEWAY_TIMEOUT = 504;
    /**
     * The HTTP version used in the request is not supported by the server.
     *
     * @link https://tools.ietf.org/html/rfc7231#section-6.6.6
     */
    public const HTTP_VERSION_NOT_SUPPORTED = 505;
    /**
     * The method could not be performed on the resource because the server is unable to store the representation needed
     * to successfully complete the request.
     *
     * @link https://tools.ietf.org/html/rfc4918#section-11.5
     */
    public const INSUFFICIENT_STORAGE = 507;
    /**
     * The server detected an infinite loop while processing the request.
     *
     * @link https://tools.ietf.org/html/rfc5842#section-7.2
     */
    public const LOOP_DETECTED = 508;
    /**
     * Further extensions to the request are required for the server to fulfil it.
     *
     * @link https://tools.ietf.org/html/rfc2774#section-7
     */
    public const NOT_EXTENDED = 510;
    /**
     * The 511 status code indicates that the client needs to authenticate to gain network access.
     *
     * @link https://tools.ietf.org/html/rfc6585#section-6
     */
    public const NETWORK_AUTHENTICATION_REQUIRED = 511;

    /**
     * @var array list of HTTP status texts
     */
    public const TEXTS = [
        self::CONTINUE => 'Continue',
        self::SWITCHING_PROTOCOLS => 'Switching Protocols',
        self::PROCESSING => 'Processing',
        self::EARLY_HINTS => 'Early Hints',
        self::OK => 'OK',
        self::CREATED => 'Created',
        self::ACCEPTED => 'Accepted',
        self::NON_AUTHORITATIVE_INFORMATION => 'Non-Authoritative Information',
        self::NO_CONTENT => 'No Content',
        self::RESET_CONTENT => 'Reset Content',
        self::PARTIAL_CONTENT => 'Partial Content',
        self::MULTI_STATUS => 'Multi-Status',
        self::ALREADY_REPORTED => 'Already Reported',
        self::IM_USED => 'IM Used',
        self::MULTIPLE_CHOICES => 'Multiple Choices',
        self::MOVED_PERMANENTLY => 'Moved Permanently',
        self::FOUND => 'Found',
        self::SEE_OTHER => 'See Other',
        self::NOT_MODIFIED => 'Not Modified',
        self::USE_PROXY => 'Use Proxy',
        self::TEMPORARY_REDIRECT => 'Temporary Redirect',
        self::PERMANENT_REDIRECT => 'Permanent Redirect',
        self::BAD_REQUEST => 'Bad Request',
        self::UNAUTHORIZED => 'Unauthorized',
        self::PAYMENT_REQUIRED => 'Payment Required',
        self::FORBIDDEN => 'Forbidden',
        self::NOT_FOUND => 'Not Found',
        self::METHOD_NOT_ALLOWED => 'Method Not Allowed',
        self::NOT_ACCEPTABLE => 'Not Acceptable',
        self::PROXY_AUTHENTICATION_REQUIRED => 'Proxy Authentication Required',
        self::REQUEST_TIMEOUT => 'Request Time-out',
        self::CONFLICT => 'Conflict',
        self::GONE => 'Gone',
        self::LENGTH_REQUIRED => 'Length Required',
        self::PRECONDITION_FAILED => 'Precondition Failed',
        self::PAYLOAD_TOO_LARGE => 'Payload Too Large',
        self::URI_TOO_LONG => 'URI Too Long',
        self::UNSUPPORTED_MEDIA_TYPE => 'Unsupported Media Type',
        self::RANGE_UNSATISFIABLE => 'Requested range unsatisfiable',
        self::EXPECTATION_FAILED => 'Expectation failed',
        self::I_AM_A_TEAPOT => 'I\'m a teapot',
        self::MISDIRECTED_REQUEST => 'Misdirected Request',
        self::UNPROCESSABLE_ENTITY => 'Unprocessable entity',
        self::LOCKED => 'Locked',
        self::FAILED_DEPENDENCY => 'Failed Dependency',
        self::TOO_EARLY => 'Too Early',
        self::UPGRADE_REQUIRED => 'Upgrade Required',
        self::PRECONDITION_REQUIRED => 'Precondition Required',
        self::TOO_MANY_REQUESTS => 'Too Many Requests',
        self::REQUEST_HEADER_FIELDS_TOO_LARGE => 'Request Header Fields Too Large',
        self::UNAVAILABLE_FOR_LEGAL_REASONS => 'Unavailable For Legal Reasons',
        self::INTERNAL_SERVER_ERROR => 'Internal Server Error',
        self::NOT_IMPLEMENTED => 'Not Implemented',
        self::BAD_GATEWAY => 'Bad Gateway',
        self::SERVICE_UNAVAILABLE => 'Service Unavailable',
        self::GATEWAY_TIMEOUT => 'Gateway Time-out',
        self::HTTP_VERSION_NOT_SUPPORTED => 'HTTP Version not supported',
        self::INSUFFICIENT_STORAGE => 'Insufficient storage',
        self::LOOP_DETECTED => 'Loop Detected',
        self::NOT_EXTENDED => 'Not Extended',
        self::NETWORK_AUTHENTICATION_REQUIRED => 'Network Authentication Required',
    ];
}
