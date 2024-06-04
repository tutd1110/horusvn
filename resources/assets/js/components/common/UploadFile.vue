<template>
    <div>
        <a-upload
            :multiple="false"
            :file-list="mapFiles(data.files)"
            list-type="picture-card"
            @preview="handlePreview"
            :beforeUpload="beforeUpload"
            @remove="handleRemove"
            @change="onFileChange(data, $event)"
        >
            <div>
                <plus-outlined />
                <div style="margin-top: 8px">Upload</div>
            </div>
        </a-upload>
        <a-modal style="width:60%" :visible="previewVisible" :title="previewTitle" :footer="null" @cancel="handleCancel">
            <img alt="example" style="width: 100%" :src="previewImage" />
        </a-modal>
    </div>
</template>
<script>
import { computed, ref } from 'vue';
import { notification } from 'ant-design-vue';
import { useI18n } from 'vue-i18n';
import { PlusOutlined } from '@ant-design/icons-vue';
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
        PlusOutlined
    },
    name: 'upload-file',
    props: {
        data: {
            type: Object,
            required: true,
        },
        url: {
            type: String,
            required: true,
        }
    },
    setup(props) {
        const { t } = useI18n();
        const errorMessages = ref("");
        const previewVisible = ref(false);
        const previewImage = ref('');
        const previewTitle = ref('');
        const uploadFile = ref({});

        const mapFiles = computed(() => {
            return files => {
                return files.map(file => {
                    if (file.path) {
                        return {
                            uid: file.id,
                            name: file.path.split('/').pop(),
                            url: '/' + file.path // set the url to the full path to the file
                        }
                    } else {
                        return {
                            uid: file.id,
                            name: 'Unknown',
                            url: ''
                        }
                    }
                })
            }
        });

        const handlePreview = async file => {
            if (!file.url && !file.preview) {
                file.preview = await getBase64(file.originFileObj);
            }
            previewImage.value = file.url || file.preview;
            previewVisible.value = true;
            previewTitle.value = file.name || file.url.substring(file.url.lastIndexOf('/') + 1);
        };

        const beforeUpload = file => {
            // Return false to prevent the upload from starting immediately
            return false;
        };

        const handleRemove = (file) => {
            const url = '/api/'+props.url+'/file/delete';
            axios.delete(url, {
                params: {
                    id: file.uid,
                }
            })
            .then(response => {
                notification.success({
                    message: t('message.MSG-TITLE-W'),
                    description: response.data.success,
                });

                const index = props.data.files.findIndex(f => f.id === file.uid)
                if (index !== -1) {
                    props.data.files.splice(index, 1)
                }
            })
            .catch(error => {
                errorMessages.value = error.response.data.errors;
                errorModal(t, errorMessages);//show error message modally
            })
        };

        const onFileChange = (data, e) => {
            if (e.file instanceof File) {
                uploadFile.value = e.file;

                let formData = new FormData();

                formData.append('file', uploadFile.value);
                formData.append(props.url+'_id', props.data.id);

                //store the files
                const url = '/api/'+props.url+'/file/store';
                axios.post(url, formData,
                {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    }
                })
                .then(response => {
                    notification.success({
                        message: t('message.MSG-TITLE-W'),
                        description: 'File has been uploaded successfully',
                    });

                    // insert the uploaded file object into the data.files array
                    props.data.files.splice(0, 0, response.data);
                })
                .catch(error => {
                    errorMessages.value = error.response.data.errors;
                    errorModal(t, errorMessages);//show error message modally
                })
            }
        };

        const handleCancel = () => {
            previewVisible.value = false;
            previewTitle.value = '';
        };

        notification.config({
            placement: 'bottomLeft',
            duration: 3,
            rtl: true,
        });

        return {
            mapFiles,
            handlePreview,
            beforeUpload,
            handleRemove,
            onFileChange,
            previewVisible,
            previewImage,
            previewTitle,
            handleCancel
        }
    }
})
</script>