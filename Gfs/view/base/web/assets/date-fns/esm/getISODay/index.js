import toDate from"../toDate/index.js";export default function getISODay(dirtyDate){if(1>arguments.length){throw new TypeError("1 argument required, but only "+arguments.length+" present")}var date=toDate(dirtyDate),day=date.getDay();if(0===day){day=7}return day}