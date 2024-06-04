<template>
    <a-modal v-model:visible="visible" style="width:40%; font-weight: bold" :maskClosable="false"
        :closable="false" :title="title" @ok="handleOk">
        <a-row>
            <a-col :span="24" :offset="0">
                <QuillEditor
                    theme="snow"
                    v-model:content="formState.note"
                    :toolbar="toolbar"
                    contentType="html"
                />
            </a-col>
        </a-row>
        <a-row style="margin-top: 100px;">
            <a-col :span="24" :offset="0">
                <a-list
                    v-if="comments.length"
                    :data-source="comments"
                    :header="`${comments.length} ${comments.length > 1 ? 'replies' : 'reply'}`"
                    item-layout="horizontal"
                >
                    <template #renderItem="{ item }">
                    <a-list-item>
                        <a-comment
                            :author="item.fullname"
                            :avatar="item.avatar"
                            :datetime="item.created_at"
                        >
                            <template #content>
                                <div v-html="item.comment"></div>
                            </template>
                        </a-comment>
                        <a v-if="userLogin.id == item.user_id" style="color: red" @click="onClickDelete(item.id)">Delete</a>
                    </a-list-item>
                    </template>
                </a-list>
                <a-comment>
                    <template #content>
                        <a-form-item>
                            <QuillEditor
                                theme="snow"
                                v-model:content="commentText"
                                :toolbar="toolbar"
                                contentType="html"
                            />
                        </a-form-item>
                        <a-form-item>
                            <a-button html-type="submit" :loading="submitting" type="primary" @click="handleSubmit">
                                Add Comment
                            </a-button>
                        </a-form-item>
                    </template>
                </a-comment>
            </a-col>
        </a-row>
    </a-modal>
</template>
<script>
import { Modal, notification } from 'ant-design-vue';
import axios from 'axios';
import { ref, h } from 'vue';
import { QuillEditor } from '@vueup/vue-quill'
import '@vueup/vue-quill/dist/vue-quill.snow.css';
import { useI18n } from 'vue-i18n';
import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc'
import timezone from 'dayjs/plugin/timezone'
import { TIME_ZONE } from '../const.js'
import relativeTime from 'dayjs/plugin/relativeTime';

dayjs.extend(utc);
dayjs.extend(timezone);
dayjs.extend(relativeTime);

export default ({
    components: {
        QuillEditor
    },
    emits: ['saved'],
    setup(props, { emit }) {
        const { t } = useI18n();
        const visible = ref(false);
        const title = ref("");
        const errorMessages = ref();
        const selectedRecord = ref("");//Store ID of selected row
        const formState = ref({
            id: "",
            name: "",
            description: ""
        });
        const userLogin = ref({});
        const strictDateFormat = "YYYY/MM/DD HH:mm:ss";
        const comments = ref([]);
        const commentText = ref("<p><br></p>");
        const submitting = ref(false);
        const toolbar = [
            [{ header: [1, 2, 3, 4, 5, 6, false] }],
            [{ size: ['small', false, 'large', 'huge'] }],
            ['bold', 'italic', 'underline', 'strike'],
            [{ font: [] }],
            ['link', 'video'],
            ['clean'],
        ];

        const formClear = () => {
            formState.value.note = ref("<p><br></p>", "");
            commentText.value = "<p><br></p>";
        };

        //get task's data
        const initData = (id) => {
            axios.get('/api/task_assignments/get_task_assignment_by_id', {
                params:{
                    id: id,
                }})
                .then(response => {
                    formState.value = response.data;
                })
                .catch((error) => {
                    errorMessages.value = error.response.data.errors;//put message content in ref
                    //When search target data does not exist
                    formState.value = []; //dataSource empty
                    errorModal();//Show error message modally
                });

            //get user login
            axios.get('/api/task_assignment_comments/get_user_login')
                .then(response => {
                    userLogin.value = response.data;
                })
                .catch((error) => {
                    errorMessages.value = error.response.data.errors;//put message content in ref
                    //When search target data does not exist
                    userLogin.value = {}; //dataSource empty
                    errorModal();//Show error message modally
                });

            //get list comments
            initComments();
        };

        const initComments = () => {
            //get list comments
            axios.get('/api/task_assignment_comments/list', {
            params:{
                task_assignment_id: selectedRecord.value,
            }})
            .then(response => {
                comments.value = transferComments(response.data);
            })
            .catch((error) => {
                errorMessages.value = error.response.data.errors;//put message content in ref
                //When search target data does not exist
                comments.value = []; //dataSource empty
                errorModal();//Show error message modally
            });
        }

        //note mode
        const ShowWithNoteMode = (id) => {
            title.value = "Ghi chú"
            visible.value = true;
            //form initialization
            formClear();
            selectedRecord.value = id
            initData(id);
        }

        const handleOk = e => {
            axios.patch('/api/task_assignments/update', {
                id: formState.value.id,
                note: formState.value.note
            })
            .then(response => {
                notification.success({
                    message: t('message.MSG-TITLE-W'),
                    description: response.data.success,
                });

                visible.value = false;
                emit('saved');
            })
            .catch(error => {
                errorMessages.value = error.response.data.errors;
                errorModal(false);
            })
        };

        const handleSubmit = () => {
            submitting.value = true;
            axios.post('/api/task_assignment_comments/store', {
                task_assignment_id: selectedRecord.value,
                comment: commentText.value
            })
            .then(response => {
                submitting.value = false;
                initComments();
                commentText.value = "<p><br></p>";
            })
            .catch((error) => {
                submitting.value = false;
                errorMessages.value = error.response.data.errors;
                errorModal(false);
            });
        };

        const transferComments = (data) => {
            var newData = [];

            data.forEach(function(item, index) {
                let time = dayjs(item.created_at).tz(TIME_ZONE.ZONE).fromNow()
                let htmlComment = renderHTML(item.comment)

                let value = {
                    id: item.id,
                    user_id: item.user_id,
                    fullname: item.fullname,
                    avatar: "/image/"+item.avatar,
                    comment: htmlComment,
                    created_at: time
                };

                newData.push(value);
            });

            return newData;
        };

        const onClickDelete = (task_assignment_comment_id) => {
            axios.delete('/api/task_assignment_comments/delete', {
                params: {
                    id: task_assignment_comment_id
                }
            })
            .then(response => {
                notification.success({
                    message: t('message.MSG-TITLE-W'),
                    description: response.data.success,
                });

                initComments()
            })
            .catch(error => {
                errorMessages.value = error.response.data.errors;
                errorModal();
            })
        }

        const renderHTML = (comment) => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(comment, 'text/html');

            return doc.body.innerHTML;
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
            userLogin,
            comments,
            submitting,
            commentText,
            visible,
            title,
            ShowWithNoteMode,
            handleOk,
            handleSubmit,
            renderHTML,
            onClickDelete
        };
    }
})
</script>