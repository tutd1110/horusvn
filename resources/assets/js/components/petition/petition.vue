<template>
    <Dialog ref="dialogRef" @saved="onSaved"></Dialog>
    <el-row :gutter="10">
        <el-col :span="24" v-if="!hideMobile" style="margin-bottom: 5px;">
            <el-button v-on:click="showRegisterModal()" v-if="!hideMobile" type="primary">Tạo yêu cầu</el-button>
            <el-button 
                :icon="Filter" 
                size="small"
                @click="handleShowFilter(showFilter)"
                style="height: 30px; width: 30px; margin-right: 5px; float: right;"
            />
        </el-col>
        <el-col :lg="3" :md="24" v-if="(session?.is_authority || session?.is_leader ) && (hideMobile)">
            <label>Nhân viên</label>
            <el-select
                v-model="formState.user_id"
                value-key="id"
                placeholder="Employees"
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
        <el-col :lg="3" :md="24" v-if="hideMobile">
            <label>Khoảng thời gian</label>
            <el-date-picker
                v-model="datePeriod"
                type="daterange"
                start-placeholder="Start date"
                end-placeholder="End date"
                format="DD/MM/YYYY"
                value-format="YYYY-MM-DD"
                style="width:100%;"
            />
        </el-col>
        <el-col :lg="2" :md="24" v-if="hideMobile">
            <label>Hình thức nghỉ phép</label>
            <el-select
                v-model="formState.type_paid"
                value-key="id"
                placeholder="Employees"
                clearable
                filterable
                style="width: 100%"
            >
                <el-option
                    v-for="item in typePaids"
                    :key="item.id"
                    :label="item.name"
                    :value="item.id"
                />
            </el-select>
        </el-col>
        <el-col :lg="2" :md="24" v-if="hideMobile">
            <label>Trạng thái</label>
            <el-select
                v-model="formState.status"
                style="width: 100%"
            >
                <el-option
                    v-for="item in filteredStatuses"
                    :key="item.value"
                    :label="item.label"
                    :value="item.value"
                />
            </el-select>
        </el-col>
        <el-col :span="2" style="padding-top: 21px;" v-if="hideMobile">
            <el-space size="small" spacer="|">
                <el-button v-on:click="search()" type="primary">Tìm kiếm</el-button>
                <el-button v-on:click="showRegisterModal()" type="primary">Tạo yêu cầu</el-button>
            </el-space>
        </el-col>
    </el-row>
    <!-- table from here -->
    <div class="table-task">
        <el-row :gutter="2" class='table-task-header'>
            <template v-for="(column, idx1) in columns" :key="idx1">
                <el-col :span="column.span" v-if="column.key === 'approve_pm'">
                    <el-card class="box-card" shadow="hover">
                        <div class="timesheets-title" v-html="column.title"></div>
                    </el-card>
                </el-col>
                <el-col :span="column.span" v-else>
                    <el-card  class="box-card" shadow="hover" >
                        <div class="timesheets-title" v-html="column.title"></div>
                    </el-card>
                </el-col>
            </template>
        </el-row>
        <el-scrollbar height="626px" class="custom-scrollbar">
            <template v-for="(record, idx2) in dataSource" :key="idx2">
                <el-row :gutter="2" :class="record['approve_pm'] == false && session?.id == 51 ? 'none_approve_pm' : ''" class="table-task-body">
                    <template v-for="(column, idx3) in columns" :key="idx3">
                        <el-col :span="getColSpan(column.key)" v-if="column.key === 'action'">
                            <el-card class="box-card" shadow="hover">
                                <div class="table-text">
                                    <!-- <template v-if="record['approve_pm'] == false && session?.id == 51 && record['user_id'] != 51">
                                        <div class="table-text" style="font-weight: 700; color: red; text-align: center;">
                                            HR chưa duyệt
                                        </div>
                                    </template>
                                    <template v-else> -->
                                        <el-select
                                            placeholder="Action"
                                            clearable
                                            class="none-border placeholder-black"
                                            style="width: 100%"
                                            @change="onChangeAction(record, $event)"
                                        >
                                            <el-option
                                                v-for="item in filteredActions"
                                                :key="item.value"
                                                :label="item.label"
                                                :value="item.value"
                                            />
                                        </el-select>
                                    <!-- </template> -->
                                </div>
                            </el-card>
                        </el-col>
                        <el-col :span="getColSpan(column.key)" v-else-if="column.key === 'approve_pm'">
                            <el-card class="box-card box-card-custom" shadow="hover">
                                <div class="table-text" v-if="record['approve_pm'] == false" style="text-align: center;">
                                    <el-button 
                                        type="success" 
                                        :icon="Check" 
                                        size="small"
                                        @click="handleApprovePm(record['id'])"
                                        style="height: auto; width: 30px; margin: 0 5px 0 0;"
                                        :style="!hideMobile? 'margin: 0 0 5px 0' : ''"
                                    />
                                    <el-button 
                                        type="danger" 
                                        :icon="Close" 
                                        size="small"
                                        @click="openDialogReason(record, 3)"
                                        style="height: auto; width: 30px; margin: 0"
                                    />
                                </div>
                                <div class="table-text" v-else>
                                    Đã gửi
                                </div>
                            </el-card>
                        </el-col>
                        <el-col :span="getColSpan(column.key)" v-else-if="column.key === 'fullname'">
                            <el-card class="box-card" shadow="hover">
                                <div class="table-text">
                                    {{ record[column.key] }}
                                </div>
                            </el-card>
                        </el-col>
                        <el-col :span="getColSpan(column.key)" v-else-if="column.key === 'detail'">
                            <el-card class="box-card box-card-custom" shadow="hover">
                                <div class="table-text">
                                    <Warning v-if="!hideMobile" style="width: 20px; vertical-align: top; transform: rotate(180deg);" @click="showModal(record)"/>
                                </div>
                            </el-card>
                        </el-col>
                        <el-col :span="getColSpan(column.key)" v-else>
                            <el-card class="box-card" shadow="hover">
                                <div class="table-text">
                                    {{ record[column.key] }}
                                </div>
                            </el-card>
                        </el-col>
                    </template>
                </el-row>
            </template>
        </el-scrollbar>
    </div>
    <el-col style="margin-top: 20px;display: flex;align-items: center;justify-content: center;">
        <el-pagination
            v-model="formState.current_page"
            :page-size="formState.per_page"
            background
            :total="total"
            @current-change="onChangePage"
        />
    </el-col>
    <el-dialog
        v-model="visibleReason"
        :title="titleReason"
        :width="!hideMobile ? '80%' : '30%'"
        align-center
        :before-close="handleClose"
    >
        <label class="sub-select">Lí do từ chối</label>
        <el-input
            v-model="rejectedReason"
            style="width: 100%"
        />
        <template #footer>
            <span class="dialog-footer">
                <el-button @click="visibleReason = false">Cancel</el-button>
                <el-button type="primary" @click="performApiActionReason">Confirm</el-button>
            </span>
        </template>
    </el-dialog>
    <el-drawer
        v-model="showFilter"
        direction="rtl"
        size="80%"
    >
        <template #header="">
                <span style="display: block;">Lọc yêu cầu</span>
            </template>
        <el-row :gutter="10">
            <el-col :lg="3" :md="24" v-if="(session?.is_authority || session?.is_leader )">
                <label>Nhân viên</label>
                <el-select
                    v-model="formState.user_id"
                    value-key="id"
                    placeholder="Employees"
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
            <el-col :lg="3" :md="24">
                <label>Khoảng thời gian</label>
                <el-date-picker
                    v-model="datePeriod"
                    type="daterange"
                    start-placeholder="Start date"
                    end-placeholder="End date"
                    format="DD/MM/YYYY"
                    value-format="YYYY-MM-DD"
                    style="width:100%;"
                />
            </el-col>
            <el-col :lg="2" :md="24">
                <label>Hình thức nghỉ phép</label>
                <el-select
                    v-model="formState.type_paid"
                    value-key="id"
                    placeholder="Employees"
                    clearable
                    filterable
                    style="width: 100%"
                >
                    <el-option
                        v-for="item in typePaids"
                        :key="item.id"
                        :label="item.name"
                        :value="item.id"
                    />
                </el-select>
            </el-col>
            <el-col :lg="2" :md="24">
                <label>Trạng thái</label>
                <el-select
                    v-model="formState.status"
                    style="width: 100%"
                >
                    <el-option
                        v-for="item in filteredStatuses"
                        :key="item.value"
                        :label="item.label"
                        :value="item.value"
                    />
                </el-select>
            </el-col>
            <el-col :span="2" style="padding-top: 21px;">
                <el-space size="small" spacer="|">
                    <el-button v-on:click="search()" type="primary">Tìm kiếm</el-button>
                </el-space>
            </el-col>
        </el-row>
    </el-drawer>
