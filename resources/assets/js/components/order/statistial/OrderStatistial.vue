<template>
  <DialogEditOrder ref="dialogEditOrderRef" :isAdministrator="props.isAdministrator" @updated-order="listenUpdatedOrder" />
  <div class="statistial-wrapper">
    <h1 class="tab-title">
      Thống kê tiền ăn
    </h1>
    <div class="statistial-top">
      <el-row :gutter="10" style="margin-bottom: 0px">
        <el-col :span="4">
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
        <el-col :span="3">
            <el-select
                v-model="formState.department_id"
                value-key="id"
                placeholder="Department"
                filterable
                clearable
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
        <el-col :span="3" class="custom-filter">
          <el-select
              v-model="formState.user_status"
              value-key="value"
              placeholder="Trạng thái"
              clearable
              filterable
              style="width: 100%"
          >
              <el-option
                  v-for="item in userStatus"
                  :key="item.value"
                  :label="item.label"
                  :value="item.value"
              />
          </el-select>
        </el-col>
        <el-col :span="3">
          <el-select
              v-model="formState.status"
              value-key="id"
              placeholder="Thanh toán"
              filterable
              style="width: 100%"
              clearable
          >
              <el-option
                  v-for="item in statusOptions"
                  :key="item.value"
                  :label="item.label"
                  :value="item.value"
              />
          </el-select>
        </el-col>
        <el-col :span="2" class="custom-filter">
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
        <el-col :span="spanCol != undefined ? spanCol : 1.5">
            <el-button type="primary" v-on:click="search()">Search</el-button>
        </el-col>
        <el-col :span="6" class="report-date-week-select">
            <a class="icon-arrow arrow-left" @click="subtractWeekTimesheet"><el-icon><ArrowLeft /></el-icon></a>
            <el-date-picker
                v-model="weekTimesheetPicker"
                type="week"
                format="[Week] ww"
                placeholder="Week"
                class="time-week"
                value-format="YYYY-MM-DD"
                @change="search"
                :clearable="false"
            />
            <a class="icon-arrow arrow-right" @click="addWeekTimesheet"><el-icon><ArrowRight /></el-icon></a>
        </el-col>
        
      </el-row>
        <ButtonConfirmPayment :is_administrator="props.isAdministrator" :countOrderWeek="countOrderWeek" @event-click-report="search" @event-reload="reloadComponent" />
    </div>
    <!-- Timesheets form from here -->
    <div class="workday-report-scrollbar">
        <el-row :gutter="4">
            <el-col :span="2">
                <el-card class="timesheet-box" shadow="always" :body-style="{padding: '0px', height: '40px'}">
                    <div class="card-content" style="font-weight: 600;">
                        Họ tên
                    </div>
                </el-card>
            </el-col>
            <template v-for="(column, indx) in columns">
                <el-col :span="getColSpan" :style="getCardStyle">
                    <el-card class="timesheet-box" shadow="always" :body-style="{ padding: '0px'}">
                        <div class="timesheets-title" v-html="formatTitle(column.title)"></div>
                    </el-card>
                </el-col>
            </template>
            <el-col :span="1">
              <el-card class="timesheet-box" shadow="always" :body-style="{padding: '0px', height: '40px'}">
                  <div class="card-content">
                      Tổng tuần
                  </div>
              </el-card>
            </el-col>
            <el-col :span="2">
              <el-card class="timesheet-box" shadow="always" :body-style="{padding: '0px', height: '40px'}">
                  <div class="card-content">
                      Tồn tuần trước
                  </div>
              </el-card>
            </el-col>
            <el-col :span="1">
              <el-card class="timesheet-box" shadow="always" :body-style="{padding: '0px', height: '40px'}">
                  <div class="card-content">
                      Số dư
                  </div>
              </el-card>
            </el-col>
            <el-col :span="props.isAdministrator ? 2 : 4">
              <el-card class="timesheet-box" shadow="always" :body-style="{padding: '0px', height: '40px'}">
                  <div class="card-content">
                      Tổng cuối
                  </div>
              </el-card>
            </el-col>
            <el-col v-if="props.isAdministrator" :span="2">
              <el-card class="timesheet-box" shadow="always" :body-style="{padding: '0px', height: '40px'}">
                  <div class="card-content">
                      Status/Action
                  </div>
              </el-card>
            </el-col>
          </el-row>
          <el-scrollbar :height="heightScroll">
            <template v-for="(item, index1) in tableData">
                <el-row :gutter="4" style="margin-bottom: 12px;">
                    <el-col :span="2">
                        <el-card shadow="hover" :body-style="elCardStyleBody">
                            <div class="card-content fullname" style="display: flex;">
                                <span>{{ item.fullname }}</span> <span v-if="item.alias_name" style="font-size: 12px">({{ item.alias_name }})</span>
                            </div>
                        </el-card>
                    </el-col>
                    <template v-for="(column, index2) in columns">
                        <el-col :span="getColSpan" :style="getCardStyle">
                            <template v-if="item.orders && item.orders[column.dataKey]">
                                <el-card shadow="hover" :body-style="elCardStyleBody" @click="openEditOrder(item.orders[column.dataKey].id)">
                                    <div class="card-content">
                                        <div class="card-content__item" v-if="item.orders[column.dataKey].status == 'COMPLETED'">
                                            <span style="text-decoration: line-through;">{{ item.orders[column.dataKey].total_amount }}</span>
                                            <span class="item-note" v-if="item.orders[column.dataKey].admin_note">{{item.orders[column.dataKey].admin_note}}</span>
                                        </div>
                                        <div class="card-content__item" v-else>
                                            <span>{{ item.orders[column.dataKey].total_amount }}</span>
                                            <span class="item-note" v-if="item.orders[column.dataKey].admin_note">{{item.orders[column.dataKey].admin_note}}</span>
                                        </div>
                                    </div>
                                </el-card>
                            </template>
                            <template v-else-if="checkDateIsSunday(column.title) && (typeof item.orders[column.dataKey] === 'undefined')">
                                <el-card shadow="hover" :body-style="elCardStyleBody">
                                    <div class="card-content">
                                        <span style="font-weight: bold" v-html="getHolidaySundayLabel(columns.length > 7)"></span>
                                    </div>
                                </el-card>
                            </template>
                            <template v-else>
                                <el-card
                                    shadow="hover"
                                    :body-style="elCardStyleBody"
                                    @click="openEditOrder"
                                >
                                    <div class="card-content">
                                        --
                                    </div>
                                </el-card>
                            </template>
                        </el-col>
                    </template>
                    <el-col :span="1">
                      <el-card class="timesheet-box" shadow="always" :body-style="{padding: '0px', height: '40px'}">
                          <div class="card-content" v-if="item.calculate_amounts">
                              {{ item.calculate_amounts.total_week_price }}
                          </div>
                      </el-card>
                    </el-col>
                    <el-col :span="2">
                      <el-card class="timesheet-box" shadow="always" :body-style="{padding: '0px', height: '40px'}">
                        <div class="card-content" v-if="item.calculate_amounts" :class="{debt: item.calculate_amounts.remaining_price > 0}">
                          {{ item.calculate_amounts.remaining_price }}
                          <span
                          title="Thu thêm tiền phạt 10% tổng nợ khi chưa đóng của tuần trước. Lưu ý: khi tiền nợ < 50k sẽ mặc định phạt lên tối thiểu 50k, nếu > 50k thì bị truy thu thêm 10% tổng nợ."
                           v-if="item.is_collected_debt && item.calculate_amounts.tax_custom" style="font-size: 12px;">+ phí nợ(10%) = {{item.calculate_amounts.tax_custom}}</span>
                        </div>
                      </el-card>
                    </el-col>
                    <el-col :span="1">
                      <el-card class="timesheet-box" shadow="always" :body-style="{padding: '0px', height: '40px'}">
                          <div class="card-content" v-if="item.calculate_amounts">
                              <el-input class="none-border" 
                              @change="updateOrderPrepaidAmount(item.id, item.prepaid_amount)"
                              v-model="item.prepaid_amount"
                              :disabled="!props.isAdministrator"
                              title="Số dư trả trước của nhân viên sẽ được trừ vào tổng cuối khi ấn xác nhận thu tiền."
                              ></el-input>
                          </div>
                      </el-card>
                    </el-col>
                    <el-col :span="props.isAdministrator ? 2 : 4">
                      <el-card class="timesheet-box" shadow="always" :body-style="{padding: '0px', height: '40px'}">
                        <div class="card-content" v-if="item.calculate_amounts">
                            {{ formatPrice(item.calculate_amounts.final_price) }}
                        </div>
                      </el-card>
                    </el-col>
                    <el-col v-if="props.isAdministrator" :span="getColSpanAction(item,'status')">
                      <el-card class="timesheet-box" shadow="always" :body-style="{padding: '0px', height: '40px'}">
                          <div class="card-content">
                              <div v-if="item.calculate_amounts.final_price">
                                <el-button type="primary" title="Xác nhận đã thanh toán" 
                                @click="confirmPaidReport(item.id, item.calculate_amounts.final_price)">Xác nhận</el-button>
                              </div>
                              <div v-if="item.calculate_amounts.final_price == 0 && item.calculate_amounts.total_week_price">
                                <el-tag class="ml-2" type="success">Đã thanh toán</el-tag>
                              </div>
                          </div>
                      </el-card>
                    </el-col>
                    <el-col v-if="props.isAdministrator" :span="getColSpanAction(item,'action')">
                      <el-card class="timesheet-box" shadow="always" :body-style="{padding: '0px', height: '40px'}">
                          <div class="card-content">
                              <div v-if="props.isAdministrator && item.calculate_amounts.remaining_price && !item.is_collected_debt">
                                <el-button 
                                title="Thu thêm tiền phạt 10% tổng nợ khi chưa đóng của tuần trước"
                                type="danger" @click="confirmCollectDebt(item.id)">Thu thêm</el-button>
                              </div>
                              <div v-if="props.isAdministrator && item.calculate_amounts.remaining_price && item.is_collected_debt">
                                <el-button title="Đã yêu cầu thu thêm tiền phạt" type="info" disabled @click="confirmCollectDebt(item.id)">Thu thêm</el-button>
                              </div>
                          </div>
                      </el-card>
                    </el-col>
                </el-row>
            </template>
        </el-scrollbar>
    </div>
  </div>
