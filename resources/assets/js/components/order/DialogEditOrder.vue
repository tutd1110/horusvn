<template>
    <el-dialog
        v-model="visible"
        style="font-weight: bold"
        draggable
        :closable="false"
        :style="{ width: '50%' }"
        title="Chỉnh sửa yêu cầu đặt món"
    >
        <el-form
            v-if="
                (currentSetting &&
                currentSetting.is_active &&
                currentSetting.store &&
                currentSetting.store.menu) || props.isAdministrator
            "
            ref="ruleFormRef"
            :model="ruleForm"
            :rules="rules"
            label-width="120px"
            class="demo-ruleForm"
            :size="formSize"
            status-icon
        >
            <div style="display: flex; justify-content: space-between">
                <div v-if="user.fullname">Họ Tên: {{user.fullname}}</div>
                <div v-if="user.orders[0]">Thời gian đặt: {{formatDateTime(user.orders[0].created_at)}}</div>
            </div>
            <!-- <h4 class="tab-sub-title">{{ currentSetting.store.name }}</h4> -->
            <!-- item ordered -->
            <div v-if="user.orders[0] && user.orders[0].items" class="ordered">
                <div>
                    <span>Món đã đặt: </span>
                    <span class="order__items">{{user.orders[0].items}}</span>
                </div>
                <div v-if="user.orders[0].note">
                    <span>Ghi chú: </span>
                    <span class="order__items">{{user.orders[0].note}}</span>
                </div>
            </div>
            <el-form-item
                style="margin-bottom: 20px; font-weight: 600"
                :label="'Chọn ' + currentSetting.store.max_item + ' món'"
                prop="items"
            >
                <el-checkbox-group class="menu-list" v-model="ruleForm.items">
                    <div
                        style="margin-right: 30px"
                        v-for="(item, index) in currentSetting.store.menu"
                        :key="index"
                    >
                        <el-checkbox :label="item.name" name="product" />
                    </div>
                </el-checkbox-group>
            </el-form-item>

            <el-form-item
                style="margin-bottom: 20px; font-weight: 600"
                label="Ghi chú"
                prop="note"
            >
                <el-input
                    placeholder="Nhiều cơm, ít cơm, 70% đá, 70% đường..."
                    v-model="ruleForm.note"
                    type="textarea"
                />
            </el-form-item>
            <el-row style="display: flex; justify-content: space-between;">
                <el-form-item>
                    <el-button
                        type="primary"
                        @click="submitForm(ruleFormRef)"
                    >
                        Submit
                    </el-button>
                    <el-button @click="resetForm(ruleFormRef)">Reset</el-button>
                </el-form-item>
                <!-- <el-form-item>
                    <el-button
                        v-if="currentSetting.is_active"
                        type="danger"
                    >
                        Hủy đặt món
                    </el-button>
                </el-form-item> -->
            </el-row>
        </el-form>
        <div v-else>
            Hôm nay không có thực đơn.
            <!-- <order-setting-empty></order-setting-empty> -->
        </div>
    </el-dialog>
</template>

<script lang="ts" setup>
import { reactive, ref, onMounted, watch } from "vue";
import { dayjs, type FormInstance, type FormRules } from "element-plus";
import axios from "axios";
import { callMessage } from "../Helper/el-message";

const props = defineProps<{
    dateFilter: string | null,
    isAdministrator: boolean
}>();

let dateOrder = ref(props.dateFilter);
watch(() => props.dateFilter, (newVal, oldVal) => {
    dateOrder.value = newVal;
});

const emit = defineEmits(['updatedOrder']);
const visible = ref(false);

let currentSetting = ref();
let user = ref();

interface RuleForm {
    user_id: number | undefined;
    store_id: number | undefined;
    items: string[];
    note: string;
    total_amount: number;
    created_at?:string | null
}

