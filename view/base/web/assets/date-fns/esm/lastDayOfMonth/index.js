import toDate from"../toDate/index.js";export default function lastDayOfMonth(dirtyDate){if(1>arguments.length){throw new TypeError("1 argument required, but only "+arguments.length+" present")}var date=toDate(dirtyDate),month=date.getMonth();date.setFullYear(date.getFullYear(),month+1,0);date.setHours(0,0,0,0);return date}