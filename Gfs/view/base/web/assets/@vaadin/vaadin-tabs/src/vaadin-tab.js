import{PolymerElement}from"../../../@polymer/polymer/polymer-element.js";import{ThemableMixin}from"../../vaadin-themable-mixin/vaadin-themable-mixin.js";import{ItemMixin}from"../../vaadin-item/src/vaadin-item-mixin.js";import{ElementMixin}from"../../vaadin-element-mixin/vaadin-element-mixin.js";import{html}from"../../../@polymer/polymer/lib/utils/html-tag.js";class TabElement extends ElementMixin(ThemableMixin(ItemMixin(PolymerElement))){static get template(){return html`
    <slot></slot>
`}static get is(){return"vaadin-tab"}static get version(){return"3.0.4"}ready(){super.ready();this.setAttribute("role","tab")}_onKeyup(event){const willClick=this.hasAttribute("active");super._onKeyup(event);if(willClick){const anchor=this.querySelector("a");if(anchor){anchor.click()}}}}customElements.define(TabElement.is,TabElement);export{TabElement};