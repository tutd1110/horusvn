<template>
    <a-row>
        <a-col :span="12">
            <a-row>
                <a-col :span="23">
                    <label name="name" style="font-weight: bold">Tiêu đề</label>
                    <a-input allow-clear v-model:value="formState.title" />
                </a-col>
            </a-row>
            <a-row>
                <a-col :span="23" style="margin-top: 30px;">
                    <label name="name" style="font-weight: bold">Nội dung</label>
                    <QuillEditor
                        theme="snow"
                        v-model:content="formState.content"
                        :toolbar="toolbar"
                        contentType="html"
                    />
                </a-col>
            </a-row>
            <a-row style="margin-top: 100px;">
                <a-radio-group v-model:value="formState.status">
                    <a-radio :value="0">Draft</a-radio>
                    <a-radio :value="1">Publish</a-radio>
                </a-radio-group>
            </a-row>
            <a-row style="margin-top: 50px;">
                <a-col :span="23">
                    <a-upload
                        :multiple="true"
                        v-model:file-list="fileList"
                        list-type="picture-card"
                        @preview="handlePreview"
                        :beforeUpload="beforeUpload"
                        @remove="handleRemove"
                    >
                        <div>
                            <plus-outlined />
                            <div style="margin-top: 8px">Upload</div>
                        </div>
                    </a-upload>
                    <a-modal :visible="previewVisible" :title="previewTitle" :footer="null" @cancel="handleCancel">
                        <img alt="example" style="width: 100%" :src="previewImage" />
                    </a-modal>
                </a-col>
            </a-row>
        <a-row style="margin-top: 30px;">
            <a-col :span="23">
                <template v-if="mode === 'NEW'">
                    <a-button type="primary" v-on:click="onAddNewButton" style="margin-right: 10px">ADD</a-button>
                </template>
                <template v-else-if="mode === 'UPDATE'">
                    <a-button type="primary" v-on:click="onUpdateButton" style="margin-right: 10px">UPDATE</a-button>
                </template>
            </a-col>
        </a-row>
        </a-col>
            <a-col :span="12">
                <label name="name" style="font-weight: bold">Danh sách thông báo</label>
                <a-table class="task" :dataSource="dataSource" :columns="columns" :pagination="{position: ['bottomCenter'],pageSize:10,showSizeChanger: true}" bordered>
                    <template #bodyCell="{column,record}">
                        <template v-if="column.key === 'status'">
                            <span>
                                <a-tag
                                    :color="record.status === 0 ? 'volcano' : 'green'"
                                >
                                    {{ record.status === 0 ? 'Draft' : 'Publish' }}
                                </a-tag>
                            </span>
                        </template>
                        <template v-if="column.key === 'action'">
                            <edit-outlined v-on:click="onEditPostButton(record.id)" style="margin-right: 10px"/>
                            <delete-outlined v-on:click="onDeletePostButton(record.id)" style="margin-right: 10px; color: red"/>
                        </template>
                    </template>
                </a-table>
        </a-col>
    </a-row>
</template>
<script>
import { Modal, notification, message } from 'ant-design-vue';
import { onMounted, ref, h } from 'vue';
import { useI18n } from 'vue-i18n';
import { QuillEditor } from '@vueup/vue-quill'
import '@vueup/vue-quill/dist/vue-quill.snow.css';
import { PlusOutlined, EditOutlined, DeleteOutlined, CommentOutlined } from '@ant-design/icons-vue';

function getBase64(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = () => resolve(reader.result);
        reader.onerror = error => reject(error);
    });
}

