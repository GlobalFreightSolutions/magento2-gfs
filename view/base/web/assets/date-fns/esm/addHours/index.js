import toInteger from"../_lib/toInteger/index.js";import addMilliseconds from"../addMilliseconds/index.js";var MILLISECONDS_IN_HOUR=36e5;export default function addHours(dirtyDate,dirtyAmount){if(2>arguments.length){throw new TypeError("2 arguments required, but only "+arguments.length+" present")}var amount=toInteger(dirtyAmount);return addMilliseconds(dirtyDate,amount*MILLISECONDS_IN_HOUR)}