</template>
<script lang="ts" setup>
import axios from 'axios';
import { onMounted, computed, ref } from 'vue';
import { callMessage } from '../Helper/el-message.js';
import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc'
import timezone from 'dayjs/plugin/timezone'
import { TIME_ZONE } from '../const.js'
import Dialog from './Dialog.vue';
import { openLoading, closeLoading } from '../Helper/el-loading';
import { ElMessageBox } from 'element-plus'
import { Check, Close, Filter, Warning} from '@element-plus/icons-vue'

dayjs.extend(utc);
dayjs.extend(timezone);

interface FormState {
    user_id?: number,
    start_date?: string,
    end_date?: string,
    type_paid?: number,
    status: number | null,
    current_page: number,
    per_page: number,
}
interface Item {
    id: number,
    user_id: number,
    fullname: number,
    type_name: string,
    info: string,
    reason: string,
    infringe_message: string,
    created_at: string,
    updated_at: string,
    status: number,
}
interface User {
    id: number,
    fullname: string
}
interface Option {
    id: number,
    name: string
}
interface Session {
    id: number,
    is_authority: boolean,
    is_leader: boolean
}
const total = ref(0)
const formState = ref<FormState>({
    status: 0,
    current_page: 1,
    per_page: 30,
});
const errorMessages = ref('')
const dataSource = ref()
const datePeriod = ref<string[]>([])
const users = ref<User[]>([])
const typePaids = ref<Option[]>([])
const session = ref<Session>()

