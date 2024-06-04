<template>
    <a-col style="margin-right:1px; margin-top: 22px;">
        <a-upload 
            :multiple="false"
            :showUploadList="false"
            :beforeUpload="beforeUpload"
        >
            <a-button type="primary" class="upload-btn">
                <span class="upload-spn">Chọn file</span>
            </a-button>
        </a-upload>
    </a-col>
    <a-col style="margin-right:1px; margin-top: 22px;">
        <a-input style="width:400px" readonly placeholder="Only file xlsx can be import" v-model:value="fileName" />
    </a-col>
    <a-col style="margin-right:10px; margin-top: 22px;">
        <a-button type="primary" :loading="loadingImport" v-on:click="doImport()">Import</a-button>
    </a-col>
</template>
<script>
import { onMounted, ref, watch } from 'vue';
import { notification } from 'ant-design-vue';
import { useI18n } from 'vue-i18n';
import { errorModal } from '../Helper/error-modal.js';

export default ({
    name: 'import-file',
    props: {
        url: {
            type: String,
            required: true,
        },
        name: {
            type: String,
            required: false,
        }
    },
    setup(props) {
        const { t } = useI18n();
        const errorMessages = ref("");
        const loadingImport = ref(false);
        const fileName = ref("");
        const excel_file = ref({});

        const beforeUpload = (file) => {
            excel_file.value = file
            fileName.value = file.name
            //Prevent upload
            return false;
        };

        const doImport = () => {
            loadingImport.value = true;

            let formData = new FormData();
            formData.append('excel_file', excel_file.value);

            axios.post(props.url, formData,
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

                loadingImport.value = false;

                fileName.value = ""
                excel_file.value = {}
            })
            .catch(error => {
                loadingImport.value = false;

                errorMessages.value = error.response.data.errors;
                errorModal(t, errorMessages);
            })
        }

        onMounted(() => {
            watch(() => props.name, (newVal, oldVal) => {
                if (newVal) {
                    fileName.value = newVal
                }
            },
            {
                immediate: true
            })
        });

        notification.config({
            placement: 'bottomLeft',
            duration: 3,
            rtl: true,
        });

        return {
            loadingImport,
            fileName,
            excel_file,
            beforeUpload,
            doImport
        }
    }
})
</script>