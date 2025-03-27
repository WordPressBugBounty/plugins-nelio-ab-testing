(()=>{var e,t={3639:(e,t,n)=>{"use strict";window.wp.coreData,window.wp.domReady;const a=window.nab.data,i=window.wp.element,r=window.nab.experimentLibrary,o=window.wp.data;function s(e,t){return(0,o.useSelect)(e,t)}const l=window.nab.editor,c=window.wp.compose,d=window.wp.url;var v=n(6942),u=n.n(v);const p=window.lodash,m=window.nab.utils,b=window.wp.components,f=window.wp.i18n;var g=function(e){var t=e.isSaving,n=e.save,a=w();return i.createElement("div",{className:"nab-javascript-editor-sidebar__actions"},i.createElement("div",{className:"nab-javascript-editor-sidebar__back-to-experiment"},i.createElement("a",{className:"nab-javascript-editor-sidebar__back-to-experiment-link",href:a,title:(0,f._x)("Back to test…","command","nelio-ab-testing")},i.createElement(b.Dashicon,{icon:"arrow-left-alt2"}))),i.createElement("div",{className:"nab-javascript-editor-sidebar__save-info"},t&&i.createElement("span",{className:"nab-javascript-editor-sidebar__saving-label"},i.createElement(b.Dashicon,{icon:"cloud"}),(0,f._x)("Saving…","text","nelio-ab-testing")),i.createElement(b.Button,{variant:"primary",disabled:t,onClick:n},(0,f._x)("Save and Preview","command","nelio-ab-testing"))))},w=function(){return s((function(e){var t;return null===(t=e(l.store).getExperimentAttribute("links"))||void 0===t?void 0:t.edit}))};const _=window.nab.components;var h=function(e){var t=e.value,n=e.onChange;return i.createElement(_.CodeEditor,{className:"nab-javascript-editor-sidebar__editor",language:"javascript",placeholder:j,value:t,onChange:n,config:{completions:[y],globals:["utils","done"],rules:{"detect-done":{level:"error",module:x}}}})},x={meta:{type:"problem",docs:{description:(0,f._x)("Ensure done() is called","user","nelio-ab-testing")},messages:{missingDone:(0,f._x)("The function done() must be called at least once.","text","nelio-ab-testing")},schema:[]},defaultOptions:[],create:function(e){var t=!1;return{CallExpression:function(e){"Identifier"===e.callee.type&&"done"===e.callee.name&&(t=!0)},"Program:exit":function(){e.sourceCode.getText().trim()&&!t&&e.report({loc:{line:1,column:0},messageId:"missingDone"})}}}};function y(e){var t=e.matchBefore(/utils\.\w*/);if(t)return{from:t.text.replace(/\w*$/,"").length+t.from,options:[{label:"domReady",type:"function",apply:"domReady(() => {\n});",detail:"(callback:Function) => void",info:(0,f._x)("Runs callback when DOM is ready. You may need to use the function if you want to target certain elements on the page, since this code may run before any elements are loaded in the DOM.","text","nelio-ab-testing")},{label:"showContent",type:"function",apply:"showContent();",detail:"() => void",info:(0,f._x)("Shows the variant right away, but doesn’t start tracking yet. To enable tracking, you should call “done” instead.","text","nelio-ab-testing")}]};var n=e.matchBefore(/\w*/);return n&&(n.from!==n.to||e.explicit)?{from:n.from,options:[{label:"done",type:"function",apply:"done();",detail:"() => void",info:(0,f._x)("Shows the variant and enables event tracking.","text","nelio-ab-testing")},{label:"utils",type:"variable",apply:"utils",detail:"Object",info:(0,f._x)("Contains several helper functions.","text","nelio-ab-testing")}]}:null}var E,j=[(0,f._x)("Write your JavaScript snippet here. Here are some useful tips:","user","nelio-ab-testing"),"\n","\n- ",(0,f.sprintf)(/* translators: variable name */ /* translators: variable name */
(0,f._x)("Declare global variable “%s”","text","nelio-ab-testing"),"abc"),"\n  window.abc = abc;","\n","\n- ",(0,f._x)("Run callback when dom is ready","text","nelio-ab-testing"),"\n  utils.domReady( callback );","\n","\n- ",(0,f._x)("Show variant:","text","nelio-ab-testing"),"\n  utils.showContent();","\n","\n- ",(0,f._x)("Show variant and track events","text","nelio-ab-testing"),"\n  done();"].join(""),k=function(){var e=(0,a.usePageAttribute)("javascript-preview/areControlsVisible",!0),t=e[0],n=e[1],r=(0,a.usePageAttribute)("javascript-preview/activeResolution","desktop"),o=r[0],s=r[1];return i.createElement("div",{className:"nab-javascript-editor-sidebar__footer-actions"},i.createElement("div",{className:u()({"nab-javascript-editor-sidebar__visibility-toggle":!0,"nab-javascript-editor-sidebar__visibility-toggle--hide":!!t,"nab-javascript-editor-sidebar__visibility-toggle--show":!t})},i.createElement(b.Button,{variant:"link",onClick:function(){return n(!t)}},t?i.createElement(i.Fragment,null,i.createElement(b.Dashicon,{icon:"admin-collapse"}),i.createElement("span",{className:"nab-javascript-editor-sidebar__visibility-toggle-label"},(0,f._x)("Hide Controls","command","nelio-ab-testing"))):i.createElement(b.Dashicon,{icon:"admin-collapse"}))),i.createElement("div",{className:"nab-javascript-editor-sidebar__resolutions"},i.createElement("div",{className:u()({"nab-javascript-editor-sidebar__resolution":!0,"nab-javascript-editor-sidebar__resolution--is-active":"desktop"===o})},i.createElement(b.Button,{variant:"link",onClick:function(){return s("desktop")}},i.createElement(b.Dashicon,{icon:"desktop"}),i.createElement("span",{className:"screen-reader-text"},(0,f._x)("Enter desktop preview mode","command","nelio-ab-testing")))),i.createElement("div",{className:u()({"nab-javascript-editor-sidebar__resolution":!0,"nab-javascript-editor-sidebar__resolution--is-active":"tablet"===o})},i.createElement(b.Button,{variant:"link",onClick:function(){return s("tablet")}},i.createElement(b.Dashicon,{icon:"tablet"}),i.createElement("span",{className:"screen-reader-text"},(0,f._x)("Enter tablet preview mode","command","nelio-ab-testing")))),i.createElement("div",{className:u()({"nab-javascript-editor-sidebar__resolution":!0,"nab-javascript-editor-sidebar__resolution--is-active":"mobile"===o})},i.createElement(b.Button,{variant:"link",onClick:function(){return s("mobile")}},i.createElement(b.Dashicon,{icon:"smartphone"}),i.createElement("span",{className:"screen-reader-text"},(0,f._x)("Enter mobile preview mode","command","nelio-ab-testing"))))))},O=function(){return O=Object.assign||function(e){for(var t,n=1,a=arguments.length;n<a;n++)for(var i in t=arguments[n])Object.prototype.hasOwnProperty.call(t,i)&&(e[i]=t[i]);return e},O.apply(this,arguments)},N=function(e){var t=e.className,n=e.alternativeId,a=e.isSaving,r=e.save,o=S(n),s=o[0],l=o[1];return i.createElement("div",{className:u()(["nab-javascript-editor-sidebar",t])},i.createElement(g,{isSaving:a,save:r}),i.createElement(h,{value:s,onChange:l}),i.createElement(k,null))},S=function(e){var t,n=s((function(t){return t(l.store).getAlternative(e)})),a=(null===(t=null==n?void 0:n.attributes)||void 0===t?void 0:t.code)||"",i=(0,o.useDispatch)(l.store).setAlternative;return[a,function(e){n&&i(n.id,O(O({},n),{attributes:(0,m.omitUndefineds)(O(O({},n.attributes),{code:e}))}))}]},C=function(e){var t,n=e.className,r=e.iframeId,o=e.isSaving,s=e.previewUrl,l=(0,a.usePageAttribute)("javascript-preview/activeResolution","desktop")[0];return i.createElement("div",{className:u()([n,"nab-javascript-preview"])},i.createElement("iframe",{id:r,className:u()((t={"nab-javascript-preview__iframe":!0},t["nab-javascript-preview__iframe--".concat(l)]=!0,t["nab-javascript-preview__iframe--is-saving"]=o,t)),title:(0,f._x)("JavaScript Preview","text","nelio-ab-testing"),src:s}))},I=function(e){var t=e.alternativeId,n=(0,c.useInstanceId)(I),r="nab-javascript-previewer__iframe-".concat(n),o=(0,a.usePageAttribute)("javascript-preview/areControlsVisible",!0)[0],s=A(t),l=P(),d=D(r),v=d[0],p=d[1];return i.createElement("div",{className:"nab-javascript-editor"},i.createElement(N,{className:u()({"nab-javascript-editor__sidebar":!0,"nab-javascript-editor__sidebar--is-collapsed":!o}),alternativeId:t,isSaving:v,save:p}),i.createElement(C,{key:"nab-javascript-editor__preview",className:u()({"nab-javascript-editor__preview":!0,"nab-javascript-editor__preview--is-fullscreen":!o}),iframeId:r,isSaving:v,previewUrl:l,value:s}))},A=function(e){return s((function(t){var n,a;return(null===(a=null===(n=t(l.store).getAlternative(e))||void 0===n?void 0:n.attributes)||void 0===a?void 0:a.code)||""}))},P=function(){return s((function(e){var t=(0,e(l.store).getAlternative)("control");if(t){var n=e(l.store),a=n.getAlternatives,i=n.getExperimentId,r=(0,d.getQueryArgs)(document.location.href).alternative||"",o=a(),s=i(),c=(0,p.indexOf)((0,p.map)(o,"id"),r),v=t.links&&t.links.preview||"/",u=(0,d.removeQueryArgs)(v,"nab-preview","experiment","alternative","timestamp","nabnonce");return(0,d.addQueryArgs)(u,{"nab-javascript-previewer":"".concat(s,":").concat(c)})}}))},D=function(e){var t=s((function(e){return!!e(a.store).getPageAttribute("javascript-preview/isLoading")})),n=(0,o.useDispatch)(a.store).setPageAttribute,i=(0,o.useDispatch)(l.store).saveExperiment;return[t,function(){n("javascript-preview/isLoading",!0),i().then((function(){var t,a=document.getElementById(e);null===(t=null==a?void 0:a.contentWindow)||void 0===t||t.location.reload(),setTimeout((function(){return n("javascript-preview/isLoading",!1)}),5e3)}))}]},B=function(e){var t=e.experimentId,n=e.alternativeId,r=s((function(e){return e(a.store).getExperiment(t)}));return r?i.createElement(i.StrictMode,null,i.createElement(l.EditorProvider,{experiment:r},i.createElement(I,{alternativeId:n}))):null},R=function(){return R=Object.assign||function(e){for(var t,n=1,a=arguments.length;n<a;n++)for(var i in t=arguments[n])Object.prototype.hasOwnProperty.call(t,i)&&(e[i]=t[i]);return e},R.apply(this,arguments)};window.nab=R(R({},(E=window)&&"object"==typeof E&&"nab"in E?window.nab:{}),{initJavaScriptEditorPage:function(e,t){(0,r.registerCoreExperiments)();var n=document.getElementById(e);if(n){var a,o,s=t.experimentId,l=t.alternativeId;a=i.createElement(B,{experimentId:s,alternativeId:l}),(o=n)&&(i.createRoot?(0,i.createRoot)(o).render(a):(0,i.render)(a,o))}}})},6942:(e,t)=>{var n;!function(){"use strict";var a={}.hasOwnProperty;function i(){for(var e="",t=0;t<arguments.length;t++){var n=arguments[t];n&&(e=o(e,r(n)))}return e}function r(e){if("string"==typeof e||"number"==typeof e)return e;if("object"!=typeof e)return"";if(Array.isArray(e))return i.apply(null,e);if(e.toString!==Object.prototype.toString&&!e.toString.toString().includes("[native code]"))return e.toString();var t="";for(var n in e)a.call(e,n)&&e[n]&&(t=o(t,n));return t}function o(e,t){return t?e?e+" "+t:e+t:e}e.exports?(i.default=i,e.exports=i):void 0===(n=function(){return i}.apply(t,[]))||(e.exports=n)}()}},n={};function a(e){var i=n[e];if(void 0!==i)return i.exports;var r=n[e]={exports:{}};return t[e](r,r.exports,a),r.exports}a.m=t,e=[],a.O=(t,n,i,r)=>{if(!n){var o=1/0;for(d=0;d<e.length;d++){n=e[d][0],i=e[d][1],r=e[d][2];for(var s=!0,l=0;l<n.length;l++)(!1&r||o>=r)&&Object.keys(a.O).every((e=>a.O[e](n[l])))?n.splice(l--,1):(s=!1,r<o&&(o=r));if(s){e.splice(d--,1);var c=i();void 0!==c&&(t=c)}}return t}r=r||0;for(var d=e.length;d>0&&e[d-1][2]>r;d--)e[d]=e[d-1];e[d]=[n,i,r]},a.n=e=>{var t=e&&e.__esModule?()=>e.default:()=>e;return a.d(t,{a:t}),t},a.d=(e,t)=>{for(var n in t)a.o(t,n)&&!a.o(e,n)&&Object.defineProperty(e,n,{enumerable:!0,get:t[n]})},a.o=(e,t)=>Object.prototype.hasOwnProperty.call(e,t),(()=>{var e={2676:0,1091:0};a.O.j=t=>0===e[t];var t=(t,n)=>{var i,r,o=n[0],s=n[1],l=n[2],c=0;if(o.some((t=>0!==e[t]))){for(i in s)a.o(s,i)&&(a.m[i]=s[i]);if(l)var d=l(a)}for(t&&t(n);c<o.length;c++)r=o[c],a.o(e,r)&&e[r]&&e[r][0](),e[r]=0;return a.O(d)},n=self.webpackChunknab=self.webpackChunknab||[];n.forEach(t.bind(null,0)),n.push=t.bind(null,n.push.bind(n))})();var i=a.O(void 0,[1091],(()=>a(3639)));i=a.O(i);var r=nab="undefined"==typeof nab?{}:nab;for(var o in i)r[o]=i[o];i.__esModule&&Object.defineProperty(r,"__esModule",{value:!0})})();