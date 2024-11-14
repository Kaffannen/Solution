import StateController from "https://kaffannen.github.io/Solution/Javascript/EzUI/InternalSupers/StateController.js";

export default class ElementController extends StateController{
    /**
     * Child UIElements
     * @type {Map<String, UIElement>}
     * */
    #UIElements= new Map();

    /**
     * Assigns a UIElement to this Pojo
     * @param {UIElement} element
     */
    addUIElement(element){
        this.#UIElements.set(element.constructor.name,element);
        return element;
    }

    /**
     * Postconstruct method when called from ElementController
     */
    defineUIElements(){
        this._infuseSearchPaths(this);
        this.setState(this.constructor.STATES.INIT)
    }

    /**
     * Gives access to a UIElement by its classdefinition or classname
     * @param {Function | String}classIdentifier
     * @returns {UIElement |*}
     */
    getUIElement(classIdentifier){
        if(classIdentifier instanceof Function)
            return this.#UIElements.get(classIdentifier.name);
        else if (typeof classIdentifier === 'string')
            return this.#UIElements.get(classIdentifier);
        else throw new Error ("Wrong input")
    }

    /**
     * Private method
     */
    _detachAllUIElements(){
        this.#UIElements.forEach(UIElement=>UIElement.detach());
    }

    /**
     * Private postconstruct method
     */
    _infuseSearchPaths(){
        let searchPath = JSON.stringify(_createSearchPathObject(this));
        this.#UIElements.forEach((element, name) => {
            element._infuseSearchPath(searchPath);
        });

        /**
         *
         * @param {ElementNode | ElementController}node
         * @return {*[]}
         */
        function _createSearchPathObject(node){
            if (node instanceof EzUI)
                return[]
            else {
                return [node.getData().id].concat(_createSearchPathObject(node.getParentNode()));
            }
        }
    }
}