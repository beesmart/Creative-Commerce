"use strict";(self.webpackChunkwoocommerce_filters=self.webpackChunkwoocommerce_filters||[]).push([[984],{5110:function(e,t,r){r.r(t);var a=r(7462),l=r(9307),s=r(9196),n=r(3026),c=r(385),u=r(2629),i=r(2819),o=r(5736),f=r(5236),d=r(7536),m=r(7826),p=r.n(m),g=r(374),h=r(389),E=r(4271),v=r(9950);const y=WCF_Frontend;t.default=e=>{let{name:t,value:r,setValue:m,options:w,group:_,...k}=e;const{watch:A}=(0,d.Gc)(),C="yes"===y.filter_num_products,{getGroup:b,getStoredValue:F,getInitialCounts:V,counts:S,prefilledValues:Z,setPrefilling:B}=(0,h.CR)(),G=F(_,t),N=A(t,G),[O,P]=(0,s.useState)(Array.isArray(G)?G:[]),[T,W]=(0,s.useState)([]),Y=b(_),q=!!(0,i.has)(Y,"data."+k.facet.slug)&&(0,s.useMemo)((()=>Y.data[k.facet.slug]),[Y.data[k.facet.slug]]);if(!q)return(0,l.createElement)(l.Fragment,null,(0,o.__)("No values found for this custom field.","woocommerce-product-filters"));(0,f.H)((()=>{const e=V(t);(0,i.has)(S,t)?W(S[t]):W(e)})),(0,s.useEffect)((()=>{let e=N;Array.isArray(e)||(0,i.isEmpty)(e)||(e=e.split(",")),(0,i.isEqual)(e,O)||P(Array.isArray(e)?p()(e):[])}),[N]),(0,s.useEffect)((()=>{(0,i.has)(Z,t)&&(B(!0),m(t,Z[t]),B(!1))}),[Z]);const x=e=>{var t;return null!==(t=T.find((t=>t.facet_value===e))?.counter)&&void 0!==t?t:0},H=e=>{let{value:t,label:r}=e;return(0,l.createElement)("div",null,(0,l.createElement)(n.Z,{checked:O.includes(t),labelPlacement:c.Oi.right,overrides:v.W9,onChange:e=>I(e.target.checked,t)},(0,u.decodeEntities)(r),C&&(0,l.createElement)("span",{className:"wcf-choices-counter"},"(",x(t),")")))},I=(e,r)=>{let a=O.filter((function(){return!0}));if(!0===e)a.push(r);else if(!1===e){var l=O.indexOf(r);a.splice(l,1)}P(a),(0,i.isEmpty)(a)?m(t,!1):(0,i.isEmpty)(a)||m(t,p()(a))};return(0,s.useEffect)((()=>(E.YB.on("filterToggled-"+t,(e=>{B(!0),m(t,e.value),B(!1)})),()=>{E.YB.remove("filterToggled-"+t)})),[]),(0,g.Z)("wcf-reset-filters",(()=>{B(!0),m(t,[]),P([]),B(!1)})),(0,l.createElement)(l.Fragment,null,(0,l.createElement)("div",{id:"wcf-databaseValues-container-"+t},(()=>{const e=[];return(0,i.forEach)(q,((t,r)=>{const a=x(t.value);if(!a||0===a)return!1;e.push(t)})),e})().map(((e,t)=>(0,l.createElement)(H,(0,a.Z)({key:t},e))))))}}}]);