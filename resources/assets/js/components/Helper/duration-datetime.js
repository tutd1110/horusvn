import dayjs from 'dayjs'
import duration from 'dayjs/plugin/duration';

dayjs.extend(duration);

export const calculateWorkDuration = (dateTime) => {
    const startDate = dayjs(dateTime);
    const currentDate = dayjs();
    const totalMonths = currentDate.diff(startDate, 'month');
    const years = Math.floor(totalMonths / 12);
    const months = totalMonths % 12;
    const days = currentDate.diff(startDate.add(years, 'year').add(months, 'month'), 'day');
    let durationStr = '';
    if (years > 0) {
        durationStr += `${years} năm `;
    }
    if (months > 0) {
        durationStr += `${months} tháng `;
    }
    if (days > 0) {
        durationStr += `${days} ngày `;
    }
    
    return durationStr.trim();
}