<template>
    <DialogEditOrder :isAdministrator="props.isAdministrator" :dateFilter="dateFilter ? dateFilter : null" @updated-order="updatedOrderListen" ref="modalEditRef" />
    <DialogScreenshotOrder :store_type="formState.store_type" :store_id="formState.store_id" ref="modalScreenshotRef" />
    <div>
        <h1 class="tab-title">
            Danh sách đặt món ngày {{ dateTitle }}
        </h1>
        <!-- filter row -->
        <el-row :gutter="10">
            <el-col :span="2" class="custom-filter">
                <label class="sub-select">Ngày đặt</label>
                <el-date-picker
                    v-model="formState.date_order"
                    type="date"
                    placeholder=""
                    size="default"
                    format="DD/MM/YYYY"
                    value-format="YYYY-MM-DD"
                    :default-value="new Date()"
                />
            </el-col>
            <el-col :span="2" class="custom-filter">
                <label class="sub-select">Tên</label>
                <el-select
                    v-model="formState.user_id"
                    value-key="id"
                    placeholder="Employees"
                    clearable
                    filterable
                    style="width: 100%"
                >
                    <el-option
                        v-for="item in filteredUsers"
                        :key="item.id"
                        :label="item.fullname"
                        :value="item.id"
                    />
                </el-select>
            </el-col>
            <el-col :span="2" class="custom-filter">
                <label class="sub-select">Bộ phận</label>
                <el-select
                    v-model="formState.department_id"
                    value-key="id"
                    placeholder="Department"
                    clearable
                    filterable
                    style="width: 100%"
                >
                    <el-option
                        v-for="item in departments"
                        :key="item.id"
                        :label="item.name"
                        :value="item.id"
                    />
                </el-select>
            </el-col>
            <!-- <el-col :span="2" class="custom-filter">
                <label class="sub-select">Trạng thái working</label>
                <el-select
                    v-model="formState.user_status"
                    value-key="value"
                    placeholder="Status"
                    clearable
                    filterable
                    style="width: 100%"
                >
                    <el-option
                        v-for="item in statuses"
                        :key="item.value"
                        :label="item.label"
                        :value="item.value"
                    />
                </el-select>
            </el-col> -->
            <el-col :span="2" class="custom-filter">
                <label class="sub-select">Loại cửa hàng</label>
                <el-select
                    v-model="formState.store_type"
                    value-key="value"
                    placeholder="Cửa hàng"
                    filterable
                    style="width: 100%"
                >
                    <el-option
                        v-for="item in storeTypeDefine"
                        :key="item.value"
                        :label="item.label"
                        :value="item.value"
                    />
                </el-select>
            </el-col>
            <el-col :span="2" class="custom-filter">
                <label class="sub-select">Tên cửa hàng</label>
                <el-select
                    v-model="formState.store_id"
                    value-key="value"
                    placeholder="Cửa hàng"
                    filterable
                    style="width: 100%"
                    clearable
                >
                    <el-option
                        v-for="item in storeName"
                        :key="item.id"
                        :label="item.name"
                        :value="item.id"
                    />
                </el-select>
            </el-col>
            <el-col :span="2" class="custom-filter">
                <label class="sub-select">Trạng thái đặt đơn</label>
                <el-select
                    v-model="formState.order_status"
                    value-key="value"
                    placeholder="Status"
                    clearable
                    filterable
                    style="width: 100%"
                >
                    <el-option
                        v-for="item in orderStatus"
                        :key="item.value"
                        :label="item.label"
                        :value="item.value"
                    />
                </el-select>
            </el-col>
            <el-col :span="2" class="custom-filter" style="padding-top: 22px">
                <el-space size="small" spacer="|">
                    <el-button
                        type="primary"
                        v-on:click="search()"
                        :loading="loadingSearch"
                        >Search</el-button
                    >
                    <el-button
                    type="primary"
                    v-on:click="openScreenshotModal()"
                    >
                        <span style="margin-right: 5px;">Screenshot</span>
                        <el-icon><FullScreen /></el-icon>
                    </el-button
                >
                </el-space>
            </el-col>
        </el-row>
        <!-- Table from here -->
        <el-table
            :data="tableData"
            height="1024"
            style="width: 100%"
            highlight-current-row
            v-loading="loadingTable"
            border
            fit
            flexible
            :row-class-name="tableRowClassName"
        >
            <el-table-column type="index" width="50" />
            <el-table-column
                label="Tên"
                width="300"
                sortable
                :sort-method="sortLastName"
            >
                <template #default="scope">
                    <div class="user-info">
                        <el-avatar shape="square" size="default" :src="getFullPathAvatar(scope.row.avatar)" />
                        <div class="user-details">
                            <span class="fullname">{{
                                scope.row.fullname
                            }}</span>
                            <span class="email">{{ scope.row.email }}</span>
                        </div>
                    </div>
                </template>
            </el-table-column>
            <el-table-column
                label="Ghi hộp cơm"
                width="200"
            >
                <template #default="scope">
                    <el-input
                    :disabled="!props.isAdministrator"
                    clearable
                     v-model="scope.row.alias_name" @change="onChangeOrderAlias(scope.row.id, $event)"/>
                </template>
            </el-table-column>
            <el-table-column label="Món">
                <template #default="scope">
                    {{ scope.row.orders[0] ? scope.row.orders[0].items : "" }}
                </template>
            </el-table-column>
            <el-table-column width="250" label="Ghi chú">
                <template #default="scope">
                    {{ scope.row.orders[0] ? scope.row.orders[0].note : "" }}
                </template>
            </el-table-column>
            <el-table-column prop="created_at" width="170" label="Thời gian đặt">
                <template #default="scope">
                    {{
                        scope.row.orders[0]
                            ? formatDateTime(scope.row.orders[0].created_at)
                            : ""
                    }}
                </template>
            </el-table-column>
            <!-- if role admin -->
            <el-table-column
                width="200"
                label="Hành động"
            >
                <template #default="scope">
                    <div style="display: flex; gap: 5px;">
                        <el-button
                            v-if="(dateAvailableOrder && currentUser && scope.row.orders[0] && currentUser.id == scope.row.id) || (props.isAdministrator)"
                            size="small"
                            type="primary"
                            @click="openEditModal(scope.row)"
                            >Chỉnh sửa</el-button
                        >
                        <el-button
                            v-if="(dateAvailableOrder && currentUser && scope.row.orders[0] && currentUser.id == scope.row.id) || (props.isAdministrator && scope.row.orders[0])"
                            size="small"
                            type="danger"
                            @click="openConfirm(scope.row.orders[0])"
                            style="margin: 0;"
                        >
                            Hủy đặt
                        </el-button>
                    </div>
                </template>
            </el-table-column>
        </el-table>
    </div>
