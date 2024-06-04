<template>
    <div class="p-pageWrapper" id="top">
        <!-- <div class="p-header" id="header">
            <div class="p-header-inner">
                <div class="p-header-content">
                    <div class="p-header-logo p-header-logo--image">
                        <a href="/home">
                            <img :src="logoSrc" srcset="" alt="Horus" width="90" height="" />
                        </a>
                    </div>
                </div>
            </div>
        </div> -->
        <div class="p-navSticky p-navSticky--primary header-pc">
            <nav class="p-nav">
                <div class="p-nav-inner">
                    <div class="p-nav-scroller hScroller">
                        <div class="hScroller-scroll">
                            <ul class="p-nav-list">
                                <li>
                                    <div class="p-header-logo p-header-logo--image">
                                        <a href="/home">
                                            <img :src="logoSrc" srcset="" alt="Horus" width="90" height="" />
                                        </a>
                                    </div>
                                </li>
                                <li>
                                    <div class="p-navEl">
                                        <a class="btn-dropdown-menu" @click="handleOnClick">
                                            <span class="p-navEl-link p-navEl-link--menuTrigger">Employee <el-icon class="custom-caret"><CaretBottom /></el-icon></span>
                                        </a>
                                        <div class="menu as menu--structural" data-menu="menu" aria-hidden="true">
                                            <span class="menu-arrow" style="left: 58.906px;"></span>
                                            <div class="menu-content">
                                                <a href="/employee" class="menu-linkRow">List</a>
                                                <a href="/employee-workday-report" class="menu-linkRow">Workday Report</a>
                                                <a href="/log" class="menu-linkRow" v-if="user && (user.id==107 || user.id==161 || user.id==63)">Log</a>
                                                <a href="/announcements/post" class="menu-linkRow" v-if="user && user.department_id==7">Post</a>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <li>
                                    <div class="p-navEl">
                                        <a class="btn-dropdown-menu" @click="handleOnClick">
                                            <span class="p-navEl-link p-navEl-link--menuTrigger">Yêu cầu <el-icon class="custom-caret"><CaretBottom /></el-icon></span>
                                            <span v-if="petition_count > 0 || deadline_mod_count > 0" class="count-notifi custom">{{ petition_count + deadline_mod_count }}</span>
                                        </a>
                                        <div class="menu as menu--structural" data-menu="menu" aria-hidden="true">
                                            <span class="menu-arrow" style="left: 58.906px;"></span>
                                            <div class="menu-content">
                                                <a href="/petitions" class="menu-linkRow">Yêu cầu<span v-if="petition_count > 0" class="count-notifi custom-item">{{ petition_count }}</span></a>
                                                <a href="/tasks/deadline-modification" class="menu-linkRow">Deadline Modification<span v-if="deadline_mod_count > 0" class="count-notifi custom-item">{{ deadline_mod_count }}</span></a>
                                                <a href="/purchase" class="menu-linkRow" v-if="user && ([46,161,194,63].includes(user.id) || user.department_id==12 || user.department_id==8 )">Purchase</a>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <li>
                                    <div class="p-navEl">
                                        <a class="btn-dropdown-menu" @click="handleOnClick">
                                            <span class="p-navEl-link p-navEl-link--menuTrigger">Chấm công <el-icon class="custom-caret"><CaretBottom /></el-icon></span>
                                            <span class="count-notifi custom" v-if="missedTimesheets > 0">{{ missedTimesheets }}</span>
                                        </a>
                                        <div class="menu as menu--structural" data-menu="menu" aria-hidden="true">
                                            <span class="menu-arrow" style="left: 58.906px;"></span>
                                            <div class="menu-content">
                                                <a href="/timesheets" class="menu-linkRow">
                                                    Bảng chấm công
                                                    <span class="count-notifi custom-item" v-if="missedTimesheets > 0">{{ missedTimesheets }}</span>
                                                </a>
                                                <a href="/timesheets/report" class="menu-linkRow">Thống kê</a>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <li>
                                    <div class="p-navEl">
                                        <a class="btn-dropdown-menu" @click="handleOnClick">
                                            <span class="p-navEl-link p-navEl-link--menuTrigger">Công việc <el-icon class="custom-caret"><CaretBottom /></el-icon></span>
                                        </a>
                                        <div class="menu as menu--structural" data-menu="menu" aria-hidden="true">
                                            <span class="menu-arrow" style="left: 58.906px;"></span>
                                            <div class="menu-content">
                                                <a v-if="is_authority===true" href="/projects" class="menu-linkRow">Dự án</a>
                                                <!-- <a v-if="is_authority===true || add_permission===true" href="/tasks" class="menu-linkRow">Quản lý công việc</a> -->
                                                <a href="/tasks" class="menu-linkRow">Quản lý công việc</a>
                                                <a v-if="is_authority===true" href="/task-gantt" class="menu-linkRow">Quản lý công việc Gantt</a>
                                                <a href="/department/tasks" class="menu-linkRow">Việc bộ phận</a>
                                                <a href="/me/tasks" class="menu-linkRow">Việc của tôi</a>
                                                <a href="/weighted/fluctuation" class="menu-linkRow">Lịch sử trọng số</a>
                                                <a href="/report" class="menu-linkRow">Thống kê</a>
                                                <a href="/working-time" v-if="user && ([46,161,51,63,90].includes(user.id))" class="menu-linkRow">Chi phí dự án</a>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <!-- <li>
                                    <div class="p-navEl">
                                        <a class="btn-dropdown-menu" @click="handleOnClick">
                                            <span class="p-navEl-link p-navEl-link--menuTrigger">Bugs <el-icon class="custom-caret"><CaretBottom /></el-icon></span>
                                            <span class="count-notifi custom" v-if="department_bugs > 0">{{ department_bugs }}</span>
                                        </a>
                                        <div class="menu as menu--structural" data-menu="menu" aria-hidden="true">
                                            <span class="menu-arrow" style="left: 58.906px;"></span>
                                            <div class="menu-content">
                                                <a :href="getDepartmentIssuesLink()" class="menu-linkRow">Bộ phận<span class="count-notifi custom-item" v-if="department_bugs > 0">{{ department_bugs }}</span></a>
                                                <a :href="getPersonalIssuesLink()" class="menu-linkRow">Cá nhân<span class="count-notifi custom-item" v-if="my_bugs > 0">{{ my_bugs }}</span></a>
                                            </div>
                                        </div>
                                    </div>
                                </li> -->
                                <li>
                                    <div class="p-navEl">
                                        <a class="btn-dropdown-menu" @click="handleOnClick">
                                            <span class="p-navEl-link p-navEl-link--menuTrigger">Bugs <el-icon class="custom-caret"><CaretBottom /></el-icon></span>
                                            <span class="count-notifi custom" v-if="assigned_department_bugs > 0">{{ assigned_department_bugs }}</span>
                                        </a>
                                        <div class="menu as menu--structural" data-menu="menu" aria-hidden="true">
                                            <span class="menu-arrow" style="left: 58.906px;"></span>
                                            <div class="menu-content">
                                                <h3 class="menu-header">Cá Nhân</h3>
                                                <div class="listPlain listColumns listColumns--narrow listColumns--together">
                                                    <a class="menu-linkRow" href="/issues/personal-self-created">Gán bug<span class="count-notifi custom-item" v-if="created_my_bugs > 0">{{ created_my_bugs }}</span></a>
                                                    <a class="menu-linkRow" href="/issues/personal-assigned">Bug bị gán<span class="count-notifi custom-item" v-if="assigned_my_bugs > 0">{{ assigned_my_bugs }}</span></a>
                                                </div>
                                                <h3 class="menu-header">Bộ Phận</h3>
                                                <div class="listPlain listColumns listColumns--narrow listColumns--together">
                                                    <a class="menu-linkRow" href="/issues/department-assigned">Bug bị gán<span class="count-notifi custom-item" v-if="assigned_department_bugs > 0">{{ assigned_department_bugs }}</span></a>
                                                </div>
                                                <h3 class="menu-header">Công ty</h3>
                                                <div class="listPlain listColumns listColumns--narrow listColumns--together">
                                                    <a class="menu-linkRow" href="/issues/department-self-created">Toàn công ty<span class="count-notifi custom-item" v-if="created_department_bugs > 0">{{ created_department_bugs }}</span></a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <li>
                                    <div class="p-navEl">
                                        <a class="btn-dropdown-menu" @click="handleOnClick">
                                            <span class="p-navEl-link p-navEl-link--menuTrigger">Data Analytics <el-icon class="custom-caret"><CaretBottom /></el-icon></span>
                                        </a>
                                        <div class="menu as menu--structural" data-menu="menu" aria-hidden="true">
                                            <span class="menu-arrow" style="left: 58.906px;"></span>
                                            <div class="menu-content">
                                                <a href="http://192.168.10.71/in-out" target="_blank" class="menu-linkRow">Tracking In/Out</a>
                                                <a href="/tracking-game" class="menu-linkRow">Tracking Game</a>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <li>
                                    <div class="p-navEl">
                                        <a class="btn-dropdown-menu" @click="handleOnClick">
                                            <span class="p-navEl-link p-navEl-link--menuTrigger">Review <el-icon class="custom-caret"><CaretBottom /></el-icon></span>
                                            <span class="count-notifi custom" v-if="review_count > 0">{{ review_count }}</span>
                                        </a>
                                        <div class="menu as menu--structural" data-menu="menu" aria-hidden="true">
                                            <span class="menu-arrow" style="left: 58.906px;"></span>
                                            <div class="menu-content">
                                                <a href="/employee/review/list" class="menu-linkRow" v-if="user && [46,63,82,90,107,51,161,232,194].includes(user.id)">Danh sách đánh giá</a>
                                                <a href="/employee/review" class="menu-linkRow" v-if="user && (user!.position >= 1 || is_mentor)">Nhân viên</a>
                                                <a href="/employee/review/personal" class="menu-linkRow" v-if="user && user.position < 2">Cá nhân</a>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <li>
                                    <div class="p-navEl">
                                        <a class="btn-dropdown-menu btn-none-dropdown" href="/announcements">
                                            <span class="p-navEl-link p-navEl-link--menuTrigger">News & Updates</span>
                                        </a>
                                    </div>
                                </li>
                                <li>
                                    <div class="p-navEl">
                                        <a class="btn-dropdown-menu" @click="handleOnClick">
                                            <span class="p-navEl-link p-navEl-link--menuTrigger">Ghi chú <el-icon class="custom-caret"><CaretBottom /></el-icon></span>
                                        </a>
                                        <div class="menu as menu--structural" data-menu="menu" aria-hidden="true">
                                            <span class="menu-arrow" style="left: 58.906px;"></span>
                                            <div class="menu-content">
                                                <a href="/journal/company" class="menu-linkRow" v-if="user && (user.id==46 || user.id==161 ) ">Công ty</a>
                                                <a href="/journal/department" class="menu-linkRow">Bộ phận</a>
                                                <a href="/journal/game" class="menu-linkRow">Game</a>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <li>
                                    <div class="p-navEl">
                                        <a class="btn-dropdown-menu btn-none-dropdown" href="/calendar">
                                            <span class="p-navEl-link p-navEl-link--menuTrigger">Lịch</span>
                                        </a>
                                    </div>
                                </li>
                                <li>
                                    <div class="p-navEl">
                                        <a class="btn-dropdown-menu btn-none-dropdown" @click="handleForumRedirectly">
                                            <span class="p-navEl-link p-navEl-link--menuTrigger">Forum</span>
                                        </a>
                                    </div>
                                </li>
                                <li>
                                    <div class="p-navEl">
                                        <a class="btn-dropdown-menu btn-none-dropdown" href="/order">
                                            <span class="p-navEl-link p-navEl-link--menuTrigger">Đặt đồ ăn</span>
                                        </a>
                                    </div>
                                </li>
                                <li v-if="user && [161].includes(user.id)">
                                    <div class="p-navEl">
                                        <a class="btn-dropdown-menu" @click="handleOnClick">
                                            <span class="p-navEl-link p-navEl-link--menuTrigger">Quản lý <el-icon class="custom-caret"><CaretBottom /></el-icon></span>
                                        </a>
                                        <div class="menu as menu--structural" data-menu="menu" aria-hidden="true">
                                            <span class="menu-arrow" style="left: 58.906px;"></span>
                                            <div class="menu-content">
                                                <a href="/management/company" class="menu-linkRow" v-if="user && [161].includes(user.id)">Công ty</a>
                                                <a href="/management/department" class="menu-linkRow" v-if="user && [161].includes(user.id)">Bộ phận</a>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="p-nav-opposite">
                        <div class="p-navgroup p-account p-navgroup--member" style="display: flex;">
                            <div class="switch-company" style="align-items: center;display: flex;">
                                <el-radio-group v-model="company" v-if="user && [46,63,107,161].includes(user.id)" @change="onChangeCompany">
                                    <el-radio-button label="Production" />
                                    <el-radio-button label="AI" />
                                </el-radio-group>
                            </div>
                            <div class="p-nav-item p-navEl ">
                                <a class="p-navgroup-link btn-dropdown-menu" @click="handleOnClick" style="align-items: center;display: flex;">
                                    <span class="avatar avatar--xxs" style="margin-right: 5px;">
                                        <img :src="getAvatarSrc(user.avatar)" v-if="user" class="avatar-u46-s" width="48" height="48" loading="lazy" />
                                    </span>
                                    <span class="p-navgroup-linkText">{{ getUserNameFromEmail() }}</span>
                                </a>
                                <div class="dropdown-account menu menu-account menu--structural menu--medium">
                                    <span class="menu-arrow" style="left: 220.5px;"></span>
                                    <div class="menu-content">
                                        <el-tabs v-model="activeName" class="account-tabs" >
                                            <el-tab-pane class="abc" label="Tài khoản của bạn" name="first">
                                                <ul class="tabPanes">
                                                    <li class="is-active" role="tabpanel" id="_xfUid-accountMenu-1690771254" aria-expanded="true">
                                                        <div class="menu-row menu-row--alt">
                                                            <div class="contentRow">
                                                                <div class="contentRow-figure">
                                                                    <span class="avatarWrapper">
                                                                        <span class="avatar avatar--m" data-user-id="46" title="doanhpt">
                                                                            <img :src="getAvatarSrc(user.avatar)" v-if="user" class="avatar-u46-m" width="96" height="96" loading="lazy" />
                                                                        </span>
                                                                        <a class="avatarWrapper-update" href="/index.php?account/avatar" data-xf-click="overlay"><span>Sửa</span></a>
                                                                    </span>
                                                                </div>
                                                                <div class="contentRow-main">
                                                                    <el-row :gutter="0" class="">
                                                                        <el-col class="contentRow-header" :span="24">
                                                                            <a href="/index.php?members/doanhpt.46/" class="username" dir="auto" data-user-id="46">{{ getUserNameFromEmail() }}</a>
                                                                        </el-col>
                                                                        <el-col class="contentRow-lesser " :span="24">
                                                                            <span class="userTitle black-color" dir="auto">New member</span>
                                                                        </el-col>
                                                                        <el-col class="contentRow-lesser" :span="23">
                                                                            <span class="userTitle" dir="auto">Bài viết</span>
                                                                        </el-col>
                                                                        <el-col class="contentRow-lesser" :span="1">
                                                                            <a href="" class="userTitle">1</a>
                                                                        </el-col>
                                                                        <el-col class="contentRow-lesser" :span="23">
                                                                            <span class="userTitle" dir="auto">Reaction score</span>
                                                                        </el-col>
                                                                        <el-col class="contentRow-lesser" :span="1">
                                                                            <a href="" class="userTitle">1</a>
                                                                        </el-col>
                                                                        <el-col class="contentRow-lesser" :span="23">
                                                                            <span class="userTitle" dir="auto">Điểm thành tích</span>
                                                                        </el-col>
                                                                        <el-col class="contentRow-lesser" :span="1">
                                                                            <a href="" class="userTitle">1</a>
                                                                        </el-col>
                                                                    </el-row>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <hr class="menu-separator menu-separator--hard" />
                                                        <el-row :gutter="0" class="menu-account-item">
                                                            <el-col :span="12"><a href="/index.php?whats-new/news-feed" class="menu-linkRow">Luồng tin</a></el-col>
                                                            <el-col :span="12"><a href="/index.php?search/member&amp;user_id=46" class="menu-linkRow">Nội dung của bạn</a></el-col>
                                                            <el-col :span="12"><a href="/index.php?account/reactions" class="menu-linkRow">Reactions received</a></el-col>
                                                        </el-row>
                                                        <hr class="menu-separator" />
                                                        <el-row :gutter="0" class="menu-account-item">
                                                            <el-col :span="12"><a href="/index.php?account/account-details" class="menu-linkRow">Thông tin tài khoản</a></el-col>
                                                            <el-col :span="12"><a href="/index.php?account/security" class="menu-linkRow">Mật khẩu và bảo mật</a></el-col>
                                                            <el-col :span="12"><a href="/index.php?account/privacy" class="menu-linkRow">Bảo mật cá nhân</a></el-col>
                                                            <el-col :span="12"><a href="/index.php?account/preferences" class="menu-linkRow">Tùy chọn</a></el-col>
                                                            <el-col :span="12"><a href="/index.php?account/signature" class="menu-linkRow">Chữ ký</a></el-col>
                                                            <el-col :span="12"><a href="/index.php?account/following" class="menu-linkRow">Đang theo dõi</a></el-col>
                                                            <el-col :span="12"><a href="/index.php?account/ignored" class="menu-linkRow">Bỏ qua</a></el-col>
                                                        </el-row>
                                                        <hr class="menu-separator" />
                                                        <el-row :gutter="0" class="menu-account-item">
                                                            <el-col :span="24"><span style="cursor: pointer" class="menu-linkRow" @click="logout()">Thoát</span></el-col>
                                                        </el-row>
                                                        <form action="" method="post" class="menu-footer" >
                                                            <input type="hidden" name="_xfToken" value="1690771254,f45815ff17eb701b0baeb2649d871018" />
                                                            <el-input
                                                                :rows="1"
                                                                type="textarea"
                                                                placeholder="Cập nhật trạng thái..."
                                                                class="input-account"
                                                                @click="showBtnPost"
                                                            />
                                                            <div class="btn-post-account" v-if="showButton">
                                                                <el-button type="primary"><el-icon><Promotion /></el-icon>Post</el-button>
                                                            </div>
                                                        </form>
                                                    </li>

                                                </ul>
                                            </el-tab-pane>
                                            <el-tab-pane label="Bookmarks" name="second">
                                                <ul class="tabPanes">
                                                    <li role="tabpanel" id="_xfUid-accountMenuBookmarks-1690771254" data-href="/index.php?account/bookmarks-popup" data-load-target=".js-bookmarksMenuBody">
                                                        <el-select
                                                            class="select-account"
                                                            multiple
                                                            filterable
                                                            remote
                                                            reserve-keyword
                                                            placeholder="Please enter a keyword"
                                                        >
                                                            <el-option
                                                            v-for="item in options"
                                                            :key="item.value"
                                                            :label="item.label"
                                                            :value="item.value"
                                                            />
                                                        </el-select>
                                                        <div class="menu-footer menu-footer--close">
                                                            <a href="/index.php?account/bookmarks" class="footer-link">Xem tất cả…</a>
                                                        </div>
                                                    </li>
                                                </ul>
                                            </el-tab-pane>
                                        </el-tabs>
                                    </div>
                                </div>
                            </div>
                            <div class="p-nav-item p-navEl ">
                                <a class="p-navgroup-link btn-dropdown-menu" @click="handleOnClick" style="align-items: center;display: flex;" >
                                    <Message style="width: 1em; height: 1em; font-size: 16px" />
                                    <!-- <span class="count-notifi">99+</span> -->
                                </a>
                                <div class="menu menu--structural menu--medium">
                                    <span class="menu-arrow"></span>
                                    <div class="menu-content">
                                        <h3 class="menu-header">Trò chuyện</h3>
                                        <div class="js-convMenuBody">
                                            <div class="none-item">Bạn không có cuộc trò chuyện nào gần đây.</div>
                                        </div>
                                        <!-- <div class="js-convMenuBody">
                                            <div class="notification-item">
                                                <a href="" class="img-notifi block-img">
                                                    <img :src="getAvatarSrc(user.avatar)" v-if="user">
                                                </a>
                                                <a href="" class="main-notifi" >
                                                    <div class="notifi-name"><span class="notifi-strong">tessmasage</span></div>
                                                    <div class="notifi-with">Với: doanhpt, bienvq</div>
                                                    <div class="notifi-time">14 phút trước</div>
                                                </a>
                                            </div>
                                            <div class="notification-item">
                                                <a href="" class="img-notifi block-img">
                                                    <img :src="getAvatarSrc(user.avatar)" v-if="user">
                                                </a>
                                                <a href="" class="main-notifi" >
                                                    <div class="notifi-name"><span class="notifi-strong">tessmasage</span></div>
                                                    <div class="notifi-with">Với: doanhpt, bienvq</div>
                                                    <div class="notifi-time">14 phút trước</div>
                                                </a>
                                            </div>
                                        </div> -->
                                        <div class="menu-footer menu-footer--split">
                                            <a href="/index.php?account/bookmarks" class="footer-link">Xem tất cả</a>
                                            <a href="/index.php?account/bookmarks" class="footer-link">Bắt đầu trò chuyện mới</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="p-nav-item p-navEl ">
                                <a class="p-navgroup-link btn-dropdown-menu" @click="handleOnClick" style="align-items: center;display: flex;">
                                    <Bell style="width: 1em; height: 1em; font-size: 16px" />
                                    <span class="p-navgroup-linkText"></span>
                                    <span class="count-notifi" v-if="unreadAlertsCount > 0">{{ unreadAlertsCount }}</span>
                                </a>
                                <div class="menu menu--structural menu--medium" >
                                    <span class="menu-arrow"></span>
                                    <div class="menu-content">
                                        <h3 class="menu-header">Thông báo</h3>
                                        <div class="js-convMenuBody">
                                            <template v-if="alerts.length > 0">
                                                <div
                                                    :class="getNotificationItemClass(alert)"
                                                    style="cursor: pointer"
                                                    v-for="(alert, idx) in alerts"
                                                    :key="idx"
                                                    v-on:click="onClickReadNoti(alert.id, alert.href)"
                                                >
                                                    <span class="img-notifi block-img">
                                                        <img :src="getAvatarSrc(alert.avatar)">
                                                    </span>
                                                    <span class="main-notifi" >
                                                        <div class="notifi-name"><strong>{{ alert.username }}</strong> {{ alert.description }}</div>
                                                        <div class="notifi-time">{{ formattedFromNow(alert.created_at) }}</div>
                                                    </span>
                                                </div>
                                            </template>
                                            <template v-else>
                                                <div class="none-item">Bạn không có thông báo nào.</div>
                                            </template>
                                            <!-- <div class="notification-item is-checked">
                                                <a href="" class="img-notifi block-img">
                                                    <img :src="avatarSrc" v-if="user">
                                                </a>
                                                <a href="" class="main-notifi" >
                                                    <div class="notifi-name"><span class="notifi-strong">bienvq</span> reacted to <span class="notifi-strong">your post</span> in the thread Thời tiết Horus with </div>
                                                    <div class="notifi-time">19 phút trước</div>
                                                </a>
                                            </div> -->
                                        </div>
                                        <div class="menu-footer menu-footer--split">
                                            <a href="" class="footer-link">Xem tất cả</a>
                                            <a href="" class="footer-link" v-on:click="onClickReadAllNotis()">Đánh dấu là đã đọc</a>
                                            <a href="" class="footer-link">Tùy chọn</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="p-navgroup p-discovery p-navEl ">
                            <div class="p-nav-item">
                                <a class="p-navgroup-link btn-dropdown-menu" @click="handleOnClick" style="align-items: center;display: flex;">
                                    <Search style="width: 1em; height: 1em; font-size: 16px; margin-right: 8px" />
                                    <span class="p-navgroup-linkText" style="font-size: 14px;">Tìm kiếm</span>
                                </a>
                                <div class="menu menu-search" data-menu="menu" aria-hidden="true">
                                    <span class="menu-arrow" ></span>
                                    <form action="/index.php?search/search" method="post" class="menu-content" data-xf-init="quick-search">
                                        <h3 class="menu-header">Tìm kiếm</h3>

                                        <div class="menu-row">
                                            <el-input 
                                                placeholder="Tìm kiếm..."
                                                class="input-account" 
                                            />
                                        </div>

                                        <div class="menu-row">
                                            <label class="iconic">
                                                <el-checkbox>
                                                    <span class="label-input">Chỉ tìm trong tiêu đề</span>
                                                </el-checkbox>
                                            </label>
                                        </div>

                                        <div class="menu-row">
                                            <div class="inputGroup">
                                                <span class="label-input">Bởi:</span>
                                                <el-select
                                                    multiple
                                                    filterable
                                                    remote
                                                    reserve-keyword
                                                    placeholder="Please enter a keyword"
                                                    style="width: 100%;"
                                                >
                                                    <el-option
                                                        v-for="item in options"
                                                        :key="item.value"
                                                        :label="item.label"
                                                        :value="item.value"
                                                    />
                                                </el-select>    
                                            </div>
                                        </div>
                                        <div class="menu-footer" style="align-items: right;">
                                            <el-button class="btn-search-footer" type="primary"><Search style="width: 1em; height: 1em; font-size: 16px; margin-right: 8px" />Tìm</el-button>
                                            <el-button class="btn-search-footer" color="#2577b1" >Tìm kiếm nâng cao</el-button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>
        </div>
        <div class="p-navSticky p-navSticky--primary header-mobile">
            <nav class="p-nav">
                <div class="p-nav-inner" style="align-items: center; justify-content: space-between;">
                    <div class="btn-drawer" style="display: inline-flex;">
                        <Menu style="width: 1em; height: 1em; font-size: 30px; cursor: pointer;" @click="drawer = true" />
                    </div>
                    <div class="p-nav-scroller hScroller">
                        <div class="hScroller-scroll">
                            <div class="p-header-logo p-header-logo--image">
                                <a href="/home">
                                    <img :src="logoSrc" srcset="" alt="Horus" width="90" height="" />
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="p-nav-opposite">
                        <div class="p-navgroup p-account p-navgroup--member" style="display: flex;">
                            <div class="p-nav-item p-navEl ">
                                <a class="p-navgroup-link btn-dropdown-menu" @click="handleOnClick" style="align-items: center;display: flex;">
                                    <span class="avatar avatar--xxs">
                                        <img :src="getAvatarSrc(user.avatar)" v-if="user" class="avatar-u46-s" width="48" height="48" loading="lazy" />
                                    </span>
                                </a>
                                <div class="dropdown-account menu menu-account menu--structural menu--medium">
                                    <span class="menu-arrow" style="left: 220.5px;"></span>
                                    <div class="menu-content">
                                        <el-tabs v-model="activeName" class="account-tabs" >
                                            <el-tab-pane class="abc" label="Tài khoản của bạn" name="first">
                                                <ul class="tabPanes">
                                                    <li class="is-active" role="tabpanel" id="_xfUid-accountMenu-1690771254" aria-expanded="true">
                                                        <div class="menu-row menu-row--alt">
                                                            <div class="contentRow">
                                                                <div class="contentRow-figure">
                                                                    <span class="avatarWrapper">
                                                                        <span class="avatar avatar--m" data-user-id="46" title="doanhpt">
                                                                            <img :src="getAvatarSrc(user.avatar)" v-if="user" class="avatar-u46-m" width="96" height="96" loading="lazy" />
                                                                        </span>
                                                                        <a class="avatarWrapper-update" href="/index.php?account/avatar" data-xf-click="overlay"><span>Sửa</span></a>
                                                                    </span>
                                                                </div>
                                                                <div class="contentRow-main">
                                                                    <el-row :gutter="0" class="">
                                                                        <el-col class="contentRow-header" :span="24">
                                                                            <a href="/index.php?members/doanhpt.46/" class="username" dir="auto" data-user-id="46">{{ getUserNameFromEmail() }}</a>
                                                                        </el-col>
                                                                        <el-col class="contentRow-lesser " :span="24">
                                                                            <span class="userTitle black-color" dir="auto">New member</span>
                                                                        </el-col>
                                                                        <el-col class="contentRow-lesser" :span="23">
                                                                            <span class="userTitle" dir="auto">Bài viết</span>
                                                                        </el-col>
                                                                        <el-col class="contentRow-lesser" :span="1">
                                                                            <a href="" class="userTitle">1</a>
                                                                        </el-col>
                                                                        <el-col class="contentRow-lesser" :span="23">
                                                                            <span class="userTitle" dir="auto">Reaction score</span>
                                                                        </el-col>
                                                                        <el-col class="contentRow-lesser" :span="1">
                                                                            <a href="" class="userTitle">1</a>
                                                                        </el-col>
                                                                        <el-col class="contentRow-lesser" :span="23">
                                                                            <span class="userTitle" dir="auto">Điểm thành tích</span>
                                                                        </el-col>
                                                                        <el-col class="contentRow-lesser" :span="1">
                                                                            <a href="" class="userTitle">1</a>
                                                                        </el-col>
                                                                    </el-row>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <hr class="menu-separator menu-separator--hard" />
                                                        <el-row :gutter="0" class="menu-account-item">
                                                            <el-col :span="12"><a href="/index.php?whats-new/news-feed" class="menu-linkRow">Luồng tin</a></el-col>
                                                            <el-col :span="12"><a href="/index.php?search/member&amp;user_id=46" class="menu-linkRow">Nội dung của bạn</a></el-col>
                                                            <el-col :span="12"><a href="/index.php?account/reactions" class="menu-linkRow">Reactions received</a></el-col>
                                                        </el-row>
                                                        <hr class="menu-separator" />
                                                        <el-row :gutter="0" class="menu-account-item">
                                                            <el-col :span="12"><a href="/index.php?account/account-details" class="menu-linkRow">Thông tin tài khoản</a></el-col>
                                                            <el-col :span="12"><a href="/index.php?account/security" class="menu-linkRow">Mật khẩu và bảo mật</a></el-col>
                                                            <el-col :span="12"><a href="/index.php?account/privacy" class="menu-linkRow">Bảo mật cá nhân</a></el-col>
                                                            <el-col :span="12"><a href="/index.php?account/preferences" class="menu-linkRow">Tùy chọn</a></el-col>
                                                            <el-col :span="12"><a href="/index.php?account/signature" class="menu-linkRow">Chữ ký</a></el-col>
                                                            <el-col :span="12"><a href="/index.php?account/following" class="menu-linkRow">Đang theo dõi</a></el-col>
                                                            <el-col :span="12"><a href="/index.php?account/ignored" class="menu-linkRow">Bỏ qua</a></el-col>
                                                        </el-row>
                                                        <hr class="menu-separator" />
                                                        <el-row :gutter="0" class="menu-account-item">
                                                            <el-col :span="24"><span style="cursor: pointer" class="menu-linkRow" @click="logout()">Thoát</span></el-col>
                                                        </el-row>
                                                        <form action="" method="post" class="menu-footer" >
                                                            <input type="hidden" name="_xfToken" value="1690771254,f45815ff17eb701b0baeb2649d871018" />
                                                            <el-input
                                                                :rows="1"
                                                                type="textarea"
                                                                placeholder="Cập nhật trạng thái..."
                                                                class="input-account"
                                                                @click="showBtnPost"
                                                            />
                                                            <div class="btn-post-account" v-if="showButton">
                                                                <el-button type="primary"><el-icon><Promotion /></el-icon>Post</el-button>
                                                            </div>
                                                        </form>
                                                    </li>

                                                </ul>
                                            </el-tab-pane>
                                            <el-tab-pane label="Bookmarks" name="second">
                                                <ul class="tabPanes">
                                                    <li role="tabpanel" id="_xfUid-accountMenuBookmarks-1690771254" data-href="/index.php?account/bookmarks-popup" data-load-target=".js-bookmarksMenuBody">
                                                        <el-select
                                                            class="select-account"
                                                            multiple
                                                            filterable
                                                            remote
                                                            reserve-keyword
                                                            placeholder="Please enter a keyword"
                                                        >
                                                            <el-option
                                                            v-for="item in options"
                                                            :key="item.value"
                                                            :label="item.label"
                                                            :value="item.value"
                                                            />
                                                        </el-select>
                                                        <div class="menu-footer menu-footer--close">
                                                            <a href="/index.php?account/bookmarks" class="footer-link">Xem tất cả…</a>
                                                        </div>
                                                    </li>
                                                </ul>
                                            </el-tab-pane>
                                        </el-tabs>
                                    </div>
                                </div>
                            </div>
                            <div class="p-nav-item p-navEl ">
                                <a class="p-navgroup-link btn-dropdown-menu" @click="handleOnClick" style="align-items: center;display: flex;" >
                                    <Message style="width: 1em; height: 1em; font-size: 16px" />
                                    <!-- <span class="count-notifi">99+</span> -->
                                </a>
                                <div class="menu menu--structural menu--medium">
                                    <span class="menu-arrow"></span>
                                    <div class="menu-content">
                                        <h3 class="menu-header">Trò chuyện</h3>
                                        <div class="js-convMenuBody">
                                            <div class="none-item">Bạn không có cuộc trò chuyện nào gần đây.</div>
                                        </div>
                                        <!-- <div class="js-convMenuBody">
                                            <div class="notification-item">
                                                <a href="" class="img-notifi block-img">
                                                    <img :src="getAvatarSrc(user.avatar)" v-if="user">
                                                </a>
                                                <a href="" class="main-notifi" >
                                                    <div class="notifi-name"><span class="notifi-strong">tessmasage</span></div>
                                                    <div class="notifi-with">Với: doanhpt, bienvq</div>
                                                    <div class="notifi-time">14 phút trước</div>
                                                </a>
                                            </div>
                                            <div class="notification-item">
                                                <a href="" class="img-notifi block-img">
                                                    <img :src="getAvatarSrc(user.avatar)" v-if="user">
                                                </a>
                                                <a href="" class="main-notifi" >
                                                    <div class="notifi-name"><span class="notifi-strong">tessmasage</span></div>
                                                    <div class="notifi-with">Với: doanhpt, bienvq</div>
                                                    <div class="notifi-time">14 phút trước</div>
                                                </a>
                                            </div>
                                        </div> -->
                                        <div class="menu-footer menu-footer--split">
                                            <a href="/index.php?account/bookmarks" class="footer-link">Xem tất cả</a>
                                            <a href="/index.php?account/bookmarks" class="footer-link">Bắt đầu trò chuyện mới</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="p-nav-item p-navEl ">
                                <a class="p-navgroup-link btn-dropdown-menu" @click="handleOnClick" style="align-items: center;display: flex;">
                                    <Bell style="width: 1em; height: 1em; font-size: 16px" />
                                    <span class="p-navgroup-linkText"></span>
                                    <span class="count-notifi" v-if="unreadAlertsCount > 0">{{ unreadAlertsCount }}</span>
                                </a>
                                <div class="menu menu--structural menu--medium" >
                                    <span class="menu-arrow"></span>
                                    <div class="menu-content">
                                        <h3 class="menu-header">Thông báo</h3>
                                        <div class="js-convMenuBody">
                                            <template v-if="alerts.length > 0">
                                                <div
                                                    :class="getNotificationItemClass(alert)"
                                                    style="cursor: pointer"
                                                    v-for="(alert, idx) in alerts"
                                                    :key="idx"
                                                    v-on:click="onClickReadNoti(alert.id, alert.href)"
                                                >
                                                    <span class="img-notifi block-img">
                                                        <img :src="getAvatarSrc(alert.avatar)">
                                                    </span>
                                                    <span class="main-notifi" >
                                                        <div class="notifi-name"><strong>{{ alert.username }}</strong> {{ alert.description }}</div>
                                                        <div class="notifi-time">{{ formattedFromNow(alert.created_at) }}</div>
                                                    </span>
                                                </div>
                                            </template>
                                            <template v-else>
                                                <div class="none-item">Bạn không có thông báo nào.</div>
                                            </template>
                                            <!-- <div class="notification-item is-checked">
                                                <a href="" class="img-notifi block-img">
                                                    <img :src="avatarSrc" v-if="user">
                                                </a>
                                                <a href="" class="main-notifi" >
                                                    <div class="notifi-name"><span class="notifi-strong">bienvq</span> reacted to <span class="notifi-strong">your post</span> in the thread Thời tiết Horus with </div>
                                                    <div class="notifi-time">19 phút trước</div>
                                                </a>
                                            </div> -->
                                        </div>
                                        <div class="menu-footer menu-footer--split">
                                            <a href="" class="footer-link">Xem tất cả</a>
                                            <a href="" class="footer-link" v-on:click="onClickReadAllNotis()">Đánh dấu là đã đọc</a>
                                            <a href="" class="footer-link">Tùy chọn</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="p-nav-item p-navEl">
                                <a class="p-navgroup-link btn-dropdown-menu" @click="handleOnClick" style="align-items: center;display: flex;">
                                    <Search style="width: 1em; height: 1em; font-size: 16px;" />
                                </a>
                                <div class="menu menu-search" data-menu="menu" aria-hidden="true">
                                    <span class="menu-arrow" ></span>
                                    <form action="/index.php?search/search" method="post" class="menu-content" data-xf-init="quick-search">
                                        <h3 class="menu-header">Tìm kiếm</h3>
    
                                        <div class="menu-row">
                                            <el-input 
                                                placeholder="Tìm kiếm..."
                                                class="input-account" 
                                            />
                                        </div>
    
                                        <div class="menu-row">
                                            <label class="iconic">
                                                <el-checkbox>
                                                    <span class="label-input">Chỉ tìm trong tiêu đề</span>
                                                </el-checkbox>
                                            </label>
                                        </div>
    
                                        <div class="menu-row">
                                            <div class="inputGroup">
                                                <span class="label-input">Bởi:</span>
                                                <el-select
                                                    multiple
                                                    filterable
                                                    remote
                                                    reserve-keyword
                                                    placeholder="Please enter a keyword"
                                                    style="width: 100%;"
                                                >
                                                    <el-option
                                                        v-for="item in options"
                                                        :key="item.value"
                                                        :label="item.label"
                                                        :value="item.value"
                                                    />
                                                </el-select>    
                                            </div>
                                        </div>
                                        <div class="menu-footer" style="align-items: right;">
                                            <el-button class="btn-search-footer" type="primary"><Search style="width: 1em; height: 1em; font-size: 16px; margin-right: 8px" />Tìm</el-button>
                                            <el-button class="btn-search-footer" color="#2577b1" >Tìm kiếm nâng cao</el-button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>
            <el-drawer
                v-model="drawer"
                title="Menu"
                direction="ltr"
                size="70%"
            >
            <template #header="">
                <span style="display: block;"><img :src="logoMobile" srcset="" alt="Horus" width="80" height="" /></span>
            </template>
                <el-menu class="menu-mobile">
                    <el-sub-menu index="1">
                        <template #title>
                            <span>Employee</span>
                        </template>
                        <el-menu-item><a href="/employee">List</a></el-menu-item>
                        <el-menu-item><a href="/employee-workday-report">Workday Report</a></el-menu-item>
                        <el-menu-item v-if="user && user.id==107"><a href="/employee">List</a></el-menu-item>
                        <el-menu-item v-if="user && user.department_id==7"><a href="/employee">List</a></el-menu-item>
                    </el-sub-menu>
                    <el-sub-menu index="2">
                        <template #title>
                            <span>Yêu cầu</span>
                            <span v-if="petition_count > 0 || deadline_mod_count > 0" class="count-notifi custom">{{ petition_count + deadline_mod_count }}</span>
                        </template>
                        <el-menu-item>
                            <a href="/petitions">
                                Yêu cầu
                                <span v-if="petition_count > 0" class="count-notifi custom-item">{{ petition_count }}</span>
                            </a>
                        </el-menu-item>
                        <el-menu-item>
                            <a href="/tasks/deadline-modification">
                                Deadline Modification
                                <span v-if="deadline_mod_count > 0" class="count-notifi custom-item">{{ deadline_mod_count }}</span>
                            </a>
                        </el-menu-item>
                        <el-menu-item v-if="user && ([46,161,194,63].includes(user.id) || user.department_id==12 || user.department_id==8 )">
                            <a href="/purchase">
                                Purchase
                            </a>
                        </el-menu-item>
                    </el-sub-menu>
                    <el-sub-menu index="3">
                        <template #title>
                            <span>Chấm công</span>
                            <span class="count-notifi custom" v-if="missedTimesheets > 0">{{ missedTimesheets }}</span>
                        </template>
                        <el-menu-item>
                            <a href="/timesheets">
                                Bảng chấm công
                                <span class="count-notifi custom-item" v-if="missedTimesheets > 0">{{ missedTimesheets }}</span>
                            </a>
                        </el-menu-item>
                        <el-menu-item>
                            <a href="/timesheets/report">Thống kê</a>
                        </el-menu-item>
                    </el-sub-menu>
                    <el-sub-menu index="4">
                        <template #title>
                            <span>Công việc</span>
                        </template>
                        <el-menu-item v-if="is_authority===true"><a href="/projects">Dự án</a></el-menu-item>
                        <!-- <el-menu-item v-if="is_authority===true || add_permission===true"><a href="/tasks">Quản lý công việc</a></el-menu-item> -->
                        <el-menu-item><a href="/tasks">Quản lý công việc</a></el-menu-item>
                        <el-menu-item v-if="is_authority===true"><a href="/task-gantt">Quản lý công việc Gantt</a></el-menu-item>
                        <el-menu-item><a href="/department/tasks">Việc bộ phận</a></el-menu-item>
                        <el-menu-item><a href="/me/tasks">Việc của tôi</a></el-menu-item>
                        <el-menu-item><a href="/weighted/fluctuation">Lịch sử trọng số</a></el-menu-item>
                        <el-menu-item><a href="/report">Thống kê</a></el-menu-item>
                        <el-menu-item><a href="/working-time" v-if="user && ([46,161,51,63,90].includes(user.id))">Chi phí dự án</a></el-menu-item>
                    </el-sub-menu>
                    <el-sub-menu index="5">
                        <template #title>
                            <span>Bug</span>
                            <span class="count-notifi custom" v-if="department_bugs > 0">{{ department_bugs }}</span>
                        </template>
                        <el-menu-item><a :href="getDepartmentIssuesLink()">Bộ phận<span class="count-notifi custom-item" v-if="department_bugs > 0">{{ department_bugs }}</span></a></el-menu-item>
                        <el-menu-item><a :href="getPersonalIssuesLink()">Cá nhân<span class="count-notifi custom-item" v-if="my_bugs > 0">{{ my_bugs }}</span></a></el-menu-item>
                    </el-sub-menu>
                    <el-sub-menu index="6">
                        <template #title>
                            <span>Data Analytics</span>
                        </template>
                        <el-menu-item><a href="http://192.168.10.173/in-out" target="_blank">Tracking In/Out</a></el-menu-item>
                        <el-menu-item><a href="/tracking-game">Tracking Game</a></el-menu-item>
                    </el-sub-menu>
                    <el-sub-menu index="7">
                        <template #title>
                            <span>Review</span>
                            <span class="count-notifi custom" v-if="review_count > 0">{{ review_count }}</span>
                        </template>
                        <el-menu-item v-if="user && [46,63,82,90,107,51].includes(user.id)"><a href="/employee/review/list" >Danh sách đánh giá</a></el-menu-item>
                        <el-menu-item v-if="user && (user!.position >= 1 || is_mentor)"><a href="/employee/review" >Nhân viên</a></el-menu-item>
                        <el-menu-item v-if="user && user.position < 2"><a href="/employee/review/personal" >Cá nhân</a></el-menu-item>
                    </el-sub-menu>
                    <el-menu-item><a href="/announcements">News & Updates</a></el-menu-item>
                    <el-sub-menu index="8">
                        <template #title>
                            <span>Lịch sử</span>
                        </template>
                        <el-menu-item v-if="user && user.id==46"><a href="/journal/company" >Công ty</a></el-menu-item>
                        <el-menu-item><a href="/journal/department">Bộ phận</a></el-menu-item>
                        <el-menu-item><a href="/journal/game">Game</a></el-menu-item>
                    </el-sub-menu>
                    <el-menu-item><a href="/calendar">Lịch</a></el-menu-item>
                    <el-menu-item><a @click="handleForumRedirectly">Forum</a></el-menu-item>
                    <el-menu-item><a href="/order">Đặt đồ ăn</a></el-menu-item>
                </el-menu>
                <div class="switch-company" style="align-items: center;display: flex;">
                    <el-radio-group v-model="company" v-if="user && [46,63,107,161].includes(user.id)" @change="onChangeCompany">
                        <el-radio-button label="Production" />
                        <el-radio-button label="AI" />
                    </el-radio-group>
                </div>
            </el-drawer>
        </div>
    </div>
