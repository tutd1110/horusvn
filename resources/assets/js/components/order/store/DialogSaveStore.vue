<template>
    <el-dialog
        v-model="visible"
        style="font-weight: bold"
        draggable
        :closable="false"
        :style="{ width: '50%' }"
        :title="title"
    >
        <el-form
            :label-position="'top'"
            label-width="100px"
            :model="formData"
            style="max-width: 100%"
        >
            <div class="flex-row">
                <el-form-item style="width: 100%" label="Loại cửa hàng">
                    <el-select
                        v-model="formData.type"
                        clearable
                        placeholder="Loại cửa hàng"
                        style="width: 100%"
                    >
                        <el-option
                            v-for="item in type_options"
                            :key="item.id"
                            :label="item.name"
                            :value="item.code"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item style="width: 100%" label="Tên cửa hàng">
                    <el-input v-model="formData.name" />
                </el-form-item>
            </div>
            <div class="flex-row">
                <el-form-item style="width: 100%" label="Ghi chú">
                    <!-- field note of store using field location in database -->
                    <el-input v-model="formData.location" />
                </el-form-item>
                <el-form-item style="width: 100%" label="Điện thoại">
                    <el-input v-model="formData.phone" />
                </el-form-item>
            </div>
            <div class="flex-row">
                <el-form-item
                    style="width: 100%"
                    label="Giá tiền(Quán cơm)"
                >
                    <el-input v-model="formData.price" />
                </el-form-item>
                <el-form-item
                    style="width: 100%"
                    label="Số món tối đa được chọn"
                >
                    <el-input v-model="formData.max_item" />
                </el-form-item>
            </div>
            <!-- menu -->
            <el-form-item label="Menu">
                <el-input
                    rows="11"
                    placeholder="Format:
                Món 1
                Món 2
                Món 3"
                    type="textarea"
                    v-model="formData.menu"
                />
            </el-form-item>
            <el-button
                type="primary"
                style="margin-right: 10px"
                @click="handleSubmitForm"
                >Submit</el-button
            >
            <el-button
                type="secondary"
                style="margin-right: 10px"
                @click="handleCancelForm"
                >Cancel</el-button
            >
        </el-form>
    </el-dialog>
</template>

<script lang="ts" setup>
import axios from "axios";
import { ref, reactive } from "vue";
import { callMessage } from "../../Helper/el-message";

const emit = defineEmits(["saved"]);

interface FormState {
    name?: string;
    class_color?: number;
}
// store type options
const type_options = [
    {
        id: 1,
        name: "Quán cơm trưa",
        code: "RICE",
    },
    {
        id: 2,
        name: "Quán ăn/uống khác",
        code: "DYNAMIC",
    },
];
const formData = reactive({
    id: null,
    type: "RICE",
    name: "",
    menu: "",
    location: "",
    phone: "",
    price: 0,
    max_item: "",
});
const errorMessages = ref("");

const visible = ref(false);
let title = ref("Thêm cửa hàng");
let isFormAdd = true;

const handleSubmitForm = async () => {
    try {
        if (isFormAdd) {
            const formDataClone = {...formData};
            formDataClone.menu = formDataClone.menu ? formatMenuItem(formDataClone.menu) : '';
            const resp = await axios.post("/api/order/stores", formDataClone);
            callMessage(resp.data.message, "success");
            emit("saved");
            visible.value = false;
        } else {
            const formDataClone = {...formData};
            formDataClone.menu = formatMenuItem(formDataClone.menu);
            const resp = await axios.put(
                "/api/order/stores/" + formDataClone.id,
                formDataClone
            );
            callMessage(resp.data.message, "success");
            emit("saved");
            visible.value = false;
        }
    } catch (er: any) {
        console.log(er);
        if (er.response.status == 422) {
            errorMessages.value = er.response.data.errors;
            callMessage(er.response.data.message, "error");
        }
    }
};

const formatMenuItem = (menus: any) => {
    menus = menus.trim();
    let data = menus.split("\n").map((val: string) => {
        return { name: val };
    });
    return data;
};

const handleCancelForm = () => {
    visible.value = false;
};

const clearFormData = () => {
    formData.id = null;
    formData.type = "RICE";
    formData.name = "";
    formData.menu = "";
    formData.location = "";
    formData.phone = "";
    formData.price = 0;
    formData.max_item = "";
}

const storeCreateModalMode = () => {
    title.value = "Thêm cửa hàng";
    visible.value = true;
    clearFormData();
    isFormAdd = true;
};

const UpdateStoreModalMode = async (id: number) => {
    title.value = "Chỉnh sửa cửa hàng";
    visible.value = true;
    isFormAdd = false;

    const resp = await axios.get("/api/order/stores/" + id);
    if (resp.status == 200) {
        let dataResp = resp.data.order_store;

        formData.id = dataResp.id;
        formData.type = dataResp.type;
        formData.name = dataResp.name;
        formData.menu = dataResp.menu
            .map((val: any) => {
                return val.name;
            })
            .join("\n");
        formData.location = dataResp.location;
        formData.phone = dataResp.phone;
        formData.price = dataResp.price;
        formData.max_item = dataResp.max_item;
    }
};

defineExpose({
    storeCreateModalMode,
    UpdateStoreModalMode,
});
</script>

<style scoped lang="scss">
.flex-row {
    display: flex;
    justify-content: space-between;
    gap: 10px;
}
</style>