</template>
<script setup lang="ts">
import axios from "axios";
import { onMounted, computed, ref, watch } from "vue";
import { callMessage } from "../Helper/el-message.js";
import dayjs from "dayjs";
import DialogEditOrder from "./DialogEditOrder.vue";
import { ElMessageBox } from 'element-plus';
import {FullScreen} from '@element-plus/icons-vue';
import DialogScreenshotOrder from './DialogScreenshotOrder.vue';

const props = defineProps<{
    currentDate: string;
    isAdministrator: boolean,
    keyHistoryComponent: number,
}>();

watch(() => props.keyHistoryComponent, (first, second) => {
    fetchOrders();
});

interface FormState {
    user_id?: number;
    department_id?: number;
    date_order?: string;
    user_type?: number;
    user_status?:number,
    order_status?:number,
    store_type?:string,
    store_id?:number,
}

interface User {
    id?: number;
    fullname: string;
    department_id?: number;
}

interface Department {
    id: number;
    name: string;
}

interface Option {
    value?: number | string,
    label: string
};

const statuses = ref<Array<Option>>([
    {
        label: 'Working',
        value: 1
    },
    {
        label: 'Quit',
        value: 2
    }
]);

const storeTypeDefine = ref<Array<Option>>([
    {
        label: 'Quán cơm trưa',
        value: 'RICE'
    },
    {
        label: 'Quán ăn/uống khác',
        value: 'DYNAMIC'
    }
]);

const orderStatus = ref<Array<Option>>([
    {
        label: 'Đã đặt',
        value: 1
    },
    {
        label: 'Chưa đặt',
        value: 2
    }
]);

const formState = ref<FormState>({
    user_status:1,
    store_type:'RICE',
    date_order: dayjs(new Date()).format('YYYY-MM-DD')
});

const storeName = ref();

const users = ref<Array<User>>([]);
const filteredUsers = computed(() => {
    const selectedDepartmentId = formState.value.department_id;
    if (selectedDepartmentId) {
        return users.value.filter(
            (user) => user.department_id === selectedDepartmentId
        );
    } else {
        return users.value;
    }
});
let dateTitle = ref('');
const errorMessages = ref();
const departments = ref<Array<Department>>([]);
const loadingSearch = ref(false);
const loadingTable = ref(false);
const tableData = ref<Array<Object>>([]);
const currentUser = ref(); 
const dateFilter = ref(formState.value.date_order);
const dateAvailableOrder = ref(false);
const currentDate = ref(dayjs().format('YYYY-MM-DD'));

