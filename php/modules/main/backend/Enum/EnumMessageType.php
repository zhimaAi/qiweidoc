<?php

namespace Modules\Main\Enum;

use Closure;

/**
 * 消息类别
 */
enum EnumMessageType: string
{
    case Text                   = 'text';
    case Image                  = 'image';
    case Revoke                 = 'revoke';
    case Agree                  = 'agree';
    case Disagree               = 'disagree';
    case Voice                  = 'voice';
    case Video                  = 'video';
    case Card                   = 'card';
    case Location               = 'location';
    case Emotion                = 'emotion';
    case File                   = 'file';
    case Link                   = 'link';
    case WeApp                  = 'weapp';
    case ChatRecord             = 'chatrecord';
    case Todo                   = 'todo';
    case Vote                   = 'vote';
    case Collect                = 'collect';
    case Redpacket              = 'redpacket';
    case Meeting                = 'meeting';
    case MeetingNotification    = 'meeting_notification';
    case DocMsg                 = 'docmsg';
    case Markdown               = 'markdown';
    case News                   = 'news';
    case Calendar               = 'calendar';
    case Mixed                  = 'mixed';
    case MeetingVoiceCall       = 'meeting_voice_call';
    case VoipDocShare           = 'voip_doc_share';
    case ExternalRedpacket      = 'external_redpacket';
    case SphFeed                = 'sphfeed';
    case VoipText               = 'voiptext';
    case QyDiskFile             = 'qydiskfile';

    public function getLabel(): string
    {
        return match ($this) {
            self::Text                  => '文本',
            self::Image                 => '图片',
            self::Revoke                => '撤回消息',
            self::Agree                 => '同意会话聊天',
            self::Disagree              => '不同意会话聊天',
            self::Voice                 => '语音',
            self::Video                 => '视频',
            self::Card                  => '名片',
            self::Location              => '位置',
            self::Emotion               => '表情包',
            self::File                  => '文件',
            self::Link                  => '链接',
            self::WeApp                 => '小程序',
            self::ChatRecord            => '会话记录消息',
            self::Todo                  => '待办',
            self::Collect               => '填表',
            self::Vote                  => '投票',
            self::Redpacket             => '红包消息',
            self::Meeting               => '会议邀请消息',
            self::MeetingNotification   => '会议控制消息',
            self::DocMsg                => '在线文档消息',
            self::Markdown              => 'Markdown格式消息',
            self::News                  => '图文消息',
            self::Calendar              => '日程消息',
            self::Mixed                 => '混合消息',
            self::MeetingVoiceCall      => '音频存档消息',
            self::VoipDocShare          => '音频共享文档消息',
            self::ExternalRedpacket     => '互通红包消息',
            self::SphFeed               => '视频号消息',
            self::VoipText              => '音视频通话',
            self::QyDiskFile            => '微盘文件',
        };
    }

    public function getMessageHandler(): Closure
    {
        return match ($this) {
            self::Text => fn ($data) => ['raw_content' => $data['text'], 'msg_content' => $data['text']['content']],
            self::Image => fn ($data) => ['raw_content' => $data['image']],
            self::Revoke => fn ($data) => ['raw_content' => $data['revoke']],
            self::Agree => fn ($data) => ['raw_content' => $data['agree']],
            self::Disagree => fn ($data) => ['raw_content' => $data['disagree']],
            self::Voice => fn ($data) => ['raw_content' => $data['voice']],
            self::Video => fn ($data) => ['raw_content' => $data['video']],
            self::Card => fn ($data) => ['raw_content' => $data['card']],
            self::Location => fn ($data) => ['raw_content' => $data['location']],
            self::Emotion => fn ($data) => ['raw_content' => $data['emotion']],
            self::File => fn ($data) => ['raw_content' => $data['file']],
            self::Link => fn ($data) => ['raw_content' => $data['link']],
            self::WeApp => fn ($data) => ['raw_content' => $data['weapp']],
            self::ChatRecord => fn ($data) => ['raw_content' => $data['chatrecord']],
            self::Todo => fn ($data) => ['raw_content' => $data['todo']],
            self::Vote => fn ($data) => ['raw_content' => $data['vote']],
            self::Collect => fn ($data) => ['raw_content' => $data['collect']],
            self::Redpacket => fn ($data) => ['raw_content' => $data['redpacket']],
            self::Meeting => fn ($data) => ['raw_content' => $data['meeting']],
            self::MeetingNotification => fn ($data) => ['raw_content' => $data['info']],
            self::DocMsg => fn ($data) => ['raw_content' => $data['doc']],
            self::Markdown => fn ($data) => ['raw_content' => $data['info']],
            self::News => fn ($data) => ['raw_content' => $data['news']],
            self::Calendar => fn ($data) => ['raw_content' => $data['calendar']],
            self::Mixed => fn ($data) => ['raw_content' => $data['mixed']],
            self::MeetingVoiceCall => fn ($data) => ['raw_content' => array_merge($data['meeting_voice_call'], ['voiceid' => $data['voiceid']])],
            self::VoipDocShare => fn ($data) => ['raw_content' => $data['voip_doc_share']],
            self::ExternalRedpacket => fn ($data) => ['raw_content' => $data['redpacket']],
            self::SphFeed => fn ($data) => ['raw_content' => $data['sphfeed']],
            self::VoipText => fn ($data) => ['raw_content' => $data['info']],
            self::QyDiskFile => fn ($data) => ['raw_content' => $data['info']],
        };
    }
}
