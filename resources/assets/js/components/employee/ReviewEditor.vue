<template>
    <a-modal v-model:visible="visible" style="width:80%; font-weight: bold" :maskClosable="false"
        :closable="false" :title="title" @ok="handleOk">
        <a-row>
            <a-col :span="4" :offset="0" style="margin-right:10px;">
                <label name="fullname">Nhân viên</label>
                <a-select
                    ref="select"
                    v-model:value="formState.employee_id"
                    style="width:100%;"
                    :options="employeeSelbox"
                    :field-names="{ label:'fullname', value: 'id' }"
                    show-search
                    allow-clear
                    :filterOption="filterUserOption"
                ></a-select>
            </a-col>
            <a-col :span="4" :offset="0" style="margin-right:10px;">
                <label name="type">Loại đánh giá</label>
                <a-select
                    ref="select"
                    v-model:value="formState.period"
                    style="width:100%;"
                    :options="periodSelbox"
                    allow-clear
                ></a-select>
            </a-col>
            <a-col :span="3" :offset="0" style="margin-right:10px;">
                <label name="start_date">Ngày đánh giá</label>
                <a-form-item :span="3">
                    <a-input allow-clear v-model:value="formState.start_date" />
                </a-form-item>
            </a-col>
            <a-col style="padding-top: 21px;" v-if="mode === 'ADD'">
                <a-button v-on:click="generateReviewButton()" type="primary">Generate</a-button>
            </a-col>

            <div class="flex-grow" />
            <template v-if="mode === 'EDIT'">
                <el-col :span="2">
                    <label>Mentor</label>
                    <el-select
                        v-model="formState.mentor_id"
                        value-key="id"
                        clearable
                        filterable
                        style="width: 100%"
                    >
                        <el-option
                            v-for="item in users"
                            :key="item.id"
                            :label="item.fullname"
                            :value="item.id"
                        />
                    </el-select>
                </el-col>
                <el-col :span="2" style="padding-top: 22px; margin-left: 10px;">
                    <el-button type="primary" v-on:click="addMentor()">Add</el-button>
                </el-col>
            </template>
            <template v-if="mode === 'EDIT'">
                <el-col :span="2">
                    <label>Leader</label>
                    <el-select
                        v-model="formState.leader_id"
                        value-key="id"
                        clearable
                        filterable
                        style="width: 100%"
                    >
                        <el-option
                            v-for="item in leader"
                            :key="item.id"
                            :label="item.fullname"
                            :value="item.id"
                        />
                    </el-select>
                </el-col>
                <el-col :span="2" style="padding-top: 22px; margin-left: 10px;">
                    <el-button type="primary" v-on:click="addLeader()">Add</el-button>
                </el-col>
            </template>
            <template v-if="mode === 'EDIT'">
                <el-col :span="2">
                    <label>Project Manager</label>
                    <el-select
                        v-model="formState.pm_id"
                        value-key="id"
                        clearable
                        filterable
                        style="width: 100%"
                    >
                        <el-option
                            v-for="item in pm"
                            :key="item.id"
                            :label="item.fullname"
                            :value="item.id"
                        />
                    </el-select>
                </el-col>
                <el-col :span="2" style="padding-top: 22px; margin-left: 10px;">
                    <el-button type="primary" v-on:click="addPM()">Add</el-button>
                </el-col>
            </template>
        </a-row>
        <template v-if="content.length > 0">
            <review-table
                :screen="'review_editor'"
                :content="content"
                :columns="columns"
                :group-employee="groupEmployee"
                :employee-position-login="2"
                :totals="totals"
            >
            </review-table>

            <a-collapse v-model:activeKey="activeKey" :bordered="false">
                <a-collapse-panel
                    v-for="(item, index) in questions"
                    :key="item.id"
                    style="background: #f7f7f7;border-radius: 4px;margin-bottom: 24px;border: 0;overflow: hidden"
                >
                    <template #header>
                        <div style="font-weight: bold">
                            {{ `${index+1}. ${item.question}` }}
                            <span v-if="[17,24,33,48].includes(item.question_id)">
                                    <span v-if="item.type === 0">Member</span>
                                    <span v-if="item.type === 0.5">của Mentor </span>
                                    <span v-if="item.type === 1"> của Leader </span>
                                    <span v-if="item.type === 2">của PM </span>
                                </span>
                            <span
                                :style="{'font-weight': 'bold', 'color': item.type === 1 ? 'green' : (item.type === 2 ? 'orange' : 'blue')}"
                            >
                                {{ item.fullname }}
                            </span>
                        </div>
                    </template>

                    <QuillEditor
                        theme="snow"
                        v-model:content="item.employee_answer"
                        :toolbar="toolbar"
                        contentType="html"
                        @blur="onChangeAnswer(item.id, item.employee_answer)"
                    />
                </a-collapse-panel>
            </a-collapse>
            <a-row>
                <h2 style="font-weight: bold; font-size:20px;">DIRECTOR</h2>
            </a-row>
            <a-row style="margin-bottom: 20px">
                <a-radio-group v-model:value="comment.status" @change="onChangeApprove()">
                    <a-radio :value="0">Approve</a-radio>
                    <a-radio :value="1">Reject</a-radio>
                </a-radio-group>
            </a-row>
            <a-row style="margin-bottom: 100px">
                <a-col :span="15">
                    <QuillEditor
                        theme="snow"
                        v-model:content="comment.director_comment"
                        :toolbar="toolbar"
                        contentType="html"
                        @blur="onChangeComment('director_comment', comment.director_comment)"
                    />
                </a-col>
            </a-row>
        </template>
    </a-modal>
