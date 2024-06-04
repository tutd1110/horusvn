<template>
    <DialogSaveStore ref="modalSaveStoreRef" @saved="onSaved" />
    <el-dialog
        v-model="visible"
        style="font-weight: bold"
        draggable
        :closable="false"
        :style="{ width: '90%' }"
        :title="title"
    >
        <el-row :gutter="20" style="margin-bottom: 0">
            <el-col :span="24" style="margin-bottom: 10px">
                <el-button
                    type="primary"
                    style="margin-right: 10px"
                    @click="showStoreAddModal"
                    >Thêm cửa hàng</el-button
                >
            </el-col>
            <el-col :span="1">
                <div class="modal-header-table">STT</div>
            </el-col>
            <el-col :span="4">
                <div class="modal-header-table">Tên cửa hàng</div>
            </el-col>
            <el-col :span="5">
                <div class="modal-header-table">Ghi chú</div>
            </el-col>
            <el-col :span="6">
                <div class="modal-header-table">Menu</div>
            </el-col>
            <el-col :span="3">
                <div class="modal-header-table">Số điện thoại</div>
            </el-col>
            <el-col :span="3">
                <div class="modal-header-table">Giá tiền</div>
            </el-col>

            <el-col :span="2">
                <div class="modal-header-table">Action</div>
            </el-col>
        </el-row>
        <el-scrollbar height="400px">
            <el-row
                :gutter="20"
                style="
                    margin-bottom: 0;
                    display: flex;
                    align-items: center;
                    border-bottom: 1px solid #e4e7ed;
                    padding: 10px 0;
                "
                v-for="(record, index) in listStoreData"
            >
                <el-col :span="1">
                    <div class="modal-body-table">{{ index + 1 }}</div>
                </el-col>
                <el-col :span="4">
                    <div class="modal-body-table">{{ record.name }}</div>
                </el-col>

                <el-col :span="5">
                    <div class="modal-body-table">{{ record.location }}</div>
                </el-col>
                <el-col :span="6">
                    <div class="modal-body-table">
                        {{ formatMenu(record.menu) }}
                    </div>
                </el-col>
                <el-col :span="3">
                    <div class="modal-body-table">{{ record.phone }}</div>
                </el-col>
                <el-col :span="3">
                    <div class="modal-body-table">
                        {{ formatPrice(record.price) }}
                    </div>
                </el-col>

                <el-col :span="2">
                    <div class="modal-body-table">
                        <EditPen
                            style="
                                width: 16px;
                                cursor: pointer;
                                color: #909399;
                                margin-right: 5px;
                            "
                            @click="showUpdateModal(record.id)"
                        />
                        <el-popconfirm
                            title="Bạn có chắc chắn xóa?"
                            @confirm="destroyStore(record.id)"
                        >
                            <template #reference>
                                <Delete
                                    style="
                                        width: 16px;
                                        cursor: pointer;
                                        color: red;
                                    "
                                />
                            </template>
                        </el-popconfirm>
                    </div>
                </el-col>
            </el-row>
        </el-scrollbar>
    </el-dialog>
</template>

<script lang="ts" setup>
import axios from "axios";
import { ref, onMounted } from "vue";
import { EditPen, Delete } from "@element-plus/icons-vue";
import DialogSaveStore from "./DialogSaveStore.vue";
import { callMessage } from "../../Helper/el-message";
import { formatPrice } from "../../Helper/format";

const errorMessages = ref("");
const modalSaveStoreRef = ref();
const visible = ref(false);
let title = ref("Cửa hàng");
let listStoreData = ref();

const emit = defineEmits(['savedStore']);

const StoreModalMode = () => {
    visible.value = true;
};

const showStoreAddModal = () => {
    modalSaveStoreRef.value.storeCreateModalMode();
};

const showUpdateModal = (id: number) => {
    modalSaveStoreRef.value.UpdateStoreModalMode(id);
};

const formatMenu = (menu: any[]) => {
    return menu
        .map((val, index) => {
            return val.name;
        })
        .join(", ");
};

// handle api
const onSaved = () => {
    _fetch();
    emit('savedStore');
};

const destroyStore = async (id: number) => {
    try {
        const resp = await axios.delete("/api/order/stores/" + id);
        callMessage(resp.data.message, "success");
        _fetch();
    } catch (error: any) {
        errorMessages.value = error.response.data.errors;
        callMessage(errorMessages.value, "error");
    }
};

const _fetch = async () => {
    try {
        const resp = await axios.get("/api/order/stores");
        listStoreData.value = resp.data.order_store.data;
    } catch (error: any) {
        errorMessages.value = error.response.data.errors;
        callMessage(errorMessages.value, "error");
    }
};

onMounted(() => {
    _fetch();
});

defineExpose({
    StoreModalMode,
});
</script>
