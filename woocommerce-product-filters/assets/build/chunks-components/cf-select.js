"use strict";(self.webpackChunkwoocommerce_filters=self.webpackChunkwoocommerce_filters||[]).push([[226],{7061:function(e,t,l){l.r(t);var o=l(9307),s=l(4376),a=l(5736),n=l(2819),r=l(7536),u=l(5236),c=l(598),i=l(374),f=l(389),d=l(9950),m=l(4271);const p=WCF_Frontend;t.default=e=>{let{name:t,value:l,setValue:g,options:h,group:b,..._}=e;const{watch:v,resetField:E}=(0,r.Gc)(),{layout:w}=(p.filter_num_products,_),{getGroup:C,getInitialCounts:F,counts:k,prefilledValues:y,setPrefilling:B,getStoredValue:S}=(0,f.CR)(),N=S(b,t),R=v(t,N),V=C(b),z=!!(0,n.has)(V,"data."+_.facet.slug)&&(0,o.useMemo)((()=>V.data[_.facet.slug]),[V.data[_.facet.slug]]);if(!z)return(0,o.createElement)(o.Fragment,null,(0,a.__)("No values found for this custom field.","woocommerce-product-filters"));const G=(0,s.Lm)()<960,[H,L]=(0,o.useState)(null),[M,T]=(0,o.useState)([]);(0,u.H)((function(){const e=F(t);(0,n.has)(k,t)?T(k[t]):T(e)})),(0,o.useEffect)((()=>{const e=R;if((0,n.isUndefined)(e)||!1===e)L([]);else if(!(0,n.isUndefined)(e)&&!1!==e){const t=z.find((t=>t.value===e));t&&L([t])}}),[R]),(0,o.useEffect)((()=>{if((0,n.has)(y,t)){const e=y[t].toString();z.find((t=>t.value===e))&&(B(!0),g(t,e),B(!1))}}),[y]),(0,o.useEffect)((()=>((0,f.BB)(T,t),m.YB.on("filterToggled-"+t,(e=>{g(t,[]),E(t),L(null)})),()=>{m.YB.remove("filterToggled-"+t)})),[]),(0,i.Z)("wcf-reset-filters",(()=>{B(!0),E(t),L(null),B(!1)}));const U=(e=>{const t=[];return(0,n.isEmpty)(M)?[]:((0,n.forEach)(e,((e,l)=>{t.push({label:e.label,id:e.value})})),t)})(z),Y=p.searchable_mobile;return(0,o.createElement)(o.Fragment,null,(0,o.createElement)(c.Z,{options:U,value:H,placeholder:(0,n.isEmpty)(U)?(0,a.__)("No options available","woocommerce-product-filters"):_.label,onChange:e=>(e=>{const l=(0,n.map)(e,"id");L(e),g(t,l[0])})(e.value),searchable:G&&Y,overrides:"horizontal"===w?d.Rd:d.Hz,noResultsMsg:(0,a.__)("No results"),clearable:!1,disabled:(0,n.isEmpty)(U),getOptionLabel:e=>{let{option:t}=e;return(0,o.createElement)(o.Fragment,null,t.label)}}))}}}]);