(()=>{var e,t={4948:(e,t,n)=>{"use strict";const r=window.nab.data,a=window.wp.element,i=window.nab.experimentLibrary,o=window.wp.apiFetch;var l=n.n(o);const s=window.wp.date,c=window.wp.url;var u=function(){return u=Object.assign||function(e){for(var t,n=1,r=arguments.length;n<r;n++)for(var a in t=arguments[n])Object.prototype.hasOwnProperty.call(t,a)&&(e[a]=t[a]);return e},u.apply(this,arguments)};var p=function(e){var t,n;return!!(null===(t=e.url)||void 0===t?void 0:t.includes("/wp-json/"))||!!(null===(n=e.url)||void 0===n?void 0:n.includes("rest_route"))||!!Object.keys(e).includes("rest_route")};const m=window.wp.components,d=window.wp.data;function v(e,t){return(0,d.useSelect)(e,t)}const b=window.wp.i18n,f=window.lodash,g=window.nab.components,E=window.nab.experiments,w=window.nab.utils;var x=function(){return x=Object.assign||function(e){for(var t,n=1,r=arguments.length;n<r;n++)for(var a in t=arguments[n])Object.prototype.hasOwnProperty.call(t,a)&&(e[a]=t[a]);return e},x.apply(this,arguments)},_=function(e,t,n){if(n||2===arguments.length)for(var r,a=0,i=t.length;a<i;a++)!r&&a in t||(r||(r=Array.prototype.slice.call(t,0,a)),r[a]=t[a]);return e.concat(r||Array.prototype.slice.call(t))},I=function(e){var t=e.experimentId,n=e.postBeingEdited,r=e.type,i=N(t);return i?a.createElement(a.Fragment,null,a.createElement(h,{icon:i,experimentId:t}),a.createElement(y,{experimentId:t,postBeingEdited:n}),a.createElement(C,{experimentId:t,postBeingEdited:n,type:r})):null},h=function(e){var t=e.icon,n=e.experimentId,r=O(n),i=P(n);return a.createElement(m.PanelRow,{className:"nab-test-panel"},a.createElement("span",{className:"nab-test-panel__icon"},a.createElement(t,null)),a.createElement("a",{className:"nab-test-panel__name",href:i},r))},y=function(e){var t=e.experimentId,n=e.postBeingEdited,r=B(t);return r?a.createElement(m.PanelRow,{className:"nab-variants-panel"},a.createElement("h2",{className:"nab-variants-panel__title"},(0,b._x)("Variants","text","nelio-ab-testing")),r.map((function(e){var t=e.name,r=e.link,i=e.postId,o=e.index;return a.createElement("div",{className:"nab-alternative",key:i},a.createElement("span",{className:"nab-alternative__letter"},(0,w.getLetter)(o)),a.createElement("span",{className:"nab-alternative__name"},n!==i?a.createElement("a",{href:r},S(t,o)):a.createElement("strong",null,S(t,o))))}))):null},C=function(e){var t,n,r=e.experimentId,i=e.postBeingEdited,o=e.type,l=k(i),s=l[0],c=l[1],u=T(r,i),p=(0,a.useState)(D),d=p[0],v=p[1],f=function(e){return v(x(x({},d),e))},E=d.isImportEnabled,w=d.isConfirmationDialogVisible,_=d.postIdToImportFrom,I=d.variantToImportFrom,h=null!==(n=null===(t=u[0])||void 0===t?void 0:t.value)&&void 0!==n?n:0;if((0,a.useEffect)((function(){I>=0||h<=0||f({postIdToImportFrom:h,variantToImportFrom:h})}),[I,h]),I<0)return null;var y=function(){return f({isImportEnabled:!E})},C=function(){return f({isConfirmationDialogVisible:!w})};return E?a.createElement(m.PanelRow,{className:"nab-content-panel"},a.createElement("h2",{className:"nab-content-panel__title"},(0,b._x)("Content","text","nelio-ab-testing")),a.createElement(m.SelectControl,{label:(0,b._x)("Import content from:","text","nelio-ab-testing"),options:u.map((function(e){return x(x({},e),{value:"".concat(e.value)})})),value:"".concat(I),onChange:function(e){var t;t=Number.parseInt(e)||0,f({variantToImportFrom:t,postIdToImportFrom:t})}}),0===I&&a.createElement(g.PostSearcher,{value:_,className:"nab-content-panel__searcher",type:o,onChange:function(e){return void 0===e&&(e=0),f({variantToImportFrom:0,postIdToImportFrom:e})},menuPlacement:"auto",menuShouldBlockScroll:!0}),a.createElement("div",{className:"nab-content-panel__actions"},a.createElement(m.Button,{variant:"link",onClick:y},(0,b._x)("Cancel","command","nelio-ab-testing")),a.createElement(m.Button,{variant:"secondary",disabled:!_,onClick:C},(0,b._x)("Import","command","nelio-ab-testing")),a.createElement(g.ConfirmationDialog,{title:(0,b._x)("Import Content?","text","nelio-ab-testing"),text:(0,b._x)("This will overwrite the current content.","text","nelio-ab-testing"),confirmLabel:s?(0,b._x)("Importing…","text","nelio-ab-testing"):(0,b._x)("Import","command","nelio-ab-testing"),isConfirmEnabled:!s,isCancelEnabled:!s,isOpen:w,onCancel:C,onConfirm:function(){return c(_)}}))):a.createElement(m.PanelRow,{className:"nab-content-panel"},a.createElement("h2",{className:"nab-content-panel__title"},(0,b._x)("Content","text","nelio-ab-testing")),a.createElement("span",{className:"nab-content-panel__label"},a.createElement(m.Dashicon,{icon:"admin-page"}),a.createElement(m.Button,{variant:"link",onClick:y},(0,b._x)("Import Content","command","nelio-ab-testing"))))},O=function(e){return v((function(t){var n;return(null===(n=t(r.store).getExperiment(e))||void 0===n?void 0:n.name)||(0,b._x)("Unnamed Test","text","nelio-ab-testing")}))},P=function(e){return v((function(t){var n;return(null===(n=t(r.store).getExperiment(e))||void 0===n?void 0:n.links.edit)||""}))},B=function(e){return v((function(t){var n;return(0,f.map)(null===(n=t(r.store).getExperiment(e))||void 0===n?void 0:n.alternatives,(function(e,t){return{index:t,postId:j(e)?e.attributes.postId:0,name:F(e)?e.attributes.name:"",link:e.links.edit}}))}))},N=function(e){return v((function(t){var n,i,o,l,s=t(r.store).getExperiment,c=t(E.store).getExperimentTypes,u=null!==(i=null===(n=s(e))||void 0===n?void 0:n.type)&&void 0!==i?i:"";return null!==(l=null===(o=c()[u])||void 0===o?void 0:o.icon)&&void 0!==l?l:function(){return a.createElement(a.Fragment,null)}}))},k=function(e){var t=(0,a.useState)(0),n=t[0],r=t[1];return(0,a.useEffect)((function(){var t,r,a,i;n&&(t={path:"/nab/v1/post/".concat(n,"/overwrites/").concat(e),method:"PUT"},r=t.url,a=t.path,i=(0,s.format)("YmjHi").substring(0,11)+"0",l()(u(u(u({},t),r&&{url:p(t)?(0,c.addQueryArgs)(r,{nabts:i}):r}),a&&{path:(0,c.addQueryArgs)(a,{nabts:i})}))).finally((function(){window.location.reload()}))}),[n]),[!!n,r]},T=function(e,t){var n=B(e).map((function(e,n){return t===e.postId?void 0:{label:(0,b.sprintf)(/* translators: variant letter */ /* translators: variant letter */
(0,b._x)("Variant %s","text","nelio-ab-testing"),(0,w.getLetter)(n)),value:e.postId}})).filter(w.isDefined);return _(_([],n,!0),[{label:(0,b._x)("Other…","text","nelio-ab-testing"),value:0}],!1)},j=function(e){return!!e.attributes.postId},F=function(e){return!!e.attributes.name},S=function(e,t){return e||(0===t?(0,b._x)("Control Version","text","nelio-ab-testing"):(0,b.sprintf)(/* translators: a letter, such as A, B, or C */ /* translators: a letter, such as A, B, or C */
(0,b._x)("Variant %s","text","nelio-ab-testing"),(0,w.getLetter)(t)))},D={isConfirmationDialogVisible:!1,isImportEnabled:!1,postIdToImportFrom:0,variantToImportFrom:-1};const A=window.wp.editPost,V=window.wp.plugins;var R,L=A.PluginDocumentSettingPanel?function(e){var t=e.experimentId,n=e.postBeingEdited,r=e.type;return a.createElement(A.PluginDocumentSettingPanel,{className:"nab-alternative-editing-sidebar",title:(0,b._x)("Nelio A/B Testing","text","nelio-ab-testing")},a.createElement(I,{experimentId:t,postBeingEdited:n,type:r}))}:function(){return null},M=function(){return M=Object.assign||function(e){for(var t,n=1,r=arguments.length;n<r;n++)for(var a in t=arguments[n])Object.prototype.hasOwnProperty.call(t,a)&&(e[a]=t[a]);return e},M.apply(this,arguments)};window.nab=M(M({},(R=window)&&"object"==typeof R&&"nab"in R?window.nab:{}),{initEditPostAlternativeMetabox:function(e){(0,i.registerCoreExperiments)();var t,n,r=e.experimentId,o=e.postBeingEdited,l=e.type,s=document.getElementById("nelioab_edit_post_alternative_box"),c=null==s?void 0:s.getElementsByClassName("inside")[0];c&&(t=a.createElement(I,{experimentId:r,postBeingEdited:o,type:l}),(n=c)&&(a.createRoot?(0,a.createRoot)(n).render(t):(0,a.render)(t,n)))},initEditPostAlternativeBlockEditorSidebar:function(e){(0,i.registerCoreExperiments)();var t=e.experimentId,n=e.postBeingEdited,r=e.type;(0,V.registerPlugin)("nelio-ab-testing",{icon:function(){return a.createElement(a.Fragment,null)},render:function(){return a.createElement(L,{experimentId:t,postBeingEdited:n,type:r})}})}})}},n={};function r(e){var a=n[e];if(void 0!==a)return a.exports;var i=n[e]={exports:{}};return t[e](i,i.exports,r),i.exports}r.m=t,e=[],r.O=(t,n,a,i)=>{if(!n){var o=1/0;for(u=0;u<e.length;u++){for(var[n,a,i]=e[u],l=!0,s=0;s<n.length;s++)(!1&i||o>=i)&&Object.keys(r.O).every((e=>r.O[e](n[s])))?n.splice(s--,1):(l=!1,i<o&&(o=i));if(l){e.splice(u--,1);var c=a();void 0!==c&&(t=c)}}return t}i=i||0;for(var u=e.length;u>0&&e[u-1][2]>i;u--)e[u]=e[u-1];e[u]=[n,a,i]},r.n=e=>{var t=e&&e.__esModule?()=>e.default:()=>e;return r.d(t,{a:t}),t},r.d=(e,t)=>{for(var n in t)r.o(t,n)&&!r.o(e,n)&&Object.defineProperty(e,n,{enumerable:!0,get:t[n]})},r.o=(e,t)=>Object.prototype.hasOwnProperty.call(e,t),(()=>{var e={9197:0,904:0};r.O.j=t=>0===e[t];var t=(t,n)=>{var a,i,[o,l,s]=n,c=0;if(o.some((t=>0!==e[t]))){for(a in l)r.o(l,a)&&(r.m[a]=l[a]);if(s)var u=s(r)}for(t&&t(n);c<o.length;c++)i=o[c],r.o(e,i)&&e[i]&&e[i][0](),e[i]=0;return r.O(u)},n=globalThis.webpackChunknab=globalThis.webpackChunknab||[];n.forEach(t.bind(null,0)),n.push=t.bind(null,n.push.bind(n))})();var a=r.O(void 0,[904],(()=>r(4948)));a=r.O(a);var i=nab="undefined"==typeof nab?{}:nab;for(var o in a)i[o]=a[o];a.__esModule&&Object.defineProperty(i,"__esModule",{value:!0})})();