<?php

use Yiisoft\ErrorHandler\CompositeException;
use Yiisoft\ErrorHandler\Exception\ErrorException;
use Yiisoft\FriendlyException\FriendlyExceptionInterface;

/**
 * @var $this \Yiisoft\ErrorHandler\Renderer\HtmlRenderer
 * @var $request \Psr\Http\Message\ServerRequestInterface|null
 * @var $throwable \Throwable
 */

$theme = $_COOKIE['yii-exception-theme'] ?? '';

$originalException = $throwable;
if ($throwable instanceof CompositeException) {
    $throwable = $throwable->getFirstException();
}
$isFriendlyException = $throwable instanceof FriendlyExceptionInterface;
$solution = $isFriendlyException ? $throwable->getSolution() : null;
$exceptionClass = get_class($throwable);
$exceptionMessage = $throwable->getMessage();

?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>
        <?= $this->htmlEncode($this->getThrowableName($throwable)) ?>
    </title>
    <style>
        <?= file_get_contents(__DIR__ . '/development.css') ?>
    </style>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700">
</head>
<body<?= !empty($theme) ? " class=\"{$this->htmlEncode($theme)}\"" : '' ?>>
<header>
    <div class="tools">
        <a href="#" title="Dark Mode" id="dark-mode">
            <svg width="28" height="28" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M27.0597 19.06c-2.5312.9821-5.2934 1.2069-7.9501.6472-2.6568-.5598-5.0934-1.8799-7.0133-3.7998-1.9198-1.9198-3.24001-4.3565-3.79975-7.01324-.55974-2.65674-.33489-5.41892.64718-7.95016-3.04045 1.1849-5.57175 3.39424-7.15685 6.24657C.201778 10.0429-.337628 13.3592.26179 16.5668c.599419 3.2077 2.30004 6.1053 4.80824 8.1928C7.57822 26.847 10.7366 27.9931 13.9997 28c2.8246.0007 5.5833-.8527 7.9141-2.4481 2.3307-1.5955 4.1245-3.8585 5.1459-6.4919z" fill="#787878"/>
            </svg>
        </a>

        <a href="#" title="Light Mode" id="light-mode">
            <svg width="32" height="32" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M15 2h2v5h-2V2zM21.6875 8.9l3.506-3.50699 1.414 1.415-3.506 3.50599-1.414-1.414zM25 15h5v2h-5v-2zM21.6875 23.1l1.414-1.414 3.506 3.506-1.414 1.415-3.506-3.507zM15 25h2v5h-2v-5zM5.39453 25.192l3.506-3.506 1.41397 1.415-3.50597 3.506-1.414-1.415zM2 15h5v2H2v-2zM5.39453 6.80801l1.414-1.415L10.3145 8.9l-1.41397 1.414-3.506-3.50599zM16 10c-1.1867 0-2.3467.3519-3.3334 1.0112-.9867.6593-1.7557 1.5963-2.2099 2.6927-.4541 1.0964-.57292 2.3028-.3414 3.4666.2315 1.1639.8029 2.233 1.6421 3.0721.8391.8392 1.9082 1.4106 3.0721 1.6421 1.1638.2315 2.3702.1127 3.4666-.3414 1.0964-.4541 2.0334-1.2232 2.6927-2.2099C21.6481 18.3467 22 17.1867 22 16c0-1.5913-.6321-3.1174-1.7574-4.2426C19.1174 10.6321 17.5913 10 16 10z" fill="#989898"/>
            </svg>
        </a>

        <a href="https://stackoverflow.com/search?<?= http_build_query(['q' => $exceptionMessage]) ?>" title="Search error on Stackoverflow" target="_blank">
            <svg width="28" height="32" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M23.312 29.151v-8.536h2.849V32H.458008V20.615H3.29701v8.536H23.312zM6.14501 26.307H20.469v-2.848H6.14501v2.848zm.35-6.468L20.47 22.755l.599-2.76-13.96899-2.912-.605 2.756zm1.812-6.74L21.246 19.136l1.203-2.6-12.93699-6.041-1.204 2.584-.001.02zm3.61999-6.38L22.88 15.86l1.813-2.163L13.74 4.562l-1.803 2.151-.01.006zM19 0l-2.328 1.724 8.541 11.473 2.328-1.724L19 0z" fill="#787878"/>
            </svg>
        </a>
        <a href="https://www.google.com/search?<?= http_build_query(['q' => $exceptionMessage]) ?>" title="Search error on Google" target="_blank">
            <svg width="24" height="24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M23.5313 9.825H12.2407v4.6406h6.45c-.2781 1.5-1.1219 2.7688-2.3937 3.6188-1.075.7187-2.4469 1.1437-4.0594 1.1437-3.12188 0-5.7625-2.1094-6.70625-4.9437-.2375-.7188-.375-1.4875-.375-2.2781 0-.7907.1375-1.5594.375-2.27818.94687-2.83125 3.5875-4.94062 6.70935-4.94062 1.7594 0 3.3375.60625 4.5813 1.79375l3.4375-3.44063C18.1813 1.20312 15.472.015625 12.2407.015625c-4.68435 0-8.73748 2.687495-10.70935 6.606245C.718848 8.24062.256348 10.0719.256348 12.0094s.4625 3.7656 1.275002 5.3843C3.50322 21.3125 7.55635 24 12.2407 24c3.2375 0 5.95-1.075 7.9313-2.9062 2.2656-2.0875 3.575-5.1625 3.575-8.8157 0-.85-.075-1.6656-.2157-2.4531z" fill="#787878"/>
            </svg>
        </a>
    </div>

    <div class="exception-card">
        <div class="exception-class">
            <?php
            if ($isFriendlyException): ?>
                <span><?= $this->htmlEncode($throwable->getName())?></span>
                &mdash;
                <?= $exceptionClass ?>
            <?php else: ?>
                <span><?= $exceptionClass ?></span>
            <?php endif ?>
        </div>

        <div class="exception-message">
            <?= nl2br($this->htmlEncode($exceptionMessage)) ?>
        </div>

        <?php if ($solution !== null): ?>
            <div class="solution"><?= $this->parseMarkdown($solution) ?></div>
        <?php endif ?>

        <?= $this->renderPreviousExceptions($originalException) ?>

        <textarea id="clipboard"><?= $this->htmlEncode($throwable) ?></textarea>
        <span id="copied">Copied!</span>

        <a href="#"
           class="copy-clipboard"
           data-clipboard="<?= $this->htmlEncode($throwable) ?>"
           title="Copy the stacktrace for use in a bug report or pastebin"
        >
            <svg width="26" height="30" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M17.9998.333344H3.33317C1.8665.333344.666504 1.53334.666504 3.00001V20.3333c0 .7334.599996 1.3334 1.333336 1.3334.73333 0 1.33333-.6 1.33333-1.3334V4.33334c0-.73333.6-1.33333 1.33333-1.33333h13.3333c.7334 0 1.3334-.6 1.3334-1.33333 0-.733337-.6-1.333336-1.3334-1.333336zm5.3334 5.333336H8.6665c-1.46666 0-2.66666 1.2-2.66666 2.66666V27c0 1.4667 1.2 2.6667 2.66666 2.6667h14.6667c1.4666 0 2.6666-1.2 2.6666-2.6667V8.33334c0-1.46666-1.2-2.66666-2.6666-2.66666zM21.9998 27H9.99984c-.73333 0-1.33334-.6-1.33334-1.3333V9.66668c0-.73334.60001-1.33334 1.33334-1.33334H21.9998c.7334 0 1.3334.6 1.3334 1.33334V25.6667c0 .7333-.6 1.3333-1.3334 1.3333z" fill="#787878"/>
            </svg>
        </a>
    </div>
