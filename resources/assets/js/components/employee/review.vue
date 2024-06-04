<template>
    <submit-review ref="modalSubmitReview"></submit-review>
    <a-row>
        <template v-if="employeePositionLogin > 0">
            <a-col :span="4" style="margin-right: 10px;">
                <a-form-item>
                    <label name="fullname">Nhân viên</label>
                    <el-select
                        v-model="formState.employee_id"
                        value-key="id"
                        placeholder="Employees"
                        clearable
                        filterable
                        style="width: 100%"
                    >
                        <el-option
                            v-for="item in employeeSelectboxes"
                            :key="item.id"
                            :label="item.fullname"
                            :value="item.id"
                        />
                </el-select>
            </a-form-item>
            </a-col>
            <a-col style="padding-top: 21px;">
                <a-button v-on:click="search()" type="primary">Tìm kiếm</a-button>
            </a-col>
            <a-col style="padding-top: 21px; margin-left: 20px">
                <a-button v-on:click="reviewSubmit()" type="primary">Lịch sử đánh giá</a-button>
            </a-col>
        </template>
    </a-row>
    <template v-if="users.length > 0">
        <a-row>
            <a-col>
                <a-checkbox v-model:checked="checked" @change="onChangeMode()"><span style="font-weight: bold">{{ title }}</span></a-checkbox>
            </a-col>
            <a-col :offset="1">
                <h1 style="font-weight: bold">Chức danh: {{ reviewee.position_name }}</h1>
            </a-col>
            <a-col :offset="1">
                <h1 style="font-weight: bold">Bộ phận: {{ reviewee.department_name }}</h1>
            </a-col>
            <a-col :offset="1">
                <h1 style="font-weight: bold">{{ ('Loại đánh giá: ' + (review.period === 0 ? '2 Tuần' : review.period === 1 ? '2 Tháng' : review.period === 2 ? '6 Tháng' : review.period === 3 ? '1 Năm' : review.period === 4 ? 'Hết học việc' :'')) }}</h1>
            </a-col>
            <a-col :offset="1">
                <h1 style="font-weight: bold">{{ rangeTimeReview }}</h1>
            </a-col>
        </a-row>
        <a-row>
            <timesheet-table :mode="mode" :users="users"></timesheet-table>
        </a-row>
    </template>
    <!-- table from here -->
    <a-row style="margin-top:10px; margin-bottom:30px;" v-if="content.length > 0">
        <a-col :offset="0" :span="13">
            <review-table
                :screen="'reviewer'"
                :content="content"
                :columns="columns"
                :group-employee="groupEmployee"
                :employee-position-login="employeePositionLogin"
                :totals="totals"
            >
            </review-table>
            <!-- question's trial job 2 weeks and 2 months for employees -->

            <div v-for="(item, index) in questions" :key="item.id">
                <u>
                    <span v-if="shouldAddSpan(index, item.type)" style="font-weight: bold; color: green">
                        <span v-if="item.type === 0">Member: </span>
                        <span v-if="item.type === 0.5">Mentor: </span>
                        <span v-if="item.type === 1">Leader: </span>
                        <span v-if="item.type === 2">PM: </span>
                        {{ item.fullname }}
                    </span>
                </u>
                <a-collapse :bordered="false">
                    <a-collapse-panel style="background: #f7f7f7;border-radius: 4px;margin-bottom: 24px;border: 0;overflow: hidden">
                        <template #header>
                            <div style="font-weight: bold">
                                {{ `${index+1}. ${item.question}` }}
                                <span v-if="[17,24,33,48].includes(item.question_id)">
                                    <span v-if="item.type === 0">Member</span>
                                    <span v-if="item.type === 0.5">của Mentor <span style="color:red;">(Không đề xuất liên quan đến lương)</span></span>
                                    <span v-if="item.type === 1"> của Leader</span>
                                    <span v-if="item.type === 2">của PM</span>
                                </span>
                            </div>
                        </template>
                        <div v-if="employeePositionLogin == item.type || employeePositionLogin == 2">
                            <QuillEditor
                                theme="snow"
                                v-model:content="item.employee_answer"
                                :toolbar="toolbar"
                                contentType="html"
                                @blur="onChangeAnswer(item.id, item.employee_answer)"
                                @focus="selectedItem = item"
                            />
                        </div>
                        <div v-else v-html="renderHTML(item.employee_answer)"></div>

                        <div>
                            <template v-for="(file, index) in item.files" :key="file.id">
                                <a-image
                                    :width="200"
                                    :src="'/' + file.file_path"
                                />
                            </template>
                        </div>
                    </a-collapse-panel>
                </a-collapse>
            </div>

            <template v-if="employeePositionLogin == 3">
                <a-row>
                    <h2 style="font-weight: bold; font-size:20px;">DIRECTOR</h2>
                    <span style="color:red">(Bạn phải chọn trạng thái và bấm Submit để gửi đanh giá của mình)</span>
                </a-row>
                <a-row style="margin-bottom: 20px">
                    <a-radio-group v-model:value="comment.status">
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
                            @focus="selectedCommentItem = comment"
                        />
                    </a-col>
                </a-row>
            </template>

            <a-row>
                <a-button type="primary" v-on:click="onSubmitReview" :disabled="employeePositionLogin == 3 && comment.status==undefined">Submit</a-button>
            </a-row>
        </a-col>
        <review-guide></review-guide>
    </a-row>
