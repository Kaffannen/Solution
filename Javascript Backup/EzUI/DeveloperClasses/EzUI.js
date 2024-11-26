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
    /**
     * Finds the UIElement which contains the HTMLElement
     * @param element the element which triggers the function
     * @return {UIElement|*}
     */
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