</header>

<main>
    <div class="call-stack">
        <?= $this->renderCallStack(
            $throwable,
            $originalException === $throwable && $originalException instanceof ErrorException
                ? $originalException->getBacktrace()
                : $throwable->getTrace()
        ) ?>
    </div>
    <?php if ($request && ($requestInfo = $this->renderRequest($request)) !== ''): ?>
        <div class="request">
            <h2>Request info</h2>
            <div class="body">
                <pre class="codeBlock language-text"><?= $this->htmlEncode(rtrim($requestInfo, "\n")) ?></pre>
            </div>
        </div>
    <?php endif ?>
    <?php if ($request && ($curlInfo = $this->renderCurl($request)) !== 'curl'): ?>
        <div class="request">
            <textarea id="clipboard"><?= $curlInfo ?></textarea>
            <span id="copied" style="top: 10px">Copied!</span>
            <h2>cURL</h2>
            <a href="#"
               class="copy-clipboard"
               data-clipboard="<?= $curlInfo ?>"
               title="Copy the cURL"
               style="right: 10px; top: 5px"
            >
                <svg width="26" height="30" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M17.9998.333344H3.33317C1.8665.333344.666504 1.53334.666504 3.00001V20.3333c0 .7334.599996 1.3334 1.333336 1.3334.73333 0 1.33333-.6 1.33333-1.3334V4.33334c0-.73333.6-1.33333 1.33333-1.33333h13.3333c.7334 0 1.3334-.6 1.3334-1.33333 0-.733337-.6-1.333336-1.3334-1.333336zm5.3334 5.333336H8.6665c-1.46666 0-2.66666 1.2-2.66666 2.66666V27c0 1.4667 1.2 2.6667 2.66666 2.6667h14.6667c1.4666 0 2.6666-1.2 2.6666-2.6667V8.33334c0-1.46666-1.2-2.66666-2.6666-2.66666zM21.9998 27H9.99984c-.73333 0-1.33334-.6-1.33334-1.3333V9.66668c0-.73334.60001-1.33334 1.33334-1.33334H21.9998c.7334 0 1.3334.6 1.3334 1.33334V25.6667c0 .7333-.6 1.3333-1.3334 1.3333z" fill="#787878"/>
                </svg>
            </a>
            <div class="body">
                <div class="codeBlock language-sh"><?= $this->htmlEncode($curlInfo) ?></div>
            </div>
        </div>
    <?php endif ?>
    <div class="footer">
        <div class="flex-1">
            <p class="timestamp">
                <?= date('Y-m-d, H:i:s') ?>
            </p>
            <?php if ($request): ?>
                <p class="server">
                    <?= $this->createServerInformationLink($request) ?>
                </p>
            <?php endif ?>
            <p>
                <a href="https://www.yiiframework.com/" target="_blank" rel="noopener noreferrer">Yii Framework</a>
                /
                <a href="https://github.com/yiisoft/docs/blob/master/guide/en/runtime/handling-errors.md" target="_blank" rel="noopener noreferrer">Error Handling Guide</a>
            </p>
        </div>

        <svg width="256" height="224" fill="none" xmlns="http://www.w3.org/2000/svg">
            <g opacity=".54">
                <path d="M221.829.00415039C240.43 9.48202 256.405 34.3532 255.604 68.1036c-2.431 41.5854-32.582 77.4244-65.387 110.4444-1.837-40.269-10.592-65.932-24.264-102.0191-12.908-36.3676 24.83-75.36066 55.876-76.52474961z" fill="url(#paint0_linear)"/>
                <path d="M121.218 177.132c9.987-12.424 17.066-23.488 22.006-33.484 33.841 17.693 25.856 14.389 42.177 33.24.055.587.107 1.172.156 1.755 1.353 26.138-17.714 86.564-92.7936 94.845-5.1971-40.603 11.6986-75.237 28.4546-96.356z" fill="url(#paint1_linear)"/>
                <path d="M.391188 25.2468C53.8858 4.07847 111.459 25.7628 144.698 70.9437c3.215 4.3698 6.264 8.9095 9.14 13.5822 5.194 33.1551 1.234 51.5681-8.876 61.3291-6.647-3.354-13.738-6.328-21.309-9.078-17.602-6.394-33.8454-10.626-57.0447-17.495C11.5203 104.248-2.06497 57.0117.391188 25.2468z" fill="url(#paint2_linear)"/>
                <path d="M151.793 81.2802c.351.5448.698 1.0856 1.041 1.6346l.421.677c.858 1.3788 1.7 2.7658 2.53 4.1693l.14.2353.223.3798.203.3509.594 1.0196.041.0743.805 1.4117.025.0413c.582 1.0279 1.156 2.0599 1.721 3.1001l.071.1321.627 1.1641.017.0289.689 1.2962.198.3798.384.7389.471.9123.454.8834c.462.9162.92 1.8332 1.37 2.7492l.116.227c.813 1.664 1.601 3.331 2.373 5.011l.587 1.284.057.128.376.834.438.987.247.569.396.913.285.656.599 1.412.041.099.64 1.535.078.186c.682 1.66 1.342 3.319 1.982 4.991l.062.153.528 1.399.033.078.306.826c.338.916.673 1.833 1.003 2.753l.128.36c.363 1.027.722 2.059 1.069 3.087l.111.326c.326.966.64 1.932.954 2.898l.153.475.037.111.495 1.565.021.07.458 1.494.458 1.54.013.033.379 1.301.12.421.231.805c.26.928.516 1.853.764 2.782l.186.693.359 1.371.016.066c.438 1.684.851 3.368 1.243 5.048l.037.174.041.177c.512 2.217.983 4.426 1.416 6.626l.041.219c.669 3.422 1.243 6.823 1.718 10.196l.008.045c.149 1.04.285 2.076.413 3.113l.053.445.174 1.482v.004l.148 1.371.05.454.107 1.086.042.437.016.178.132 1.49c-11.818-15.22-25.936-25.709-42.877-33.916 13.672-27.03 11.752-46.2909 9.114-63.4468z" fill="url(#paint3_linear)"/>
            </g>
            <defs>
                <linearGradient id="paint0_linear" x1="209.492" y1=".00415039" x2="209.492" y2="178.548" gradientUnits="userSpaceOnUse">
                    <stop stop-color="#73B723"/>
                    <stop offset="1" stop-color="#D8EE61"/>
                </linearGradient>
                <linearGradient id="paint1_linear" x1="171.417" y1="177.732" x2="82.6039" y2="244.133" gradientUnits="userSpaceOnUse">
                    <stop stop-color="#98C9EA"/>
                    <stop offset=".688649" stop-color="#026FB2"/>
                    <stop offset="1" stop-color="#086EB6"/>
                </linearGradient>
                <linearGradient id="paint2_linear" x1=".119629" y1="25.2237" x2="115.057" y2="128.362" gradientUnits="userSpaceOnUse">
                    <stop stop-color="#D73721"/>
                    <stop offset="1" stop-color="#F7D768"/>
                </linearGradient>
                <linearGradient id="paint3_linear" x1="155.954" y1="111.456" x2="193.631" y2="126.545" gradientUnits="userSpaceOnUse">
                    <stop stop-color="#D4C883"/>
                    <stop offset="1" stop-color="#A1D1A7"/>
                </linearGradient>
            </defs>
        </svg>
    </div>
