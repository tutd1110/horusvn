import { Modal } from 'ant-design-vue';
import { h } from 'vue';

export const errorModal = (t, errorMessages) => {
    Modal.warning({
        title: t('message.MSG-TITLE-W'),
        content: h('ul', {}, errorMessages.value.split('<br>').map((error) => { return h('li', error) })),
    });
};