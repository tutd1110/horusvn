<template>
    <a-modal v-model:visible="visible" style="width:1000px; font-weight: bold" :footer="null" :maskClosable="false"
        :closable="false" :title="title">
        <a-form :model="formState" autocomplete="off" style="width:800px;">
            <a-row>
                <a-col :span="13" :offset="5">
                    <label name="name">Tên dự án</label>
                    <a-form-item :span="3">
                        <a-input allow-clear v-model:value="formState.name" />
                    </a-form-item>
                </a-col>
                <a-col :span="4" :offset="2">
                    <label name="name">Mã dự án</label>
                    <a-form-item :span="3">
                        <a-input :disabled="true" v-model:value="formState.code" />
                    </a-form-item>
                </a-col>
            </a-row>
            <a-row>
                <a-col :span="19" :offset="5" style="padding-bottom: 108px;">
                    <label name="name">Mô tả dự án</label>
                    <QuillEditor
                        theme="snow"
                        v-model:content="formState.description"
                        :toolbar="toolbar"
                        contentType="html"
                    />
                </a-col>
            </a-row>
            <a-row>
                <a-col :span="19" :offset="5">
                    <label name="name">Note</label>
                    <a-form-item :span="3">
                        <a-input allow-clear v-model:value="formState.note" />
                    </a-form-item>
                </a-col>
                <a-col :span="19" :offset="5">
                    <label name="name">Người tham gia</label>
                    <a-form-item :span="1">
                        <!-- <a-select
                                ref="select"
                                v-model:value="formState.user_ids"
                                style="width:100%;"
                                :options="userSelectbox"
                                mode="multiple"
                                :field-names="{ label:'fullname', value: 'id' }"
                                :filterOption="filterOption"
                        ></a-select> -->
                        <el-select
                                v-model="formState.user_ids"
                                multiple
                                filterable
                                clearable
                                style="width:100%;"
                            >
                                <el-option
                                    v-for="item in userSelectbox"
                                    :key="item.id"
                                    :label="item.fullname"
                                    :value="item.id"
                                />
                            </el-select>
                    </a-form-item>
                </a-col>
            </a-row>
            <!--Delete button-->
            <a-row style="margin-left:166px">
                <a-col>
                    <a-form-item>
                        <single-submit-button style="width:100px" v-if="mode==='UPDATE'" type="primary" danger :onclick="onClickDeleteButton">Xoá</single-submit-button>
                    </a-form-item>
                </a-col>

            </a-row>
            <!--Cancel button-->
            <a-row style="float:right; margin-top:-53.5px">
                <a-col style="margin-right:10px;"> 
                    <a-form-item>
                        <a-button style="width:100px" @click="cancel">Huỷ</a-button>
                    </a-form-item>
                </a-col>
                <a-form-item>
                    <a-col>
                        <single-submit-button style="width:100px" v-if="mode==='ADD'" type="primary" :onclick="onClickStoreButton">Thêm</single-submit-button>
                        <single-submit-button style="width:100px" v-if="mode==='UPDATE'" type="primary" :onclick="onClickUpdateButton">Cập nhật</single-submit-button>
                    </a-col>
                </a-form-item>
            </a-row>
        </a-form>
    </a-modal>
</template>
<script>
import { Modal } from 'ant-design-vue';
import axios from 'axios';
import { ref, h } from 'vue';
import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc'
import timezone from 'dayjs/plugin/timezone'
import { TIME_ZONE } from '../const.js'
import { useI18n } from 'vue-i18n';
import SingleSubmitButton from '../Shared/SingleSubmitButton/SingleSubmitButton.vue';
import { QuillEditor } from '@vueup/vue-quill'
import '@vueup/vue-quill/dist/vue-quill.snow.css';

dayjs.extend(utc);
dayjs.extend(timezone);

