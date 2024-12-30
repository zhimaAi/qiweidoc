<?php

/**
 * 存储配置
 */

namespace Modules\Main\DTO;

use Common\DTO\CommonDTO;
use Yiisoft\Validator\Result;
use Yiisoft\Validator\Rule\Callback;
use Yiisoft\Validator\Rule\In;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Number;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\Rule\Type\IntegerType;
use Yiisoft\Validator\Rule\Type\StringType;
use Yiisoft\Validator\ValidationContext;

class CloudStorageSettingDTO extends CommonDTO
{
    public function getRules(): iterable
    {
        return [
            'local_session_file_retention_days' => [
                new Required('不能为空'),
                new IntegerType('格式不正确'),
                new Number(min: 0, lessThanMinMessage: '不能小于0'),
            ],
            'provider' => [
                new Required('不能为空'),
                new StringType('格式不正确'),
                new In(array_keys(self::getProviderRegionList()), message: '取值不正确'),
            ],
            'region' => [
                new Required('不能为空'),
                new StringType('格式不正确'),
            ],
            'endpoint' => [
                new Required('不能为空'),
                new StringType('格式不正确'),
            ],
            'bucket' => [
                new Required('不能为空'),
                new StringType('格式不正确'),
                new Length(max: 64, greaterThanMaxMessage: '不能超过64个字符长度'),
            ],
            'access_key' => [
                new Required('不能为空'),
                new StringType('格式不正确'),
                new Length(max: 64, greaterThanMaxMessage: '不能超过64个字符长度'),
            ],

            'secret_key' => [
                new Required('不能为空'),
                new StringType('格式不正确'),
                new Length(max: 64, greaterThanMaxMessage: '不能超过64个字符长度'),
            ],

            new Callback(
                static function (mixed $value, Callback $rule, ValidationContext $context) {
                    $provider = $context->getDataSet()->getAttributeValue('provider');
                    $region = $context->getDataSet()->getAttributeValue('region');
                    $regionList = self::getProviderRegionList();
                    if (empty($regionList[$provider][$region])) {
                        return (new Result())->addError('不合法', [], ['region']);
                    }
                    return new Result();
                },
                skipOnError: true,
            )
        ];
    }

