import toDate from"../toDate/index.js";export default function getDecade(dirtyDate){if(1>arguments.length){throw new TypeError("1 argument required, but only "+arguments.length+" present")}var date=toDate(dirtyDate),year=date.getFullYear(),decade=10*Math.floor(year/10);return decade}