</template>
<script lang="ts" setup>
import axios from 'axios';
import { onMounted, computed, ref, watch } from 'vue';
import { ArrowLeft, ArrowRight  } from '@element-plus/icons-vue';
import { callMessage } from '../../Helper/el-message.js';
import dayjs from 'dayjs';
import { openLoading, closeLoading } from '../../Helper/el-loading';
import {formatPrice} from '../../Helper/format';
import ButtonConfirmPayment from './ButtonConfirmPayment.vue';
import { ElMessageBox,ElMessage } from 'element-plus';
import DialogEditOrder from './DialogEditOrder.vue';

const props = defineProps<{
    isAdministrator: boolean,
    keyHistoryComponent: number
}>();

watch(() => props.keyHistoryComponent, (first, second) => {
    search();
});

interface FormState {
  user_id?: number,
  department_id?: number,
  start_date: string,
  end_date: string,
  status?: string,
  user_status?:number,
  store_id?:number,
};
interface User {
  id?: number,
  fullname: string,
  department_id?: number
};
interface Department {
  id: number,
  name: string
};
interface Work {
  total_amount: number,
  orders: number,
  status: string,
  id:number,
  admin_note:string
}
interface CalculateAmounts{
  "total_week_price": number,
  "remaining_price": number,
  "final_price": number,
  "tax_custom": number
}
interface Employee {
  id: number,
  fullname: string,
  alias_name: string,
  is_collected_debt: boolean,
  orders: { [date: string]: Work },
  calculate_amounts: CalculateAmounts,
  payment_status: string,
  prepaid_amount:number
}
interface ElCardStyle {
  padding: string,
  height: string
}

