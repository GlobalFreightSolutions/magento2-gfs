import getQuarter from"../getQuarter/index.js";import toDate from"../toDate/index.js";export default function differenceInCalendarQuarters(dirtyDateLeft,dirtyDateRight){if(2>arguments.length){throw new TypeError("2 arguments required, but only "+arguments.length+" present")}var dateLeft=toDate(dirtyDateLeft),dateRight=toDate(dirtyDateRight),yearDiff=dateLeft.getFullYear()-dateRight.getFullYear(),quarterDiff=getQuarter(dateLeft)-getQuarter(dateRight);return 4*yearDiff+quarterDiff}