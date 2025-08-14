<?php

namespace Modules\Main\Service;

use SplFileObject;

/**
 * Notes:
 * User: rand
 * Date: 2025/8/11 17:57
 */
class CsvService
{
    // 分隔符
    private $glue = ',';
    // excel文件后缀名
    private $fileExt = '.csv';
    // splFileObj对象
    private $splFileObj;
    // 存储所有创建的文件
    private $allFiles = [];

    /**
     * Notes: 写入csv缓冲区
     * User: Geek
     * DateTime: 2022/1/18 17:54
     * @param $data
     * @param $file_path
     * @param $file_name
     */
    public function writeCsv($data, $file_path, $file_name)
    {
        if (empty($this->splFileObj)) {
            $this->allFiles[] = $file_name;
            $file = $file_path . $file_name;
            $this->splFileObj = new SplFileObject($file, 'a+');
        }
        $data_str = implode($this->glue, $data) . PHP_EOL;
        $data_str = $this->iconvLocal($data_str);
        $this->splFileObj->fwrite($data_str);
    }

    public function iconvLocal($str)
    {
        return iconv('UTF-8', 'gbk//IGNORE', $str);
    }

    /**
     * Notes: 清空缓冲区，并把数据写入csv文件中
     * User: Geek
     * DateTime: 2022/1/18 17:55
     */
    public function saveCsv()
    {
        if (is_object($this->splFileObj)) {
            $this->splFileObj->fflush();
        }
        $this->splFileObj = null;
    }

    /**
     * 获取文件名
     * User: fabian
     * Date: 2022/09/07
     * @param $file
     * @return string
     */
    public function getFileName($file): string
    {
        return $file . $this->fileExt;
    }

}
