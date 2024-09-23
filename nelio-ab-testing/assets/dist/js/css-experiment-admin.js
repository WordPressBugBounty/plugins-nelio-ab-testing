(()=>{var e,t={751:(e,t,n)=>{"use strict";n.r(t),window.wp.coreData,window.wp.domReady;const r=window.nab.data,a=window.wp.element,i=window.nab.experimentLibrary,o=window.wp.data,s=window.nab.editor,c=window.wp.url;var l=n(6942),d=n.n(l);const u=window.lodash,v=window.nab.utils,m=window.wp.components,b=window.wp.i18n;var p=function(){var e=f(),t=_(),n=t[0],r=t[1];return a.createElement("div",{className:"nab-css-editor-sidebar__actions"},a.createElement("div",{className:"nab-css-editor-sidebar__back-to-experiment"},a.createElement("a",{className:"nab-css-editor-sidebar__back-to-experiment-link",href:e,title:(0,b._x)("Back to test…","command","nelio-ab-testing")},a.createElement(m.Dashicon,{icon:"arrow-left-alt2"}))),a.createElement("div",{className:"nab-css-editor-sidebar__save-info"},n&&a.createElement("span",{className:"nab-css-editor-sidebar__saving-label"},a.createElement(m.Dashicon,{icon:"cloud"}),(0,b._x)("Saving…","text","nelio-ab-testing")),a.createElement(m.Button,{variant:"primary",disabled:n,onClick:function(){r()}},(0,b._x)("Save","command","nelio-ab-testing"))))},f=function(){return(0,o.useSelect)((function(e){var t;return null===(t=e(s.STORE_NAME).getExperimentAttribute("links"))||void 0===t?void 0:t.edit}))},_=function(){return[(0,o.useSelect)((function(e){return e(s.STORE_NAME).isExperimentBeingSaved()})),(0,o.useDispatch)(s.STORE_NAME).saveExperiment]},E=function(e){var t=e.className,n=e.value,r=e.onChange;return a.createElement(m.TextareaControl,{className:t,value:n,onChange:r,autoComplete:"off",autoCorrect:"off",autoCapitalize:"off",spellCheck:"false"})},w=function(){var e=(0,r.usePageAttribute)("css-preview/areControlsVisible",!0),t=e[0],n=e[1],i=(0,r.usePageAttribute)("css-preview/activeResolution","desktop"),o=i[0],s=i[1];return a.createElement("div",{className:"nab-css-editor-sidebar__footer-actions"},a.createElement("div",{className:d()({"nab-css-editor-sidebar__visibility-toggle":!0,"nab-css-editor-sidebar__visibility-toggle--hide":!!t,"nab-css-editor-sidebar__visibility-toggle--show":!t})},a.createElement(m.Button,{variant:"link",onClick:function(){return n(!t)}},t?a.createElement(a.Fragment,null,a.createElement(m.Dashicon,{icon:"admin-collapse"}),a.createElement("span",{className:"nab-css-editor-sidebar__visibility-toggle-label"},(0,b._x)("Hide Controls","command","nelio-ab-testing"))):a.createElement(m.Dashicon,{icon:"admin-collapse"}))),a.createElement("div",{className:"nab-css-editor-sidebar__resolutions"},a.createElement("div",{className:d()({"nab-css-editor-sidebar__resolution":!0,"nab-css-editor-sidebar__resolution--is-active":"desktop"===o})},a.createElement(m.Button,{variant:"link",onClick:function(){return s("desktop")}},a.createElement(m.Dashicon,{icon:"desktop"}),a.createElement("span",{className:"screen-reader-text"},(0,b._x)("Enter desktop preview mode","command","nelio-ab-testing")))),a.createElement("div",{className:d()({"nab-css-editor-sidebar__resolution":!0,"nab-css-editor-sidebar__resolution--is-active":"tablet"===o})},a.createElement(m.Button,{variant:"link",onClick:function(){return s("tablet")}},a.createElement(m.Dashicon,{icon:"tablet"}),a.createElement("span",{className:"screen-reader-text"},(0,b._x)("Enter tablet preview mode","command","nelio-ab-testing")))),a.createElement("div",{className:d()({"nab-css-editor-sidebar__resolution":!0,"nab-css-editor-sidebar__resolution--is-active":"mobile"===o})},a.createElement(m.Button,{variant:"link",onClick:function(){return s("mobile")}},a.createElement(m.Dashicon,{icon:"smartphone"}),a.createElement("span",{className:"screen-reader-text"},(0,b._x)("Enter mobile preview mode","command","nelio-ab-testing"))))))},g=function(){return g=Object.assign||function(e){for(var t,n=1,r=arguments.length;n<r;n++)for(var a in t=arguments[n])Object.prototype.hasOwnProperty.call(t,a)&&(e[a]=t[a]);return e},g.apply(this,arguments)},y=function(e){var t=e.className,n=e.alternativeId,r=h(n),i=r[0],o=r[1];return a.createElement("div",{className:d()(["nab-css-editor-sidebar",t])},a.createElement(p,null),a.createElement(E,{className:"nab-css-editor-sidebar__editor",value:i,onChange:o}),a.createElement(w,null))},h=function(e){var t,n=(0,o.useSelect)((function(t){return t(s.STORE_NAME).getAlternative(e)})),r=(null===(t=null==n?void 0:n.attributes)||void 0===t?void 0:t.css)||"",a=(0,o.useDispatch)(s.STORE_NAME).setAlternative;return[r,function(e){n&&a(n.id,g(g({},n),{attributes:(0,v.omitUndefineds)(g(g({},n.attributes),{css:e}))}))}]};const x=window.wp.compose;var N,O=function(e){var t=e.className,n=e.previewUrl,i=e.value,o=(0,x.useInstanceId)(O),s=(0,r.usePageAttribute)("css-preview/activeResolution","desktop")[0],c=S(o,i);return a.createElement("div",{className:d()([t,"nab-css-preview"])},a.createElement("iframe",{id:"nab-css-previewer__iframe-".concat(o),className:d()(["nab-css-preview__iframe","nab-css-preview__iframe--".concat(s)]),title:(0,b._x)("CSS Preview","text","nelio-ab-testing"),onLoad:c,src:n}))},S=function(e,t){var n=(0,a.useState)(),r=n[0],i=n[1],o=function(){clearTimeout(r),i(setTimeout((function(){var n,r=document.getElementById("nab-css-previewer__iframe-".concat(e));(null===(n=null==r?void 0:r.contentWindow)||void 0===n?void 0:n.postMessage)&&r.contentWindow.postMessage({type:"css-preview",value:t})}),500))};return(0,a.useEffect)(o,[t]),o},k=function(e){var t=e.alternativeId,n=(0,r.usePageAttribute)("css-preview/areControlsVisible",!0)[0],i=A(t),o=C();return a.createElement("div",{className:"nab-css-editor"},a.createElement(y,{className:d()({"nab-css-editor__sidebar":!0,"nab-css-editor__sidebar--is-collapsed":!n}),alternativeId:t}),a.createElement(O,{key:"nab-css-editor__preview",className:d()({"nab-css-editor__preview":!0,"nab-css-editor__preview--is-fullscreen":!n}),previewUrl:o,value:i}))},A=function(e){return(0,o.useSelect)((function(t){var n,r;return(null===(r=null===(n=t(s.STORE_NAME).getAlternative(e))||void 0===n?void 0:n.attributes)||void 0===r?void 0:r.css)||""}))},C=function(){return(0,o.useSelect)((function(e){var t=(0,e(s.STORE_NAME).getAlternative)("control");if(t){var n=e(s.STORE_NAME),r=n.getAlternatives,a=n.getExperimentId,i=(0,c.getQueryArgs)(document.location.href).alternative||"",o=r(),l=a(),d=(0,u.indexOf)((0,u.map)(o,"id"),i),v=t.links&&t.links.preview||"/",m=(0,c.removeQueryArgs)(v,"nab-preview","experiment","alternative","timestamp","nabnonce");return(0,c.addQueryArgs)(m,{"nab-css-previewer":"".concat(l,":").concat(d)})}}))},M=function(e){var t=e.experimentId,n=e.alternativeId,i=(0,o.useSelect)((function(e){return e(r.STORE_NAME).getExperiment(t)}));return i?a.createElement(a.StrictMode,null,a.createElement(s.EditorProvider,{experiment:i},a.createElement(k,{alternativeId:n}))):null},T=function(){return T=Object.assign||function(e){for(var t,n=1,r=arguments.length;n<r;n++)for(var a in t=arguments[n])Object.prototype.hasOwnProperty.call(t,a)&&(e[a]=t[a]);return e},T.apply(this,arguments)};window.nab=T(T({},(N=window)&&"object"==typeof N&&"nab"in N?window.nab:{}),{initCssEditorPage:function(e,t){(0,i.registerCoreExperiments)();var n=document.getElementById(e);if(n){var r,o,s=t.experimentId,c=t.alternativeId;r=a.createElement(M,{experimentId:s,alternativeId:c}),(o=n)&&(a.createRoot?(0,a.createRoot)(o).render(r):(0,a.render)(r,o))}}})},6942:(e,t)=>{var n;!function(){"use strict";var r={}.hasOwnProperty;function a(){for(var e="",t=0;t<arguments.length;t++){var n=arguments[t];n&&(e=o(e,i(n)))}return e}function i(e){if("string"==typeof e||"number"==typeof e)return e;if("object"!=typeof e)return"";if(Array.isArray(e))return a.apply(null,e);if(e.toString!==Object.prototype.toString&&!e.toString.toString().includes("[native code]"))return e.toString();var t="";for(var n in e)r.call(e,n)&&e[n]&&(t=o(t,n));return t}function o(e,t){return t?e?e+" "+t:e+t:e}e.exports?(a.default=a,e.exports=a):void 0===(n=function(){return a}.apply(t,[]))||(e.exports=n)}()}},n={};function r(e){var a=n[e];if(void 0!==a)return a.exports;var i=n[e]={exports:{}};return t[e](i,i.exports,r),i.exports}r.m=t,e=[],r.O=(t,n,a,i)=>{if(!n){var o=1/0;for(d=0;d<e.length;d++){for(var[n,a,i]=e[d],s=!0,c=0;c<n.length;c++)(!1&i||o>=i)&&Object.keys(r.O).every((e=>r.O[e](n[c])))?n.splice(c--,1):(s=!1,i<o&&(o=i));if(s){e.splice(d--,1);var l=a();void 0!==l&&(t=l)}}return t}i=i||0;for(var d=e.length;d>0&&e[d-1][2]>i;d--)e[d]=e[d-1];e[d]=[n,a,i]},r.n=e=>{var t=e&&e.__esModule?()=>e.default:()=>e;return r.d(t,{a:t}),t},r.d=(e,t)=>{for(var n in t)r.o(t,n)&&!r.o(e,n)&&Object.defineProperty(e,n,{enumerable:!0,get:t[n]})},r.o=(e,t)=>Object.prototype.hasOwnProperty.call(e,t),r.r=e=>{"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},(()=>{var e={7470:0,3115:0};r.O.j=t=>0===e[t];var t=(t,n)=>{var a,i,[o,s,c]=n,l=0;if(o.some((t=>0!==e[t]))){for(a in s)r.o(s,a)&&(r.m[a]=s[a]);if(c)var d=c(r)}for(t&&t(n);l<o.length;l++)i=o[l],r.o(e,i)&&e[i]&&e[i][0](),e[i]=0;return r.O(d)},n=globalThis.webpackChunknab=globalThis.webpackChunknab||[];n.forEach(t.bind(null,0)),n.push=t.bind(null,n.push.bind(n))})();var a=r.O(void 0,[3115],(()=>r(751)));a=r.O(a);var i=nab="undefined"==typeof nab?{}:nab;for(var o in a)i[o]=a[o];a.__esModule&&Object.defineProperty(i,"__esModule",{value:!0})})();