export default ({
    components: {
        SingleSubmitButton,
        QuillEditor
    },
    emits: ['saved'],
    watch: {
        'formState.name': function (newVal) {
            if (newVal) {
                let arrProjectName = newVal.split(/[. ]/);
                let projectCode = '';
                arrProjectName.forEach(item => {
                    projectCode = projectCode + item.charAt(0);
                })

                this.formState.code = projectCode.toUpperCase();
            } else {
                this.formState.code = '';
            }
        },
    },
    setup(props, { emit }) {
        const { t } = useI18n();
        const errorMessages = ref();
        const title = ref("");
        const visible = ref(false);
        const formState = ref({
            id: "",
            name: "",
            code: "",
            description: "",
            user_ids: [],
            datePeriod: []
        });//form value
        const mode = ref("");//New mode or edit mode or change
        const selectedRecord = ref("");//Store ID of selected row
        const strictDateFormat = "YYYY/MM/DD HH:mm:ss"
        const exclusionControl = ref();//exclusion control
        const userSelectbox = ref([]);

        const toolbar = [
            [{ header: [1, 2, 3, 4, 5, 6, false] }],
            [{ size: ['small', false, 'large', 'huge'] }],
            ['bold', 'italic', 'underline', 'strike'],
            [{ font: [] }],
            ['link'],
            ['clean'],
        ];

        const cancel = () => {
            visible.value = false;
        };

        const formClear = () => {
            formState.value.id = "";
            formState.value.name = "";
            formState.value.code = "";
            formState.value.description = ref("<p><br></p>", "");
            formState.value.user_ids = [];
            formState.value.datePeriod = [];
            formState.value.note = "";
        };

        //select box list generation
        const CreateSelbox = () => {
            //users
            axios.get('/api/project/get_users')
            .then(response => {
                userSelectbox.value = response.data;
            })
        };

        const ShowWithAddMode = () => {
            mode.value = "ADD";
            title.value = "Thêm dự án"
            visible.value = true;
            //form initialization
            formClear();
            CreateSelbox();
        };

        const ShowWithUpdateMode = (id, updated_at) => {
            mode.value = "UPDATE";
            title.value = "Chỉnh sửa thông tin dự án"
            visible.value = true;
            formClear();
            CreateSelbox();
            selectedRecord.value = id;

            axios
                .get('/api/project/get_project_by_id/', {
                    params: {
                        id: selectedRecord.value
                    }
                })
                .then(response => {
                    exclusionControl.value = dayjs(response.data.updated_at).tz(TIME_ZONE.ZONE).format(strictDateFormat);
                    formState.value = response.data;
                })
                .catch(error => {
                    errorMessages.value = error.response.data.errors;
                    errorModal(true);
                })
        };

        const onClickDeleteButton = (event) => {
            event.preventDefault();
            return new Promise((resolve, reject) => {
                Modal.confirm({
                    title: 'Nếu bạn xoá dự án này, các công việc liên quan đến nó sẽ bị xoá, bạn có chắc chắn xoá thông tin dự án này?',
                    okText: 'Ok',
                    cancelText: 'Huỷ',
                    onOk() {
                        axios.delete('/api/project/delete', {
                            params: {
                                id: selectedRecord.value,
                                check_updated_at: exclusionControl.value
                            }
                        })
                        .then(response => {
                            resolve();
                            _update();
                        })
                        .catch(error => {
                            reject();
                            errorMessages.value = error.response.data.errors;
                            errorModal(false);
                        })
                    },
                    onCancel() {
                        reject();
                    },
                })
            })
        };

        const onClickUpdateButton = (event) => {
            event.preventDefault();
            return new Promise((resolve, reject) => {
                //get users list that has selected on select box
                var user_ids = [];
                formState.value.user_ids.forEach(function(element) {
                    var item = {
                        id: element
                    };
                    user_ids.push(item);
                })
                axios.patch('/api/project/update', {
                    id: formState.value.id,
                    name: formState.value.name,
                    code: formState.value.code,
                    description: formState.value.description,
                    note: formState.value.note,
                    user_ids: user_ids,
                    check_updated_at: exclusionControl.value,//exclusion control
                })
                .then(response => {
                    resolve();
                    _update();
                })
                .catch(error => {
                    reject();
                    errorMessages.value = error.response.data.errors;//put message content in ref
                    errorModal(false);//show error message modally
                })
            })
        };

        const _update = () => {
            visible.value = false;
            emit('saved');
        };

        const onClickStoreButton = (event) => {
            event.preventDefault();
            return new Promise((resolve, reject) => {
                //get users list that has selected on select box
                var user_ids = [];
                formState.value.user_ids.forEach(function(element) {
                    var item = {
                        id: element
                    };
                    user_ids.push(item);
                })
                axios.post('/api/project/store', {
                    name: formState.value.name,
                    code: formState.value.code,
                    description: formState.value.description,
                    note: formState.value.note,
                    user_ids: user_ids,
                })
                .then(response => {
                    resolve();
                    _update();
                })
                .catch(error => {
                    reject();
                    errorMessages.value = error.response.data.errors;//put message content in ref
                    errorModal(false);//show error message modally
                })
            })
        };

        const filterOption = (input, option) => {
            if (option.fullname) {
                return option.fullname.toLowerCase().indexOf(input.toLowerCase()) >= 0 ? true : false;
            }
        }

        const errorModal = (isEdit) => {
            Modal.warning({
                title: t('message.MSG-TITLE-W'),
                content: h('ul', {}, errorMessages.value.split('<br>').map((error) => { return h('li', error) })),
                onOk() {
                    //isEdit is boolean, show us about mode edit/delete
                    //when init edit/delete if there is no data match, we'll show message modal dialog
                    //onOk we'll close edit/delete mode modal and re-init list data
                    if (isEdit) {
                        _update();
                    }
                }
            });
        };

        return {
            cancel,
            t,
            errorModal,
            errorMessages,
            formState,
            userSelectbox,
            title,
            visible,
            mode,
            ShowWithAddMode,
            ShowWithUpdateMode,
            onClickStoreButton,
            onClickUpdateButton,
            onClickDeleteButton,
            toolbar,
            filterOption
        };
    }
})
</script>