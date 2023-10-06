"use strict";(self.webpackChunkwoocommerce_filters=self.webpackChunkwoocommerce_filters||[]).push([[769],{8136:function(e,t,l){l.r(t),l.d(t,{default:function(){return w}});var n=l(9307),r=l(9196),o=l(598),a=l(2819),s=l(5736),c=function(){return c=Object.assign||function(e){for(var t,l=1,n=arguments.length;l<n;l++)for(var r in t=arguments[l])Object.prototype.hasOwnProperty.call(t,r)&&(e[r]=t[r]);return e},c.apply(this,arguments)},i=l(5236),u=l(4548),f=l(7536),d=l(374),p=l(9950),h=l(389),m=l(4271),g=l(4184),b=l.n(g),y=l(4376);const v=WCF_Frontend,E=e=>{let{slug:t,term:l,handleChildrenSelection:r,hasValue:a,map:c,layout:i,searchable:u,getOptionLabel:f,label:d,getPlaceholder:h}=e;const m=a(l.depth+1)?c[l.depth+1]:null;return(0,n.createElement)(o.Z,{key:t,options:l.children,value:m,onChange:e=>r(e.value),overrides:"horizontal"===i?p.Rd:p.Hz,noResultsMsg:(0,s.__)("No results"),searchable:u,clearable:!1,placeholder:h(l.children,m,(0,s.__)("Select an option")),getOptionLabel:f})};var w=e=>{let{name:t,value:l,setValue:g,options:w,group:k,..._}=e;const{watch:C,resetField:O}=(0,f.Gc)(),{layout:A}=(v.filter_num_products,_),{getGroup:S,getStoredValue:M,getInitialCounts:j,counts:F,prefilledValues:V,setPrefilling:L,submittedValues:N}=(0,h.CR)(),[R,z]=(0,r.useState)([]),[B,P]=(0,r.useState)([]),H=(0,y.Lm)()<960,Z=S(k),G=!!(0,a.has)(Z,"data."+_.facet.slug)&&(0,r.useMemo)((()=>Z.data[_.facet.slug]),[Z.data[_.facet.slug]]);if(!G)return(0,n.createElement)(n.Fragment,null,(0,s.__)("No terms found for the specified taxonomy.","woocommerce-product-filters"));const[T,{set:U,setMultiple:Y,has:x,remove:I,removeMultiple:W,removeAll:q}]=function(e){var t=(0,r.useState)({}),l=t[0],n=t[1],o=(0,r.useCallback)((function(e,t){n((function(l){var n;return c(c({},l),((n={})[e]=t,n))}))}),[]),a=(0,r.useCallback)((function(e){return void 0!==l[e]}),[l]),s=(0,r.useCallback)((function(e){n((function(t){return c(c({},t),e)}))}),[]),i=(0,r.useCallback)((function(){for(var e=[],t=0;t<arguments.length;t++)e[t]=arguments[t];n((function(t){for(var l=c({},t),n=0,r=e;n<r.length;n++)delete l[r[n]];return l}))}),[n]),u=(0,r.useCallback)((function(e){n((function(t){var l=c({},t);return delete l[e],l}))}),[n]),f=(0,r.useCallback)((function(){n((function(e){var t=c({},e);for(var l in t)delete t[l];return t}))}),[n]);return[l,{has:a,remove:u,removeAll:f,removeMultiple:i,set:o,setMultiple:s}]}(),D=M(k,t),J=C(t,D);(0,i.H)((function(){const e=j(t);(0,a.has)(F,t)?P(F[t]):P(e)}));const K=e=>{let{option:t}=e;return(0,n.createElement)(n.Fragment,null,t.label)},Q=(e=>{const t=[];return(0,a.isEmpty)(B)?[]:((0,a.forEach)(e,((e,l)=>{t.push(e)})),t)})(G),X=b()("wcf-dropdown-wrapper","wcf-hierarchical-dropdown",{"is-active":!(0,a.isEmpty)(R)}),$=v.searchable_mobile,ee=e=>{const t=e[0];if(t){const e=te([t]),l=t.depth;!(0,a.isEmpty)(e)&&Array.isArray(e)&&(0,a.forEach)(T,((e,t)=>{e.depth>=l&&I(e.depth)})),U(t.depth,t)}},te=e=>{let t=[];return e.map((e=>(e.children&&e.children.length&&(t=[...t,...e.children]),e))).concat(t.length?te(t):t)},le=(e,t,l)=>(0,a.isEmpty)(e)?(0,s.__)("No options available","woocommerce-product-filters"):(0,a.isEmpty)(t)&&!(0,a.isEmpty)(e)?l:null;(0,u.l)((()=>{let e=T[Object.keys(T).pop()];g(t,e.id)}),[T]);const ne=(e,t)=>{if(e.id===t)return[e.slug];if(e.children||Array.isArray(e)){let l=Array.isArray(e)?e:e.children;for(let n of l){let l=ne(n,t);if(l)return e.id&&l.unshift(e.slug),l}}};return(0,r.useEffect)((()=>{const e=J;if((0,a.isUndefined)(e)||!1===e)z([]);else if(!(0,a.isUndefined)(e)&&!1!==e){const t=(0,m.w8)(G,e);if(t?.id){const e=ne(G,t.id);if(!(0,a.isEmpty)(e)){const t={};e.map(((e,l)=>{const n=(0,m.w8)(G,e);n&&(t[n.depth]=n)})),(0,a.isEmpty)(t)||Y(t)}z([t])}}}),[J]),(0,r.useEffect)((()=>{if((0,a.has)(V,t)){const e=(0,m.w8)(G,V[t].toString());if(!(0,a.isEmpty)(e)){const t=ne(G,e.id);if(!(0,a.isEmpty)(t)){const e={};t.map(((t,l)=>{const n=(0,m.w8)(G,t);n&&(e[n.depth]=n)})),(0,a.isEmpty)(e)||Y(e)}}}}),[V]),(0,r.useEffect)((()=>((0,h.BB)(P,t),m.YB.on("filterToggled-"+t,(e=>{g(t,[]),O(t),z("")})),()=>{m.YB.remove("filterToggled-"+t)})),[]),(0,d.Z)("wcf-reset-filters",(()=>{L(!0),g(t,!1),z(""),L(!1)})),(0,n.createElement)(n.Fragment,null,(0,n.createElement)("div",{className:X},(0,n.createElement)(o.Z,{options:Q,value:T[Object.keys(T)[0]],onChange:e=>(e=>{q();const t=e[0];t&&U(t.depth,t)})(e.value),searchable:H&&$,overrides:"horizontal"===A?p.Rd:p.Hz,noResultsMsg:(0,s.__)("No results"),clearable:!1,placeholder:le(Q,T[Object.keys(T)[0]],_.label),getOptionLabel:K,disabled:(0,a.isEmpty)(Q)}),(()=>{const e=[];return Object.keys(T).forEach((function(t){const l=t,r=T[t];r?.children&&e.push((0,n.createElement)(E,{key:l,slug:l,term:r,hasValue:x,map:T,handleChildrenSelection:ee,layout:A,searchable:H&&$,getOptionLabel:K,label:_.label,getPlaceholder:le}))})),e})()))}}}]);