export default ({
    components: {
        QuillEditor,
        PlusOutlined,
        EditOutlined,
        DeleteOutlined,
        CommentOutlined
    },
    setup() {
        const { t } = useI18n();
        const dataSource = ref();
        const errorMessages = ref("");
        const formState = ref({
            id: "",
            title: "",
            content: "<p><br></p>",
            status: 0
        });
        const mode = ref("NEW");
        const toolbar = [
            [{ header: [1, 2, 3, 4, 5, 6, false] }],
            [{ size: ['small', false, 'large', 'huge'] }],
            ['bold', 'italic', 'underline', 'strike'],
            ['blockquote', 'code-block'],
            [{ align: [] }],
            [{ list: 'ordered' }, { list: 'bullet' }],
            [{ color: [] }, { background: [] }],
            [{ font: [] }],
            ['link'],
            // ['link'],
            ['clean'],
        ];
        const previewVisible = ref(false);
        const previewImage = ref('');
        const previewTitle = ref('');
        const fileList = ref([]);
        const uploadFiles = ref([]);
        const listPostFilesRemoved = ref([]);
        const columns = ref([
            {
                title: 'Title',
                dataIndex: 'title',
                key: 'title',
                align: 'center',
                width: 850,
            },
            {
                title: 'Status',
                dataIndex: 'status',
                key: 'status',
                align: 'center',
                width: 100,
            },
            {
                title: 'Author',
                dataIndex: 'fullname',
                key: 'fullname',
                align: 'center',
                width: 150,
            },
            {
                title: 'Action',
                dataIndex: '',
                key: 'action',
                fixed: false,
                align: 'center',
                width: 70,
            }
        ]);

        const formClear = () => {
            formState.value.id = ref();
            formState.value.title = ref("");
            formState.value.status = 0;
            formState.value.content = ref("<p><br></p>", "");
            fileList.value = [];
            uploadFiles.value = [];
            listPostFilesRemoved.value = [];
            mode.value = 'NEW';
        };

        const handleRemove = file => {
            const index = fileList.value.indexOf(file);

            if (mode.value == 'UPDATE') {
                listPostFilesRemoved.value.push(fileList.value[index].uid);

                // Find the index of the file in the uploadFiles array
                const indexUploadFiles = uploadFiles.value.findIndex(f => f.name === file.name);

                // If the file exists in the array, remove it
                if (indexUploadFiles !== -1) {
                    uploadFiles.value.splice(indexUploadFiles, 1);
                }
            } else if (mode.value == 'NEW') {
                if (index !== -1) {
                    fileList.value.splice(index, 1);
                    // Remove the file from the uploadFiles array as well
                    uploadFiles.value.splice(index, 1);
                }
            }
        };

        const handleCancel = () => {
            previewVisible.value = false;
            previewTitle.value = '';
        };

        const handlePreview = async file => {
            if (!file.url && !file.preview) {
                file.preview = await getBase64(file.originFileObj);
            }
            previewImage.value = file.url || file.preview;
            previewVisible.value = true;
            previewTitle.value = file.name || file.url.substring(file.url.lastIndexOf('/') + 1);
        };

        const beforeUpload = file => {
            // Create a new File object from the Proxy object
            const convertedFile = new File([file], file.name, { type: file.type });

            // Replace the Proxy object with the new File object
            uploadFiles.value.push(convertedFile);

            // Return false to prevent the upload from starting immediately
            return false;
        };

        const onAddNewButton = () => {
            let formData = new FormData();
            uploadFiles.value.forEach((file, index) => {
                if (file instanceof File) {
                    formData.append(`files[${index}]`, file);
                }
            });

            formData.append('title', formState.value.title);
            formData.append('status', formState.value.status);
            formData.append('content', formState.value.content);
            
            axios.post('/api/announcements/store', formData,
            {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            })
            .then(response => {
                notification.success({
                    message: t('message.MSG-TITLE-W'),
                    description: response.data.success,
                });

                formClear()
                _fetch()
            })
            .catch(error => {
                errorMessages.value = error.response.data.errors;
                errorModal(false);
            })
        }

        const onEditPostButton = (post_id) => {
            axios.get('/api/announcements/get_post_by_id', {
                params: {
                    id: post_id
                }
            })
            .then(response => {
                formState.value = response.data;

                let files = response.data.post_files
                fileList.value = files.map(file => ({
                    uid: file.id,
                    name: file.path.split('/').pop(),
                    url: '/' + file.path // set the url to the full path to the file
                }));

                mode.value = 'UPDATE';
            })
            .catch(error => {
                formState.value = {}
                mode.value = 'NEW';
                errorMessages.value = error.response.data.errors;
                errorModal(true);
            })
        }

        const onUpdateButton = () => {
            let formData = new FormData();
            formData.append('_method', 'PATCH');

            uploadFiles.value.forEach((file, index) => {
                if (file instanceof File) {
                    formData.append(`files[${index}]`, file);
                }
            });

            formData.append('id', formState.value.id);
            if (listPostFilesRemoved.value.length > 0) {
                listPostFilesRemoved.value.forEach((value, index) => {
                    formData.append(`post_files_ids_removed[${index}]`, value);
                });
            }
            formData.append('title', formState.value.title);
            formData.append('content', formState.value.content);
            formData.append('status', formState.value.status);
            
            axios.post('/api/announcements/update', formData,
            {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            })
            .then(response => {
                notification.success({
                    message: t('message.MSG-TITLE-W'),
                    description: response.data.success,
                });

                formClear()
                _fetch()
            })
            .catch(error => {
                errorMessages.value = error.response.data.errors;
                errorModal(false);
            })
        }

        const onDeletePostButton = (post_id) => {
            Modal.confirm({
                title: 'Bạn có chắc chắn xoá bài viết này?',
                okText: 'Ok',
                cancelText: 'Huỷ',
                onOk() {
                    axios.delete('/api/announcements/delete', {
                        params: {
                            id: post_id
                        }
                    })
                    .then(response => {
                        _fetch();
                    })
                    .catch(error => {
                        _fetch();
                        errorMessages.value = error.response.data.errors;
                        errorModal(false);
                    })
                }
            })
        }

        const _fetch = () => {
            axios.get('/api/announcements/list')
            .then(response => {
                dataSource.value = response.data;
            })
            .catch((error) => {
                //When search target data does not exist
                dataSource.value = []; //dataSource empty
            });
        };

        onMounted(() => {
            _fetch();
        });

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
            dataSource,
            columns,
            formState,
            mode,
            toolbar,
            fileList,
            previewVisible,
            previewImage,
            handlePreview,
            handleCancel,
            previewTitle,
            handleRemove,
            beforeUpload,
            onAddNewButton,
            onEditPostButton,
            onUpdateButton,
            onDeletePostButton
        }
    }
})
</script>
<style lang="scss">
.ant-upload-select-picture-card i {
  font-size: 32px;
  color: #999;
}

.ant-upload-select-picture-card .ant-upload-text {
  margin-top: 8px;
  color: #666;
}
</style>