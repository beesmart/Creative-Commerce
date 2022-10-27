/* Shop View Switcher - Creative Commerce Snippet */


const layoutSwitch = document.querySelectorAll('.cc-fluid-switch');
const products = document.querySelector(".fl-content .products");
    
    
// On page load check local storage and assign class as required.
// 
const savedView = localStorage.getItem('cc-shop-view');
    
    if(savedView){
        document.querySelector("#" + savedView).classList.add('fluid-on');
        if(savedView === "cc-fluid-rows") {
            products.classList.add('fluid-rows')
        }
    } else {
        document.querySelector("#cc-fluid-grid").classList.add('fluid-on');
    }

    for (const makeSwitch of layoutSwitch) {
        makeSwitch.addEventListener('click', function handleClick(e) {
            e.srcElement.id === "cc-fluid-grid" ? products.classList.remove('fluid-rows') : products.classList.add('fluid-rows')
            
            localStorage.setItem('cc-shop-view', e.srcElement.id);
            
            sibling = getSiblings(e.srcElement);

            e.srcElement.classList.add('fluid-on')
            sibling[0].classList.remove('fluid-on')
            
        });
    }
    

    
var getSiblings = function (elem) {

    // Setup siblings array and get the first sibling
var siblings = [];
var sibling = elem.parentNode.firstChild;

    // Loop through each sibling and push to the array
    while (sibling) {
        if (sibling.nodeType === 1 && sibling !== elem && (sibling.id === "cc-fluid-grid" || sibling.id === "cc-fluid-rows")) {
            siblings.push(sibling);
        }
        sibling = sibling.nextSibling
    }

    return siblings;

};