(()=>{var __webpack_modules__={2132:(_,e,r)=>{"use strict";r.r(e);var a,t=r(97299);window.nab=Object.assign(Object.assign({},(a=window)&&"object"==typeof a&&"nab"in a?window.nab:{}),{initJavaScriptPreviewer:t.p})},49180:(_,e,r)=>{"use strict";r.d(e,{Fc:()=>i,im:()=>c});var a=r(54494);function t(_){if(!/^[a-z]+:\/\//.test(_))return!1;_=_.replace(/^https?:\/\//,"http://");const e=document.location.href.replace(/^https?:\/\//,"").replace(/\/.*$/,"");return 0!==_.indexOf("http://"+e)}function i(){Array.from(document.querySelectorAll("a")).forEach((_=>{t(_.getAttribute("href")||"")&&(_.classList.add("nab-disabled-link"),_.addEventListener("click",(_=>_.preventDefault())))}))}function c(_,e){Array.from(document.querySelectorAll("a")).forEach((r=>{const i=r.getAttribute("href")||"";if(t(i))return;const c=i.replace(/#.*$/,""),p=i.replace(/^[^#]*/,""),u=(0,a.addQueryArgs)(c,{[_]:null!=e?e:""})+p;r.setAttribute("href",u)}))}},54494:(_,e,r)=>{"use strict";var a=r(93832);r.o(a,"addQueryArgs")&&r.d(e,{addQueryArgs:function(){return a.addQueryArgs}}),r.o(a,"getQueryArgs")&&r.d(e,{getQueryArgs:function(){return a.getQueryArgs}})},70557:(_,e,r)=>{"use strict";r.d(e,{default:()=>t});var a=r(98490);const t=r.n(a)()},93832:_=>{"use strict";_.exports=window.wp.url},97299:(__unused_webpack_module,__webpack_exports__,__webpack_require__)=>{"use strict";__webpack_require__.d(__webpack_exports__,{p:()=>initJavaScriptPreviewer});var _safe_wordpress_dom_ready__WEBPACK_IMPORTED_MODULE_0__=__webpack_require__(70557),_safe_wordpress_url__WEBPACK_IMPORTED_MODULE_1__=__webpack_require__(54494),_packages_utils_links__WEBPACK_IMPORTED_MODULE_2__=__webpack_require__(49180);function initJavaScriptPreviewer(alternative){(0,_packages_utils_links__WEBPACK_IMPORTED_MODULE_2__.Fc)(),(0,_packages_utils_links__WEBPACK_IMPORTED_MODULE_2__.im)("nab-javascript-previewer");const run=eval(`(()=>${alternative.run})()`);run((()=>{}),{showContent:()=>{},domReady:_safe_wordpress_dom_ready__WEBPACK_IMPORTED_MODULE_0__.default});const value=(0,_safe_wordpress_url__WEBPACK_IMPORTED_MODULE_1__.getQueryArgs)(document.location.href)["nab-javascript-previewer"];value&&(0,_safe_wordpress_dom_ready__WEBPACK_IMPORTED_MODULE_0__.default)((()=>(0,_packages_utils_links__WEBPACK_IMPORTED_MODULE_2__.im)("nab-javascript-previewer",value)))}},98490:_=>{"use strict";_.exports=window.wp.domReady}},__webpack_module_cache__={},deferred;function __webpack_require__(_){var e=__webpack_module_cache__[_];if(void 0!==e)return e.exports;var r=__webpack_module_cache__[_]={exports:{}};return __webpack_modules__[_](r,r.exports,__webpack_require__),r.exports}__webpack_require__.m=__webpack_modules__,deferred=[],__webpack_require__.O=(_,e,r,a)=>{if(!e){var t=1/0;for(u=0;u<deferred.length;u++){for(var[e,r,a]=deferred[u],i=!0,c=0;c<e.length;c++)(!1&a||t>=a)&&Object.keys(__webpack_require__.O).every((_=>__webpack_require__.O[_](e[c])))?e.splice(c--,1):(i=!1,a<t&&(t=a));if(i){deferred.splice(u--,1);var p=r();void 0!==p&&(_=p)}}return _}a=a||0;for(var u=deferred.length;u>0&&deferred[u-1][2]>a;u--)deferred[u]=deferred[u-1];deferred[u]=[e,r,a]},__webpack_require__.n=_=>{var e=_&&_.__esModule?()=>_.default:()=>_;return __webpack_require__.d(e,{a:e}),e},__webpack_require__.d=(_,e)=>{for(var r in e)__webpack_require__.o(e,r)&&!__webpack_require__.o(_,r)&&Object.defineProperty(_,r,{enumerable:!0,get:e[r]})},__webpack_require__.o=(_,e)=>Object.prototype.hasOwnProperty.call(_,e),__webpack_require__.r=_=>{"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(_,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(_,"__esModule",{value:!0})},(()=>{var _={6744:0,209:0};__webpack_require__.O.j=e=>0===_[e];var e=(e,r)=>{var a,t,[i,c,p]=r,u=0;if(i.some((e=>0!==_[e]))){for(a in c)__webpack_require__.o(c,a)&&(__webpack_require__.m[a]=c[a]);if(p)var o=p(__webpack_require__)}for(e&&e(r);u<i.length;u++)t=i[u],__webpack_require__.o(_,t)&&_[t]&&_[t][0](),_[t]=0;return __webpack_require__.O(o)},r=globalThis.webpackChunknab=globalThis.webpackChunknab||[];r.forEach(e.bind(null,0)),r.push=e.bind(null,r.push.bind(r))})();var __webpack_exports__=__webpack_require__.O(void 0,[209],(()=>__webpack_require__(2132)));__webpack_exports__=__webpack_require__.O(__webpack_exports__);var __webpack_export_target__=nab="undefined"==typeof nab?{}:nab;for(var __webpack_i__ in __webpack_exports__)__webpack_export_target__[__webpack_i__]=__webpack_exports__[__webpack_i__];__webpack_exports__.__esModule&&Object.defineProperty(__webpack_export_target__,"__esModule",{value:!0})})();