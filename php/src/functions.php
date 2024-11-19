<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

use Yiisoft\Db\Migration\MigrationBuilder;

function ddump($var, $echo = true, $label = null, $strict = true, $level = 0): ?string
{
    $label = ($label === null) ? '' : rtrim($label) . ' ';
    if (! $strict) {
        if (ini_get('html_errors')) {
            $output = print_r($var, true);
            $output = "<pre>" . $label . htmlspecialchars($output, ENT_QUOTES) . "</pre>";
        } else {
            $output = $label . " : " . print_r($var, true);
        }
    } else {
        ob_start();
        var_dump($var);
        $output = ob_get_clean();
        if (! extension_loaded('xdebug')) {
            $output = preg_replace("/\]\=\>".PHP_EOL."(\s+)/m", "] => ", $output);
            if (php_sapi_name() !== 'cli') {
                // $output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
                $output = '<pre>' . $label . $output . '</pre>';
            }
        }
    }

    $eol = php_sapi_name() == 'cli' ? PHP_EOL : '<BR/>';
    $debugInfo = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $level + 1);
    $message = 'file: '.$debugInfo[$level]['file']. ':'.$debugInfo[$level]['line'] . $eol;
    $output = $message . $output;
    $output = now() . ' '. $output . $eol;
    if ($echo) {
        echo($output);

        return null;
    } else {
        return $output;
    }
}


/**
 * @param $data
 * @param $num
 * Notes: 数据分组
 * User: rand
 * Date: 2024/11/7 10:26
 * @return array
 */
function arraySplit($data, $num = 5): array
{
    $arrRet = [];
    if (! isset($data) || empty($data)) {
        return $arrRet;
    }

    $iCount = count($data) / $num;
    if (! is_int($iCount)) {
        $iCount = ceil($iCount);
    } else {
        $iCount += 1;
    }
    for ($i = 0; $i < $iCount;++$i) {
        $arrInfos = array_slice($data, $i * $num, $num);
        if (empty($arrInfos)) {
            continue;
        }
        $arrRet[] = $arrInfos;
        unset($arrInfos);
    }

    return $arrRet;
}

/**
 * 从毫秒时间戳创建 DateTimeImmutable
 *
 * @param int|float $timestamp 毫秒时间戳
 * @param string $timezone 时区，默认Asia/Shanghai
 * @return DateTimeImmutable
 */
function datetime_from_milliseconds(
    int|float $timestamp,
    string $timezone = 'Asia/Shanghai'
): DateTimeImmutable {
    return (new DateTimeImmutable())
        ->setTimestamp((int) ($timestamp / 1000))
        ->setTimezone(new DateTimeZone($timezone));
}

/**
 * 从秒级时间戳创建 DateTimeImmutable
 *
 * @param int $timestamp 秒级时间戳
 * @param string $timezone 时区，默认Asia/Shanghai
 * @return DateTimeImmutable
 */
function datetime_from_seconds(
    int $timestamp,
    string $timezone = 'Asia/Shanghai'
): DateTimeImmutable {
    return (new DateTimeImmutable())
        ->setTimestamp($timestamp)
        ->setTimezone(new DateTimeZone($timezone));
}

/**
 * 从日期时间字符串创建 DateTimeImmutable
 *
 * @param string $datetime 日期时间字符串，如：2024-02-28 14:00:00
 * @param string $format 日期格式，默认Y-m-d H:i:s
 * @param string $timezone 时区，默认Asia/Shanghai
 * @return DateTimeImmutable|null 解析失败时返回null
 */
function datetime_from_string(
    string $datetime,
    string $format = 'Y-m-d H:i:s',
    string $timezone = 'Asia/Shanghai'
): ?DateTimeImmutable {
    $tz = new DateTimeZone($timezone);
    $date = DateTimeImmutable::createFromFormat($format, $datetime, $tz);

    return $date ?: null;
}

function migrate_exec(MigrationBuilder $b, string $statements)
{
    $statements = explode(";", $statements);
    $b->getDb()->transaction(function () use ($b, $statements) {
        foreach ($statements as $statement) {
            $b->execute($statement);
        }
    });
}

function now()
{
    return date('Y-m-d H:i:s');
}