</template>
<script lang="ts" setup>
import axios from 'axios';
import { onMounted, computed, ref } from 'vue';
import dayjs from 'dayjs';
import relativeTime from 'dayjs/plugin/relativeTime';
import { CaretBottom, Bell, Search, Message, Promotion, Menu } from '@element-plus/icons-vue'
import { ElMessageBox } from 'element-plus'
import type { Action } from 'element-plus'
import { formatPrice } from '../Helper/format';

dayjs.extend(relativeTime)

interface User {
    id: number,
    avatar: string,
    email: string,
    position: number,
    department_id: number
};
interface Alert {
    id: number,
    username: string,
    avatar: string,
    created_at: string,
    description: string,
    href: string,
    read_date: number
};

const drawer = ref(false)
const petition_count = ref(0)
const deadline_mod_count = ref(0)
const department_bugs = ref(0)
const my_bugs = ref(0)
const assigned_department_bugs = ref(0)
const assigned_my_bugs = ref(0)
const created_department_bugs = ref(0)
const created_my_bugs = ref(0)
const activeName = ref('first')
const is_authority = ref(false)
const add_permission = ref(false)
const review_count = ref(0)
const is_mentor = ref(false)
const user = ref<User>();
const handleOnClick = (event: MouseEvent) => {
    const targetElement = event.currentTarget as Element;

    const menuElement = targetElement.nextElementSibling;
    if (menuElement) {
        menuElement.classList.toggle('show-menu-dropdown');
    }

    const parentElement = targetElement.closest('.p-navEl');
    if (parentElement) {
        parentElement.classList.toggle('is-menuOpen');
    }

    const allMenus = document.querySelectorAll('.menu');
    allMenus.forEach((menu) => {
        if (menu !== menuElement && menu.classList.contains('show-menu-dropdown')) {
            menu.classList.remove('show-menu-dropdown');
            const parentElement = menu.closest('.p-navEl');
            if (parentElement) {
                parentElement.classList.remove('is-menuOpen');
            }
        }
    });
};
const handleForumRedirectly = () => {
    axios.post('/api/forum/redirectly')
    .then(response => {
        // Open the updated URL in a new tab
        window.open(response.data, '_blank');
    })
    .catch(error => {
    })
}
const missedTimesheets = ref();
const getCountMissedTimesheets = () => {
    axios.post('/api/home/get-process-attendance', {
        // "start_date": dayjs().startOf('month').format('YYYY/MM/DD'),
        // "end_date": dayjs().endOf('month').format('YYYY/MM/DD'),
        "start_date": dayjs().subtract(7, 'day').startOf('day').format('YYYY/MM/DD'),
        "end_date": dayjs().add(1, 'day').endOf('day').format('YYYY/MM/DD'),
        "user_id": user.value?.id,
    })
    .then(response => {
        const data = response.data;
        missedTimesheets.value = data.count.missed_timesheets_log
    })
    .catch(error => {

    });
}
const getUserNameFromEmail = () => {
    const [name] = user.value?.email?.split("@") || "unknown";

    return name;
}
const getAvatarSrc = (avatar: string) => {
    return '/image/' + avatar;
};
const logoSrc = computed(() => {
    return '/avatar/logo.png';
});
const logoMobile = computed(() => {
    return '/avatar/thumb-HORUS.png';
});
document.addEventListener('click', (event) => {
    const target = event.target as Element; // Type assertion to Element

    if (!target.closest('.btn-dropdown-menu') && !target.closest('.menu')) {
        const allMenus = document.querySelectorAll('.menu');
        allMenus.forEach((menu) => {
            if (menu.classList.contains('show-menu-dropdown')) {
                menu.classList.remove('show-menu-dropdown');
                const parentElement = menu.closest('.p-navEl');
                if (parentElement) {
                    parentElement.classList.remove('is-menuOpen');
                }
            }
        });
    }
});
const showButton = ref(false)
const showBtnPost = () => {
    showButton.value = true;
}
const options = ref([
    {
        label: 'option a',
        key: 1,
        value: 1
    },
    {
        label: 'option b',
        key: 2,
        value: 2
    },
]);
const alerts = ref<Alert[]>([])
const getNotificationItemClass = (alert: Alert) => {
    return 'notification-item' + (alert.read_date > 0 ? ' is-checked' : '');
};
const formattedFromNow = (date: string | undefined) => {
    let dateOject = dayjs(date, 'YYYY/MM/DD HH:mm:ss');
    return dayjs(dateOject).fromNow();
}
const onClickReadNoti = (id: number, href: string) => {
    axios.post('/api/employee/read-noti', {id: id})
    .then(response => {
        getAlerts()

        const newTab = window.open(href, '_blank');
        newTab?.focus();
    })
    .catch(error => {
        console.log(error)
    })
}
const onClickReadAllNotis = () => {
    axios.post('/api/employee/read-all-notis')
    .then(response => {
        getAlerts()
    })
    .catch(error => {
        console.log(error)
    })
}
const getAlerts = () => {
    //get alerts
    axios.get('/api/employee/get-notifications').then(response => {
        alerts.value = response.data
    })
}
const unreadAlertsCount = computed(() => {
    return alerts.value.filter(alert => alert.read_date === 0).length;
});
const getDepartmentIssuesLink = () => {
    // if (user.value?.department_id === 2) {
    if (user.value?.department_id != 5) {
        return '/issues/department-assigned'
    }

    return '/issues/department-self-created'
}
const getPersonalIssuesLink = () => {
    if (user.value?.department_id === 2) {
        return '/issues/personal-assigned'
    }

    return '/issues/personal-self-created'
}

