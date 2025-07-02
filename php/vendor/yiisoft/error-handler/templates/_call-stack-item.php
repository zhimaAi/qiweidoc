<?php

use Yiisoft\ErrorHandler\Renderer\HtmlRenderer;

/**
 * @var string|null $file
 * @var int|null $line
 * @var string|null $class
 * @var string|null $function
 * @var int $index
 * @var string[] $lines
 * @var int $begin
 * @var int $end
 * @var array $args
 * @var bool $isVendorFile
 * @var ReflectionMethod[] $reflectionParameters
 * @var HtmlRenderer $this
 */

$icon = <<<HTML
<svg class="external-link" width="20" height="20" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
    <title>Open the target page</title>
    <path d="M20,11a1,1,0,0,0-1,1v6a1,1,0,0,1-1,1H6a1,1,0,0,1-1-1V6A1,1,0,0,1,6,5h6a1,1,0,0,0,0-2H6A3,3,0,0,0,3,6V18a3,3,0,0,0,3,3H18a3,3,0,0,0,3-3V12A1,1,0,0,0,20,11Z,M16,5h1.58l-6.29,6.28a1,1,0,0,0,0,1.42,1,1,0,0,0,1.42,0L19,6.42V8a1,1,0,0,0,1,1h0a1,1,0,0,0,1-1V4a1,1,0,0,0-1-1L16,3h0a1,1,0,0,0,0,2Z" />
    <path d="M16,5h1.58l-6.29,6.28a1,1,0,0,0,0,1.42,1,1,0,0,0,1.42,0L19,6.42V8a1,1,0,0,0,1,1h0a1,1,0,0,0,1-1V4a1,1,0,0,0-1-1L16,3h0a1,1,0,0,0,0,2Z" />
</svg>
HTML;
?>
<li class="<?= (!empty($lines) && ($index === 1 || !$isVendorFile)) ? 'application ' : '' ?>call-stack-item"
    data-line="<?= (int) ($line - $begin) ?>">
    <div class="element-wrap">
        <div class="flex-1 mw-100">
            <?php if ($file !== null): ?>
                <span class="file-name">
                    <?= "{$index}. in {$this->htmlEncode($file)}" ?>
                    <?php if ($this->traceHeaderLine !== null): ?>
                        <?= strtr($this->traceHeaderLine, ['{file}' => $file, '{line}' => $line + 1, '{icon}' => $icon]) ?>
                    <?php endif ?>
                </span>
            <?php endif ?>

            <?php if ($function !== null): ?>
                <span class="function-info word-break">
                    <?php
                    echo $file === null ? "{$index}." : '&mdash;&nbsp;';
                    $function = $class === null ? $function : "{$this->removeAnonymous($class)}::$function";

                    echo '<span class="function">' . $this->htmlEncode($function) . '</span>';
                    echo '<span class="arguments">(';
                    echo '<span class="short-arguments">' . $this->argumentsToString($args, true) . '</span>';
                    echo '<span class="full-arguments hidden">' . $this->argumentsToString($args, false) . '</span>';
                    echo ')</span>';
                    ?>
                </span>
            <?php endif ?>
        </div>

        <?php if ($line !== null): ?>
            <div class="flex flex-column">
                <?= sprintf('at line %d', $line + 1) ?>
                <?php if (!empty($args)): ?>
                    <button class="button toggleFunctionArguments">arguments</button>
                <?php endif ?>
            </div>
        <?php endif ?>
    </div>
    <div class="functionArguments hidden">
        <?php if ($function !== null) { ?>
            <div class="functionArguments_title">
                <?= $this->htmlEncode($function) ?> arguments:
            </div>
            <table>
                <?php foreach ($args as $key => $argument) { ?>
                    <tr>
                        <td class="functionArguments_key">
                            $<?= $this->htmlEncode(
                                is_int($key) && isset($reflectionParameters[$key])
                                    ? $reflectionParameters[$key]->getName()
                                    : $key
                            ) ?>
                        </td>
                        <td class="functionArguments_type">
                            <?= gettype($argument) ?>
                        </td>
                        <td class="functionArguments_value word-break">
                            <?= $this->argumentsToString(is_array($argument) ? $argument : [$argument]) ?>
                        </td>
                    </tr>
                <?php } ?>
            </table>
        <?php } ?>
    </div>
    <?php if (!empty($lines)): ?>
        <div class="element-code-wrap">
            <div class="code-wrap">
                <div class="error-line"></div>
                <?php for ($i = $begin; $i <= $end; ++$i): ?><div class="hover-line"></div><?php endfor ?>
                <div class="code">
                    <?php for ($i = $begin; $i <= $end; ++$i): ?><span class="lines-item"><?= (int) ($i + 1) ?></span><?php endfor ?>
                    <pre class="codeBlock language-php"><?php
                        // Fill empty lines with a whitespace to avoid rendering problems in Opera.
                        for ($i = $begin; $i <= $end; ++$i) {
                            echo (trim($lines[$i]) === '') ? " \n" : $this->htmlEncode($lines[$i]);
                        }
                        ?></pre>
                </div>
            </div>
        </div>
    <?php endif ?>
</li>