</template>
<script>
import { Modal, notification } from 'ant-design-vue';
import { onMounted, ref, h, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc'
import timezone from 'dayjs/plugin/timezone'
import { TIME_ZONE } from '../const.js'
import { QuillEditor } from '@vueup/vue-quill'
import '@vueup/vue-quill/dist/vue-quill.snow.css';
import ReviewGuide from './ReviewGuide.vue';
import SubmitReview from './SubmitReview.vue';
import TimesheetTable from '../timesheet/TimesheetTable.vue';
import ReviewTable from '../common/ReviewTable.vue';
import { handleUserTimesheet } from '../Helper/handle-user-timesheet.js';
import { errorModal } from '../Helper/error-modal.js';
import { useTotals, assignEmployeeFullnameToColumns } from '../Helper/employee-review.js'

dayjs.extend(utc);
dayjs.extend(timezone);

export default ({
    components: {
        TimesheetTable,
        QuillEditor,
        ReviewGuide,
        ReviewTable,
        SubmitReview
    },
    setup() {
        const { t } = useI18n();
        const content = ref([]);
        const questions = ref([]);
        const questionGroups = ref([]);
        const errorMessages = ref("");
        const formState = ref([]);
        const employeePositionLogin = ref();
        const employeeSelectboxes = ref([]);
        const dateFormat = "DD/MM/YYYY";
        const users = ref([]);
        const mode = ref(true);
        const checked = ref(true);
        const title = ref("Bảng giờ công");
        const review = ref({});
        const reviewee = ref({});
        const rangeTimeReview = ref("");
        const groupEmployee = ref([]);
        const comment = ref();
        const selectedItem = ref();
        const selectedCommentItem = ref();
        const modalSubmitReview = ref();
        const reviewSubmit = () => {
            modalSubmitReview.value.ShowReviewSubmit();
        }
        // const toolbar = [
        //     [{ header: [1, 2, 3, 4, 5, 6, false] }],
        //     [{ size: ['small', false, 'large', 'huge'] }],
        //     ['bold', 'italic', 'underline', 'strike'],
        //     [{ font: [] }],
        //     ['link'],
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
        const columns = ref([]);

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

        const onChangeMode = () => {
            if (!checked.value) {
                mode.value = false
                title.value = "Bảng Warrior";
            } else {
                mode.value = true
                title.value = "Bảng giờ công";
            }
        };

        const search = () => {
            axios.get('/api/employee/review/get_reviews', {
                params: {
                    employee_id: formState.value.employee_id
                }
            })
            .then(response => {
                review.value = response.data.review;
                reviewee.value = response.data.reviewee;
                comment.value = response.data.comment
                content.value = response.data.content;
                questions.value = response.data.questions

                groupEmployee.value = response.data.employees;
                columns.value = assignEmployeeFullnameToColumns(groupEmployee.value)

                let startDateDMY = dayjs(reviewee.value.created_at).tz(TIME_ZONE.ZONE).format("DD/MM/YYYY")
                let startDate = dayjs(reviewee.value.created_at).tz(TIME_ZONE.ZONE).format("YYYY/MM/DD")
                let label = "Ngày bắt đầu làm việc: ";
                if ([2].includes(review.value.period)) {
                    startDate = dayjs(reviewee.value.last_review).format("YYYY/MM/DD")

                    startDateDMY = dayjs(reviewee.value.last_review).tz(TIME_ZONE.ZONE).format("DD/MM/YYYY")

                    label = "Lần đánh giá trước: ";
                }
                getEmployeeTimesheet(startDate, reviewee.value.id)

                rangeTimeReview.value = label + startDateDMY + ' - ' + 'Ngày đánh giá: ' + dayjs().format("DD/MM/YYYY")
            })
            .catch((error) => {
                //When search target data does not exist
                formClear()
            });
        }

        const getEmployeeTimesheet = (startDate, employeeId) => {
            //get timesheet report
            axios.post('/api/timesheet/get_report', {
                    user_id: employeeId,
                    start_date: startDate,
                    end_date: dayjs().format("YYYY/MM/DD")
            }).then(response => {
                const formSate = ref({
                    start_date: startDate,
                    end_date: dayjs().format("YYYY/MM/DD")
                });
                const current_start_date = dayjs(dayjs().startOf('month')).format("YYYY/MM/DD");
                const current_end_date = dayjs(dayjs().endOf('month')).format("YYYY/MM/DD");
                const args = [
                    response.data, "YYYY/MM/DD", formSate, current_start_date, current_end_date, "review"
                ]
                users.value = handleUserTimesheet(...args)
            }).catch((error) => {
                users.value = [];
                errorMessages.value = error.response.data.errors;//put message content in ref
                //When search target data does not exist
                errorModal(t, errorMessages);//show error message modally
            });
        }

        const { totals } = useTotals(content)

        const renderHTML = (htmlString) => {
            let content = ""
            if (htmlString) {
                content = JSON.stringify(htmlString)
                        .replace(/^"/, '')  // remove opening double quote
                        .replace(/"$/, '');  // remove closing double quote
            }
            return content
        }

        const formClear = () => {
            review.value = {};
            reviewee.value = {};
            content.value = [];
            questions.value = [];
            columns.value = [];
            comment.value = {};
            groupEmployee.value = [];
            rangeTimeReview.value = ""
        };

        //select box list generation
        const CreateSelbox = () => {
            //create select boxes
            axios.get('/api/employee/review/get_employees')
            .then(response => {
                employeePositionLogin.value = response.data.employee_position;
                employeeSelectboxes.value = response.data.employees;
            }).catch((error) => {
                //When search target data does not exist
                employeePositionLogin.value = "";
                employeeSelectboxes.value = []; //employeeSelectboxes empty
            });
        };

        const _fetch = () => {
            //select box list generation
            CreateSelbox()
        };

        const onSubmitReview = () => {
            if (selectedItem.value) {
                updateEmployeeAnswer({'id': selectedItem.value.id, answer: selectedItem.value.employee_answer})
            }

            if (selectedCommentItem.value) {
                if (employeePositionLogin.value == 3) {
                    updateReviewComment({id: comment.value.id, director_comment: comment.value.director_comment})
                }
            }
            Modal.confirm({
                title: 'Bạn có chắc chắn muốn gửi thông tin đánh giá này chứ?',
                okText: 'Ok',
                cancelText: 'Huỷ',
                onOk() {
                    if (employeePositionLogin.value == 3) {
                        onChangeApprove();
                        onChangeComment('director_comment', comment.value.director_comment)
                    }
                    axios.post('/api/employee/review/submit', {id: review.value.id})
                    .then(response => {
                        _fetch();

                        formClear();
                        formState.value.employee_id = "";
                    })
                    .catch(error => {
                        errorMessages.value = error.response.data.errors;
                        errorModal(t, errorMessages);//show error message modally
                    })
                }
            })
        }

        const shouldAddSpan = (index, type) => {
            if (index === 0) {
                return true;
            }
            const prevType = questions.value[index-1].type;
            return (prevType !== type) && (type === 0.5 || type === 1 || type === 2);
        }

        onMounted(() => {
            _fetch();
        });

        notification.config({
            placement: 'bottomLeft',
            duration: 3,
            rtl: true,
        });

        return {
            review,
            reviewee,
            rangeTimeReview,
            users,
            mode,
            checked,
            title,
            onChangeMode,
            groupEmployee,
            selectedItem,
            selectedCommentItem,
            comment,
            content,
            totals,
            employeePositionLogin,
            questions,
            employeeSelectboxes,
            toolbar,
            errorMessages,
            formState,
            columns,
            renderHTML,
            onChangeAnswer,
            onSubmitReview,
            onChangeComment,
            onChangeApprove,
            search,
            shouldAddSpan,
            reviewSubmit,
            modalSubmitReview
        };
    }
})
</script>