const visibleReason = ref(false)

// Computed property for computed formState
const computedFormState = computed<FormState>(() => {
    const newFormState: FormState = {
        ...formState.value,
    };

    if (datePeriod.value && datePeriod.value.length === 2) {
        newFormState.start_date = datePeriod.value[0];
        newFormState.end_date = datePeriod.value[1];
    } else {
        newFormState.start_date = undefined;
        newFormState.end_date = undefined;
    }

    return newFormState;
});
const columns = ref([
    { title: "Name", key: "fullname", span: 3},
    { title: "Loại yêu cầu", key: "type_name", span: 4},
    { title: "Thông tin yêu cầu", key: "info", span: 4},
    { title: "Lý do", key: "reason", span: 7},
    { title: "Trạng thái", key: "infringe_message", span: 3},
    { title: "Ngày gửi", key: "created_at", span: 2},
    { title: "Thao tác", key: "action", span: 1},
])
const getColSpan = (key: string) => {
    const foundColumn = columns.value.find(column => column.key === key);
    return foundColumn ? foundColumn.span : 1; // Default to 1 if not found
}
const statuses = ref([
    { label: "Yêu cầu cần duyệt", value: 0 },
    { label: "Đã duyệt yêu cầu", value: 1 },
    { label: "Đã vi phạm", value: 2 },
    { label: "Bị từ chối", value: 3 },
    { label: "Đã xoá", value: 4 }
]);
const actions = ref([
    { label: "Sửa", value: 0 },
    { label: "Duyệt", value: 1 },
    { label: "Vi phạm",value: 2 },
    { label: "Từ chối",value: 3 },
    { label: "Xoá",value: 4 }
]);
const filteredActions = computed(() => {
    const allowedValues = session.value?.is_authority ? [0, 1, 2, 3, 4] : [0];
    return actions.value.filter(action => allowedValues.includes(action.value));
});
const filteredStatuses = computed(() => {
    const allowedValues = session.value?.is_authority ? [0, 1, 2, 3, 4] : [0, 1, 2, 3];
    return statuses.value.filter(status => allowedValues.includes(status.value));
});
const onChangePage = (page: number) => {
    formState.value.current_page = page

    search()
}
const search = () => {
    showFilter.value = false
    _fetch()
}
const dialogRef = ref()
const showRegisterModal = () => {
    dialogRef.value.ShowWithAddMode();
};
const showEditModal = (item: Item, value: number, check_updated_at: string) => {
    const isAuthority = session.value?.is_authority;
    const isUserAndEditableStatus = item.user_id === session.value?.id && formState.value.status === 0;
    
    if (isAuthority || isUserAndEditableStatus) {
        dialogRef.value.ShowWithUpdateMode(item.id, check_updated_at);
    }
}
const showModal = (item: Item) => {
    const check_updated_at = dayjs(item.updated_at).tz(TIME_ZONE.ZONE).format("YYYY/MM/DD HH:mm:ss");
    const isAuthority = session.value?.is_authority;
    
    dialogRef.value.ShowDetail(item.id, check_updated_at);
}
const performApiAction = (item: Item, value: number, check_updated_at: string) => {
    axios.patch('/api/petition/action', {
        id: item.id,
        key: value,
        check_updated_at: check_updated_at,//exclusion control
    }).then(response => {
        _fetch();
    }).catch(error => {
        errorMessages.value = error.response.data.errors;
        callMessage(errorMessages.value, 'error');
    })
}
interface DialogData {
    item?: any,
    value?: number,
    check_updated_at?: string
}
const titleReason = ref('')
const rejectedReason = ref('')
const dialogData = ref<DialogData>({})
const handleClose = (done: () => void) => {
  ElMessageBox.confirm('Bạn có chắc chắn muôn thoát?')
    .then(() => {
      done()
    })
}
const openDialogReason = (item: Item, value: number) => {
    const check_updated_at = dayjs(item.updated_at).tz(TIME_ZONE.ZONE).format("YYYY/MM/DD HH:mm:ss");
    dialogData.value.item = item;
    dialogData.value.value = value;
    dialogData.value.check_updated_at = check_updated_at;
    visibleReason.value = true
    titleReason.value = 'Từ chối đơn "'+item.type_name+'" của '+item.fullname;
}
const performApiActionReason = () => {
    axios.patch('/api/petition/action', {
        id: dialogData.value.item.id,
        key: dialogData.value.value,
        check_updated_at: dialogData.value.check_updated_at,//exclusion control
        rejected_reason: rejectedReason.value
    }).then(response => {
        visibleReason.value = false;
        _fetch();
    }).catch(error => {
        errorMessages.value = error.response.data.errors;
        callMessage(errorMessages.value, 'error');
    })
}
const performDeleteApiAction = (item: Item, value: number, check_updated_at: string) => {
    axios.delete('/api/petition/delete', {
        params: {
            id: item.id,
            key: value,
            check_updated_at: check_updated_at,//exclusion control
        }
    })
    .then(response => {
        _fetch();
    })
    .catch(error => {
        errorMessages.value = error.response.data.errors;
        callMessage(errorMessages.value, 'error');
    })
}
// Define the type for action handlers
type ActionHandler = (item: Item, value: number, check_updated_at: string) => void;
const actionHandlers: Record<number, ActionHandler> = {
    0: showEditModal,
    1: performApiAction,
    2: performApiAction,
    // 3: performApiAction,
    3: openDialogReason,
    4: performDeleteApiAction,
}
const onChangeAction  = (item: Item, value: number) => {
    const check_updated_at = dayjs(item.updated_at).tz(TIME_ZONE.ZONE).format("YYYY/MM/DD HH:mm:ss");

    const selectedHandler = actionHandlers[value];
    if (selectedHandler) {
        selectedHandler(item, value, check_updated_at);
    }
}
const handleApprovePm = (id:number) => {
    axios.patch('/api/petition/update_approve_pm', {
        id: id,
        approve_pm: 2,
    }).then(response => {
        _fetch();
        callMessage(response.data.success, 'success');
    }).catch(error => {
        errorMessages.value = error.response.data.errors;
        callMessage(errorMessages.value, 'error');
    })
    
}
const _fetch = () => {
    openLoading('custom-scrollbar');
    
    if (formState.value.status == 0 && session.value?.id == 82) {
        columns.value = [
            { title: "Name", key: "fullname", span: 3},
            { title: "Loại yêu cầu", key: "type_name", span: 3},
            { title: "Thông tin yêu cầu", key: "info", span: 4},
            { title: "Lý do", key: "reason", span: 7},
            { title: "Trạng thái", key: "infringe_message", span: 3},
            { title: "Ngày gửi", key: "created_at", span: 2},
            { title: "Gửi PM", key: "approve_pm", span: 1},
            { title: "Thao tác", key: "action", span: 1},
        ]
    } else {
        columns.value = [
            { title: "Name", key: "fullname", span: 3},
            { title: "Loại yêu cầu", key: "type_name", span: 4},
            { title: "Thông tin yêu cầu", key: "info", span: 4},
            { title: "Lý do", key: "reason", span: 7},
            { title: "Trạng thái", key: "infringe_message", span: 3},
            { title: "Ngày gửi", key: "created_at", span: 2},
            { title: "Thao tác", key: "action", span: 1},
        ]
    }
    var screenWidth = window.screen.width;
    if (screenWidth >= 1080) {
        hideMobile.value = true
    } else {
        hideMobile.value = false
        if (formState.value.status == 0 && session.value?.id == 82) {
            columns.value = [
                { title: "Name", key: "fullname", span: 10},
                { title: "Loại yêu cầu", key: "type_name", span: 6},
                { title: "Gửi PM", key: "approve_pm", span: 4},
                { title: "Thao tác", key: "detail", span: 4},
            ]
        } else {
            columns.value = [
                { title: "Name", key: "fullname", span: 10},
                { title: "Loại yêu cầu", key: "type_name", span: 10},
                { title: "Thao tác", key: "detail", span: 4},
            ]
        }
    } 
    axios.post('/api/petition/get_petition_list', computedFormState.value).then(response => { 
        formState.value.current_page = response.data.currentPage
        dataSource.value = response.data.items;
        total.value = response.data.totalItems
        closeLoading();
    })
    .catch((error) => {
        closeLoading();
        dataSource.value = []; //dataSource empty
        errorMessages.value = error.response.data.errors;
        callMessage(errorMessages.value, 'error');
    });
};
const onSaved = () => {
    _fetch();
};
const showFilter = ref(false)
const hideMobile = ref(true)
const handleShowFilter = (status:boolean) => {
    showFilter.value = !status
}
onMounted(() => {
    axios.get('/api/petition/get_selectboxes')
    .then(response => {
        typePaids.value = response.data.type_paid
        users.value = response.data.users
        session.value = response.data.user_login

        _fetch();
    })
});
</script>