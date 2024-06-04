<template>
    <el-card shadow="hover" class="warrior-year-wrapper">
        <el-row :gutter="10" style="margin-bottom: 0;">
            <el-col :span="5" class="title" style="font-weight: bold;">
                Warrior năm
            </el-col>
            <div class="flex-grow" />
            <el-col :span="3" class="custom-filter">
                <el-date-picker
                v-model="currentYear"
                type="year"
                placeholder="Year"
                :size="'default'"
                @change="onChangeFilter('desc')"
                />
            </el-col>
            <el-col :span="3" class="custom-filter">
                <el-select
                    v-model="department_id"
                    value-key="id"
                    placeholder="Bộ phận"
                    clearable
                    filterable
                    style="width: 100%"
                    @change="onChangeFilter('desc')"
                >
                    <el-option
                        v-for="item in props.departments"
                        :key="item.id"
                        :label="item.name"
                        :value="item.id"
                    />
                </el-select>
            </el-col>
            <el-col :span="2" class="title" style="font-weight: bold;">
                <el-button type="primary" @click="handleExport" :loading="loadingExport">Export</el-button>
            </el-col>
            <el-dropdown class="el-dropdown-cus">
              <button class="btn-more">
                  <el-icon class="more-filled-icon"><MoreFilled /></el-icon>
              </button>
              <template #dropdown>
                <el-dropdown-menu>
                  <el-dropdown-item @click="onChangeFilter('desc')">Top 10 cao nhất</el-dropdown-item>
                  <el-dropdown-item @click="onChangeFilter('asc')">Top 10 thấp nhất</el-dropdown-item>
                </el-dropdown-menu>
              </template>
            </el-dropdown>
            <el-col :span="24" class="table-data">
                <el-table :data="warriorData" v-loading="loading" class="table-warrior-year">
                    <el-table-column label="Member" width="300">
                      <template #default="scope">
                        <div class="user-info">
                          <el-avatar :src="getFullPathAvatar(scope.row.avatar)" />
                          <div class="user-details">
                              <span class="fullname">{{ scope.row.fullname }}</span>
                              <span class="duration">{{ scope.row.total_work_date}}</span>
                          </div>
                        </div>
                      </template>
                    </el-table-column>
                    <el-table-column width="180">
                        <template #default="scope">
                            <div class="progress-bar">
                                <div 
                                style="height: 100%; background: #4CB759;"
                                :style="{
                                  'width':scope.row.percent_warrior1 +'%',
                                }"
                                :title="'Warrior 1('+scope.row.percent_warrior1+'%)'"></div>
                                <div :title="'Warrior 2('+scope.row.percent_warrior2+'%)'" 
                                style="height: 100%; background: #FFA500;"
                                :style="{
                                  'width':scope.row.percent_warrior2 +'%',
                                }"></div>
                                <div :title="'Warrior 3('+scope.row.percent_warrior3+'%)'" 
                                style="height: 100%; background: #800000;"
                                :style="{
                                  'width':scope.row.percent_warrior3 +'%',
                                }"></div>
                            </div>
                        </template>
                    </el-table-column>
                    <el-table-column class="table-col-warrior1" label="Warrior 1" width="180">
                      <template #default="scope">
                        {{ scope.row.month_warrior1 }}
                      </template>
                    </el-table-column>
                    <el-table-column class="table-col-warrior2" label="Warrior 2" width="180">
                      <template #default="scope">
                        {{ scope.row.month_warrior2 }}
                      </template>
                    </el-table-column>
                    <el-table-column class="table-col-warrior3" label="Warrior 3" width="180">
                      <template #default="scope">
                        {{ scope.row.month_warrior3 }}
                      </template>
                    </el-table-column>
                    <el-table-column class="table-col-warrior4" label="Tổng">
                      <template #default="scope">
                        {{ scope.row.total_warrior}}
                      </template>
                    </el-table-column>
                  </el-table>
            </el-col>
        </el-row>
    </el-card>
