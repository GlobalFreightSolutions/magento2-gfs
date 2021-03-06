import"../polymer/polymer-legacy.js";import"../paper-styles/color.js";import{PaperSpinnerBehavior}from"./paper-spinner-behavior.js";import"./paper-spinner-styles.js";import{Polymer}from"../polymer/lib/legacy/polymer-fn.js";const $_documentContainer=document.createElement("template");$_documentContainer.setAttribute("style","display: none;");$_documentContainer.innerHTML=`<dom-module id="paper-spinner">
  <template strip-whitespace="">
    <style include="paper-spinner-styles"></style>

    <div id="spinnerContainer" class-name="[[__computeContainerClasses(active, __coolingDown)]]" on-animationend="__reset" on-webkit-animation-end="__reset">
      <div class="spinner-layer layer-1">
        <div class="circle-clipper left"></div>
        <div class="circle-clipper right"></div>
      </div>

      <div class="spinner-layer layer-2">
        <div class="circle-clipper left"></div>
        <div class="circle-clipper right"></div>
      </div>

      <div class="spinner-layer layer-3">
        <div class="circle-clipper left"></div>
        <div class="circle-clipper right"></div>
      </div>

      <div class="spinner-layer layer-4">
        <div class="circle-clipper left"></div>
        <div class="circle-clipper right"></div>
      </div>
    </div>
  </template>

  
</dom-module>`;document.head.appendChild($_documentContainer.content);Polymer({is:"paper-spinner",behaviors:[PaperSpinnerBehavior]});