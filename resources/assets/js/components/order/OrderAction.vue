<template>
    <div class="order-action-wrapper">
        <el-form
            v-if="
                currentSetting &&
                currentSetting.is_active &&
                currentSetting.store &&
                currentSetting.store.menu
            "
            ref="ruleFormRef"
            :model="ruleForm"
            :rules="rules"
            label-width="120px"
            class="demo-ruleForm"
            :size="formSize"
            status-icon
        >
            <h1 class="tab-title">
                Form đặt đồ ăn ngày {{ props.currentDate }}
            </h1>
            <div class="sub-desc">
                Thời gian đặt:
                <span>{{formatTime(currentSetting.start_time)}}</span>
                <span v-if="currentSetting.end_time"> - {{formatTime(currentSetting.end_time)}}</span>
            </div>
            <h4 class="tab-sub-title">Quán: <span style="font-size: 30px;">{{ currentSetting.store.name }}</span><br>
            <span style="font-size: 23px;color: red;">({{ currentSetting.store.price }} VNĐ<span style="color: #111;">/suất</span>)</span></h4>
            <el-form-item
                style="margin-bottom: 20px; font-weight: 600;"
                :label="'Chọn ' + currentSetting.store.max_item + ' món ' + (currentSetting.store.location ? currentSetting.store.location : '')"
                prop="items"
                :label-width="230"
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
                style="margin-bottom: 20px;font-weight: 600;"
                label="Ghi chú"
                prop="note"
                :label-width="230"
            >
                <el-input placeholder="Nhiều cơm, ít cơm, 70% đá, 70% đường..." v-model="ruleForm.note" type="textarea" />
            </el-form-item>
            <el-form-item  :label-width="230">
                <el-button
                    v-if="currentSetting.is_active"
                    type="primary"
                    @click="submitForm(ruleFormRef)"
                >
                    Submit
                </el-button>
                <el-button
                    v-else
                    disabled
                    title="Chưa cho phép đặt món"
                    type="primary"
                    @click="submitForm(ruleFormRef)"
                >
                    Submit
                </el-button>
                <el-button @click="resetForm(ruleFormRef)">Reset</el-button>
            </el-form-item>
        </el-form>
        <div v-else>
            <order-setting-empty></order-setting-empty>
        </div>
    </div>
</template>

<script lang="ts" setup>
import { reactive, ref, onMounted, watch } from "vue";
import { dayjs, type FormInstance, type FormRules } from "element-plus";
import OrderSettingEmpty from "./OrderSettingEmpty.vue";
import axios from "axios";
import { callMessage } from "../Helper/el-message";

//   handle submit
const props = defineProps<{
    currentDate?: string,
    keyActionComponent: number
}>();

const emit = defineEmits(["savedOrder"]);

watch(() => props.keyActionComponent, (first, second) => {
    fetchSetting();      
});

const currentSetting = ref();

interface RuleForm {
    store_id: number | undefined,
    items: string[];
    note: string;
    total_amount: number
}

const formSize = ref("default");
const ruleFormRef = ref<FormInstance>();
const ruleForm = reactive<RuleForm>({
    store_id: undefined,
    items: [],
    note: "",
    total_amount:0
});
const errorMessages = ref();
const rules = ref();

const submitForm = async (formEl: FormInstance | undefined) => {
    if (!formEl) return;
    await formEl.validate((valid, fields) => {
        if (valid) {
            let formData = {...ruleForm};
            formData.items = convertStringItems(formData.items);
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
            
            if(resp.data.setting && resp.data.setting.store){
                ruleForm.total_amount =  resp.data.setting.store.price;
                ruleForm.store_id =  resp.data.setting.store.id;
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
            };

        }
    } catch (error: any) {
        errorMessages.value = error.response.data.errors;
        callMessage(errorMessages.value, "error");
    }
};

const convertStringItems = (items: any) => {
    return items.map((val : string)=>{
        return val; 
    }).join(', ')
};

const formatTime = (datetime: string | undefined) => {
    return dayjs(datetime).format("HH:mm:ss");
};

const callSubmitApi = async (data : any)=>{
    try{
        const resp = await axios.post('/api/order',data);
        if(resp.data.success){
            callMessage(resp.data.message,'success');
            errorMessages.value = {};

            // reload listing
            emit('savedOrder')
        }else{
            callMessage(resp.data.message,'error');
            errorMessages.value = {};

            setTimeout(()=>{
               // reload page if disabled_order = false
                if(resp.data.disabled_order){
                    window.location.reload();
                }
            },2000);
            
        }
    }catch(error:any){
        errorMessages.value = error.response.data.errors;
        callMessage(errorMessages.value, "error");
    }
}

// handle api
onMounted(() => {
    fetchSetting();
});
</script>
<style lang="scss">
.order-action-wrapper{
    .tab-title {
        font-size: 1.5rem;
        font-weight: 500;
        text-align: center;
    }
    .tab-sub-title {
        text-align: center;
        margin-bottom: 30px;
        span{
            color: var(--el-color-primary);
        }
    }
    .sub-desc{
        text-align: center;
        margin-bottom: 30px;
    
        span{
            font-size: 18px;
            font-weight: 600;
        }
    }
    .menu-list {
        max-height: 250px;
        display: flex;
        flex-direction: column;
        flex-wrap: wrap;
    }
    .el-form-item__label{
        display: inline-flex;
        justify-content: flex-start;
    }
}
</style>