</template>
<script>
import { Modal, notification } from 'ant-design-vue';
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import ReviewTable from '../common/ReviewTable.vue';
import { QuillEditor } from '@vueup/vue-quill'
import '@vueup/vue-quill/dist/vue-quill.snow.css';
import { errorModal } from '../Helper/error-modal.js';
import { useTotals, assignEmployeeFullnameToColumns } from '../Helper/employee-review.js'

export default ({
    components: {
        ReviewTable,
        QuillEditor
    },
    setup() {
        const { t } = useI18n();
        const visible = ref(false);
        const title = ref("");
        const mode = ref("");
        const formState = ref([]);
        const users = ref([]);
        const leader = ref([]);
        const pm = ref([]);
        const activeKey = ref(['1']);
        const employeeSelbox = ref([]);
        const periodSelbox = ref([]);
        const groupEmployee = ref([]);
        const errorMessages = ref();
        const content = ref([]);
        const questions = ref([]);
        const review = ref({});
        const comment = ref();
        const columns = ref([]);
        // const toolbar = [
        //     [{ header: [1, 2, 3, 4, 5, 6, false] }],
        //     [{ size: ['small', false, 'large', 'huge'] }],
        //     ['bold', 'italic', 'underline', 'strike'],
        //     [{ font: [] }],
        //     ['clean'],
        // ];
        const toolbar = [
            [{ header: [1, 2, 3, 4, 5, 6, false] }],
            [{ size: ['small', false, 'large', 'huge'] }],
            ['bold', 'italic', 'underline', 'strike'],
            ['blockquote', 'code-block'],
            [{ align: [] }],
            [{ list: 'ordered' }, { list: 'bullet' }, { list: 'check' }],
            [{ color: [] }, { background: [] }],
            [{ font: [] }],
            ['link', 'video'],
            ['clean'],
        ];

        const formClear = () => {
            formState.value.employee_id = "";
            formState.value.period = "";
            formState.value.mentor_id = "";
            formState.value.start_date = "";
            review.value = {};
            comment.value = {};
            content.value = [];
            questions.value = [];
            groupEmployee.value = [];
            columns.value = [];
        };

        //Selboxes
        const CreateSelbox = () => {
            axios.get('/api/common/review_selboxes')
            .then(response => {
                employeeSelbox.value = response.data.employees;
                periodSelbox.value = response.data.period.map((item, index) => ({
                    label: item,
                    value: index,
                }));
            });

            axios.get('/api/common/get_employees')
            .then(response => {
                users.value = response.data
                leader.value = response.data.filter(user => user.position === 1 || user.id === 63);
                pm.value = response.data.filter(user => user.position === 2);
            })
        }

        //Register Review Modal
        const showRegisterReviewModal = () => {
            title.value = "Add Employee's Review"
            mode.value = "ADD";
            visible.value = true;

            formClear()
            CreateSelbox()
        };

        //Edit Review Modal
        const showEditReviewModal = (review_id) => {
            title.value = "Edit Employee's Review"
            mode.value = "EDIT";
            visible.value = true;

            formClear()
            CreateSelbox()

            loadReviewContent(review_id)
        };

        const handleOk = e => {
            visible.value = false
        }

        const generateReviewButton = () => {
            let submitData = {
                employee_id: formState.value.employee_id,
                period: formState.value.period,
                start_date: formState.value.start_date
            };

            axios.post('/api/review/store', submitData)
            .then(response => {
                loadReviewContent(response.data)
            })
            .catch((error) => {
                errorMessages.value = error.response.data.errors;//put message content in ref
                errorModal(t, errorMessages);//show error message modally
            });
        }

        const loadReviewContent = (review_id) => {
            axios.get('/api/review/load_review_data', {
                params: {
                    id: review_id
                }
            })
            .then(response => {
                review.value = response.data.review;
                formState.value.employee_id = review.value.employee_id
                formState.value.period = review.value.period
                formState.value.start_date = review.value.start_date

                comment.value = response.data.comment
                content.value = response.data.content;
                questions.value = response.data.questions

                groupEmployee.value = response.data.employees;
                columns.value = assignEmployeeFullnameToColumns(groupEmployee.value)
            })
            .catch((error) => {
                //When search target data does not exist
                review.value = [];
                content.value = [];
                questions.value = [];
                comment.value = {};
                columns.value = [];
                groupEmployee.value = 0;
            });
        }

        const onChangeAnswer = (id, value) => {
            updateEmployeeAnswer({'id': id, answer: value})
        }

        const onChangeComment = (column, value) => {
            updateReviewComment({id: comment.value.id, [column]: value})
        }

        const onChangeApprove = () => {
            updateReviewComment({id: comment.value.id, status: comment.value.status});
        }

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

        const updateReviewComment = (submitData) => {
            axios.post('/api/employee/review/comment', submitData)
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

        const addMentor = () => {
            let submitData = {
                mentor_id: formState.value.mentor_id,
                employee_id: formState.value.employee_id,
                review_id: review.value.id
            }

            axios.post('/api/employee/review/add-mentor', submitData)
            .then(response => {
                notification.success({
                    message: t('message.MSG-TITLE-W'),
                    description: response.data.success,
                });

                loadReviewContent(review.value.id)
            })
            .catch(error => {
                errorMessages.value = error.response.data.errors;//put message content in ref
                errorModal(t, errorMessages);//show error message modally
            })
        }
        const addLeader = () => {
            let submitData = {
                leader_id: formState.value.leader_id,
                employee_id: formState.value.employee_id,
                review_id: review.value.id
            }

            axios.post('/api/employee/review/add-leader', submitData)
            .then(response => {
                notification.success({
                    message: t('message.MSG-TITLE-W'),
                    description: response.data.success,
                });

                loadReviewContent(review.value.id)
            })
            .catch(error => {
                errorMessages.value = error.response.data.errors;//put message content in ref
                errorModal(t, errorMessages);//show error message modally
            })
        }
        const addPM = () => {
            let submitData = {
                pm_id: formState.value.pm_id,
                employee_id: formState.value.employee_id,
                review_id: review.value.id
            }

            axios.post('/api/employee/review/add-pm', submitData)
            .then(response => {
                notification.success({
                    message: t('message.MSG-TITLE-W'),
                    description: response.data.success,
                });

                loadReviewContent(review.value.id)
            })
            .catch(error => {
                errorMessages.value = error.response.data.errors;//put message content in ref
                errorModal(t, errorMessages);//show error message modally
            })
        }

        const { totals } = useTotals(content)

        const filterUserOption = (input, option) => {
            if (option.fullname) {
                return option.fullname.toLowerCase().indexOf(input.toLowerCase()) >= 0 ? true : false;
            }
        }

        return {
            title,
            mode,
            visible,
            activeKey,
            showRegisterReviewModal,
            showEditReviewModal,
            toolbar,
            users,
            leader,
            pm,
            handleOk,
            formState,
            employeeSelbox,
            periodSelbox,
            generateReviewButton,
            content,
            questions,
            review,
            groupEmployee,
            comment,
            columns,
            totals,
            onChangeAnswer,
            onChangeComment,
            onChangeApprove,
            filterUserOption,
            addMentor,
            addLeader,
            addPM,
        }
    }
})
</script>