const search = () => {
    loadingSearch.value = true;
    loadingTable.value = true;
    dateFilter.value = formState.value.date_order;
    if(formState.value.date_order){
        dateTitle.value = formState.value.date_order;
    }
    axios
        .get("/api/order",{
            params:formState.value
        })
        .then((response) => {
            loadingSearch.value = false;
            loadingTable.value = false;
            tableData.value = response.data.users.order;
            checkDateAvailableOrder();
        })
        .catch((error) => {
            loadingSearch.value = false;
            loadingTable.value = false;
            tableData.value = [];
            errorMessages.value = error.response.data.errors;
            callMessage(errorMessages.value, "error");
        });
};
const modalEditRef = ref();
const modalScreenshotRef = ref();
const openEditModal = (user : string) => {
    modalEditRef.value.ShowEditModalMode(user);
};
const openScreenshotModal = ()=>{
    modalScreenshotRef.value.showScreenshotModalMode();
}
const openConfirm = (order : any) => {
  ElMessageBox.confirm(
    'Bạn chắc chắn hủy đặt món? Tiếp tục?',
    'Warning',
    {
      confirmButtonText: 'OK',
      cancelButtonText: 'Cancel',
      type: 'warning',
    }
  )
    .then(() => {
        destroyOrder(order);
    })
    .catch(() => {})
}
onMounted(() => {
    getCurrentDate();

    axios.get("/api/common/departments").then((response) => {
        departments.value = response.data;
    });
    axios.get("/api/common/get_employees").then((response) => {
        users.value = response.data;
    });
    axios.get("/api/order/stores").then((response) => {
        storeName.value = response.data.order_store.data;
    });
    
    fetchOrders();

    checkDateAvailableOrder();
});

const checkDateAvailableOrder = ()=>{
    if(currentDate.value == dateFilter.value || !currentDate.value){
        dateAvailableOrder.value = true;
    }else{
        dateAvailableOrder.value = false;
    }
}

const fetchCurrentUserLogin = async ()=>{
    try{
        const response = await axios.get("/api/whoami");

        loadingTable.value = false; 
        currentUser.value = response.data.user;
    }catch(error : any){
        loadingTable.value = false;
        errorMessages.value = error.response.data.errors;
        callMessage(errorMessages.value, "error");
    }
}
fetchCurrentUserLogin();

const fetchOrders = async () =>{
    loadingTable.value = true;
    
    try{
        const response = await axios.get("/api/order",{
            params: formState.value
        });
        loadingTable.value = false;
        tableData.value = response.data.users.order;
    }catch(error : any){
        loadingTable.value = false;
        errorMessages.value = error.response.data.errors;
        callMessage(errorMessages.value, "error");
        //When search target data does not exist
        tableData.value = [];
    }
}

const sortLastName = (a: any, b: any) => {
    const extractLastName = (fullName: any) => {
        let nameParts = fullName
            .replace(/\(\d+\)/, "")
            .trim()
            .split(" ");
        return nameParts.pop().toLowerCase();
    };

    const lastNameA = extractLastName(a.fullname);
    const lastNameB = extractLastName(b.fullname);

    return lastNameA.localeCompare(lastNameB, "vi");
};

const sortCreatedAt = (a: any, b: any) => {
    console.log('sort created at')    
};

const formatDateTime = (date: string | undefined) => {
    return dayjs(date).format("DD/MM/YYYY HH:mm:ss");
};

const getCurrentDate = ()=>{
    const date = new Date();
    let day = date.getDate();
    let month = date.getMonth() + 1;
    let year = date.getFullYear();

    dateTitle.value = `${day}-${month}-${year}`;
}

const tableRowClassName = ({
  row,
  rowIndex,
}: {
  row: any
  rowIndex: number
}) => {
    if(row.orders[0]){
        return 'success-row'      
    }
  return ''
}

const getFullPathAvatar = (path : string | '') => {
    return window.location.origin + '/' + path;
}

const onChangeOrderAlias = async (userId : number, aliasName : Event) => {
    try{
        let body = {
            user_id : userId,
            alias_name : aliasName
        };
        const resp = await axios.patch('/api/order/user/quick-update',body);
        if(resp.data.success){
            callMessage(resp.data.success,'success');
            errorMessages.value = {};
        }else{
            callMessage(resp.data.message,'error');
            errorMessages.value = {};
        }
    }catch(error : any){
        errorMessages.value = error.response.data.errors;
        callMessage(errorMessages.value, "error");
    }
}

const updatedOrderListen = ()=>{
    fetchOrders();
}

const destroyOrder = async (order : any) =>{
    try{
        const resp = await axios.delete('/api/order/'+order.id);

        if(resp.data.success){
            errorMessages.value = {};
            callMessage(resp.data.success, "success");
            fetchOrders();
        }else{
            callMessage(resp.data.error, "error");
        }
    }catch(error : any){
        errorMessages.value = error.response.data.errors;
        callMessage(errorMessages.value, "error");
    }    
}
</script>   
<style lang="scss">
.success-row{
    background-color: #c5f9f2 !important;
}
.tab-title {
    margin-bottom: 30px;
    font-size: 1.5rem;
    font-weight: 500;
    text-align: center;
}
</style>
