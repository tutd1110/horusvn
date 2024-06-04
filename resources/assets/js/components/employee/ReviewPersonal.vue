<template>
    <a-row>
        <a-col style="padding-top: 19px; margin: 0px 0px 0px 15%">
            <h2 style="font-weight: bold; font-size:30px" v-if="reviewTitle">Employee Review {{ reviewTitle }}</h2>
        </a-col>
    </a-row>
    <!-- table from here -->
    <a-row style="margin-top:10px; margin-bottom:30px;" v-if="content.length > 0">
        <a-col :offset="0" :span="13">
            <review-table
                :screen="'reviewee'"
                :content="content"
                :columns="columns"
                :group-employee="groupEmployee"
                :employee-position-login="0"
                :totals="totals"
            >
            </review-table>
            <!-- question's trial job 2 weeks and 2 months for employees -->

            <a-collapse v-model:activeKey="activeKey" :bordered="false">
                <a-collapse-panel
                    v-for="(item, index) in questions"
                    :key="item.id"
                    :style="customStyle"
                >
                    <template #header>
                        <div style="font-weight: bold">
                            {{ `${index+1}. ${item.question}` }}
                        </div>
                    </template>
                    <div>
                        <QuillEditor
                            theme="snow"
                            v-model:content="item.employee_answer"
                            :toolbar="toolbar"
                            contentType="html"
                            @blur="onChangeAnswer(item.id, item.employee_answer)"
                            @focus="selectedItem = item"
                        />
                    </div>
                    <br>
                    <div>
                        <a-upload
                            :multiple="false"
                            :file-list="questionFiles(item)"
                            list-type="picture-card"
                            @preview="handlePreview"
                            :beforeUpload="beforeUpload"
                            @remove="handleRemove(item.id, $event)"
                            @change="onFileChange(item.id, $event)"
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
                </a-collapse-panel>
            </a-collapse>
            <a-row>
                <a-button type="primary" v-on:click="onSubmitReview">Submit</a-button>
            </a-row>
        </a-col>
        <review-guide></review-guide>
    </a-row>