</template>
<script lang="ts" setup>
import {ref} from 'vue';
import { MoreFilled } from '@element-plus/icons-vue'
import dayjs from 'dayjs';
import axios from 'axios';
import {handleStatistialWarrior} from '../Helper/handle-statistial-warrior';
import { downloadFile } from '../Helper/export';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
    departments:Department
}>();

interface Department {
    id: number,
    name: string
};

const department_id = ref(props.departments);
const loading = ref(false);
let currentDate = dayjs()
const startOfYearOriginal = currentDate.startOf('year').format('YYYY/MM/DD')
const endOfYearOriginal = currentDate.endOf('year').format('YYYY/MM/DD')
let startOfYear = currentDate.startOf('year').format('YYYY/MM/DD')
let endOfYear = currentDate.endOf('year').format('YYYY/MM/DD')

const currentYear = ref(currentDate.startOf('year').format('YYYY/MM/DD'))
const warriorDataBase = ref();
const warriorData = ref();
const loadingExport = ref(false);
const errorMessages = ref("");

const onChangeFilter = async (sort:string)=>{
  loading.value = true;
  startOfYear = dayjs(currentYear.value).startOf('year').format('YYYY/MM/DD')
  endOfYear = dayjs(currentYear.value).endOf('year').format('YYYY/MM/DD')

  try{
    // call api
    const resp = await axios.get('/api/statistial/top/warrior',{
      params:{
        start_date:startOfYear,
        end_date:endOfYear,
        department_id: department_id.value
      }
    });
    warriorDataBase.value = resp.data;
    loading.value = false;
    
    const args = [
      resp.data, startOfYear, endOfYear, startOfYearOriginal, endOfYearOriginal, sort
    ]
        
    warriorData.value = handleStatistialWarrior(...args);
  }catch(error: any){
    console.log(error);
  }
}
onChangeFilter('desc');

const { t } = useI18n();
const handleExport = async ()=>{
    loadingExport.value = true;
    try{
        const args = [warriorDataBase.value, startOfYear, endOfYear, startOfYearOriginal, endOfYearOriginal, 'desc', 'export'];
        const warriorDataExport = handleStatistialWarrior(...args);

        const dataExport = {
          warrior:warriorDataExport,
          type:'warrior_year',
          year:dayjs(currentYear.value).format('YYYY'),
          department_id:department_id.value
        };

        const resp = await downloadFile('/api/statistial/export/top', dataExport, errorMessages, t);
        loadingExport.value = false;
    }catch(error: any){
        loadingExport.value = false;
    }
}
const getFullPathAvatar = (path : string | '') => {
    return window.location.origin + '/image/' + path;
}
const getDataExport = ()=>{
    const args = [warriorDataBase.value, startOfYear, endOfYear, startOfYearOriginal, endOfYearOriginal, 'desc', 'export'];
    const warriorDataExport = handleStatistialWarrior(...args);

    const dataExport = {
      warrior:warriorDataExport,
      type:'warrior_year',
      year:dayjs(currentYear.value).format('YYYY'),
    };

    return dataExport;
}

// send data to parent component
defineExpose({
  getDataExport
})
</script>
<style lang="scss">
.warrior-year-wrapper{
  .table-data{
    .progress-bar{
        display: flex;
        width: 95%;
        border-radius: 4px;
        overflow: hidden;
        height: 18px;
        background: #ccc;
    }
    .user-details{
      .fullname{
          color: #000 !important;
      }
      .duration{
          font-size: .8rem;
      }
    }
  }
  .el-dropdown-cus{
    height: 32px;
  }
  .more-filled-icon{
    rotate: 90deg;
  }
  .btn-more{
    width: 100%;
    height: 100%;
    background-color: none;
    border: unset;
    background-color: unset;
    &:hover{
        background-color: unset;
        border: unset;
    }
    &:focus{
        background-color: unset;
    }
    &:active{
        border: unset;
        background-color: unset;
    }
  }

  .table-warrior-year{
    width: 100%; 
    margin-top: 20px;
    tr th:first-child .cell{
      color: var(--el-text-color-primary) !important;
    }
  }
}
</style>