import toDate from"../../toDate/index.js";export default function startOfUTCISOWeek(dirtyDate){if(1>arguments.length){throw new TypeError("1 argument required, but only "+arguments.length+" present")}var weekStartsOn=1,date=toDate(dirtyDate),day=date.getUTCDay(),diff=(day<weekStartsOn?7:0)+day-weekStartsOn;date.setUTCDate(date.getUTCDate()-diff);date.setUTCHours(0,0,0,0);return date}