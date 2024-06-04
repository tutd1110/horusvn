<template>
    <a-modal v-model:visible="visible" style="width:750px" :footer="null" :maskClosable="false" :closable="false">
        <a-form :model="formState" autocomplete="off" style="width:700px;">
            <a-form-item>
                <a-input allow-clear placeholder="Title" v-model:value="formState.title" />
            </a-form-item>
            <a-form-item>
                <template v-if="type === 'Department'">
                    <a-col>
                        <!-- <a-select
                            ref="select"
                            v-model:value="formState.department_id"
                            allow-clear
                            mode="multiple"
                            style="width:100%;"
                            :options="options"
                        ></a-select> -->
                        <el-select
                            v-model="formState.department_id"
                            multiple
                            filterable
                            clearable
                            style="width:100%;"
                        >
                            <el-option
                                v-for="item in options"
                                :key="item.value"
                                :label="item.label"
                                :value="item.value"
                            />
                        </el-select>
                    </a-col>
                </template>
                <template v-else-if="type === 'Game'">
                    <a-col>
                        <!-- <a-select
                            ref="select"
                            v-model:value="formState.game_id"
                            allow-clear
                            mode="multiple"
                            style="width:100%;"
                            :options="options"
                        ></a-select> -->
                        <el-select
                            v-model="formState.game_id"
                            multiple
                            filterable
                            clearable
                            style="width:100%;"
                        >
                            <el-option
                                v-for="item in options"
                                :key="item.value"
                                :label="item.label"
                                :value="item.value"
                            />
                        </el-select>
                    </a-col>
                </template>
                <template v-else>
                    <a-tag color="#108ee9">{{ type }}</a-tag>
                </template>
            </a-form-item>
            <a-form-item>
                <a-col>
                    <a-select
                        ref="select"
                        v-model:value="formState.status"
                        style="width:100%;"
                        :options="status"
                    ></a-select>
                </a-col>
            </a-form-item>
            <a-form-item>
                <QuillEditor
                    theme="snow"
                    v-model:content="formState.description"
                    :toolbar="toolbar"
                    contentType="html"
                />
            </a-form-item>
            <a-form-item>
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
            </a-form-item>
            <a-form-item style="float:right; margin-top: -21px;">
                <a-button style="width:100px; margin-right: 10px" @click="cancel">Huỷ</a-button>
                <a-button style="width:100px;" v-if="mode==='ADD'" type="primary" :loading="isLoading" :onclick="onClickStoreButton">Thêm</a-button>
                <a-button style="width:100px;" v-if="mode==='UPDATE'" type="primary" :loading="isLoading" :onclick="onClickUpdateButton">Cập nhật</a-button>
            </a-form-item>
        </a-form>
    </a-modal>
