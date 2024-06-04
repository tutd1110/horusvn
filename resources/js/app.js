import './bootstrap';

import i18n from "./i18n";
import { createApp } from 'vue/dist/vue.esm-bundler';
import Antd from 'ant-design-vue';
import 'ant-design-vue/dist/antd.css';

import login from "../assets/js/components/login/login.vue";
import layout from "../assets/js/components/layout/layout.vue";
import home from "../assets/js/components/home/home.vue";
import log from "../assets/js/components/log/log.vue";
import MenuBar from "../assets/js/components/MenuBar/MenuBar.vue";
import BreadCrumb from "../assets/js/components/BreadCrumb/BreadCrumb.vue";
import employee from "../assets/js/components/employee/index.vue";
import EmployeeWorkdayReport from "../assets/js/components/employee/EmployeeWorkdayReport.vue";
import EmployeeReview from "../assets/js/components/employee/review.vue";
import EmployeeReviewList from "../assets/js/components/employee/ReviewList.vue";
import EmployeeReviewPersonal from "../assets/js/components/employee/ReviewPersonal.vue";
import report from "../assets/js/components/report/report.vue";
import project from "../assets/js/components/project/project.vue";
import task from "../assets/js/components/task/task.vue";
import DeadlineModification from "../assets/js/components/task/DeadlineModification.vue";
import departmentWithTask from "../assets/js/components/department/task/task.vue";
import meWithTask from "../assets/js/components/me/task/task.vue";
import weightedFluctuation from "../assets/js/components/weighted/fluctuation.vue";
import petition from "../assets/js/components/petition/petition.vue";
import timesheet from "../assets/js/components/timesheet/timesheet.vue";
import TimesheetReport from "../assets/js/components/timesheet/TimesheetReport.vue";
import AnnouncementsPost from "../assets/js/components/announcements/post.vue";
import AnnouncementsList from "../assets/js/components/announcements/list.vue";
import JournalCompany from "../assets/js/components/journal/company.vue";
import JournalDepartment from "../assets/js/components/journal/department.vue";
import JournalGame from "../assets/js/components/journal/game.vue";
import calendar from "../assets/js/components/calendar/calendar.vue";
import gantt from "../assets/js/components/task/gantt.vue";
import TrackingGame from "../assets/js/components/tracking/TrackingGame.vue";
import Order from "../assets/js/components/order/Order.vue";
import Statistial from "../assets/js/components/statistial/Statistial.vue";

import DepartmentSelfCreate from "../assets/js/components/Issues/Department/SelfCreated.vue";
import DepartmentAssigned from "../assets/js/components/Issues/Department/Assigned.vue";
import PersonalSelfCreate from "../assets/js/components/Issues/Personal/SelfCreated.vue";
import PersonalAssigned from "../assets/js/components/Issues/Personal/Assigned.vue";

import Purchase from "../assets/js/components/purchase/purchase.vue";
import WorkingTime from "../assets/js/components/working/WorkingTime.vue";

import Company from "../assets/js/components/management/Company.vue";
import Department from "../assets/js/components/management/Department.vue";

import Warrior from "../assets/js/components/warrior/Warrior.vue";

const app = createApp({
    components: {
        'login' : login,
        'layout' : layout,
        'home' : home,
        'log' : log,
        'MenuBar' : MenuBar,
        'BreadCrumb' : BreadCrumb,
        'employee' : employee,
        'EmployeeWorkdayReport': EmployeeWorkdayReport,
        'EmployeeReview': EmployeeReview,
        'EmployeeReviewList': EmployeeReviewList,
        'EmployeeReviewPersonal': EmployeeReviewPersonal,
        'report' : report,
        'project' : project,
        'task' : task,
        'DeadlineModification': DeadlineModification,
        'departmentWithTask' : departmentWithTask,
        'meWithTask' : meWithTask,
        'weightedFluctuation': weightedFluctuation,
        'petition' : petition,
        'timesheet' : timesheet,
        'TimesheetReport' : TimesheetReport,
        'AnnouncementsPost' : AnnouncementsPost,
        'AnnouncementsList' : AnnouncementsList,
        'JournalCompany': JournalCompany,
        'JournalDepartment': JournalDepartment,
        'JournalGame': JournalGame,
        'DepartmentSelfCreate': DepartmentSelfCreate,
        'DepartmentAssigned': DepartmentAssigned,
        'PersonalSelfCreate': PersonalSelfCreate,
        'PersonalAssigned': PersonalAssigned,
        'calendar': calendar,
        'gantt': gantt,
        'TrackingGame': TrackingGame,
        'order': Order,
        'Statistial':Statistial,
        'Purchase':Purchase,
        'WorkingTime': WorkingTime,
        'Company': Company,
        'Department': Department,
        'Warrior': Warrior,
    }
});

app.use(Antd).use(i18n).mount("#app");