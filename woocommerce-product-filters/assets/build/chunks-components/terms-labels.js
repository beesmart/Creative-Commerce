"use strict";(self.webpackChunkwoocommerce_filters=self.webpackChunkwoocommerce_filters||[]).push([[908],{8186:function(e,t,a){a.r(t);var r=a(9307),s=a(9196),o=a(2819),n=a(5736),i=a(1075),l=a(5236),c=a(2629),m=a(7536),u=a(374),d=a(9950),f=a(389),p=a(4271);const h=(0,r.lazy)((()=>a.e(172).then(a.bind(a,4230)).then((e=>({default:e.Button}))))),g=(0,r.lazy)((()=>a.e(286).then(a.bind(a,2489)).then((e=>({default:e.ButtonGroup}))))),y=(0,r.lazy)((()=>a.e(683).then(a.bind(a,521)))),E=WCF_Frontend;t.default=e=>{let{name:t,value:a,setValue:w,options:b,group:_,...S}=e;const{watch:A}=(0,m.Gc)(),v="yes"===E.filter_num_products,[k,B]=(0,s.useState)(12),{submittedValues:z,getGroup:C,getStoredValue:N,getInitialCounts:F,counts:M,prefilledValues:V,setPrefilling:I}=(0,f.CR)(),G=N(_,t),Y=A(t,N(_,t)),[x,L]=(0,s.useState)(Array.isArray(G)?G:[]),[T,q]=(0,s.useState)(!0),[H,O]=(0,s.useState)([]),P=C(_),R=!!(0,o.has)(P,"data."+S.facet.slug)&&(0,s.useMemo)((()=>P.data[S.facet.slug]),[P.data[S.facet.slug]]);if(!R)return(0,r.createElement)(r.Fragment,null,(0,n.__)("No terms found for the specified taxonomy.","woocommerce-product-filters"));(0,l.H)((async()=>{const e=F(t);e&&O(e),(0,o.has)(M,t)&&O(M[t]),"horizontal"===S.layout&&(await(0,p.f6)(B,5,t),(0,o.has)(z,t)&&L(Array.isArray(z[t])?z[t].map(String):[]))})),(0,s.useEffect)((()=>{const e=Y;e!==x&&L(Array.isArray(e)?e.map(String):[])}),[Y]),(0,s.useEffect)((()=>{(0,o.has)(V,t)&&(I(!0),w(t,V[t].map(String)),I(!1))}),[V]),(0,s.useEffect)((()=>{"horizontal"!==S.layout&&(0,f.BB)(O,t,{method:q,itemsAmount:12,mobile:S?.mobile})}),[]),(0,s.useEffect)((()=>{if((0,o.has)(z,t)&&!0===S.mobile){const e=z[t].map(String);(0,o.isEqual)(e,x)||(I(!0),w(t,e),I(!1))}}),[z]),(0,s.useEffect)((()=>(p.YB.on("filterToggled-"+t,(e=>{I(!0),w(t,e.value),I(!1)})),()=>{p.YB.remove("filterToggled-"+t)})),[]),(0,u.Z)("wcf-reset-filters",(()=>{I(!0),w(t,[]),L([]),I(!1)}));const W=(0,p.V9)(R,H),Z=!0===T||!0===S?.mobile?W.slice(0,k):W,j=Array.isArray(W)?parseInt(W.length):0;return(0,o.isEmpty)(Z)?(0,r.createElement)("span",{className:"wcf-no-options-label"},(0,n.__)("No options available","woocommerce-product-filters")):(0,r.createElement)(r.Fragment,null,(0,r.createElement)("div",{id:"wcf-items-container-"+t},(0,r.createElement)(g,{overrides:d.BY,size:i.NO.compact,mode:"checkbox",selected:x,onClick:(e,a)=>{let r=[],s=e.target.dataset.termId;(0,o.isEmpty)(s)&&(s=e.target.parentElement.dataset.termId),r=x.includes(s)?x.filter((e=>e!==s)):[...x,s],L(r),w(t,r)}},Z.map((e=>(0,r.createElement)(h,{key:e.term_id,isSelected:x.includes(Number.isFinite(e.term_id)?e.term_id.toString():e.term_id),type:"button","data-term-id":e.term_id,overrides:d.w9},(0,c.decodeEntities)(e.name),v&&(0,r.createElement)("span",{className:"wcf-choices-counter"},"(",e.count,")"))))),(0,r.createElement)(y,{displayShowMore:j>k,showMoreMethod:()=>B(j),displayShowLess:k>12,showLessMethod:()=>B(12)})))}}}]);