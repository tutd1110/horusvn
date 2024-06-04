<template>
    <a-modal v-model:visible="visible" style="width:60%; font-weight: bold" :footer="null" :maskClosable="false"
        :closable="true" :title="title" @cancel="handleCancel">
        <a-form :model="formState" autocomplete="off" style="width:170%;">
            <a-row>
                <a-col :span="12" :offset="1" style="padding-bottom: 108px;">
                    <label name="name">Thông tin công việc</label>
                    <QuillEditor
                        theme="snow"
                        v-model:content="formState.description"
                        :toolbar="toolbar"
                        contentType="html"
                        @blur="onChangeDescription()"
                    />
                </a-col>
            </a-row>
            <a-row>
                <a-col :span="12" :offset="1">
                    <upload-file :data="formState" url="task"></upload-file>
                </a-col>
            </a-row>
        </a-form>
    </a-modal>
</template>
<script>
import { Modal, notification } from 'ant-design-vue';
import axios from 'axios';
import { ref, h } from 'vue';
import { QuillEditor } from '@vueup/vue-quill'
import '@vueup/vue-quill/dist/vue-quill.snow.css';
import { useI18n } from 'vue-i18n';
import UploadFile from './UploadFile.vue';

export default ({
    components: {
        QuillEditor,
        UploadFile
    },
    emits: ['saved'],
    setup(props, { emit }) {
        const { t } = useI18n();
        const visible = ref(false);
        const countChanged = ref(0);
        const title = ref("");
        const errorMessages = ref();
        const isReadonly = ref(false);
        const formState = ref({
            id: "",
            name: "",
            description: "",
            files: []
        });
        const toolbar = [
            [{ header: [1, 2, 3, 4, 5, 6, false] }],
            [{ size: ['small', false, 'large', 'huge'] }],
            ['bold', 'italic', 'underline', 'strike'],
            [{ font: [] }],
            ['link'],
            ['clean'],
        ];

        const formClear = () => {
            countChanged.value = 0;
            formState.value.name = ref("");
            formState.value.description = ref("<p><br></p>", "");
            title.value = "";
        };

        //get task's data
        const initData = (id) => {
            axios.get('/api/department/task/get_task_description_by_id', {
                params:{
                    id: id,
                }})
                .then(response => {
                    formState.value = response.data;

                    title.value = "Chỉnh sửa thông tin công việc: " + formState.value.name;
                })
                .catch((error) => {
                    errorMessages.value = error.response.data.errors;//put message content in ref
                    //When search target data does not exist
                    formState.value = []; //dataSource empty
                    errorModal();//Show error message modally
                });
        };

        //info mode
        const ShowWithDescriptionMode = (id, is_readonly = false) => {
            visible.value = true;
            //form initialization
            formClear();
            isReadonly.value = is_readonly
            initData(id);
        }

        const onChangeDescription = () => {
            if (!isReadonly.value) {
                update(formState.value.id, 'description', formState.value.description)
            }
        }

        const update = (id, column, value) => {
            let submitData = {
                id: id,
                [column]: value
            }

            axios.patch('/api/department/task/quick_update', submitData)
            .then(response => {
                notification.success({
                    message: t('message.MSG-TITLE-W'),
                    description: response.data.success,
                });

                countChanged.value++;
            })
            .catch(error => {
                errorMessages.value = error.response.data.errors;//put message content in ref
                errorModal(false);//show error message modally
            })
        }

        const handleCancel = () => {
            if (countChanged.value > 0) {
                emit('saved');
            }
        }

        const errorModal = () => {
            Modal.warning({
                title: t('message.MSG-TITLE-W'),
                content: h('ul', {}, errorMessages.value.split('<br>').map((error) => { return h('li', error) })),
            });
        };

        notification.config({
            placement: 'bottomLeft',
            duration: 3,
            rtl: true,
        });

        return {
            formState,
            toolbar,
            visible,
            title,
            ShowWithDescriptionMode,
            onChangeDescription,
            handleCancel
        };
    }
})
</script>