import dayjs from 'dayjs';

export const handleUserTimesheet = (data, dateFormat, formState, current_start_date, current_end_date, screen) => {
    try {
        var newData = [];

        let current_work_day = data.current_work_day
        let work_day = data.work_day
        let users = data.users

        users.forEach(function(item) {
            let date_official = "Thử việc";
            let total_work_date = "Thử việc";
            let years = 0;
            let currentWarTitle = "Soldier";
            let nextWarTitle = "Warrior 1";
            let timeKeepTitle = 0;
            let timeNextTitle = 0;
            if (item.date_official) {
                let obj = getDuration(dayjs(), dayjs(item.date_official));
                total_work_date = obj.string
                years = obj.total_years

                date_official = dayjs(item.date_official).format(dateFormat)
            }

            let total_time_ot_war = Number(item.go_early_sum)
                + Number(item.leave_late_sum)
                + Number(item.extra_warrior_time)
                - Number(item.non_office_time_goouts)
            total_time_ot_war = total_time_ot_war > 0 ? convertSecondToHour(total_time_ot_war) : 0

            let currentWorkdayOnWarrior = (current_work_day.expectWorkDays);
            let expectWorkdayOnWarrior = (work_day.expectWorkDays);
            
            let expectWorkdayW1 = expectWorkdayOnWarrior*2;
            let expectWorkdayW2 = expectWorkdayOnWarrior*3;
            let expectWorkdayW3 = expectWorkdayOnWarrior*4;

            let workdayW1 = expectWorkdayOnWarrior*2;
            let workdayW2 = expectWorkdayOnWarrior*3;
            let workdayW3 = expectWorkdayOnWarrior*4;

            if (formState.value.start_date == current_start_date
                && formState.value.end_date == current_end_date
                || !formState.value.start_date && !formState.value.end_date) {
                workdayW1 = currentWorkdayOnWarrior*2;
                workdayW2 = currentWorkdayOnWarrior*3;
                workdayW3 = currentWorkdayOnWarrior*4;
            }

            //employee that has working under 3 years
            if (total_time_ot_war >= workdayW1 && total_time_ot_war < workdayW2) {
                currentWarTitle = "Warrior 1";
                nextWarTitle = "Warrior 2";
                timeKeepTitle = expectWorkdayW1 - total_time_ot_war
                timeNextTitle = expectWorkdayW2 - total_time_ot_war
            } else if (total_time_ot_war >= workdayW2 && total_time_ot_war < workdayW3) {
                currentWarTitle = "Warrior 2";
                nextWarTitle = "Warrior 3";
                timeKeepTitle = expectWorkdayW2 - total_time_ot_war
                timeNextTitle = expectWorkdayW3 - total_time_ot_war
            } else if (total_time_ot_war >= workdayW3) {
                currentWarTitle = "Warrior 3";
                nextWarTitle = "Warrior 3";
                timeKeepTitle = expectWorkdayW3 - total_time_ot_war
                timeNextTitle = expectWorkdayW3 - total_time_ot_war
            } else {
                timeNextTitle = expectWorkdayW1 - total_time_ot_war
            }

            if (years >= 3) { //employee that has working over 3 years
                //workday on Warrior 1
                workdayW1 = workdayW1/2;
                workdayW2 = workdayW1*2;
                workdayW3 = workdayW1*3;

                if (total_time_ot_war >= workdayW1 && total_time_ot_war < workdayW2) {
                    currentWarTitle = "Warrior 1";
                    nextWarTitle = "Warrior 2";
                    timeKeepTitle = workdayW1 - total_time_ot_war
                    timeNextTitle = workdayW2 - total_time_ot_war
                } else if (total_time_ot_war >= workdayW2 && total_time_ot_war < workdayW3) {
                    currentWarTitle = "Warrior 2";
                    nextWarTitle = "Warrior 3";
                    timeKeepTitle = workdayW2 - total_time_ot_war
                    timeNextTitle = workdayW3 - total_time_ot_war
                } else if (total_time_ot_war >= workdayW3) {
                    currentWarTitle = "Warrior 3";
                    nextWarTitle = "Warrior 3";
                    timeKeepTitle = workdayW3 - total_time_ot_war
                    timeNextTitle = workdayW3 - total_time_ot_war
                } else {
                    timeNextTitle = workdayW1 - total_time_ot_war
                }
            }

            let startDate = current_start_date
            let endDate = current_end_date
            if (formState.value.start_date && formState.value.end_date) {
                startDate = formState.value.start_date
                endDate = formState.value.end_date
            }

            let countSaturdayFilterRange = countSaturdayInRange(dayjs(startDate), dayjs(endDate))
            let countSaturdayCurrentRange = countSaturdayInRange(dayjs().startOf('month'), dayjs())

            let diff_work_day = 1
            if (expectWorkdayOnWarrior > currentWorkdayOnWarrior) {
                diff_work_day = (expectWorkdayOnWarrior+(countSaturdayFilterRange*0.5)) - ((currentWorkdayOnWarrior+(countSaturdayCurrentRange*0.5))-1)
            }

            let avgHoldTitle = timeKeepTitle ? (timeKeepTitle/(diff_work_day)).toFixed(2) : 0;
            let avgNextTitle = timeNextTitle ? (timeNextTitle/(diff_work_day)).toFixed(2) : 0;

            let value = {
                id: item.id,
                fullname: item.fullname,
                date_official: date_official,
                total_work_date: total_work_date,
                go_early_sum: convertSecondToHour(item.go_early_sum),
                late_sum: convertSecondToHour(item.late_sum),
                late_sum_none_petition: convertSecondToHour(item.late_sum_none_petition),
                late_count: item.late_count,
                pe_late_count: item.pe_late_count,
                early_sum: convertSecondToHour(item.early_sum),
                early_count: item.early_count,
                leave_late_sum: convertSecondToHour(item.leave_late_sum),
                pe_early_count: item.pe_early_count,
                total_late_nd_early: convertSecondToHour(item.total_late_nd_early),
                workday_late_nd_early: (item.total_late_nd_early/60/60/8).toFixed(2),
                office_goouts: item.office_goouts,
                office_time_goouts: convertSecondToHour(item.office_time_goouts),
                non_office_goouts: item.non_office_goouts,
                non_office_time_goouts: convertSecondToHour(item.non_office_time_goouts),
                paid_leave: item.paid_leave,
                un_paid_leave: item.un_paid_leave,
                extra_warrior_time: convertSecondToHour(item.extra_warrior_time),
                workday_extra_warrior_time: (item.extra_warrior_time/60/60/8).toFixed(2),
                total_time_ot_war: total_time_ot_war,
                extra_workday: item.extra_workday,
                origin_workday: roundNumber(item.origin_workday),
                // minus paid_workday when leave_holiday in holiday
                paid_workday: roundNumber(item.paid_workday + work_day.workDayHoliday - item.leave_holiday),
                rate_late: calculateRateLate(item),
                current_title: currentWarTitle,
                time_keep_title: timeKeepTitle.toFixed(2),
                avg_hold_title: avgHoldTitle,
                next_title: nextWarTitle,
                time_next_title: timeNextTitle.toFixed(2),
                avg_next_title: avgNextTitle,
            };
            newData.push(value);
        });
        
        let newDataCopy = [...newData];
        if (screen === 'timesheet') {
            const sumObj = {
                id: 0,
                fullname: 'Total',
                date_official: '',
                total_work_date: '',
                current_title: '',
                next_title: '',
                late_count: newData.reduce((acc, item) => {
                    const value = parseFloat(item.late_count);
                    return !isNaN(value) ? acc + value : acc;
                }, 0).toFixed(0),
                pe_late_count: newData.reduce((acc, item) => {
                    const value = parseFloat(item.pe_late_count);
                    return !isNaN(value) ? acc + value : acc;
                }, 0).toFixed(0),
                late_sum: newData.reduce((acc, item) => {
                    const value = parseFloat(item.late_sum);
                    return !isNaN(value) ? acc + value : acc;
                }, 0).toFixed(2),
                late_sum_none_petition: newData.reduce((acc, item) => {
                    const value = parseFloat(item.late_sum_none_petition);
                    return !isNaN(value) ? acc + value : acc;
                }, 0).toFixed(2),
                go_early_sum: newData.reduce((acc, item) => {
                    const value = parseFloat(item.go_early_sum);
                    return !isNaN(value) ? acc + value : acc;
                }, 0).toFixed(2),
                early_count: newData.reduce((acc, item) => {
                    const value = parseFloat(item.early_count);
                    return !isNaN(value) ? acc + value : acc;
                }, 0).toFixed(0),
                pe_early_count: newData.reduce((acc, item) => {
                    const value = parseFloat(item.pe_early_count);
                    return !isNaN(value) ? acc + value : acc;
                }, 0).toFixed(0),
                early_sum: newData.reduce((acc, item) => {
                    const value = parseFloat(item.early_sum);
                    return !isNaN(value) ? acc + value : acc;
                }, 0).toFixed(2),
                leave_late_sum: newData.reduce((acc, item) => {
                    const value = parseFloat(item.leave_late_sum);
                    return !isNaN(value) ? acc + value : acc;
                }, 0).toFixed(2),
                office_goouts: newData.reduce((acc, item) => {
                    const value = parseFloat(item.office_goouts);
                    return !isNaN(value) ? acc + value : acc;
                }, 0).toFixed(0),
                office_time_goouts: newData.reduce((acc, item) => {
                    const value = parseFloat(item.office_time_goouts);
                    return !isNaN(value) ? acc + value : acc;
                }, 0).toFixed(0),
                total_late_nd_early: newData.reduce((acc, item) => {
                    const value = parseFloat(item.total_late_nd_early);
                    return !isNaN(value) ? acc + value : acc;
                }, 0).toFixed(2),
                workday_late_nd_early: newData.reduce((acc, item) => {
                    const value = parseFloat(item.workday_late_nd_early);
                    return !isNaN(value) ? acc + value : acc;
                }, 0).toFixed(2),
                origin_workday: newData.reduce((acc, item) => {
                    const value = parseFloat(item.origin_workday);
                    return !isNaN(value) ? acc + value : acc;
                }, 0).toFixed(2),
                extra_workday: newData.reduce((acc, item) => {
                    const value = parseFloat(item.extra_workday);
                    return !isNaN(value) ? acc + value : acc;
                }, 0).toFixed(2),
                workday_extra_warrior_time: newData.reduce((acc, item) => {
                    const value = parseFloat(item.workday_extra_warrior_time);
                    return !isNaN(value) ? acc + value : acc;
                }, 0).toFixed(2),
                extra_warrior_time: newData.reduce((acc, item) => {
                    const value = parseFloat(item.extra_warrior_time);
                    return !isNaN(value) ? acc + value : acc;
                }, 0).toFixed(2),
                total_time_ot_war: newData.reduce((acc, item) => {
                    const value = parseFloat(item.total_time_ot_war);
                    return !isNaN(value) ? acc + value : acc;
                }, 0).toFixed(2),
                paid_leave: newData.reduce((acc, item) => {
                    const value = parseFloat(item.paid_leave);
                    return !isNaN(value) ? acc + value : acc;
                }, 0).toFixed(2),
                un_paid_leave: newData.reduce((acc, item) => {
                    const value = parseFloat(item.un_paid_leave);
                    return !isNaN(value) ? acc + value : acc;
                }, 0).toFixed(2),
                paid_workday: newData.reduce((acc, item) => {
                    const value = parseFloat(item.paid_workday);
                    return !isNaN(value) ? acc + value : acc;
                }, 0).toFixed(2),
                non_office_goouts: newData.reduce((acc, item) => {
                    const value = parseFloat(item.non_office_goouts);
                    return !isNaN(value) ? acc + value : acc;
                }, 0).toFixed(2),
                non_office_time_goouts: newData.reduce((acc, item) => {
                    const value = parseFloat(item.non_office_time_goouts);
                    return !isNaN(value) ? acc + value : acc;
                }, 0).toFixed(2),
                rate_late: '',
                time_keep_title: '',
                avg_hold_title: '',
                time_next_title: '',
                avg_next_title: '',
            };

            newDataCopy = [{...sumObj}, ...newDataCopy.map(item => ({ ...item }))];
        }

        return newDataCopy;
    } catch(err) {
        console.log(err.message)
    } 
}

