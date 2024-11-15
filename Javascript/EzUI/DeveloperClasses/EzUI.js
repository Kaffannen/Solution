//import ElementNode from "https://kaffannen.github.io/Solution/Javascript/EzUI/DeveloperClasses/ElementNode.js";

//export default class EzUI extends ElementNode{
class EzUI extends ElementNode{
    #api;

    constructor(api) {
        super();
        this.#api = api;
    }

    /**
     * Please override this method to provide doc
     * @return {EasyChatAPI | EasyChatMockAPI}
     */
    getApi(){
        return this.#api;
    }

    // /**
    //  *
    //  * @param {HTMLElement |*} element
    //  * @return {Node}
    //  */
    // findNode(element){
    //     while (!element.hasAttribute("data-searchobject")&&element.parentElement)
    //         element=element.parentElement;
    //     let pathArray;
    //     if (element.hasAttribute("data-searchobject"))
    //         pathArray = JSON.parse(element.getAttribute("data-searchobject"))
    //     else
    //         pathArray = [];
    //     let pojo=this;
    //     while (pathArray.length!==0){
    //         pojo=pojo.getChildNode(pathArray.pop());
    //     }
    //     return pojo;
    // }
    //
    // /**
    //  *
    //  * @param {HTMLElement | *} element
    //  * @param {Function} classConstructor
    //  * @return {UIElement}
    //  */
    // findUIElement(element,classConstructor){
    //     if (!(classConstructor instanceof Function))
    //         throw new Error("FindUIElement was called with string not a function (remove.name from parameter)");
    //     let node = this.findNode(element);
    //     return node.getUIElement(classConstructor);
    // }
    // /**
    //  * @param {HTMLElement | *} element
    //  * @param {Function} classname
    //  * @param {String} inputElementKey
    //  * @returns {HTMLInputElement}
    //  */
    // findInputElement(element,classname,inputElementKey){
    //     let uIElement = this.findUIElement(element,classname);
    //     return uIElement.getInputElement(inputElementKey);
    // }
    find(element){
        while (!element.hasAttribute("data-searchobject")&&element.parentElement)
            element=element.parentElement;
        let pathArray;
        let elementClassName;
        if (element.hasAttribute("data-searchobject")){
            pathArray = JSON.parse(element.getAttribute("data-searchobject"));
            elementClassName=element.id;
        }
        else
            pathArray = [];
        let pojo=this;
        while (pathArray.length!==0){
            pojo=pojo.getChildNode(pathArray.pop());
        }
        return pojo.getUIElement(elementClassName);
    }
}

/**
 * Finds the UIElement which contains the HTMLElement
 * @param element the element which triggers the function
 * @return {UIElement|*}
 */
function find(element){
    while (!element.hasAttribute("data-searchobject")&&element.parentElement)
        element=element.parentElement;
    let pathArray;
    let elementClassName;
    if (element.hasAttribute("data-searchobject")){
        pathArray = JSON.parse(element.getAttribute("data-searchobject"));
        elementClassName=element.id;
    }
    else
        pathArray = [];
    let pojo=program;
    while (pathArray.length!==0){
        pojo=pojo.getChildNode(pathArray.pop());
    }
    return pojo.getUIElement(elementClassName);
}