const dialogEditOrderRef = ref();

const statusOptions = [
  {
    value: 'NONE',
    label: 'Chưa thanh toán',
  },
  {
    value: 'COMPLETED',
    label: 'Đã thanh toán',
  },
];

interface Option {
    value?: number,
    label: string
};

const countOrderWeek = ref(0);
const userStatus = ref<Array<Option>>([
    {
        label: 'Working',
        value: 1
    },
    {
        label: 'Quit',
        value: 2
    }
]);
const currentDate = dayjs();
const startDate = currentDate.startOf('week').format('YYYY/MM/DD');
const endDate = currentDate.endOf('week').format('YYYY/MM/DD');
const departments = ref<Array<Department>>([]);
const formState = ref<FormState>({
  start_date: startDate,
  end_date: endDate,
  user_status:1
});
const getColSpanAction = (item:any , type : string)=>{
  if(type == 'status'){
    if(!props.isAdministrator) return 2;
    else if(item.calculate_amounts.final_price == 0 && item.calculate_amounts.total_week_price) return 2;
    else return 1;
  }else{
    if(!props.isAdministrator) return 0;
    if(item.calculate_amounts.final_price == 0 && item.calculate_amounts.total_week_price) return 0;
    else return 1;
  }
  
}
// Computed property for computed formState
const computedFormState = computed<FormState>(() => {
  const newFormState: FormState = {
      ...formState.value,
  };

  if (weekTimesheetPicker.value) {
      newFormState.start_date = dayjs(weekTimesheetPicker.value).startOf('week').format('YYYY/MM/DD');
      newFormState.end_date = dayjs(weekTimesheetPicker.value).endOf('week').format('YYYY/MM/DD');
  }

  return newFormState;
});
const users = ref<Array<User>>([]);
const filteredUsers = computed(() => {
  const selectedDepartmentId = formState.value.department_id;
  if (selectedDepartmentId) {
      return users.value.filter(user => user.department_id === selectedDepartmentId);
  } else {
      return users.value;
  }
});
const weekTimesheetPicker = ref(currentDate.startOf('week').format('YYYY/MM/DD'));
const generateColumns = (startDate: Date, endDate: Date, props?: any) => {
  const diffInDays = Math.floor((endDate.getTime() - startDate.getTime()) / (1000 * 60 * 60 * 24)) + 1;

  const dateFormats: Record<string, Intl.DateTimeFormatOptions> = {
      week: {
          weekday: "long",
          day: "numeric",
          month: "numeric",
      },
      month: {
          weekday: "short",
          day: "numeric", // Change the order to day/month
          month: "numeric",
      },
  };

  const columns = Array.from({ length: diffInDays }, (_, index) => {
      const currentDate = new Date(startDate);
      currentDate.setDate(startDate.getDate() + index);
      const year = currentDate.getFullYear().toString();
      const month = (currentDate.getMonth() + 1).toString().padStart(2, '0');
      const day = currentDate.getDate().toString().padStart(2, '0');
      const formattedKey = `${year}${month}${day}`;
      const formattedDate = currentDate.toLocaleDateString('en-TT', dateFormats['week']);

      return {
          ...props,
          dataKey: formattedKey,
          title: formattedDate,
      };
  });

  return columns;
}
const columns = ref(generateColumns(new Date(startDate), new Date(endDate)));