</template>
<script>
import { useI18n } from 'vue-i18n';
import { ref } from 'vue';
import { PlusOutlined } from '@ant-design/icons-vue';
import { QuillEditor } from '@vueup/vue-quill'
import '@vueup/vue-quill/dist/vue-quill.snow.css';
import { errorModal } from '../Helper/error-modal.js';

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
        PlusOutlined
    },
    emits: ['saved'],
    setup(props, { emit }) {
        const { t } = useI18n();
        const errorMessages = ref();
        const title = ref("");
        const visible = ref(false);
        const mode = ref("");//New mode or edit mode or change
        const type = ref("");
        const selectedRecord = ref("");//Store ID of selected row
        const options = ref([]);
        const status = ref([]);
        const formState = ref({});
        const toolbar = [
            [{ header: [1, 2, 3, 4, 5, 6, false] }],
            [{ size: ['small', false, 'large', 'huge'] }],
            ['bold', 'italic', 'underline', 'strike'],
            ['blockquote', 'code-block'],
            [{ align: [] }],
            [{ list: 'ordered' }, { list: 'bullet' }],
            [{ color: [] }, { background: [] }],
            [{ font: [] }],
            ['link', 'video'],
            ['clean'],
        ];
        const previewVisible = ref(false);
        const previewImage = ref('');
        const previewTitle = ref('');
        const fileList = ref([]);
        const uploadFiles = ref([]);
        const listPostFilesRemoved = ref([]);
        const isLoading = ref(false);
        const cancel = () => {
            visible.value = false;
        };
        const formClear = () => {
            formState.value.id = "";
            formState.value.title = "";
            formState.value.description = ref("<p><br></p>", "");
            formState.value.department_id = ref([]);
            formState.value.game_id = ref([]);
            formState.value.status = ref(0);
            listPostFilesRemoved.value = [];
            uploadFiles.value = [];
            fileList.value = [];
        };
        //select box list generation
        const CreateSelbox = () => {
            // if (type.value != 'Company') {
                axios.get('/api/journal/get_selbox', {
                    params: {
                        type: type.value
                    }
                })
                .then(response => {
                    let data = response.data
                    status.value = response.data.status
                })
            // }

        };
        //new mode
        const ShowWithAddMode = (text, selbox) => {
            mode.value = "ADD";
            type.value = text;
            visible.value = true;
            options.value = selbox;
            //form initialization
            formClear();
            CreateSelbox();
        };
        //edit mode
        const ShowWithEditMode = (text, selbox, id) => {
            mode.value = "UPDATE";
            type.value = text;
            visible.value = true;
            options.value = selbox;
            //form initialization
            formClear();
            CreateSelbox();

            selectedRecord.value = id;

            axios.get('/api/journal/get_journal_by_id', {
                params: {
                    id: selectedRecord.value
                }
            })
            .then(response => {
                const data = response.data;
                formState.value = data;

                if (data.departments.length > 0) {
                    formState.value.department_id = data.departments.map((department) => department.department_id);
                }
                if (data.games.length > 0) {
                    formState.value.game_id = data.games.map((game) => game.game_id);
                }

                let files = data.files
                fileList.value = files.map(file => ({
                    uid: file.id,
                    name: file.path.split('/').pop(),
                    url: '/' + file.path // set the url to the full path to the file
                }));
            })
            .catch(error => {
                formState.value = {}
                errorMessages.value = error.data.errors;
                errorModal(t, errorMessages);
            })
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
            } else if (mode.value == 'ADD') {
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
        const onClickStoreButton = () => {
            isLoading.value = true;
            let formData = new FormData();
            formData = mapFormData(formData)

            axios.post('/api/journal/store', formData,
            {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            })
            .then(response => {
                isLoading.value = false;
                _update()
            })
            .catch(error => {
                isLoading.value = false;
                errorMessages.value = error.response.data.errors;
                errorModal(t, errorMessages);
            })
        }
        const onClickUpdateButton = () => {
            isLoading.value = true;
            let formData = new FormData();
            formData.append('_method', 'PATCH');
            formData = mapFormData(formData)
            formData.append('id', formState.value.id);
            if (listPostFilesRemoved.value.length > 0) {
                listPostFilesRemoved.value.forEach((value, index) => {
                    formData.append(`files_ids_removed[${index}]`, value);
                });
            }
            
            axios.post('/api/journal/update', formData,
            {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            })
            .then(response => {
                isLoading.value = false;
                _update()
            })
            .catch(error => {
                isLoading.value = false;
                errorMessages.value = error.response.data.errors;
                errorModal(t, errorMessages);
            })
        }
        const mapFormData = (formData) => {
            uploadFiles.value.forEach((file, index) => {
                if (file instanceof File) {
                    formData.append(`files[${index}]`, file);
                }
            });
            formData.append('title', formState.value.title);
            formData.append('description', formState.value.description);
            if (type.value === 'Department') {
                formData.append('department_id', formState.value.department_id);

                formState.value.department_id.forEach((item, index) => {
                    formData.append(`department_id[${index}]`, item);
                });
            } else if (type.value === 'Game') {
                formData.append('game_id', formState.value.game_id);

                formState.value.game_id.forEach((item, index) => {
                    formData.append(`game_id[${index}]`, item);
                });
            }
            formData.append('type', type.value);
            formData.append('status', formState.value.status);

            return formData
        }
        const _update = () => {
            visible.value = false;
            emit('saved');
        };

        return {
            formState,
            visible,
            isLoading,
            mode,
            type,
            options,
            toolbar,
            cancel,
            fileList,
            previewVisible,
            previewImage,
            handlePreview,
            handleCancel,
            previewTitle,
            handleRemove,
            beforeUpload,
            ShowWithAddMode,
            ShowWithEditMode,
            onClickStoreButton,
            onClickUpdateButton,
            status
        };
    }
})
</script>