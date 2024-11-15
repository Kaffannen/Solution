//import ElementController from "https://kaffannen.github.io/Solution/Javascript/EzUI/InternalSupers/ElementController.js";

//export default class ElementNode extends ElementController {
class ElementNode extends ElementController {
    /**
     *@type {Object}
     */
    #data;

    /**
     * The Pojo's parent in tree structure
     * @type{ElementNode | EzUI}
     */
    #parent;

    /**
     * The Pojo's children in the tree structure
     * @type{Map<Number, ElementNode>}
     */
    #children;

    /**
     * A particular child
     * @type {ElementNode}
     */
    #favourite;

    /**
     * Constructor
     * @param{Object} pojo
     * @param {ElementNode} parent
     */
    constructor(pojo = undefined, parent = undefined) {
        super();
        this.#data = pojo;
        this.#parent = parent;
        if (parent !== undefined)
            parent._addChildNode(this);
        this.#children = new Map();
    }

    /**
     * Gives access to the parent
     * @returns {ElementNode}
     */
    getParentNode() {
        return this.#parent;
    }

    /**
     * private helper method for constructing
     * @param {ElementNode}child
     */
    _addChildNode(child) {
        this.#children.set(child.getData().id, child);
    }

    /**
     * Gives access to a child
     * @param {Number} identifier
     * @returns {ElementNode}
     */
    getChildNode(identifier) {
        return this.#children.get(identifier);
    }

    /**
     * Orphans a child and detaches all of its UIElements
     * @param {ElementNode}child
     */
    removeChild(child) {
        detachRecursively(child);
        this.#children.delete(child.getData().id)
        if (this.getFavourite()===child)
            this.setFavourite(undefined);
        /**
         *
         * @param {ElementNode} node
         */
        function detachRecursively(node) {
            node._getChildren().forEach(child => {
                detachRecursively(child)
            })
            node._detachAllUIElements();
        }
    }

    /**
     * Returns the dataobject contained in the node
     * @return {Object}
     */
    getData() {
        return this.#data;
    }

    /**
     * Sets a favourite node from the nodes children
     * @param {ElementNode} newFavourite
     // * @return {Node} previousFavourite
     */
    setFavourite(newFavourite) {
        this.#favourite = newFavourite;
    }

    /**
     * returns the current favourite
     * @return {ElementNode}
     */
    getFavourite() {
        return this.#favourite;
    }

    /**
     * private method
     * @return {Map<Number, ElementNode>}
     */
    _getChildren() {
        return this.#children;
    }
}