const getColSpan = computed(() => {
  return columns.value.length > 7 ? 1 : 2;
});
const getCardStyle = computed(() => {
  const width = (100-8.3333333333) / (columns.value.length);
  return { maxWidth: `${width}%` };
});
const formatTitle = (title: string) => {
  const [firstLine, secondLine] = title.split(',');
  const isSunday = ['Sunday', 'Sun'].includes(firstLine); // Check if firstLine is 'Sunday' or 'Sun'

  let dateLabel = `<span class="timesheets-date-label ${isSunday ? 'timesheets-red-text' : ''}">${firstLine}</span>`;
  let dateMD = `<span class="timesheets-date-md ${isSunday ? 'timesheets-red-text' : ''}">${secondLine}</span>`;

  return `${dateLabel} ${dateMD}`;
};
const tableData = ref<Array<Employee>>([]);
const errorMessages = ref('');
const elCardStyleBody = ref<ElCardStyle>({padding: '0px', height: '40px'});
const search = () => {
  openLoading('workday-report-scrollbar'); 
  axios.get('/api/order/statistial/week', {
    params:{
      ...computedFormState.value,
    }
  })
  .then(response => {
      columns.value = generateColumns(new Date(computedFormState.value.start_date), new Date(computedFormState.value.end_date));
      tableData.value = response.data.data;
      countOrderWeek.value = response.data.countOrderWeek ? response.data.countOrderWeek : 0;
      closeLoading(); // Close the loading indicator
  })
  .catch((error) => {
      closeLoading(); // Close the loading indicator
      errorMessages.value = error.response.data.errors;
      //When search target data does not exist
      tableData.value = []; //dataSource empty
      callMessage(errorMessages.value, 'error');
  });
}
// Function to subtract one week from weekTimesheetPicker
const subtractWeekTimesheet = () => {
  const newDate = dayjs(weekTimesheetPicker.value).subtract(1, 'week');
  weekTimesheetPicker.value = newDate.format('YYYY-MM-DD');
  search()
};
// Function to add one week to weekTimesheetPicker
const addWeekTimesheet = () => {
  const newDate = dayjs(weekTimesheetPicker.value).add(1, 'week');
  weekTimesheetPicker.value = newDate.format('YYYY-MM-DD');
  search()
};
const checkDateIsSunday = (title: string) => {
  return title.includes('Sunday') || title.includes('Sun');
}

const getHolidaySundayLabel = (holiday: boolean) => {
  if (holiday) {
      return '<span>Ngày</span><br /><span>Nghỉ</span>';
  }
  return 'Ngày nghỉ';
}

const reloadComponent = () => {
  weekTimesheetPicker.value = startDate;
  formState.value.start_date = startDate;
  formState.value.end_date = endDate;
  formState.value.status = '';
  formState.value.user_status = 1;
  delete formState.value.user_id;
  delete formState.value.department_id;

  search();
} 

