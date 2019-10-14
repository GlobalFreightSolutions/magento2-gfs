import toInteger from"../_lib/toInteger/index.js";import toDate from"../toDate/index.js";export default function lastDayOfWeek(dirtyDate,dirtyOptions){if(1>arguments.length){throw new TypeError("1 argument required, but only "+arguments.length+" present")}var options=dirtyOptions||{},locale=options.locale,localeWeekStartsOn=locale&&locale.options&&locale.options.weekStartsOn,defaultWeekStartsOn=null==localeWeekStartsOn?0:toInteger(localeWeekStartsOn),weekStartsOn=null==options.weekStartsOn?defaultWeekStartsOn:toInteger(options.weekStartsOn);if(!(0<=weekStartsOn&&6>=weekStartsOn)){throw new RangeError("weekStartsOn must be between 0 and 6")}var date=toDate(dirtyDate),day=date.getDay(),diff=(day<weekStartsOn?-7:0)+6-(day-weekStartsOn);date.setHours(0,0,0,0);date.setDate(date.getDate()+diff);return date}