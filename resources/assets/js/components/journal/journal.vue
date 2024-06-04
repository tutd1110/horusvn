<template>
    <data-form ref="modalRef" @saved="onSaved"></data-form>
    <el-row :gutter="10">
        <el-col :span="3">
            <label>Người thực hiện</label>
            <el-select
                v-model="formState.user_id"
                clearable
                multiple
                filterable
                collapse-tags
                collapse-tags-tooltip
                value-key="id"
                style="width: 100%"
            >
                <el-option
                    v-for="item in users"
                    :key="item.id"
                    :label="item.fullname"
                    :value="item.id"
                />
            </el-select>
        </el-col>
        <el-col :span="3" v-if="type === 'Game'">
            <label>Game</label>
            <el-select
                v-model="formState.game_id"
                multiple
                clearable
                filterable
                collapse-tags
                collapse-tags-tooltip
                value-key="value"
                style="width: 100%"
            >
                <el-option
                    v-for="item in options"
                    :key="item.value"
                    :label="item.label"
                    :value="item.value"
                />
            </el-select>
        </el-col>
        <el-col :span="3" v-if="type === 'Department'">
            <label>Department</label>
            <el-select
                v-model="formState.department_id"
                multiple
                clearable
                filterable
                collapse-tags
                collapse-tags-tooltip
                value-key="value"
                style="width: 100%"
            >
                <el-option
                    v-for="item in options"
                    :key="item.value"
                    :label="item.label"
                    :value="item.value"
                />
            </el-select>
        </el-col>
        <el-col :span="3" v-if="is_show_status && type != 'Company'">
            <label>Hiển thị</label>
            <el-select
                v-model="formState.status"
                value-key="value"
                style="width: 100%"
            >
                <el-option
                    v-for="item in status"
                    :key="item.value"
                    :label="item.label"
                    :value="item.value"
                />
            </el-select>
        </el-col>
        <el-col :span="4">
            <label>Khoảng thời gian</label>
            <el-date-picker
                id="select-daterange"
                v-model="datePeriod"
                type="daterange"
                unlink-pannels
                range-separator="To"
                start-placeholder="Start date"
                end-placeholder="End date"
                size="default"
                style="width: 100%"
            ></el-date-picker>
        </el-col>
        <el-col :span="1" style="margin-top: 22px">
            <el-space size="small" spacer="|">
                <el-button type="primary" v-on:click="search()" :loading-icon="elemeIcon" :loading="loadingSearch">Search</el-button>
                <el-button color="#626aef" :icon="editIcon" v-on:click="showRegisterModal" />
            </el-space>
        </el-col>
    </el-row>
    <el-scrollbar height="1050px">
        <el-col :span="24">
            <el-collapse accordion>
                <draggable
                    :list="journals"
                    item-key="id"
                    @sort="onDragSort"
                >
                    <template #item="{ element, index }">
                        <el-collapse-item :name="index" style="user-select: text;">
                            <template #title>
                                {{ element.title }}
                                <el-icon class="header-icon" style="margin-left: 20px; margin-right: 5px" v-if="userLogin.id == 51 || userLogin.id == element.user_id">
                                    <Edit v-on:click="showUpdateModal(element.id)"/>
                                </el-icon>
                                <el-icon class="header-icon" style="color: red" v-if="userLogin.id == 51 || userLogin.id == element.user_id">
                                    <Delete v-on:click="onClickDelete(element.id)"/>
                                </el-icon>
                            </template>
                            <div class="journal-container">
                                <div class="journal-content">
                                    <div class="content-body">
                                        <span v-html="element.description"></span>
                                    </div>
                                    <div class="date-time">
                                        <template v-for="(file, idx2) in element.other_files" :key="idx2+1">
                                            <a :href="file" download>{{ extractFileName(file) }}</a> |
                                            <br>
                                        </template>
                                        {{ element.fullname }} - {{ element.created_at }}
                                    </div>
                                    <template v-if="type === 'Department'">
                                        <el-tag
                                            v-for="department in element.departments"
                                            :key="department.department_id"
                                            type="success"
                                            class="mx-1"
                                            effect="plain"
                                            round
                                        >
                                        {{ department.name }}
                                        </el-tag>
                                    </template>
                                    <template v-else-if="type === 'Game'">
                                        <el-tag
                                            v-for="game in element.games"
                                            :key="game.game_id"
                                            type="success"
                                            class="mx-1"
                                            effect="plain"
                                            round
                                        >
                                        {{ game.name }}
                                        </el-tag>
                                    </template>
                                    <template v-else>
                                        <el-tag
                                            type="success"
                                            class="mx-1"
                                            effect="plain"
                                            round
                                        >Company</el-tag>
                                    </template>
                                </div>
                                <div class="slideshow">
                                    <template v-for="(image, idx) in element.image_files" :key="idx+1">
                                        <el-image
                                            style="width: 100px; height: 100px"
                                            :src="image"
                                            :zoom-rate="1.2"
                                            :initial-index="4"
                                            :preview-src-list="element.image_files"
                                            fit="cover"
                                        />
                                    </template>
                                </div>
                            </div>
                        </el-collapse-item>
                    </template>
                </draggable>
            </el-collapse>
        </el-col>
    </el-scrollbar>
