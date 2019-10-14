import toDate from"../toDate/index.js";export default function eachDayOfInterval(dirtyInterval,options){if(1>arguments.length){throw new TypeError("1 argument required, but only "+arguments.length+" present")}var interval=dirtyInterval||{},startDate=toDate(interval.start),endDate=toDate(interval.end),endTime=endDate.getTime();if(!(startDate.getTime()<=endTime)){throw new RangeError("Invalid interval")}var dates=[],currentDate=startDate;currentDate.setHours(0,0,0,0);var step=options&&"step"in options?+options.step:1;if(1>step||isNaN(step))throw new RangeError("`options.step` must be a number greater than 1");while(currentDate.getTime()<=endTime){dates.push(toDate(currentDate));currentDate.setDate(currentDate.getDate()+step);currentDate.setHours(0,0,0,0)}return dates}