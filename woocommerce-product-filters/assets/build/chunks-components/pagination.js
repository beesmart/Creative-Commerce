(self.webpackChunkwoocommerce_filters=self.webpackChunkwoocommerce_filters||[]).push([[625],{4712:function(e,a,t){"use strict";t.r(a),t.d(a,{default:function(){return m}});var r=t(9307),n=t(9196),s=t(6483),o=t(5697),u=t.n(o),c=t(7738);const l=e=>{const[a,t]=(0,n.useState)(1);e.currentPage&&a!==e.currentPage&&t(parseInt(e.currentPage,10));const{total:s,limit:o,pageCount:u,className:l}=e,i=(0,c.UM)({limit:o,pageCount:u,total:s,page:a}),{firstPage:g,lastPage:p,hasNextPage:m,hasPreviousPage:P,previousPage:f,nextPage:h,totalPages:d}=i,y=s>0?(0,c.rx)(g,p):[];return(0,r.createElement)("div",{className:l},e.children({pages:y,previousPage:f,nextPage:h,totalPages:d,currentPage:a,hasNextPage:m,hasPreviousPage:P,getPageItemProps:e=>{const{pageValue:a,onPageChange:r,...n}=e;return{onClick:e=>{"function"==typeof r&&r(a,e),t(a)},...n}}}))};l.propTypes={total:u().number.isRequired,className:u().string,limit:u().number,pageCount:u().number,currentPage:u().number,pageValue:u().number,children:u().func.isRequired,onPageChange:u().func},l.defaultProps={limit:10,pageCount:5,currentPage:0,pageValue:0};var i=l,g=t(389);const p=WCF_Frontend;var m=()=>{const{productsCount:e,getStoredValues:a,orderby:t}=(0,g.CR)();let o=1;"undefined"!=typeof WCF_Prefiller&&(o=WCF_Prefiller.paged);const u=!1===e?p.products_count:e,c=p.posts_per_page,[l,m]=(0,n.useState)(o),P=c,f=u,h=async(e,r)=>{if(r.preventDefault(),e!==l)try{await g.cX.run({values:JSON.stringify(a()),paged:e,orderby:t})}catch(e){console.error(e)}},d=e=>(0,s.addQueryArgs)(document.URL,{paged:e});return(0,n.useEffect)((()=>{const e=g.Gd.subscribe((e=>e.paged),(e=>m(e)));return()=>{e()}}),[]),(0,r.createElement)(i,{className:"wcf-pagination-wrapper",total:f,limit:P,pageCount:5,currentPage:parseInt(l)},(e=>{let{pages:a,currentPage:t,hasNextPage:n,hasPreviousPage:s,previousPage:o,nextPage:u,getPageItemProps:c}=e;return(0,r.createElement)("nav",{className:"woocommerce-pagination"},(0,r.createElement)("ul",{className:"page-numbers"},s&&(0,r.createElement)("li",c({pageValue:o,onPageChange:h}),(0,r.createElement)("a",{href:d(o),className:"page-numbers"},"←")),a.map((e=>t===e?(0,r.createElement)("li",c({pageValue:e,key:e,onPageChange:h}),(0,r.createElement)("span",{"aria-current":"page",className:"page-numbers current"},e)):(0,r.createElement)("li",c({pageValue:e,key:e,onPageChange:h}),(0,r.createElement)("a",{href:d(e),className:"page-numbers"},e)))),n&&(0,r.createElement)("li",c({pageValue:u,onPageChange:h}),(0,r.createElement)("a",{href:d(u),className:"page-numbers"},"→"))))}))}},7738:function(e,a){"use strict";a.rx=function(e,a){return[...Array(a-e+1)].map(((a,t)=>e+t))},a.UM=function(e){const{limit:a,pageCount:t,total:r,page:n}=e,s=Math.ceil(r/a),o=r;let u=n;u<1&&(u=1),u>s&&(u=s);let c=Math.max(1,u-Math.floor(t/2)),l=Math.min(s,u+Math.floor(t/2));l-c+1<t&&(u<s/2?l=Math.min(s,l+(t-(l-c))):c=Math.max(1,c-(t-(l-c)))),l-c+1>t&&(u>s/2?c+=1:l-=1);const i=a*(u-1),g=a*u-1;return{totalPages:s,pages:Math.min(l-c+1,s),currentPage:u,firstPage:c,lastPage:l,previousPage:u-1,nextPage:u+1,hasPreviousPage:u>1,hasNextPage:u<s,totalResults:o,results:Math.min(g-i+1,o),firstResult:i,lastResult:g}}},2703:function(e,a,t){"use strict";var r=t(414);function n(){}function s(){}s.resetWarningCache=n,e.exports=function(){function e(e,a,t,n,s,o){if(o!==r){var u=new Error("Calling PropTypes validators directly is not supported by the `prop-types` package. Use PropTypes.checkPropTypes() to call them. Read more at http://fb.me/use-check-prop-types");throw u.name="Invariant Violation",u}}function a(){return e}e.isRequired=e;var t={array:e,bigint:e,bool:e,func:e,number:e,object:e,string:e,symbol:e,any:e,arrayOf:a,element:e,elementType:e,instanceOf:a,node:e,objectOf:a,oneOf:a,oneOfType:a,shape:a,exact:a,checkPropTypes:s,resetWarningCache:n};return t.PropTypes=t,t}},5697:function(e,a,t){e.exports=t(2703)()},414:function(e){"use strict";e.exports="SECRET_DO_NOT_PASS_THIS_OR_YOU_WILL_BE_FIRED"}}]);