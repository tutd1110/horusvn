<template>
    <el-dialog
        v-model="visible"
        class="screenshot-modal"
        draggable
        border
        fullscreen
        :closable="false"
        :style="{ width: '80%' }"
        title="Screenshot mode"
    >
        <el-table
            :data="tableData"
            height="auto"
            style="width: 100%; font-size: 14px"
            highlight-current-row
            v-loading="loadingTable"
            border="2px"
            fit
            flexible
            :row-class-name="tableRowClassName"
        >
            <el-table-column label="TT" type="index" width="50" style="padding: 0" />
            <el-table-column
                label="Tên"
                width="250"
                sortable
                :sort-method="sortLastName"
                style="padding: 0"
            >
                <template #default="scope">
                    <div class="user-info">
                        <div class="user-details">
                            <span class="fullname">{{
                                scope.row.fullname
                            }}</span>
                        </div>
                    </div>
                </template>
            </el-table-column>
            <el-table-column label="Ghi hộp cơm" width="200" style="padding: 0">
                <template #default="scope">
                    {{ scope.row.alias_name }}
                </template>
            </el-table-column>
            <el-table-column style="padding: 0" label="Món">
                <template #default="scope">
                    {{ scope.row.orders[0] ? scope.row.orders[0].items : "" }}
                </template>
            </el-table-column>
            <el-table-column style="padding: 0" width="250" label="Ghi chú">
                <template #default="scope">
                    {{ scope.row.orders[0] ? scope.row.orders[0].note : "" }}
                </template>
            </el-table-column>
        </el-table>
    </el-dialog>
</template>

<script lang="ts" setup>
import axios from "axios";
import { onMounted, ref, watch } from "vue";
import { callMessage } from "../Helper/el-message.js";
const visible = ref(false);

let dateTitle = ref("");
const errorMessages = ref();
const loadingTable = ref(false);
const tableData = ref<Array<Object>>([]);

const props = defineProps<{
    store_type?:string,
    store_id?:number
}>();

watch(visible, async (newValue, oldValue) => {
    if(newValue){
        fetchOrders();
    }
});

onMounted(() => {
    getCurrentDate();
    fetchOrders();
});

const fetchOrders = async () => {
    loadingTable.value = true;
    try {
        const response = await axios.get("/api/order", {
            params: {
                user_status: 1,
                order_status: 1,
                store_type: props.store_type ?? 'RICE',
                store_id: props.store_id ?? ''
            },
        });
        loadingTable.value = false;
        tableData.value = response.data.users.order;
    } catch (error: any) {
        loadingTable.value = false;
        errorMessages.value = error.response.data.errors;
        callMessage(errorMessages.value, "error");
        //When search target data does not exist
        tableData.value = [];
    }
};

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

const getCurrentDate = () => {
    const date = new Date();
    let day = date.getDate();
    let month = date.getMonth() + 1;
    let year = date.getFullYear();
    dateTitle.value = `${day}-${month}-${year}`;
};

const tableRowClassName = ({
    row,
    rowIndex,
}: {
    row: any;
    rowIndex: number;
}) => {
    // if(row.orders[0]){
    //     return 'success-row'
    // }
    return "";
};

const showScreenshotModalMode = () => {
    visible.value = true;
};

defineExpose({
    showScreenshotModalMode,
});
</script>
<style lang="scss">
.screenshot-modal {
    font-weight: 550;
    .flex-row {
        display: flex;
        justify-content: space-between;
        gap: 10px;
    }
    .menu-list {
        display: flex;
        flex-direction: column;
        max-height: 150px;
        flex-wrap: wrap;
    }
    .tab-sub-title {
        color: rgb(0, 102, 255);
    }
    .ordered {
        margin: 20px 0;
        .order__items {
            font-weight: 500;
        }
    }
    .el-dialog__body {
        padding: 7px 0 0 0 !important;
    }
    td,
    th {
        font-size: 14px !important;
        padding: 2px 0 !important;
    }
    span,
    div.cell,
    div {
        font-size: 14px !important;
    }
}
</style>
