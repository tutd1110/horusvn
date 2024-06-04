import dayjs from 'dayjs';

export const handleStatistialWarrior = (data:any, start_date:string, end_date:string, current_start_date:string, current_end_date:string,sort='desc',type='') => {
    console.log(type)
    
    try {
        var newData:any = [];

        let current_work_day = data.current_work_day
        let work_day = data.work_day
        let users = data.users

        users.forEach(function(item:any) {
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
            }

            let countWarrior1 = 0; // so thang dat warrior1
            let countWarrior2 = 0; // so thang dat warrior2
            let countWarrior3 = 0; // so thang dat warrior3

            // calculate warrior time
            Object.values(item.timesheets).forEach(function(timesheet:any, indx){
                let monTimesheet = indx + 1;
                let currentWorkdayOnWarrior = Number(current_work_day.expectWorkDays);
                let expectWorkdayOnWarrior = Number(work_day[monTimesheet].expectWorkDays);
                
                let expectWorkdayW1 = expectWorkdayOnWarrior*2;
                let expectWorkdayW2 = expectWorkdayOnWarrior*3;
                let expectWorkdayW3 = expectWorkdayOnWarrior*4;
    
                let workdayW1 = expectWorkdayOnWarrior*2;// mốc để đạt warrior 1 ở range thời gian hiện tại
                let workdayW2 = expectWorkdayOnWarrior*3;// mốc để đạt warrior 2 ở range thời gian hiện tại
                let workdayW3 = expectWorkdayOnWarrior*4;// mốc để đạt warrior 3 ở range thời gian hiện tại

                if(Object.keys(timesheet).length){
                    let total_time_ot_war:any = Number(timesheet.go_early_sum)
                        + Number(timesheet.leave_late_sum)
                        + Number(timesheet.extra_warrior_time)
                        - Number(timesheet.non_office_time_goouts)
                    total_time_ot_war = total_time_ot_war > 0 ? convertSecondToHour(total_time_ot_war) : 0
        
                    if (start_date == current_start_date
                        && end_date == current_end_date
                        || !start_date && !end_date) {
                        workdayW1 = currentWorkdayOnWarrior*2;
                        workdayW2 = currentWorkdayOnWarrior*3;
                        workdayW3 = currentWorkdayOnWarrior*4;
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
                           
                            countWarrior1 += 1;
                        } else if (total_time_ot_war >= workdayW2 && total_time_ot_war < workdayW3) {
                            currentWarTitle = "Warrior 2";
                            nextWarTitle = "Warrior 3";
                            timeKeepTitle = workdayW2 - total_time_ot_war
                            timeNextTitle = workdayW3 - total_time_ot_war
                            
                            countWarrior2 += 1;
                        } else if (total_time_ot_war >= workdayW3) {
                            currentWarTitle = "Warrior 3";
                            nextWarTitle = "Warrior 3";
                            timeKeepTitle = workdayW3 - total_time_ot_war
                            timeNextTitle = workdayW3 - total_time_ot_war

                            countWarrior3 += 1;
                        } else {
                            timeNextTitle = workdayW1 - total_time_ot_war
                        }
                    }else{
                        //employee that has working under 3 years
                        if (total_time_ot_war >= workdayW1 && total_time_ot_war < workdayW2) {
                            currentWarTitle = "Warrior 1";
                            nextWarTitle = "Warrior 2";
                            timeKeepTitle = expectWorkdayW1 - total_time_ot_war
                            timeNextTitle = expectWorkdayW2 - total_time_ot_war

                            countWarrior1 += 1;
                        } else if (total_time_ot_war >= workdayW2 && total_time_ot_war < workdayW3) {
                            currentWarTitle = "Warrior 2";
                            nextWarTitle = "Warrior 3";
                            timeKeepTitle = expectWorkdayW2 - total_time_ot_war
                            timeNextTitle = expectWorkdayW3 - total_time_ot_war

                            countWarrior2 += 1;
                        } else if (total_time_ot_war >= workdayW3) {
                            currentWarTitle = "Warrior 3";
                            nextWarTitle = "Warrior 3";
                            timeKeepTitle = expectWorkdayW3 - total_time_ot_war
                            timeNextTitle = expectWorkdayW3 - total_time_ot_war

                            countWarrior3 += 1;
                        } else {
                            timeNextTitle = expectWorkdayW1 - total_time_ot_war
                        }
                    }
                }
            })

            // set and reset count warrior month
            let tempData = {
                id:item.id,
                fullname:item.fullname,
                avatar:item.avatar,
                department_id:item.department_id,
                total_work_date: total_work_date,
                month_warrior1: countWarrior1,
                month_warrior2: countWarrior2,
                month_warrior3: countWarrior3,
                percent_warrior1: countWarrior1 ? countWarrior1 / 12 * 100 : 0,
                percent_warrior2: countWarrior2 ? countWarrior2 / 12 * 100 : 0,
                percent_warrior3: countWarrior3 ? countWarrior3 / 12 * 100 : 0,
                total_warrior: countWarrior1 + countWarrior2 + countWarrior3
            }
            newData.push(tempData);

            countWarrior1 = 0;
            countWarrior2 = 0;
            countWarrior3 = 0;
        });
        // sort data total default = desc
        newData = sortArray(newData,sort, type);
        
        return newData;
    } catch(err:any) {
        console.log(err.message)
    } 
}

const sortArray = (arr:any,sort:string, type:string | '')=>{
    let newArr = arr;
    if(sort == 'desc'){
        newArr = arr.sort(function(a:any, b:any){
            if(a.total_warrior != b.total_warrior){
                return b.total_warrior - a.total_warrior;
            }
            if(a.month_warrior3 != b.month_warrior3){
                return b.month_warrior3 - a.month_warrior3;
            }
            if(a.month_warrior2 != b.month_warrior2){
                return b.month_warrior2 - a.month_warrior2;
            }
            if(a.month_warrior1 != b.month_warrior1){
                return b.month_warrior1 - a.month_warrior1;
            }
            return b.total_warrior - a.total_warrior;
        });
    }else{
        newArr = arr.sort(function(a:any, b:any){
            if(a.total_warrior != b.total_warrior){
                return a.total_warrior - b.total_warrior;
            }
            if(a.month_warrior3 != b.month_warrior3){
                return a.month_warrior3 - b.month_warrior3;
            }
            if(a.month_warrior2 != b.month_warrior2){
                return a.month_warrior2 - b.month_warrior2;
            }
            if(a.month_warrior1 != b.month_warrior1){
                return a.month_warrior1 - b.month_warrior1;
            }
            return a.total_warrior - b.total_warrior;
        });
    }

    if(type == 'export'){
        return newArr; 
    }
    return newArr.slice(0,10);
}

const getDuration = (currentDate:any, dateCompare:any) => {
    const years = currentDate.diff(dateCompare, 'year');
    const months = currentDate.diff(dateCompare, 'month') - years * 12;
    const days = currentDate.diff(dateCompare.add(years, 'year').add(months, 'month'), 'day');

    let strYear = years ? years+" năm " : "";
    let strMonth = months ? months+" tháng " : "";

    let string = strYear + strMonth + days + " ngày";

    return {"total_years":years, "string":string};
}

const convertSecondToHour = (seconds:number) => {
    return roundNumber((seconds/60/60));
}

const roundNumber = (number:number) => {
    return number.toFixed(2);
}
