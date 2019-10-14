import toInteger from"../_lib/toInteger/index.js";import getTimezoneOffsetInMilliseconds from"../_lib/getTimezoneOffsetInMilliseconds/index.js";var MILLISECONDS_IN_HOUR=36e5,MILLISECONDS_IN_MINUTE=6e4,DEFAULT_ADDITIONAL_DIGITS=2,patterns={dateTimeDelimiter:/[T ]/,timeZoneDelimiter:/[Z ]/i,timezone:/([Z+-].*)$/},dateRegex=/^-?(?:(\d{3})|(\d{2})(?:-?(\d{2}))?|W(\d{2})(?:-?(\d{1}))?|)$/,timeRegex=/^(\d{2}(?:[.,]\d*)?)(?::?(\d{2}(?:[.,]\d*)?))?(?::?(\d{2}(?:[.,]\d*)?))?$/,timezoneRegex=/^([+-])(\d{2})(?::?(\d{2}))?$/;export default function parseISO(argument,dirtyOptions){if(1>arguments.length){throw new TypeError("1 argument required, but only "+arguments.length+" present")}var options=dirtyOptions||{},additionalDigits=null==options.additionalDigits?DEFAULT_ADDITIONAL_DIGITS:toInteger(options.additionalDigits);if(2!==additionalDigits&&1!==additionalDigits&&0!==additionalDigits){throw new RangeError("additionalDigits must be 0, 1 or 2")}if(!("string"===typeof argument||"[object String]"===Object.prototype.toString.call(argument))){return new Date(NaN)}var dateStrings=splitDateString(argument),parseYearResult=parseYear(dateStrings.date,additionalDigits),date=parseDate(parseYearResult.restDateString,parseYearResult.year);if(isNaN(date)||!date){return new Date(NaN)}var timestamp=date.getTime(),time=0,offset;if(dateStrings.time){time=parseTime(dateStrings.time);if(isNaN(time)){return new Date(NaN)}}if(dateStrings.timezone){offset=parseTimezone(dateStrings.timezone);if(isNaN(offset)){return new Date(NaN)}}else{var fullTime=timestamp+time,fullTimeDate=new Date(fullTime);offset=getTimezoneOffsetInMilliseconds(fullTimeDate);var fullTimeDateNextDay=new Date(fullTime);fullTimeDateNextDay.setDate(fullTimeDate.getDate()+1);var offsetDiff=getTimezoneOffsetInMilliseconds(fullTimeDateNextDay)-offset;if(0<offsetDiff){offset+=offsetDiff}}return new Date(timestamp+time+offset)}function splitDateString(dateString){var dateStrings={},array=dateString.split(patterns.dateTimeDelimiter),timeString;if(/:/.test(array[0])){dateStrings.date=null;timeString=array[0]}else{dateStrings.date=array[0];timeString=array[1];if(patterns.timeZoneDelimiter.test(dateStrings.date)){dateStrings.date=dateString.split(patterns.timeZoneDelimiter)[0];timeString=dateString.substr(dateStrings.date.length,dateString.length)}}if(timeString){var token=patterns.timezone.exec(timeString);if(token){dateStrings.time=timeString.replace(token[1],"");dateStrings.timezone=token[1]}else{dateStrings.time=timeString}}return dateStrings}function parseYear(dateString,additionalDigits){var regex=new RegExp("^(?:(\\d{4}|[+-]\\d{"+(4+additionalDigits)+"})|(\\d{2}|[+-]\\d{"+(2+additionalDigits)+"})$)"),captures=dateString.match(regex);if(!captures)return{year:null};var year=captures[1]&&parseInt(captures[1]),century=captures[2]&&parseInt(captures[2]);return{year:null==century?year:100*century,restDateString:dateString.slice((captures[1]||captures[2]).length)}}function parseDate(dateString,year){if(null===year)return null;var captures=dateString.match(dateRegex);if(!captures)return null;var isWeekDate=!!captures[4],dayOfYear=parseDateUnit(captures[1]),month=parseDateUnit(captures[2])-1,day=parseDateUnit(captures[3]),week=parseDateUnit(captures[4])-1,dayOfWeek=parseDateUnit(captures[5])-1;if(isWeekDate){if(!validateWeekDate(year,week,dayOfWeek)){return new Date(NaN)}return dayOfISOWeekYear(year,week,dayOfWeek)}else{var date=new Date(0);if(!validateDate(year,month,day)||!validateDayOfYearDate(year,dayOfYear)){return new Date(NaN)}date.setUTCFullYear(year,month,Math.max(dayOfYear,day));return date}}function parseDateUnit(value){return value?parseInt(value):1}function parseTime(timeString){var captures=timeString.match(timeRegex);if(!captures)return null;var hours=parseTimeUnit(captures[1]),minutes=parseTimeUnit(captures[2]),seconds=parseTimeUnit(captures[3]);if(!validateTime(hours,minutes,seconds)){return NaN}return hours%24*MILLISECONDS_IN_HOUR+minutes*MILLISECONDS_IN_MINUTE+1e3*seconds}function parseTimeUnit(value){return value&&parseFloat(value.replace(",","."))||0}function parseTimezone(timezoneString){if("Z"===timezoneString)return 0;var captures=timezoneString.match(timezoneRegex);if(!captures)return 0;var sign="+"===captures[1]?-1:1,hours=parseInt(captures[2]),minutes=captures[3]&&parseInt(captures[3])||0;if(!validateTimezone(hours,minutes)){return NaN}return sign*(hours*MILLISECONDS_IN_HOUR+minutes*MILLISECONDS_IN_MINUTE)}function dayOfISOWeekYear(isoWeekYear,week,day){var date=new Date(0);date.setUTCFullYear(isoWeekYear,0,4);var fourthOfJanuaryDay=date.getUTCDay()||7;date.setUTCDate(date.getUTCDate()+(7*(week||0)+(day||0)+1-fourthOfJanuaryDay));return date}var daysInMonths=[31,null,31,30,31,30,31,31,30,31,30,31];function isLeapYearIndex(year){return 0===year%400||0===year%4&&year%100}function validateDate(year,month,date){return!(0>month||11<month||1>date||date>(daysInMonths[month]||(isLeapYearIndex(year)?29:28)))}function validateDayOfYearDate(year,dayOfYear){return!(1>dayOfYear||dayOfYear>(isLeapYearIndex(year)?366:365))}function validateWeekDate(_year,week,day){return!(0>week||52<week||0>day||6<day)}function validateTime(hours,minutes,seconds){return!(0>seconds||60<=seconds||0>minutes||60<=minutes||0>hours||25<=hours)}function validateTimezone(_hours,minutes){return!(0>minutes||59<minutes)}