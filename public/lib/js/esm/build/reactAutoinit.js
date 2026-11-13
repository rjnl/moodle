import{flushSync as p}from"react-dom";import{dynamicImport as M}from"@moodle/lms/core/dynamicImport";import{isProfilerEnabled as g}from"@moodle/lms/core/profiler";import{mountReactApp as v,unmountReactApp as y}from"@moodle/lms/core/mount";import A from"@moodle/lms/core/pending";/**
 * Auto-init shim for Mustache React helper components.
 *
 * Scans the DOM for elements with the `data-react-component` attribute and
 * mounts the matching React component into each one. A MutationObserver watches
 * for dynamically injected content (AJAX, fragments) so components are mounted
 * and unmounted automatically without any additional initialiser call.
 *
 * The expected DOM contract is:
 * ```html
 *   <div
 *     data-react-component="@mod_book/viewer"
 *     data-react-props='{"title":"My Book"}'
 *   ></div>
 * ```
 *
 * @module     core/reactAutoinit
 * @copyright  Meirza <meirza.arson@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */const i="[data-react-component]",h=200,u=new WeakMap,s=new Map,c=[],n=g(),E=async()=>{if(document.readyState==="loading")return new Promise(t=>{document.addEventListener("DOMContentLoaded",()=>{t()},{once:!0})})},P=t=>{const e=t.dataset.reactProps;if(e===void 0||e==="")return{};try{const o=JSON.parse(e);return typeof o!="object"||o===null?{}:o}catch(o){return console.error("[reactAutoinit] invalid JSON",e,o),{}}},C=async t=>{if(!t.startsWith("@moodle/lms/")){console.error("[reactAutoinit] Invalid component format, expected @moodle/lms/<component>/<path>:",t);return}try{return n&&console.log(`[reactAutoinit] Loading: ${t}`),await M(t)}catch(e){console.error(`[reactAutoinit] Failed to import: ${t}`,e);return}},T=(t,e,o)=>{const r=t.dataset.reactComponent??"Unknown",a=v(t,e,o,{id:r});u.set(t,a)},w=({element:t,component:e})=>{const o=t.dataset.reactComponent;if(t.isConnected&&t.dataset.reactMounting!==void 0)try{T(t,e,P(t)),t.dataset.reactMounted="1",n&&console.log(`[reactAutoinit] Mounted via default: ${o}`)}catch(r){console.error("[reactAutoinit] Mount failed:",o,r)}delete t.dataset.reactMounting},f=()=>{const t=c.splice(0,h);p(()=>{for(const e of t)w(e)});for(const{pendingPromise:e}of t)e.resolve();c.length>0&&setTimeout(f,0)},L=t=>{c.push(t),c.length===1&&queueMicrotask(f)},d=async t=>{if(t.dataset.reactMounted!==void 0||t.dataset.reactMounting!==void 0)return;t.dataset.reactMounting="1";const e=t.dataset.reactComponent;if(e===void 0||e===""){delete t.dataset.reactMounting;return}const o=new A(`reactAutoInit:${e}`);s.has(e)||s.set(e,C(e));const r=await s.get(e),a=r?.default;if(a){L({element:t,component:a,pendingPromise:o});return}console.warn(r?"[reactAutoinit] Module has no default export:":"[reactAutoinit] Component not found:",e),delete t.dataset.reactMounting,o.resolve()},l=t=>{const e=u.get(t)??(()=>{y(t)});try{e(),n&&console.log(`[reactAutoinit] Unmounted: ${t.dataset.reactComponent}`)}catch(o){console.error("[reactAutoinit] Error unmounting:",o)}u.delete(t),delete t.dataset.reactMounted,delete t.dataset.reactMounting},b=t=>{const e=t.querySelectorAll(i);n&&e.length>0&&console.log(`[reactAutoinit] Found ${e.length} component(s) to mount`);for(const o of e)d(o)},R=t=>{if(t instanceof HTMLElement){t.matches(i)&&(n&&console.log("[reactAutoinit] New component detected"),d(t));for(const e of t.querySelectorAll(i))d(e)}},H=t=>{if(t instanceof HTMLElement){t.matches(i)&&l(t);for(const e of t.querySelectorAll(i))l(e)}},O=()=>{const t=new MutationObserver(e=>{for(const o of e)o.addedNodes.forEach(R),o.removedNodes.forEach(H)});return t.observe(document.documentElement,{childList:!0,subtree:!0}),t};let m=null;const S=async()=>{await E(),n&&console.log("[reactAutoinit] Initializing (profiling enabled)..."),m===null&&(m=O()),n&&console.log("[reactAutoinit] MutationObserver active"),b(document)};S();
