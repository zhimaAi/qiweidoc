import {ref} from 'vue';
import BenzAMRRecorder from 'benz-amr-recorder';
import {message} from 'ant-design-vue';

export function VoicePlayHandle() {
    const _key = ref(null)
    const _player = ref(null)
    const clear = () => {
        _key.value = null
        _player.value = null
    }
    const stop = () => {
        _player.value.stop()
    }
    const play = (key, voice) => {
        if (_player.value !== null) {
            stop()
            if (key === _key.value) {
                clear()
                return
            }
        }
        try {
            _key.value = key
            _player.value = new BenzAMRRecorder();
            _player.value.initWithUrl(voice).then(res => {
                _player.value.play()
            })
            _player.value.onEnded(() => {
                clear()
            })
        } catch (e) {
            console.error('播放失败:', err);
            message.error('播放失败，请检查文件！')
        }
    }
    const getPlayerKey = () => {
        return _key
    }
    return {
        play,
        stop,
        clear,
        getPlayerKey,
    }
}
