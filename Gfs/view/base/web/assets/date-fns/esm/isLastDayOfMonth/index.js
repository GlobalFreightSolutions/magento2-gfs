import toDate from"../toDate/index.js";import endOfDay from"../endOfDay/index.js";import endOfMonth from"../endOfMonth/index.js";export default function isLastDayOfMonth(dirtyDate){if(1>arguments.length){throw new TypeError("1 argument required, but only "+arguments.length+" present")}var date=toDate(dirtyDate);return endOfDay(date).getTime()===endOfMonth(date).getTime()}