</template>
<script>
import axios from 'axios';
import { onMounted, shallowRef, markRaw, computed, ref, h } from 'vue';
import { Edit, Delete, Eleme, Link } from '@element-plus/icons-vue'
import DataForm from './DataForm.vue';
import { useI18n } from 'vue-i18n';
import { errorModal } from '../Helper/error-modal.js';
import { buildFormStateTime } from '../Helper/build-datetime.js';
import draggable from "vuedraggable";

export default ({
    components: {
        DataForm,
        draggable,
        Edit,
        Link,
        Delete
    },
    name: 'journal',
    props: {
        type: {
            type: String,
            required: true,
        }
    },
    emits: ['saved'],
    setup(props, { emit }) {
        const { t } = useI18n();
        const editIcon = shallowRef(Edit);
        const elemeIcon = shallowRef(Eleme);
        const loadingSearch = ref(false);
        const baseUrl = ref("");
        const formState = ref({
            status: 0
        });
        const errorMessages = ref("");
        const modalRef = ref();
        const users = ref([]);
        const journals = ref([]);
        const options = ref([]);
        const status = ref([]);
        const datePeriod = ref([]);
        const dateFormat = 'DD/MM/YYYY';
        const is_show_status = ref(false);
        const _fetch = () => {
            // Fetch journals
            axios.get('/api/journal/get_journals', {
                params: {
                    type: props.type,
                },
            })
            .then(response => {
                journals.value = transferJournals(response.data);
            })
            .catch(error => {
                journals.value = [];
                errorMessages.value = error.response.data.errors;
                errorModal(t, errorMessages);
            });
        };
        const handleDatePeriod = () => {
            buildFormStateTime(formState, datePeriod)
        };
        const search = () => {
            loadingSearch.value = true;
            handleDatePeriod()
            formState.value.type = props.type

            axios.get('/api/journal/get_journals', {
                params: formState.value,
            })
            .then(response => {
                loadingSearch.value = false;
                journals.value = transferJournals(response.data);
            })
            .catch(error => {
                loadingSearch.value = false;
                journals.value = [];
                errorMessages.value = error.response.data.errors;
                errorModal(t, errorMessages);
            });
        }
        const transferJournals = (data) => {
            var newData = [];

            data.forEach(function(item, index) {
                let htmlDescription = renderHTML(item.description)
                const imageList = item.image_files.map((file) => baseUrl.value + file.path);
                const otherFileList = item.other_files.map((file) => baseUrl.value + file.path);

                let value = {
                    id: item.id,
                    user_id: item.user_id,
                    fullname: item.fullname,
                    image_files: imageList,
                    other_files: otherFileList,
                    title: item.title,
                    description: htmlDescription,
                    created_at: item.created_at
                };

                if (props.type === 'Department') {
                    value.departments = item.departments
                } else if (props.type === 'Game') {
                    value.games = item.games
                }

                newData.push(value);
            });

            return newData;
        };
        const renderHTML = (description) => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(description, 'text/html');

            return doc.body.innerHTML;
        }
        const onClickDelete = (journalId) => {
            ElMessageBox.confirm(
                'Bạn có chắc chắn xoá bài viết này?',
                t('message.MSG-TITLE-W'),
                {
                    confirmButtonText: 'OK',
                    cancelButtonText: 'Cancel',
                    type: 'warning',
                    icon: markRaw(Delete),
                    draggable: true,
                    showClose: false,
                    closeOnClickModal: false
                }
            )
            .then(() => {
                axios.delete('/api/journal/delete', {
                    params: {
                        id: journalId
                    }
                })
                .then(response => {
                    ElMessage({
                        message: response.data.success,
                        type: 'success',
                    })
                    search();
                })
                .catch(error => {
                    search();
                    errorMessages.value = error.response.data.errors;
                    errorModal(t, errorMessages);
                })
            })
            .catch(() => {
                ElMessage({
                    type: 'info',
                    message: 'Delete canceled',
                })
            })
        }
        const CreateSelbox = () => {
            //create select boxes
            axios.get('/api/journal/get_selbox')
            .then(response => {
                users.value = response.data.users
                status.value = response.data.status
                is_show_status.value = response.data.view_status
                if (props.type === 'Department') {
                    options.value = response.data.departments
                } else if (props.type === 'Game') {
                    options.value = response.data.games
                }
            })
        }
        const showRegisterModal = () => {
            modalRef.value.ShowWithAddMode(props.type, options.value);
        }
        const showUpdateModal = (id) => {
            modalRef.value.ShowWithEditMode(props.type, options.value, id);
        }
        const onDragSort = () => {
            const sortedJournals = journals.value.map((item, index) => ({
                id: item.id,
                ordinal_number: index + 1,
            }));

            axios.post('/api/journal/update-order', {journals: sortedJournals})
            .then(response => {
                ElMessage({
                    message: response.data.success,
                    type: 'success',
                })
            })
            .catch(error => {
                errorMessages.value = error.response.data.errors;
                errorModal(t, errorMessages);
            })
        }
        const extractFileName = (url) => {
            const fileName = url.substring(url.lastIndexOf('/') + 1);

            return fileName;
        }
        const onSaved = () => {
            // _fetch();
            search();
        };
        const userLogin = ref()
        onMounted(() => {
            axios.get('/api/common/get_user_login')
            .then(response => {
                userLogin.value = response.data
            })
            .catch(error => {
                errorMessages.value = error.response.data.errors;
                errorModal(t, errorMessages);
            })

            // Retrieve the base URL from the current page's URL
            const currentUrl = window.location.href;
            const segments = currentUrl.split('/');
            baseUrl.value = `${segments[0]}//${segments[2]}`+'/';
            CreateSelbox()
            _fetch()
        });
        return {
            baseUrl,
            modalRef,
            formState,
            datePeriod,
            dateFormat,
            options,
            status,
            onSaved,
            users,
            journals,
            search,
            showRegisterModal,
            showUpdateModal,
            onClickDelete,
            onDragSort,
            editIcon,
            elemeIcon,
            loadingSearch,
            extractFileName,
            userLogin,
            is_show_status
        };
    }
})
</script>
<style lang="scss">
/* Styles for the journal container */
.journal-container {
  display: flex;
  align-items: flex-start;
  margin-bottom: 20px; /* Add margin bottom for spacing between journal entries */
}
/* Styles for the journal content */
.journal-content {
  flex: 1;
  padding-right: 20px;
  margin-left: 30px;
}
/* Styles for the slideshow */
.slideshow {
  flex: 1;
  display: flex; /* Add flex display to align images horizontally */
}
/* Styles for the date/time */
.date-time {
  font-size: 12px;
  color: #666;
  margin-bottom: 10px;
}
/* Styles for the content body */
.content-body {
  font-size: 10px;
  color: #555;
  margin-bottom: 10px;
}
/* Styles for the slideshow images */
.slide {
  flex: 1; /* Distribute space evenly among images */
  max-height: 200px; /* Adjust the max height to control image size */
  object-fit: cover; /* Maintain aspect ratio and cover container */
  width: 200px;
}
/* Style for the active slide */
.slide.active {
  display: block;
}
/* Style for the ant collapse header */
.el-collapse-item__header {
    font-weight: bold;
    font-size: 15px
}
.demo-image__error .image-slot {
  font-size: 30px;
}
.demo-image__error .image-slot .el-icon {
  font-size: 30px;
}
.demo-image__error .el-image {
  width: 100%;
  height: 200px;
}
.el-row {
  margin-bottom: 20px;
}
</style>