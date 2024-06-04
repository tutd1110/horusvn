<template>
    <el-dialog 
    v-model="visible" 
    style="font-weight: bold" 
    :style="{ width: hideMobile ? '30%' : '90%' }"
    draggable 
    :closable="false" 
    :title="title">
        <el-row :gutter="20" style="margin-bottom: 0;">
            <el-col :span="22" :offset="hideMobile ? 1 : 0">
                <el-button type="primary" style="margin-bottom: 20px;" @click="quickAddButton">Thêm sự kiện</el-button>
            </el-col>
            <el-col :span="12" :offset="hideMobile ? 1 : 0">
                <div class="modal-header-table">Sự kiện</div>
            </el-col>
            <el-col :span="8">
                <div class="modal-header-table">Chọn màu</div>
            </el-col>
            <el-col :span="2">
                <div class="modal-header-table">Action</div>
            </el-col>
        </el-row>
        <el-scrollbar height="300px">
            <el-row :gutter="20" style="margin-bottom: 0; display: flex; align-items: center; border-bottom: 1px solid #E4E7ED; padding: 5px 0;" 
            v-for="(record, key) in dataSource" :key="key"
            >
                <el-col :span="11" :offset="hideMobile ? 1 : 0">
                    <el-input v-model="record.name" placeholder="Please input" clearable class="none-border" @change="onChangeName(record.id, $event)"/>
                </el-col>
                <el-col :span="1">
                    <div :class="'select-color-' + record.class_color" style="width: 18px; height: 18px; border-radius: 4px;"></div>
                </el-col>
                <el-col :span="8">
                    <el-select 
                        v-model="record.class_color_name" 
                        clearable
                        placeholder="Select"
                        class="none-border"
                        @change="onChangeColor(record.id, $event)"
                    >
                        <el-option
                            v-for="item in colors"
                            :key="item.value"
                            :label="item.label"
                            :value="item.value"
                            style="display: flex; align-items: center;"
                            :disabled="dataSource.some((selected:any) => selected.class_color == item.value && selected != record)"
                        >
                            <span
                                style="
                                    display: flex;
                                    align-items: center;
                                    margin-right: 20px;
                                    height: 16px;
                                    width: 16px;
                                    border-radius: 50%;
                                "
                                :style="{ backgroundColor: item.key }"
                                >
                            </span>
                            <span >{{ item.label }}</span>
                        </el-option>
                    </el-select>
                </el-col>
                <el-col :span="2" style="text-align: center;">
                    <div class="modal-body-table">
                        <Delete
                            style="width: 16px; cursor: pointer; color:red" @click="deleteItem(record.id)"
                        />
                    </div>
                </el-col>
            </el-row>
        </el-scrollbar>
  </el-dialog>
</template>

<script lang="ts" setup>
    import axios from 'axios';
    import { ref, onMounted, computed} from 'vue';
    import { Delete } from '@element-plus/icons-vue';
    import { callMessage } from '../Helper/el-message.js';

    interface FormState {
        name?: string,
        class_color?: number,
    };
    const dataSource = ref()
    const errorMessages = ref('');
    const visible = ref(false)

    const ConfigModalMode = () => { 
        visible.value = true;
    };
    const title = ref('Cấu hình')
    const colors = ref();
    const formState = ref<FormState>({});

    // Computed property for computed formState
    const computedFormState = computed<FormState>(() => {
        const newFormState: FormState = {
            ...formState.value,
        };
        return newFormState;
    });
    const submitData = {
        name: computedFormState.value.name,
        class_color: computedFormState.value.class_color,
    };
    const quickAddButton = () => {

        axios.post('/api/calendar_event/store_config', submitData)
        .then(response => {
            _fetch()
            callMessage(response.data.success, 'success');
        })
        .catch(error => {
            errorMessages.value = error.response.data.errors;
            callMessage(errorMessages.value, 'error');
        })
    }
    const onChangeName = (id: number, value: number) => {
        update(id, 'name', value)
    }
    const onChangeColor = (id: number, value: number) => {
        update(id, 'class_color', value)
    }
    const update = (id: number, column: string, value: string | number | number[]) => {
        let submitData = {
            id: id,
            [column]: value ? value : ""
        }
        
        axios.patch('/api/calendar_event/quick_update_config', submitData)
        .then(response => {
            callMessage(response.data.success, 'success');
            _fetch()
        })
        .catch(error => {
            errorMessages.value = error.response.data.errors;//put message content in ref
            callMessage(errorMessages.value, 'error');
        })
    }
    const deleteItem = (id: number) => {
        let submitData = {
            id: id,
        }
        axios.post('/api/calendar_event/delete_config', submitData)
        .then(response => {
            callMessage(response.data.success, 'success');
            _fetch()
        })
        .catch(error => {
            errorMessages.value = error.response.data.errors;//put message content in ref
            callMessage(errorMessages.value, 'error');
        })
    }
    const _fetch = () => {
        axios.get('/api/calendar_event/get_event_list')
        .then(response => {
            dataSource.value = response.data;
            emit('saved');
        })
        .catch((error) => {
            errorMessages.value = error.response.data.errors;//put message content in ref
            //When search target data does not exist
            dataSource.value = []; //dataSource empty
            callMessage(errorMessages.value, 'error');
        });
    }
    const emit = defineEmits(['saved'])
    const hideMobile = ref(true)
    onMounted(() => {
        var screenWidth = window.screen.width;
        if (screenWidth >= 1080) {
            hideMobile.value = true
        } else {
            hideMobile.value = false
        } 
        
        axios.get('/api/calendar_event/get_selectboxes')
        .then(response => {
            colors.value = response.data.colors;
        })

        _fetch()
    })
    defineExpose({
        ConfigModalMode,
    });
</script>