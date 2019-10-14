"use strict";const styleTextSet=new Set;export const scopingAttribute="shady-unscoped";export function processUnscopedStyle(style){const text=style.textContent;if(!styleTextSet.has(text)){styleTextSet.add(text);const newStyle=style.cloneNode(!0);document.head.appendChild(newStyle)}}export function isUnscopedStyle(style){return style.hasAttribute(scopingAttribute)}