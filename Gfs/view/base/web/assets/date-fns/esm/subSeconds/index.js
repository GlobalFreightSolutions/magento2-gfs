import toInteger from"../_lib/toInteger/index.js";import addSeconds from"../addSeconds/index.js";export default function subSeconds(dirtyDate,dirtyAmount){if(2>arguments.length){throw new TypeError("2 arguments required, but only "+arguments.length+" present")}var amount=toInteger(dirtyAmount);return addSeconds(dirtyDate,-amount)}