</main>

<script>
    <?= file_get_contents(__DIR__ . '/highlight.min.js') ?>
</script>
<script>
    window.onload = function() {
        const codeBlocks = document.querySelectorAll('.solution pre code,.codeBlock');
        const callStackItems = document.getElementsByClassName('call-stack-item');

        // If there are grouped vendor package files
        const vendorCollapse = document.getElementsByClassName('call-stack-vendor-collapse');
        for (let i = 0, imax = vendorCollapse.length; i < imax; ++i) {
            vendorCollapse[i].addEventListener('click', function (event) {
                const vendorCollapseState = this.getElementsByClassName('call-stack-vendor-state')[0];
                const vendorCollapseItems = this.parentElement.getElementsByClassName('call-stack-vendor-items')[0];

                if (vendorCollapseItems.style.display === 'block') {
                    vendorCollapseItems.style.display = 'none';
                    vendorCollapseState.innerText = '+';
                } else {
                    vendorCollapseItems.style.display = 'block';
                    vendorCollapseState.innerText = '–';
                }
            });
        }

        // highlight code blocks
        hljs.configure({
            ignoreUnescapedHTML: true,
        });
        hljs.listLanguages().forEach(function(language) {
            hljs.getLanguage(language).disableAutodetect = true;
        });
        for (let i = 0, imax = codeBlocks.length; i < imax; ++i) {
            hljs.highlightElement(codeBlocks[i]);
        }

        const refreshCallStackItemCode = function(callStackItem) {
            if (!callStackItem.getElementsByTagName('pre')[0]) {
                return;
            }
            const top = callStackItem.getElementsByClassName('code-wrap')[0].offsetTop - window.pageYOffset + 3,
                lines = callStackItem.getElementsByTagName('pre')[0].getClientRects(),
                lineNumbers = callStackItem.getElementsByClassName('lines-item'),
                errorLine = callStackItem.getElementsByClassName('error-line')[0],
                hoverLines = callStackItem.getElementsByClassName('hover-line');

            for (let i = 0, imax = lines.length; i < imax; ++i) {
                if (!lineNumbers[i]) {
                    continue;
                }

                lineNumbers[i].style.top = parseInt(lines[i].top - top) + 'px';
                hoverLines[i].style.top = parseInt(lines[i].top - top) + 'px';
                hoverLines[i].style.height = parseInt(lines[i].bottom - lines[i].top + 6) + 'px';
                hoverLines[i].style.width = hoverLines[i].parentElement.parentElement.scrollWidth + 'px'

                if (parseInt(callStackItem.getAttribute('data-line')) === i) {
                    errorLine.style.top = parseInt(lines[i].top - top) + 'px';
                    errorLine.style.height = parseInt(lines[i].bottom - lines[i].top + 6) + 'px';
                    errorLine.style.width = errorLine.parentElement.parentElement.scrollWidth + 'px';
                }
            }
        };

        for (var i = 0, imax = callStackItems.length; i < imax; ++i) {
            let stackItem = callStackItems[i];
            refreshCallStackItemCode(stackItem);

            // toggle code block visibility
            stackItem.querySelector('.toggleFunctionArguments')?.addEventListener('click', function (e) {
                e.stopPropagation();
                stackItem.getElementsByClassName('functionArguments')[0].classList.toggle('hidden');
            });

            // toggle code block visibility
            const arguments = stackItem.querySelector('.arguments');
            arguments?.addEventListener('select', function (e) {
                e.stopPropagation();
                e.stopImmediatePropagation();
            })

            arguments?.addEventListener('click', function (e) {
                e.stopPropagation();
                // stop click event on selecting text
                if (document.getSelection()?.type === 'Range') {
                    return;
                }

                const fullArguments = stackItem.querySelector('.full-arguments');
                const shortArguments = stackItem.querySelector('.short-arguments');
                if (fullArguments) {
                    fullArguments.classList.toggle('hidden');
                    shortArguments.classList.toggle('hidden');
                }
            });

            // toggle code block visibility
            stackItem.getElementsByClassName('element-wrap')[0].addEventListener('click', function (event) {
                if (event.target.nodeName.toLowerCase() === 'a') {
                    return;
                }
                // stop click event on selecting text
                if (document.getSelection()?.type === 'Range') {
                    return;
                }

                var callStackItem = this.parentNode,
                    code = callStackItem.getElementsByClassName('code-wrap')[0];

                if (typeof code !== 'undefined') {
                    code.style.display = window.getComputedStyle(code).display === 'block' ? 'none' : 'block';
                    refreshCallStackItemCode(callStackItem);
                }
            });

        }

        // handle copy stacktrace action on clipboard button
        const copyIntoClipboard = function(e) {
            e.preventDefault();
            const parentContainer = e.currentTarget.parentElement;
            const textarea = parentContainer.querySelector('#clipboard');
            textarea.focus();
            textarea.select();

            let succeeded;
            try {
                succeeded = document.execCommand('copy');
            } catch (err) {
                succeeded = false;
            }
            if (succeeded) {
                const hint = parentContainer.querySelector('#copied');
                if (!hint) {
                    return
                }
                hint.style.display = 'block';
                setTimeout(() => hint.style.display = 'none', 2000);
            } else {
                // fallback: show textarea if browser does not support copying directly
                textarea.style.top = 0;
            }
        }
        const elements = document.querySelectorAll('.copy-clipboard')
        for (let element of elements) {
            element.onclick = copyIntoClipboard;
        }


        // handle theme change
        document.getElementById('dark-mode').onclick = function(e) {
            e.preventDefault();
            enableDarkTheme();
        }

        document.getElementById('light-mode').onclick = function(e) {
            e.preventDefault();
            enableLightTheme();
        }

        function enableDarkTheme() {
            document.body.classList.remove('light-theme');
            document.body.classList.add('dark-theme');
            setCookie('yii-exception-theme', 'dark-theme');
        }

        function enableLightTheme() {
            document.body.classList.remove('dark-theme');
            document.body.classList.add('light-theme');
            setCookie('yii-exception-theme', 'light-theme');
        }
    };

    // Highlight lines that have text in them but still support text selection:
    document.onmousedown = function() { document.getElementsByTagName('body')[0].classList.add('mousedown'); }
    document.onmouseup = function() { document.getElementsByTagName('body')[0].classList.remove('mousedown'); }

    <?php if (empty($theme)): ?>
    var theme = getCookie('yii-exception-theme');

    if (theme) {
        document.body.classList.add(theme);
    }
    <?php endif; ?>

    function setCookie(name, value) {
        var date = new Date(2100, 0, 1);
        var expires = "; expires=" + date.toUTCString();

        document.cookie = name + "=" + (value || "")  + expires + "; path=/";
    }

    function getCookie(name) {
        var nameEQ = name + "=";
        var ca = document.cookie.split(';');

        for (var i=0; i < ca.length; i++) {
            var c = ca[i];
            while (c.charAt(0)==' ') c = c.substring(1,c.length);
            if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
        }

        return null;
    }

    function eraseCookie(name) {
        document.cookie = name +'=; Path=/; Expires=Thu, 01 Jan 1970 00:00:01 GMT;';
    }
</script>
</body>

</html>