const callQuickUpdatePaidStatus = async (userId : number, total_amount: number)=>{
  try{
    const resp = await axios.patch('/api/order/user/quick-update',{
      user_id: userId,
      paid_report_status: 'COMPLETED',
      total_paid_amount: total_amount,
    });
    callMessage(resp.data.success, "success");
    search();
  }catch(error: any){
    callMessage(error.response.data.message, "error");
  } 
}

const callQuickUpdateIsCollectedDebt = async (userId : number,value = true)=>{
  try{
    const resp = await axios.patch('/api/order/user/quick-update',{
      user_id: userId,
      is_collected_debt: value
    });
    callMessage('Cập nhật thu phạt thành công', "success");
    search();
  }catch(error: any){
    callMessage(error.response.data.message, "error");
  } 
}

const confirmPaidReport = async (user_id : number, total_paid_amount:number) => {
  ElMessageBox.confirm(
    'Xác nhận người dùng đã thanh toán hết khoản nợ. Tiếp tục?',
    'Xác nhận',
    {
      confirmButtonText: 'OK',
      cancelButtonText: 'Cancel',
      type: 'success',
    }
  ).then(() => {
      callQuickUpdatePaidStatus(user_id, total_paid_amount)
    })
    .catch(() => {})
}

const confirmCollectDebt = async (user_id : number) => {
  ElMessageBox.confirm(
    'Xác nhận buộc người dùng nộp thêm 10%(tiền nợ). Lưu ý: khi tiền nợ < 50k sẽ mặc định phạt lên tối thiểu 50k, nếu > 50k thì bị truy thu thêm 10% tổng nợ. Tiếp tục?',
    'Xác nhận',
    {
      confirmButtonText: 'Thu phạt',
      cancelButtonText: 'Không thu phạt',
      type: 'warning',
    }
  ).then(() => {
      callQuickUpdateIsCollectedDebt(user_id);
    })
    .catch(() => {
      // callQuickUpdateIsCollectedDebt(user_id, false);
    })
}

const updateOrderPrepaidAmount = async (user_id:number, amount:number)=>{
  try{
    const resp = await axios.patch('/api/order/order-user/quick-update',{
                  user_id:user_id,
                  prepaid_amount: amount ? amount : 0
                });
    callMessage('Cập nhật thành công!','success')
  }catch(error:any){
    callMessage(error.response.data.message,'error');
  }
}

const openEditOrder = (id:number | undefined)=>{
  dialogEditOrderRef.value.showEditModal(id ?? undefined);
}

const listenUpdatedOrder = ()=>{
  search()
}

const heightScroll = ref()
const spanCol = ref()
const storeName = ref()
onMounted(() => {
  axios.get("/api/common/departments").then((response) => {
     departments.value = response.data;
  });
  axios.get("/api/common/get_employees?user_status=1").then((response) => {
     users.value = response.data;
  });
  axios.get("/api/order/stores").then((response) => {
      storeName.value = response.data.order_store.data;
  });

  search()

  var screenWidth = window.screen.width; // Screen width in pixels
  var screenHeight = window.screen.height; // Screen height in pixels

  if (screenWidth === 2560 && screenHeight === 1440) {
      heightScroll.value = '750px'
      spanCol.value = '1'
  } else if (screenWidth === 1920 && screenHeight === 1080) {
      heightScroll.value = '650px'
      spanCol.value = '1'
  } else if (screenWidth === 1080 && screenHeight === 1920) {
      heightScroll.value = '1750px'
      spanCol.value = '2'
  } else if (screenWidth >= 1080 && screenHeight >= 1920) {
      heightScroll.value = '1600px'
      spanCol.value = '2'
  }
});

</script>
<style lang="scss">
.statistial-wrapper{
  .el-card{
    .el-card__body{
      .card-content{
        &.debt{
          background-color: #f7a5a5;
        }
      }
    }
  }
  .card-content{
    display: flex;
    flex-direction: column;
  
    &.fullname{
      font-size: 10px !important;
    }
    .card-content__item{
      display: flex;
      flex-direction: column;
      .item-note{
        font-size: 12px;
        color: var(--el-text-color-secondary);
      }
    }
  }
  .timesheets-date-md {
    font-size: 12px;
  }
  .tasks-time-red-text {
    font-size: 14px;
    font-weight: bold;
    line-height: normal;
    color: red;
  }
  
  .tasks-time-green-text {
    font-size: 14px;
    font-weight: bold;
    line-height: normal;
    color: green;
  }
  .statistial-top{
    display: flex;
    justify-content: space-between;
  }
}
</style>