const company = ref<String>('');
const onChangeCompany = () => {
    const departmentMap = {
        'Production': 1,
        'AI': 12,
    };
    const requestBody = { department_id: departmentMap[company.value as keyof typeof departmentMap] };

    axios.post('/api/home/switch-company', requestBody)
    .then(response => {
        window.location.reload();
    })
    .catch(error => {
    });
}
const audio = ref(new Audio('/audios/buzz.mp3'))
onMounted(() => {
    axios.get('/api/whoami').then(response => {
        user.value = response.data.user

        window.Echo.private('buzz.user.'+response.data.user.id)
        .listen('.notification', (e: any) => {
            audio.value.play();

            let nameNotification = e.nameNotification + ' is calling...';
            ElMessageBox.alert(nameNotification, 'ALERT', {
                confirmButtonText: 'OK',
                showClose: false,
                callback: (action: Action) => {
                    audio.value.pause();
                },
            });
        });
        window.Echo.private('buzz.calendar.'+response.data.user.id)
        .listen('.notification', (e: any) => {
            audio.value.play();

            let nameNotification = 'Lịch "' + e.nameNotification + ' " còn 5 phút nữa sẽ bắt đầu!';
            ElMessageBox.alert(nameNotification, 'ALERT', {
                confirmButtonText: 'OK',
                showClose: false,
                callback: (action: Action) => {
                    audio.value.pause();
                },
            });
        });
        // popup nhắc nhở đóng tiền ăn
        window.Echo.private('noti-payment.user.'+response.data.user.id)
        .listen('.notification-payment', (res: any) => {
            if(res.total_amount && res.total_amount > 0){
                let nameNotification = 'Tiền cơm tuần này của bạn là ' + formatPrice(res.total_amount);
                ElMessageBox.alert(nameNotification, 'Thông báo đóng tiền cơm tuần.', {
                    confirmButtonText: 'Đóng tiền',
                    showClose: false,
                    callback: (action: Action) => {
                        window.location.href = '/order/#tab4';
                    },
                });  
            }
        });

        is_authority.value = response.data.is_authority
        add_permission.value = response.data.add_permission
        petition_count.value = response.data.petition_count
        deadline_mod_count.value = response.data.deadline_mod_count
        review_count.value = response.data.review_count
        is_mentor.value = response.data.is_mentor
        company.value = (response.data.user.department_id == 12)? 'AI' : 'Production';
        let bugs = response.data.bugs
        department_bugs.value = bugs.total_department_bugs
        my_bugs.value = bugs.total_my_bugs
        created_department_bugs.value = bugs.created_total_department_bugs
        created_my_bugs.value = bugs.created_total_my_bugs
        assigned_department_bugs.value = bugs.assigned_total_department_bugs
        assigned_my_bugs.value = bugs.assigned_total_my_bugs
        
        getCountMissedTimesheets()
    })
    
    getAlerts()
})
const logout = () => {
    axios.post('/logout')
    .then(response => {
        window.location.href="/login"
    });
}
</script>