const getDuration = (currentDate, dateCompare) => {
    const years = currentDate.diff(dateCompare, 'year');
    const months = currentDate.diff(dateCompare, 'month') - years * 12;
    const days = currentDate.diff(dateCompare.add(years, 'year').add(months, 'month'), 'day');

    let strYear = years ? years+" năm " : "";
    let strMonth = months ? months+" tháng " : "";

    let string = strYear + strMonth + days + " ngày";

    return {"total_years":years, "string":string};
}

const countSaturdayInRange = (startDate, endDate) => {
    let count = 0;

    const daysDiff = endDate.diff(startDate, 'day');
    for (let i = 0; i <= daysDiff; i++) {
        const currentDate = startDate.add(i, 'day');
        if (currentDate.day() === 6) { // 6 represents Saturday
            count++;
        }
    }

    return count;
}

const convertSecondToHour = (seconds) => {
    return roundNumber((seconds/60/60));
}

const roundNumber = (number) => {
    return number.toFixed(2);
}

const calculateRateLate = (item) => {
    if (!item.late_count || item.late_count === 0) {
        return 0;
    }

    const lateCount = item.late_count;
    const totalWorkdays = item.origin_workday + item.extra_workday;
    if (totalWorkdays === 0) {
        return 0;
    }

    return ((lateCount / totalWorkdays) * 100).toFixed(2);
}