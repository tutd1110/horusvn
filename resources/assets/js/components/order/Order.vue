<template>
    <DialogStore @saved-store="savedStoreListen" ref="modalConfigRef" />
    <DialogSetting ref="modalSettingRef" @savedConfig="savedConfigAction" />
    <PopupAlertPayment ref="modalAlertPaymentRef" @redirect-statistial-tab="activeTabCustom('tab3')" />
    <div>
        <div class="order-header">
            <!-- tab define -->
            <div class="tabs-headers">
                <a @click="activeTabCustom('tab1')" :class="{active : tab == 'tab1'}" class="tab-item" href="#tab1">Đặt đồ</a>
                <a @click="activeTabCustom('tab2')" :class="{active : tab == 'tab2'}" class="tab-item" href="#tab2">Danh sách</a>
                <a @click="activeTabCustom('tab3')" :class="{active : tab == 'tab3'}" class="tab-item" href="#tab3">Thống kê</a>
                <a @click="activeTabCustom('tab4')" :class="{active : tab == 'tab4'}" class="tab-item" href="#tab4">QR Code</a>
            </div>
            <div v-if="is_administrator" class="content-header">
                <el-button type="primary" @click="showStoreModal"
                    >Cửa hàng</el-button
                >
                <el-button type="success" @click="showSettingModal"><el-icon><Setting :currentDate="currentDate" /></el-icon>
                    <span style="margin-left: 5px">
                        Cấu hình lên món</span>
                    </el-button>
            </div>
        </div>

        <div class="tab-content">
            <div class="tab-content__item" v-show="tab == 'tab1'">
                <OrderAction @saved-order="savedOrderListen" :keyActionComponent="keyActionComponent" :currentDate="currentDate" />
            </div>
            <div class="tab-content__item" v-show="tab == 'tab2'">
                <OrderHistory :keyHistoryComponent="keyHistoryComponent" :current-date="currentDate" :isAdministrator="is_administrator" @savedOrder="savedOrderListen" />
            </div>
            <div class="tab-content__item" v-show="tab == 'tab3'">
                <OrderStatistial :keyHistoryComponent="keyHistoryComponent" :isAdministrator = "is_administrator" />
            </div>
            <div class="tab-content__item" v-show="tab == 'tab4'">
                <OrderSettingEmpty/>
            </div>
        </div>
    </div>
</template>
<script lang="ts" setup>
import axios from "axios";
import { onMounted, ref } from "vue";
import { Setting } from "@element-plus/icons-vue";
import { callMessage } from "../Helper/el-message.js";
import OrderAction from "./OrderAction.vue";
import OrderHistory from "./OrderHistory.vue";
import OrderStatistial from "./statistial/OrderStatistial.vue";
import DialogStore from "./store/DialogStore.vue";
import DialogSetting from "./setting/DialogSetting.vue";
import OrderSettingEmpty from './OrderSettingEmpty.vue';
import { ElLoading } from 'element-plus';
import PopupAlertPayment from './PopupAlertPayment.vue';

const errorMessages = ref("");
const is_administrator = ref(false);
const modalConfigRef = ref();
const modalSettingRef = ref();
const modalAlertPaymentRef = ref();
const currentSetting = ref();
const currentDate = ref();
const keyActionComponent = ref(0);
const keyHistoryComponent = ref(0);
// tab custom
const hashValue = window.location.hash.substr(1) ? window.location.hash.substr(1) : 'tab1'; 
const tab = ref(hashValue);

onMounted(() => {
    // handle order
    fetchSetting();
    getCurrentDate();
    // showAlertPaymentModal();
});

const activeTabCustom = (param : string)=>{ 
    tab.value = param;
}

// order handle
const fetchSetting = async () => {
    try {
        const resp = await axios.get("/api/order/setting/current");
        if (resp.status == 200) {
            is_administrator.value = resp.data.is_administrator;
            currentSetting.value = resp.data.setting;
        }
    } catch (error : any) {
        errorMessages.value = error.response.data.errors;
        callMessage(errorMessages.value, "error");
    }
};
const showAlertPaymentModal = ()=>{
    modalAlertPaymentRef.value.showAlertModalMode();
}
const showStoreModal = () => {
    modalConfigRef.value.StoreModalMode();
};
const showSettingModal = () => {
    modalSettingRef.value.SettingModalMode();
};
const savedConfigAction = () => {
    keyActionComponent.value = keyActionComponent.value += 1;
    // keyHistoryComponent.value = keyHistoryComponent.value += 1;
    fetchSetting();
};

const savedOrderListen = ()=>{
    keyHistoryComponent.value = keyHistoryComponent.value += 1;
    activeTabCustom('tab2');
    // openLoadingScreen();
}

const savedStoreListen = ()=>{
    savedConfigAction();
}

const openLoadingScreen = () => {
  const loading = ElLoading.service({
    lock: true,
    text: 'Loading',
    background: 'rgba(0, 0, 0, 0.7)',
  })
  setTimeout(() => {
    activeTabCustom('tab2');
    loading.close()
  }, 1000)
}

const getCurrentDate = ()=>{
    const date = new Date();
    let day = date.getDate();
    let month = date.getMonth() + 1;
    let year = date.getFullYear();

    currentDate.value = `${day}-${month}-${year}`;
}
</script>
<style lang="scss">
.user-info {
    display: flex;
    align-items: center; /* Vertically align items */
}

.user-details {
    display: flex;
    flex-direction: column;
    margin-left: 10px; /* Adjust margin as needed */
}

.fullname,
.email {
    height: 100%; /* Set the height to match the avatar */
}

.demo-tabs > .el-tabs__content {
    padding: 32px;
    color: #6b778c;
    font-size: 32px;
    font-weight: 600;
}

.tabs-order {
    position: relative;
    .btn-setting {
        position: absolute;
    }
}
.el-tabs {
    &.tab-order {
        height: auto !important;
    }
}
.content-header {
    display: flex;
    justify-content: end;
}
.qrcode-title{
    text-align: center;
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 30px;
}
.order-header{
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}
.tabs-headers{
    display: flex;
    flex-direction: row;

    .tab-item{
        padding: 5px 10px;
        color: var(--el-text-color-primary);

        &.active{
            color: var(--el-color-primary);
            border-bottom: 2px solid var(--el-color-primary);
        }
        &:hover{
            color: var(--el-color-primary);
        }
    }
}
</style>