const formSize = ref("default");
const ruleFormRef = ref<FormInstance>();
const ruleForm = reactive<RuleForm>({
    user_id: undefined,
    store_id: undefined,
    items: [],
    note: "",
    total_amount: 0,
    created_at: dateOrder.value
});
const errorMessages = ref();
const rules = ref();

const submitForm = async (formEl: FormInstance | undefined) => {
    if (!formEl) return;
    await formEl.validate((valid, fields) => {
        if (valid) {
            ruleForm.created_at = dateOrder.value;
            let formData = {...ruleForm};
            formData.items = convertStringItems(formData.items);
            formData.user_id = user.value.id;
            callSubmitApi(formData);
        } else {
            console.log("error submit!", fields);
        }
    });
};

const resetForm = (formEl: FormInstance | undefined) => {
    if (!formEl) return;
    formEl.resetFields();
};

const fetchSetting = async () => {
    try {
        const resp = await axios.get("/api/order/setting/current");
        if (resp.status == 200) {
            currentSetting.value = resp.data.setting;

            if (resp.data.setting && resp.data.setting.store) {
                ruleForm.total_amount = resp.data.setting.store.price;
                ruleForm.store_id = resp.data.setting.store.id;
                rules.value = reactive<FormRules>({
                    items: [
                        {
                            type: "array",
                            required: true,
                            message: "Vui lòng chọn món",
                            trigger: "change",
                        },
                        {
                            type: "array",
                            max: currentSetting.value.store.max_item,
                            message:
                                "Chọn tối đa " +
                                currentSetting.value.store.max_item +
                                " món",
                            trigger: "change",
                        },
                    ],
                    note: [
                        {
                            max: 255,
                            message: "Ghi chú không quá 255 kí tự",
                            trigger: "blur",
                        },
                    ],
                });
            }
        }
    } catch (error: any) {
        errorMessages.value = error.response.data.errors;
        callMessage(errorMessages.value, "error");
    }
};

const convertStringItems = (items: any) => {
    return items
        .map((val: string) => {
            return val;
        })
        .join(", ");
};

const formatTime = (datetime: string | undefined) => {
    return dayjs(datetime).format("HH:mm:ss");
};

const formatDateTime = (datetime : string | undefined) => {
    return dayjs(datetime).format('YYYY-MM-DD HH:mm:ss');
}

const callSubmitApi = async (data: any) => {
    const orderId = user.value.orders[0] ? user.value.orders[0].id : null;
    try {
        let resp : any = {};
        if(orderId){
            resp = await axios.put("/api/order/" + orderId, data);
        }else{
            resp = await axios.post("/api/order", data);
        }

        if (resp.data.success) {
            callMessage(resp.data.message, "success");
            errorMessages.value = {};
            emit('updatedOrder');
            ruleForm.items = [];
            toggleDialog();
        } else {
            callMessage(resp.data.message, "error");
            errorMessages.value = {};
        }
    } catch (error: any) {
        errorMessages.value = error.response.data.errors;
        callMessage(errorMessages.value, "error");
    }
};

const ShowEditModalMode = async (
    userParam : any
) => {
    fetchSetting();
    visible.value = true;
    user.value = userParam;

    ruleForm.items = user.value.orders[0].items.split(', ');
    
    ruleForm.note = user.value.orders[0] ? user.value.orders[0].note : '';
};

const toggleDialog = ()=>{
    visible.value = !visible.value;
}

onMounted(() => {
    fetchSetting();
});

defineExpose({
    ShowEditModalMode,
});
</script>
<style lang="scss" scoped>
.flex-row {
    display: flex;
    justify-content: space-between;
    gap: 10px;
}
.menu-list {
    display: flex;
    flex-direction: column;
    max-height: 400px;
    flex-wrap: wrap;
}
.tab-sub-title{
    color: rgb(0, 102, 255);
}
.ordered{
    margin: 20px 0;
    .order__items{
        font-weight: 500;
    }
}
</style>
