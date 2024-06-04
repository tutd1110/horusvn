<template>
    <el-row :gutter="2" class="table-timesheet-body" style="margin-bottom: 0px; margin: 0px 1px;">
        <el-col :span="6">
            <el-row :gutter="2" style="margin-bottom: 5px;">
                <el-col :span="10"><el-card class="card-timesheet-body col2" shadow="hover"><span><b>Họ tên</b></span></el-card></el-col>
                <el-col :span="5"><el-card class="card-timesheet-body col2" shadow="hover"><span><b>Ngày làm việc chính thức</b></span></el-card></el-col>
                <el-col :span="9"><el-card class="card-timesheet-body col2" shadow="hover"><span><b>Số ngày đã làm việc chính thức</b></span></el-card></el-col>
            </el-row>
        </el-col>
        <el-col :span="2">
            <el-row :gutter="2" style="margin-bottom: 5px;">
                <el-col :span="24" style="margin-bottom: 5px;"><el-card class="card-timesheet-body col0" shadow="hover"><span><b>Đi muộn</b></span></el-card></el-col>
                <el-col :span="8"><el-card class="card-timesheet-body col1" shadow="hover"><span><b>Không đơn</b></span></el-card></el-col>
                <el-col :span="6"><el-card class="card-timesheet-body col1" shadow="hover"><span><b>Có đơn</b></span></el-card></el-col>
                <el-col :span="10"><el-card class="card-timesheet-body col1" shadow="hover"><span><b>KĐ/TG</b></span></el-card></el-col>
            </el-row>
        </el-col>
        <el-col :span="2">
            <el-row :gutter="2" style="margin-bottom: 5px;">
                <el-col :span="24" style="margin-bottom: 5px;"><el-card class="card-timesheet-body col0" shadow="hover"><span><b>Về sớm</b></span></el-card></el-col>
                <el-col :span="8"><el-card class="card-timesheet-body col1" shadow="hover"><span><b>Không đơn</b></span></el-card></el-col>
                <el-col :span="8"><el-card class="card-timesheet-body col1" shadow="hover"><span><b>Có đơn</b></span></el-card></el-col>
                <el-col :span="8"><el-card class="card-timesheet-body col1" shadow="hover"><span><b>Thời gian</b></span></el-card></el-col>
            </el-row>
        </el-col>
        <el-col :span="14">
            <el-row :gutter="2" style="margin-bottom: 5px;"  v-if="mode == true">
                <el-col :span="3">
                    <el-row :gutter="2" style="margin-bottom: 5px;">
                        <el-col :span="24" style="margin-bottom: 5px;"><el-card class="card-timesheet-body col0" shadow="hover"><span><b>Ra ngoài</b></span></el-card></el-col>
                        <el-col :span="12"><el-card class="card-timesheet-body col1" shadow="hover"><span><b>Số lần</b></span></el-card></el-col>
                        <el-col :span="12"><el-card class="card-timesheet-body col1" shadow="hover"><span><b>Thời gian</b></span></el-card></el-col>
                    </el-row>
                </el-col>
                <el-col :span="2" ><el-card class="card-timesheet-body col2" shadow="hover"><span><b>Tổng giờ ĐMVS</b></span></el-card></el-col>
                <el-col :span="2" ><el-card class="card-timesheet-body col2" shadow="hover"><span><b>ĐMVS/Công</b></span></el-card></el-col>
                <el-col :span="2" ><el-card class="card-timesheet-body col2" shadow="hover"><span><b>Công thực tế</b></span></el-card></el-col>
                <el-col :span="2" ><el-card class="card-timesheet-body col2" shadow="hover"><span><b>Công đăng kí làm thêm</b></span></el-card></el-col>
                <el-col :span="2" ><el-card class="card-timesheet-body col2" shadow="hover"><span><b>Nghỉ phép có lương</b></span></el-card></el-col>
                <el-col :span="3" ><el-card class="card-timesheet-body col2" shadow="hover"><span><b>Nghỉ phép không lương</b></span></el-card></el-col>
                <el-col :span="2" ><el-card class="card-timesheet-body col2" shadow="hover"><span><b>Công tính lương</b></span></el-card></el-col>
                <el-col :span="3" ><el-card class="card-timesheet-body col2" shadow="hover"><span><b>Ngày phép có lương còn lại</b></span></el-card></el-col>
                <el-col :span="3" ><el-card class="card-timesheet-body col2" shadow="hover"><span><b>Tỉ lệ đi muộn</b></span></el-card></el-col>
            </el-row>
            <el-row :gutter="2" style="margin-bottom: 5px;" v-else>
                <el-col :span="2" style="width: 11% !important; flex: 0 0 11%; max-width: none;">
                    <el-row :gutter="2" style="margin-bottom: 5px;">
                        <el-col :span="24" style="margin-bottom: 5px;"><el-card class="card-timesheet-body col0" shadow="hover"><span><b>Ra ngoài</b></span></el-card></el-col>
                        <el-col :span="12"><el-card class="card-timesheet-body col1" shadow="hover"><span><b>Số lần</b></span></el-card></el-col>
                        <el-col :span="12"><el-card class="card-timesheet-body col1" shadow="hover"><span><b>Thời gian</b></span></el-card></el-col>
                    </el-row>
                </el-col>
                <el-col style="width: 6.5% !important; flex: 0 0 6.5%; max-width: none;" :span="2" ><el-card class="card-timesheet-body col2" shadow="hover"><span><b>Tổng giờ ĐMVS</b></span></el-card></el-col>
                <el-col style="width: 6.5% !important; flex: 0 0 6.5%; max-width: none;" :span="1" ><el-card class="card-timesheet-body col2" shadow="hover"><span><b>ĐMVS/ Công</b></span></el-card></el-col>
                <el-col style="width: 6.5% !important; flex: 0 0 6.5%; max-width: none;" :span="1" ><el-card class="card-timesheet-body col2" shadow="hover"><span><b>Số giờ đi sớm</b></span></el-card></el-col>
                <el-col style="width: 6.5% !important; flex: 0 0 6.5%; max-width: none;" :span="1" ><el-card class="card-timesheet-body col2" shadow="hover"><span><b>Số giờ về muộn</b></span></el-card></el-col>
                <el-col style="width: 5.5% !important; flex: 0 0 5.5%; max-width: none;" :span="1" ><el-card class="card-timesheet-body col2" shadow="hover"><span><b>Công đăng kí nỗ lực</b></span></el-card></el-col>
                <el-col style="width: 5.5% !important; flex: 0 0 5.5%; max-width: none;" :span="1" ><el-card class="card-timesheet-body col2" shadow="hover"><span><b>Số giờ ĐKNL</b></span></el-card></el-col>
                <el-col style="width: 5.5% !important; flex: 0 0 5.5%; max-width: none;" :span="1" ><el-card class="card-timesheet-body col2" shadow="hover"><span><b>Tổng giờ nỗ lực</b></span></el-card></el-col>
                <el-col style="width: 7% !important; flex: 0 0 7%; max-width: none;" :span="2" ><el-card class="card-timesheet-body col2" shadow="hover"><span><b>Warrior hiện tại</b></span></el-card></el-col>
                <el-col style="width: 6.5% !important; flex: 0 0 6.5%; max-width: none;" :span="2" ><el-card class="card-timesheet-body col2" shadow="hover"><span><b>TG để giữ Warrior</b></span></el-card></el-col>
                <el-col style="width: 6.5% !important; flex: 0 0 6.5%; max-width: none;" :span="2" ><el-card class="card-timesheet-body col2" shadow="hover"><span><b>TGTB để giữ Warrior</b></span></el-card></el-col>
                <el-col style="width: 7% !important; flex: 0 0 7%; max-width: none;" :span="2" ><el-card class="card-timesheet-body col2" shadow="hover"><span><b>Warrior tiếp theo</b></span></el-card></el-col>
                <el-col style="width: 6.5% !important; flex: 0 0 6.5%; max-width: none;" :span="2" ><el-card class="card-timesheet-body col2" shadow="hover"><span><b>TG để lên Warrior</b></span></el-card></el-col>
                <el-col style="width: 6.5% !important; flex: 0 0 6.5%; max-width: none;" :span="2" ><el-card class="card-timesheet-body col2" shadow="hover"><span><b>TGTB để lên Warrior</b></span></el-card></el-col>
                <el-col style="width: 6.5% !important; flex: 0 0 6.5%; max-width: none;" :span="2" ><el-card class="card-timesheet-body col2" shadow="hover"><span><b>Tỉ lệ đi muộn</b></span></el-card></el-col>
            </el-row>
        </el-col>
    </el-row>
    <el-scrollbar max-height="505px" style="width: 100%">
        <el-row :gutter="2" class="table-timesheet-body" style="margin: 0px 1px; width: 100%" v-for="(user, index) in users" :key="index">
            <el-col :span="6">
                <el-row :gutter="2" style="margin-bottom: 5px;">
                    <el-col :span="10" v-if="user.rate_late >= 12.5"><el-card class="card-timesheet-body col0" shadow="hover" style="text-align: left;"><span style="color: red;"><b>{{ user.fullname }}</b></span></el-card></el-col>
                    <el-col :span="10" v-else><el-card class="card-timesheet-body col0" shadow="hover" style="text-align: left;"><span>{{ user.fullname }}</span></el-card></el-col>
                    <el-col :span="5"><el-card class="card-timesheet-body col0" shadow="hover"><span>{{ user.date_official }}</span></el-card></el-col>
                    <el-col :span="9"><el-card class="card-timesheet-body col0" shadow="hover"><span>{{ user.total_work_date }}</span></el-card></el-col>
                </el-row>
            </el-col>
            <el-col :span="2">
                <el-row :gutter="2" style="margin-bottom: 5px;">
                    <el-col :span="8"><el-card class="card-timesheet-body col0" shadow="hover"><span>{{ user.late_count }}</span></el-card></el-col>
                    <el-col :span="6"><el-card class="card-timesheet-body col0" shadow="hover"><span>{{ user.pe_late_count }}</span></el-card></el-col>
                    <el-col :span="10"><el-card class="card-timesheet-body col0" shadow="hover"><span style="font-size: 12px;" :style="(user.late_sum_none_petition >= 0.5) ? 'color: red;' : ''">{{ user.late_sum_none_petition != 0 ? user.late_sum_none_petition+'/' : ''}}{{ user.late_sum }}</span></el-card></el-col>
                </el-row>
            </el-col>
            <el-col :span="2">
                <el-row :gutter="2" style="margin-bottom: 5px;">
                    <el-col :span="8"><el-card class="card-timesheet-body col0" shadow="hover"><span>{{ user.early_count }}</span></el-card></el-col>
                    <el-col :span="8"><el-card class="card-timesheet-body col0" shadow="hover"><span>{{ user.pe_early_count }}</span></el-card></el-col>
                    <el-col :span="8"><el-card class="card-timesheet-body col0" shadow="hover"><span>{{ user.early_sum }}</span></el-card></el-col>
                </el-row>
            </el-col>
            <el-col :span="14">
                <el-row :gutter="2" style="margin-bottom: 5px;"  v-if="mode == true">
                    <el-col :span="3">
                        <el-row :gutter="2" style="margin-bottom: 5px;">
                            <el-col :span="12"><el-card class="card-timesheet-body col0" shadow="hover"><span>{{ user.office_goouts }}</span></el-card></el-col>
                            <el-col :span="12"><el-card class="card-timesheet-body col0" shadow="hover"><span>{{ user.office_time_goouts }}</span></el-card></el-col>
                        </el-row>
                    </el-col>
                    <el-col :span="2" ><el-card class="card-timesheet-body col0" shadow="hover"><span>{{ user.total_late_nd_early }}</span></el-card></el-col>
                    <el-col :span="2" ><el-card class="card-timesheet-body col0" shadow="hover"><span>{{ user.workday_late_nd_early }}</span></el-card></el-col>
                    <el-col :span="2" ><el-card class="card-timesheet-body col0" shadow="hover"><span>{{ user.origin_workday }}</span></el-card></el-col>
                    <el-col :span="2" ><el-card class="card-timesheet-body col0" shadow="hover"><span>{{ user.extra_workday }}</span></el-card></el-col>
                    <el-col :span="2" ><el-card class="card-timesheet-body col0" shadow="hover"><span>{{ user.paid_leave }}</span></el-card></el-col>
                    <el-col :span="3" ><el-card class="card-timesheet-body col0" shadow="hover"><span>{{ user.un_paid_leave }}</span></el-card></el-col>
                    <el-col :span="2" ><el-card class="card-timesheet-body col0" shadow="hover"><span>{{ user.paid_workday }}</span></el-card></el-col>
                    <el-col :span="3" ><el-card class="card-timesheet-body col0" shadow="hover"><span>{{ 0 }}</span></el-card></el-col>
                    <el-col :span="3" ><el-card class="card-timesheet-body col0" shadow="hover">
                        <span :style="{ color: user.rate_late > 12.5 ? 'red' : 'inherit' }">
                        {{ user.rate_late > 0 ? user.rate_late + '%' : 0 }}
                        </span>
                    </el-card></el-col>
                </el-row>
                <el-row :gutter="2" style="margin-bottom: 5px;" v-else>
                    <el-col :span="2" style="width: 11% !important; flex: 0 0 11%; max-width: none;">
                        <el-row :gutter="2" style="margin-bottom: 5px;">
                            <el-col :span="12"><el-card class="card-timesheet-body col0" shadow="hover"><span>{{ user.non_office_goouts }}</span></el-card></el-col>
                            <el-col :span="12"><el-card class="card-timesheet-body col0" shadow="hover"><span>{{ user.non_office_time_goouts }}</span></el-card></el-col>
                        </el-row>
                    </el-col>
                    <el-col style="width: 6.5% !important; flex: 0 0 6.5%; max-width: none;" :span="2" ><el-card class="card-timesheet-body col0" shadow="hover"><span>{{ user.total_late_nd_early }}</span></el-card></el-col>
                    <el-col style="width: 6.5% !important; flex: 0 0 6.5%; max-width: none;" :span="1" ><el-card class="card-timesheet-body col0" shadow="hover"><span>{{ user.workday_late_nd_early }}</span></el-card></el-col>
                    <el-col style="width: 6.5% !important; flex: 0 0 6.5%; max-width: none;" :span="1" ><el-card class="card-timesheet-body col0" shadow="hover"><span>{{ user.go_early_sum }}</span></el-card></el-col>
                    <el-col style="width: 6.5% !important; flex: 0 0 6.5%; max-width: none;" :span="1" ><el-card class="card-timesheet-body col0" shadow="hover"><span>{{ user.leave_late_sum }}</span></el-card></el-col>
                    <el-col style="width: 5.5% !important; flex: 0 0 5.5%; max-width: none;" :span="1" ><el-card class="card-timesheet-body col0" shadow="hover"><span>{{ user.workday_extra_warrior_time }}</span></el-card></el-col>
                    <el-col style="width: 5.5% !important; flex: 0 0 5.5%; max-width: none;" :span="1" ><el-card class="card-timesheet-body col0" shadow="hover"><span>{{ user.extra_warrior_time }}</span></el-card></el-col>
                    <el-col style="width: 5.5% !important; flex: 0 0 5.5%; max-width: none;" :span="1" ><el-card class="card-timesheet-body col0" shadow="hover"><span>{{ user.total_time_ot_war }}</span></el-card></el-col>
                    <el-col v-if="user.current_title == 'Warrior 1'" style="width: 7% !important; flex: 0 0 7%; max-width: none;" :span="2" ><el-card class="card-timesheet-body col0" shadow="hover"><span style="color:green;"><b>{{ user.current_title }}</b></span></el-card></el-col>
                    <el-col v-else-if="user.current_title == 'Warrior 2'" style="width: 7% !important; flex: 0 0 7%; max-width: none;" :span="2" ><el-card class="card-timesheet-body col0" shadow="hover"><span style="color:orange;"><b>{{ user.current_title }}</b></span></el-card></el-col>
                    <el-col v-else-if="user.current_title == 'Warrior 3'" style="width: 7% !important; flex: 0 0 7%; max-width: none;" :span="2" ><el-card class="card-timesheet-body col0" shadow="hover"><span style="color:#800000;"><b>{{ user.current_title }}</b></span></el-card></el-col>
                    <el-col v-else style="width: 7% !important; flex: 0 0 7%; max-width: none;" :span="2" ><el-card class="card-timesheet-body col0" shadow="hover"><span style="color:gray;"><b>{{ user.current_title }}</b></span></el-card></el-col>
                    <el-col style="width: 6.5% !important; flex: 0 0 6.5%; max-width: none;" :span="2" ><el-card class="card-timesheet-body col0" shadow="hover"><span>{{ user.time_keep_title }}</span></el-card></el-col>
                    <el-col style="width: 6.5% !important; flex: 0 0 6.5%; max-width: none;" :span="2" ><el-card class="card-timesheet-body col0" shadow="hover"><span>{{ user.avg_hold_title }}</span></el-card></el-col>
                    <el-col v-if="user.next_title == 'Warrior 1'" style="width: 7% !important; flex: 0 0 7%; max-width: none;" :span="2" ><el-card class="card-timesheet-body col0" shadow="hover"><span style="color:green;"><b>{{ user.next_title }}</b></span></el-card></el-col>
                    <el-col v-else-if="user.next_title == 'Warrior 2'" style="width: 7% !important; flex: 0 0 7%; max-width: none;" :span="2" ><el-card class="card-timesheet-body col0" shadow="hover"><span style="color:orange;"><b>{{ user.next_title }}</b></span></el-card></el-col>
                    <el-col v-else-if="user.next_title == 'Warrior 3'" style="width: 7% !important; flex: 0 0 7%; max-width: none;" :span="2" ><el-card class="card-timesheet-body col0" shadow="hover"><span style="color:#800000;"><b>{{ user.next_title }}</b></span></el-card></el-col>
                    <el-col v-else style="width: 7% !important; flex: 0 0 7%; max-width: none;" :span="2" ><el-card class="card-timesheet-body col0" shadow="hover"><span style="color:gray;"><b>{{ user.next_title }}</b></span></el-card></el-col>
                    <el-col style="width: 6.5% !important; flex: 0 0 6.5%; max-width: none;" :span="2" ><el-card class="card-timesheet-body col0" shadow="hover"><span>{{ user.time_next_title }}</span></el-card></el-col>
                    <el-col style="width: 6.5% !important; flex: 0 0 6.5%; max-width: none;" :span="2" ><el-card class="card-timesheet-body col0" shadow="hover"><span>{{ user.avg_next_title }}</span></el-card></el-col>
                    <el-col style="width: 6.5% !important; flex: 0 0 6.5%; max-width: none;" :span="2" ><el-card class="card-timesheet-body col0" shadow="hover">
                        <span :style="{ color: user.rate_late > 12.5 ? 'red' : 'inherit' }">{{ user.rate_late > 0 ? user.rate_late + '%' : 0 }}</span>
                    </el-card></el-col>
                </el-row>
            </el-col>
        </el-row>
    </el-scrollbar>
</template>
<script>
export default ({
    name: 'timesheet-table',
    props: {
        users: {
            type: Array,
            required: true,
        },
        mode: {
            type: Boolean,
            required: true,
        }
    },
    setup(props) {
        return {
        }
    }
})
</script>