</template>
<script>
import { Modal, notification } from 'ant-design-vue';
import { onMounted, ref, h, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { PlusOutlined } from '@ant-design/icons-vue';
import { QuillEditor } from '@vueup/vue-quill'
import '@vueup/vue-quill/dist/vue-quill.snow.css';
import ReviewTable from '../common/ReviewTable.vue';
import ReviewGuide from './ReviewGuide.vue';
import { errorModal } from '../Helper/error-modal.js';
import { useTotals, assignEmployeeFullnameToColumns } from '../Helper/employee-review.js'

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
        ReviewTable,
        ReviewGuide,
        PlusOutlined
    },
    setup() {
        const { t } = useI18n();
        const content = ref([]);
        const questions = ref([]);
        const errorMessages = ref("");
        const formState = ref([]);
        const groupEmployee = ref([]);
        const reviewTitle = ref();
        const review = ref({});
        const activeKey = ref(['1']);
        const customStyle = ref('background: #f7f7f7;border-radius: 4px;margin-bottom: 24px;border: 0;overflow: hidden');
        const toolbar = [
            [{ header: [1, 2, 3, 4, 5, 6, false] }],
            [{ size: ['small', false, 'large', 'huge'] }],
            ['bold', 'italic', 'underline', 'strike'],
            [{ font: [] }],
            ['clean'],
        ];
        const selectedItem = ref();
        const previewVisible = ref(false);
        const previewImage = ref('');
        const previewTitle = ref('');
        const uploadFile = ref({});
        const columns = ref([]);

        const onChangeAnswer = (id, value) => {
            updateEmployeeAnswer({'id': id, answer: value})
        }

        const { totals } = useTotals(content)

        const updateEmployeeAnswer = (submitData) => {
            axios.patch('/api/employee/review/employee_answers', submitData)
            .then(response => {
                notification.success({
                    message: t('message.MSG-TITLE-W'),
                    description: response.data.success,
                });
            })
            .catch(error => {
                errorMessages.value = error.response.data.errors;//put message content in ref
                errorModal(t, errorMessages);//show error message modally
            })
        }

        const _fetch = () => {
            try {
                axios.get('/api/employee/review/get_reviews')
                    .then(response => {
                        review.value = response.data.review;
                        reviewTitle.value = response.data.review.name;
                        content.value = response.data.content;
                        questions.value = response.data.questions

                        groupEmployee.value = response.data.employees;
                        columns.value = assignEmployeeFullnameToColumns(groupEmployee.value)
                    })
                    .catch((error) => {
                        //When search target data does not exist
                        review.value = [];
                        reviewTitle.value = "";
                        columns.value = [];
                        content.value = [];
                        questions.value = [];
                        groupEmployee.value = 0;
                    });
            } catch (error) {
                console.log(error)
            }
        };

        const onSubmitReview = () => {
            Modal.confirm({
                title: 'Bạn có chắc chắn muốn gửi thông tin đánh giá này chứ?',
                okText: 'Ok',
                cancelText: 'Huỷ',
                onOk() {
                    axios.post('/api/employee/review/submit', {id: review.value.id})
                    .then(response => {
                        if (selectedItem.value) {
                            updateEmployeeAnswer({'id': selectedItem.value.id, answer: selectedItem.value.employee_answer})
                        }

                        window.location.href="/home"
                    })
                    .catch(error => {
                        errorMessages.value = error.response.data.errors;
                        errorModal(t, errorMessages);//show error message modally
                    })
                }
            })
        }

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

        const onFileChange = (employee_answer_id, e) => {
            if (e.file instanceof File) {
                uploadFile.value = e.file;

                let formData = new FormData();

                formData.append('file', uploadFile.value);
                formData.append('review_id', review.value.id);
                formData.append('employee_answer_id', employee_answer_id);

                //store the files
                axios.post('/api/employee/review/file/store', formData,
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

                    const updatedQuestions = questions.value.map(q => {
                        if (q.id === employee_answer_id) {
                            return {
                                ...q,
                                files: [
                                    ...q.files,
                                    {
                                        id: response.data.id,
                                        employee_answer_id: employee_answer_id,
                                        file_path: response.data.file_path
                                    }
                                ]
                            }
                        } else {
                            return q
                        }
                    })

                    questions.value = updatedQuestions
                })
                .catch(error => {
                    errorMessages.value = error.response.data.errors;
                    errorModal(t, errorMessages);//show error message modally
                })
            }
        };

        const handleRemove = (question_id, file) => {
            axios.delete('/api/employee/review/file/delete', {
                params: {
                    id: file.uid,
                }
            })
            .then(response => {
                notification.success({
                    message: t('message.MSG-TITLE-W'),
                    description: response.data.success,
                });

                const questionIndex = questions.value.findIndex(q => q.id === question_id);
                if (questionIndex >= 0) {
                    const question = questions.value[questionIndex];
                    question.files = question.files.filter(item => item.id !== file.uid);
                    questions.value.splice(questionIndex, 1, question);
                }
            })
            .catch(error => {
                errorMessages.value = error.response.data.errors;
                errorModal(t, errorMessages);//show error message modally
            })
        };

        const questionFiles = computed(() => {
            return question => {
                return question.files.map(file => {
                    if (file.file_path) {
                        return {
                            uid: file.id,
                            name: file.file_path.split('/').pop(),
                            url: '/' + file.file_path // set the url to the full path to the file
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

        const handleCancel = () => {
            previewVisible.value = false;
            previewTitle.value = '';
        };

        onMounted(() => {
            _fetch();
        });

        notification.config({
            placement: 'bottomLeft',
            duration: 3,
            rtl: true,
        });

        return {
            reviewTitle,
            content,
            questions,
            activeKey,
            selectedItem,
            customStyle,
            toolbar,
            errorMessages,
            formState,
            previewVisible,
            previewImage,
            previewTitle,
            columns,
            totals,
            groupEmployee,
            questionFiles,
            onChangeAnswer,
            onSubmitReview,
            handlePreview,
            beforeUpload,
            handleRemove,
            handleCancel,
            onFileChange
        };
    }
})
</script>
<style lang="scss">
    .ant-table-thead th {
        font-weight: bold !important;
    }
</style>