    public static function getProviderRegionList(): array
    {
        return [
            // 参考: help.aliyun.com/zh/oss/user-guide/regions-and-endpoints
            '阿里云' => [
                // 亚太-中国
                'cn-hangzhou' => [
                    'name'                  => '华东1（杭州）',
                    'endpoint'              => 'https://oss-cn-hangzhou.aliyuncs.com',
                ],
                'cn-shanghai' => [
                    'name'                  => '华东2（上海）',
                    'endpoint'              => 'https://oss-cn-shanghai.aliyuncs.com',
                ],
                'cn-nanjing' => [
                    'name'                  => '华东5（南京-本地地域）',
                    'endpoint'              => 'https://oss-cn-nanjing.aliyuncs.com',
                ],
                'cn-fuzhou' => [
                    'name'                  => '华东6（福州-本地地域）',
                    'endpoint'              => 'https://oss-cn-fuzhou.aliyuncs.com',
                ],
                'cn-wuhan-lr' => [
                    'name'                  => '华中1（武汉-本地地域）',
                    'endpoint'              => 'https://oss-cn-wuhan-lr.aliyuncs.com',
                ],
                'cn-qingdao' => [
                    'name'                  => '华北1（青岛）',
                    'endpoint'              => 'https://oss-cn-qingdao.aliyuncs.com',
                ],
                'cn-beijing' => [
                    'name'                  => '华北2（北京）',
                    'endpoint'              => 'https://oss-cn-beijing.aliyuncs.com',
                ],
                'cn-zhangjiakou' => [
                    'name'                  => '华北3（张家口）',
                    'endpoint'              => 'https://oss-cn-zhangjiakou.aliyuncs.com',
                ],
                'cn-huhehaote' => [
                    'name'                  => '华北5（呼和浩特）',
                    'endpoint'              => 'https://oss-cn-huhehaote.aliyuncs.com',
                ],
                'cn-wulanchabu' => [
                    'name'                  => '华北6（乌兰察布）',
                    'endpoint'              => 'https://oss-cn-wulanchabu.aliyuncs.com',
                ],
                'cn-shenzhen' => [
                    'name'                  => '华南1（深圳）',
                    'endpoint'              => 'https://oss-cn-shenzhen.aliyuncs.com',
                ],
                'cn-heyuan' => [
                    'name'                  => '华南2（河源）',
                    'endpoint'              => 'https://oss-cn-heyuan.aliyuncs.com',
                ],
                'cn-guangzhou' => [
                    'name'                  => '华南3（广州）',
                    'endpoint'              => 'https://oss-cn-guangzhou.aliyuncs.com',
                ],
                'cn-chengdu' => [
                    'name'                  => '西南1（成都）',
                    'endpoint'              => 'https://oss-cn-chengdu.aliyuncs.com',
                ],
                'cn-hongkong' => [
                    'name'                  => '中国香港',
                    'endpoint'              => 'https://oss-cn-hongkong.aliyuncs.com',
                ],

                // 亚太-其他
                'ap-northeast-1' => [
                    'name'                  => '日本（东京）',
                    'endpoint'              => 'https://oss-ap-northeast-1.aliyuncs.com',
                ],
                'ap-northeast-2' => [
                    'name'                  => '韩国（首尔）',
                    'endpoint'              => 'https://oss-ap-northeast-2.aliyuncs.com',
                ],
                'ap-southeast-1' => [
                    'name'                  => '新加坡',
                    'endpoint'              => 'https://oss-ap-southeast-1.aliyuncs.com',
                ],
                'ap-southeast-3' => [
                    'name'                  => '马来西亚（吉隆坡）',
                    'endpoint'              => 'https://oss-ap-southeast-3.aliyuncs.com',
                ],
                'ap-southeast-5' => [
                    'name'                  => '印度尼西亚（雅加达）',
                    'endpoint'              => 'https://oss-ap-southeast-5.aliyuncs.com',
                ],
                'ap-southeast-6' => [
                    'name'                  => '菲律宾（马尼拉）',
                    'endpoint'              => 'https://oss-ap-southeast-6.aliyuncs.com',
                ],
                'ap-southeast-7' => [
                    'name'                  => '泰国（曼谷）',
                    'endpoint'              => 'https://oss-ap-southeast-7.aliyuncs.com',
                ],

                // 欧洲与美洲
                'eu-central-1' => [
                    'name'                  => '德国（法兰克福）',
                    'endpoint'              => 'https://oss-eu-central-1.aliyuncs.com',
                ],
                'eu-west-1' => [
                    'name'                  => '英国（伦敦）',
                    'endpoint'              => 'https://oss-eu-west-1.aliyuncs.com',
                ],
                'us-west-1' => [
                    'name'                  => '美国（硅谷）',
                    'endpoint'              => 'https://oss-us-west-1.aliyuncs.com',
                ],
                'us-east-1' => [
                    'name'                  => '美国（弗吉尼亚）',
                    'endpoint'              => 'https://oss-us-east-1.aliyuncs.com',
                ],
                'me-east-1' => [
                    'name'                  => '阿联酋（迪拜）',
                    'endpoint'              => 'https://oss-me-east-1.aliyuncs.com',
                ],
            ],

            // 参考: https://cloud.tencent.com/document/product/436/6224
            '腾讯云' => [

                // 中国大陆
                'ap-beijing-1' => [
                    'name'                  => '北京一区',
                    'endpoint'              => 'https://cos.ap-beijing-1.myqcloud.com',
                ],
                'ap-beijing' => [
                    'name'                  => '北京',
                    'endpoint'              => 'https://cos.ap-beijing.myqcloud.com',
                ],
                'ap-nanjing' => [
                    'name'                  => '南京',
                    'endpoint'              => 'https://cos.ap-nanjing.myqcloud.com',
                ],
                'ap-shanghai' => [
                    'name'                  => '上海',
                    'endpoint'              => 'https://cos.ap-shanghai.myqcloud.com',
                ],
                'ap-guangzhou' => [
                    'name'                  => '广州',
                    'endpoint'              => 'https://cos.ap-guangzhou.myqcloud.com',
                ],
                'ap-chengdu' => [
                    'name'                  => '成都',
                    'endpoint'              => 'https://cos.ap-chengdu.myqcloud.com',
                ],
                'ap-chongqing' => [
                    'name'                  => '重庆',
                    'endpoint'              => 'https://cos.ap-chongqing.myqcloud.com',
                ],

                // 中国香港及境外地域
                'ap-hongkong' => [
                    'name'                  => '中国香港',
                    'endpoint'              => 'https://cos.ap-hongkong.myqcloud.com',
                ],
                'ap-singapore' => [
                    'name'                  => '新加坡',
                    'endpoint'              => 'https://cos.ap-singapore.myqcloud.com',
                ],
                'ap-jakarta' => [
                    'name'                  => '雅加达',
                    'endpoint'              => 'https://cos.ap-jakarta.myqcloud.com',
                ],
                'ap-seoul' => [
                    'name'                  => '首尔',
                    'endpoint'              => 'https://cos.ap-seoul.myqcloud.com',
                ],
                'ap-bangkok' => [
                    'name'                  => '曼谷',
                    'endpoint'              => 'https://cos.ap-bangkok.myqcloud.com',
                ],
                'ap-tokyo' => [
                    'name'                  => '东京',
                    'endpoint'              => 'https://cos.ap-tokyo.myqcloud.com',
                ],
                'na-siliconvalley' => [
                    'name'                  => '硅谷（美西）',
                    'endpoint'              => 'https://cos.na-siliconvalley.myqcloud.com',
                ],
                'na-ashburn' => [
                    'name'                  => '弗吉尼亚（美东）',
                    'endpoint'              => 'https://cos.na-ashburn.myqcloud.com',
                ],
                'sa-saopaulo' => [
                    'name'                  => '圣保罗',
                    'endpoint'              => 'https://cos.sa-saopaulo.myqcloud.com',
                ],
                'eu-frankfurt' => [
                    'name'                  => '法兰克福',
                    'endpoint'              => 'https://cos.eu-frankfurt.myqcloud.com',
                ],
            ],

            // 参考: https://cloud.baidu.com/doc/BOS/s/xjwvyq9l4
            '百度云' => [
                's3.bj' => [
                    'name'          => '北京',
                    'endpoint'      => 'https://s3.bj.bcebos.com',
                ],
                's3.gz' => [
                    'name'          => '广州',
                    'endpoint'      => 'https://s3.gz.bcebos.com',
                ],
                's3.su' => [
                    'name'          => '苏州',
                    'endpoint'      => 'https://s3.su.bcebos.com',
                ],
                's3.bd' => [
                    'name'          => '保定',
                    'endpoint'      => 'https://s3.bd.bcebos.com',
                ],
                's3.fwh' => [
                    'name'          => '金融云武汉专区',
                    'endpoint'      => 'https://s3.fwh.bcebos.com',
                ],
                's3.fsh' => [
                    'name'          => '金融云上海专区',
                    'endpoint'      => 'https://s3.fsh.bcebos.com',
                ],
                's3.hkg' => [
                    'name'          => '香港',
                    'endpoint'      => 'https://s3.hkg.bcebos.com',
                ],
            ],

            // 参考: https://developer.qiniu.com/kodo/4088/s3-access-domainname
            '七牛云' => [
                'cn-east-1' => [
                    'name'          => '华东-浙江',
                    'endpoint'      => 'https://s3.cn-east-1.qiniucs.com',
                ],
                'cn-east-2' => [
                    'name'          => '华东-浙江2',
                    'endpoint'      => 'https://s3.cn-east-2.qiniucs.com',
                ],
                'cn-north-1' => [
                    'name'          => '华北-河北',
                    'endpoint'      => 'https://s3.cn-north-1.qiniucs.com',
                ],
                'cn-south-1' => [
                    'name'          => '华南-广东',
                    'endpoint'      => 'https://s3.cn-south-1.qiniucs.com',
                ],
                'cn-northwest-1' => [
                    'name'          => '西北-陕西1',
                    'endpoint'      => 'https://s3.cn-northwest-1.qiniucs.com',
                ],
                'us-north-1' => [
                    'name'          => '北美-洛杉矶',
                    'endpoint'      => 'https://s3.us-north-1.qiniucs.com',
                ],
                'ap-southeast-1' => [
                    'name'          => '亚太-新加坡（原东南亚）',
                    'endpoint'      => 'https://s3.ap-southeast-1.qiniucs.com',
                ],
                'ap-southeast-2' => [
                    'name'          => '亚太-河内',
                    'endpoint'      => 'https://s3.ap-southeast-2.qiniucs.com',
                ],
                'ap-southeast-3' => [
                    'name'          => '亚太-胡志明',
                    'endpoint'      => 'https://s3.ap-southeast-3.qiniucs.com',
                ],
            ],

            // 参考: https://console.huaweicloud.com/apiexplorer/#/endpoint/OBS
            '华为云' => [
                'cn-north-1' => [
                    'name'          => '华北-北京一',
                    'endpoint'      => 'https://obs.cn-north-1.myhuaweicloud.com',
                ],
                'cn-north-4' => [
                    'name'          => '华北-北京四',
                    'endpoint'      => 'https://obs.cn-north-4.myhuaweicloud.com',
                ],
                'cn-north-9' => [
                    'name'          => '华北-乌兰察布一',
                    'endpoint'      => 'https://obs.cn-north-9.myhuaweicloud.com',
                ],
                'cn-east-2'  => [
                    'name'          => '华东-上海二',
                    'endpoint'      => 'https://obs.cn-east-2.myhuaweicloud.com',
                ],
                'cn-east-3'  => [
                    'name'          => '华东-上海一',
                    'endpoint'      => 'https://obs.cn-east-3.myhuaweicloud.com',
                ],
                'ap-southeast-1' => [
                    'name'          => '中国香港',
                    'endpoint'      => 'https://obs.ap-southeast-1.myhuaweicloud.com',
                ],
                'ap-southeast-2' => [
                    'name'          => '亚太-曼谷',
                    'endpoint'      => 'https://obs.ap-southeast-2.myhuaweicloud.com',
                ],
                'ap-southeast-3' => [
                    'name'          => '亚太-新加坡',
                    'endpoint'      => 'https://obs.ap-southeast-3.myhuaweicloud.com',
                ],
                'ap-southeast-4' => [
                    'name'          => '亚太-雅加达',
                    'endpoint'      => 'https://obs.ap-southeast-4.myhuaweicloud.com',
                ],
                'af-south-1' => [
                    'name'          => '非洲-约翰内斯堡',
                    'endpoint'      => 'https://obs.af-south-1.myhuaweicloud.com',
                ],
            ],

            // 参考: https://docs.ucloud.cn/ufile/s3/s3_introduction
            'UCloud优刻得' => [
                's3-cn-bj' => [
                    'name'          => '华北一',
                    'endpoint'      => 'https://s3-cn-bj.ufileos.com',
                ],
                's3-cn-wlcb' => [
                    'name'          => '华北二',
                    'endpoint'      => 'https://s3-cn-wlcb.ufileos.com',
                ],
                's3-cn-sh' => [
                    'name'          => '上海',
                    'endpoint'      => 'https://s3-cn-sh2.ufileos.com',
                ],
                's3-cn-gd' => [
                    'name'          => '广州',
                    'endpoint'      => 'https://s3-cn-gd.ufileos.com',
                ],
                's3-hk' => [
                    'name'          => '香港',
                    'endpoint'      => 'https://s3-hk.ufileos.com',
                ],
                's3-us-ca' => [
                    'name'          => '洛杉矶',
                    'endpoint'      => 'https://s3-us-ca.ufileos.com',
                ],
                's3-sg' => [
                    'name'          => '新加坡',
                    'endpoint'      => 'https://s3-sg.ufileos.com',
                ],
                's3-idn-jakarta' => [
                    'name'          => '雅加达',
                    'endpoint'      => 'https://s3-idn-jakarta.ufileos.com',
                ],
                's3-tw-tp'  => [
                    'name'          => '台北',
                    'endpoint'      => 'https://s3-tw-tp.ufileos.com',
                ],
                's3-afr-nigeria' => [
                    'name'          => '拉各斯',
                    'endpoint'      => 'https://s3-afr-nigeria.ufileos.com',
                ],
                's3-bra-saopaulo' => [
                    'name'          => '圣保罗',
                    'endpoint'      => 'https://s3-bra-saopaulo.ufileos.com',
                ],
                's3-uae-dubai' => [
                    'name'          => '迪拜',
                    'endpoint'      => 'https://s3-uae-dubai.ufileos.com',
                ],
                's3-ge-fra' => [
                    'name'          => '法兰克福',
                    'endpoint'      => 'https://s3-ge-fra.ufileos.com',
                ],
                's3-vn-sng' => [
                    'name'          => '胡志明市',
                    'endpoint'      => 'https://s3-vn-sng.ufileos.com',
                ],
                's3-us-ws' => [
                    'name'          => '华盛顿',
                    'endpoint'      => 'https://s3-us-ws.ufileos.com',
                ],
                's3-ind-mumbai' => [
                    'name'          => '孟买',
                    'endpoint'      => 'https://s3-ind-mumbai.ufileos.com',
                ],
                's3-kr-seoul' => [
                    'name'          => '首尔',
                    'endpoint'      => 'https://s3-kr-seoul.ufileos.com',
                ],
                's3-jpn-tky' => [
                    'name'          => '东京',
                    'endpoint'      => 'https://s3-jpn-tky.ufileos.com',
                ],
                's3-th-bkk' => [
                    'name'          => '曼谷',
                    'endpoint'      => 'https://s3-th-bkk.ufileos.com',
                ],
                's3-uk-london' => [
                    'name'          => '伦敦',
                    'endpoint'      => 'https://s3-uk-london.ufileos.com',
                ],
            ],

            // 参考: https://min.io/docs/minio/linux/developers/go/API.html#MakeBucket
            'MinIO' => [
                'us-east-1' => [
                    'name'      => 'us-east-1',
                    'endpoint'  => '',
                ],
                'us-east-2' => [
                    'name'      => 'us-east-2',
                    'endpoint'  => '',
                ],
                'us-west-1' => [
                    'name'  => 'us-west-1',
                    'endpoint' => '',
                ],
                'us-west-2' => [
                    'name' => 'us-west-2',
                    'endpoint' => '',
                ],
                'ca-central-1' => [
                    'name' => 'ca-central-1',
                    'endpoint' => '',
                ],
                'eu-west-1' => [
                    'name' => 'eu-west-1',
                    'endpoint' => '',
                ],
                'eu-west-2' => [
                    'name' => 'eu-west-2',
                    'endpoint' => '',
                ],
                'eu-west-3' => [
                    'name' => 'eu-west-3',
                    'endpoint' => '',
                ],
                'eu-central-1' => [
                    'name' => 'eu-central-1',
                    'endpoint' => '',
                ],
                'eu-north-1' => [
                    'name' => 'eu-north-1',
                    'endpoint' => '',
                ],
                'ap-east-1' => [
                    'name' => 'ap-east-1',
                    'endpoint' => '',
                ],
                'ap-south-1' => [
                    'name' => 'ap-south-1',
                    'endpoint' => '',
                ],
                'ap-southeast-1' => [
                    'name' => 'ap-southeast-1',
                    'endpoint' => '',
                ],
                'ap-southeast-2' => [
                    'name' => 'ap-southeast-2',
                    'endpoint' => '',
                ],
                'ap-northeast-1' => [
                    'name' => 'ap-northeast-1',
                    'endpoint' => '',
                ],
                'ap-northeast-2' => [
                    'name' => 'ap-northeast-2',
                    'endpoint' => '',
                ],
                'ap-northeast-3' => [
                    'name' => 'ap-northeast-3',
                    'endpoint' => '',
                ],
                'me-south-1' => [
                    'name' => 'me-south-1',
                    'endpoint' => '',
                ],
                'sa-east-1' => [
                    'name' => 'sa-east-1',
                    'endpoint' => '',
                ],
                'us-gov-west-1' => [
                    'name' => 'us-gov-west-1',
                    'endpoint' => '',
                ],
                'us-gov-east-1' => [
                    'name' => 'us-gov-east-1',
                    'endpoint' => '',
                ],
                'cn-north-1' => [
                    'name' => 'cn-north-1',
                    'endpoint' => '',
                ],
                'cn-northwest-1' => [
                    'name' => 'cn-northwest-1',
                    'endpoint' => '',
                ],
            ],
        ];
    }
}
