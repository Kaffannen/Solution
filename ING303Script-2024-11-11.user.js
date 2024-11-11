// ==UserScript==
// @name         ING303Script
// @namespace    http://tampermonkey.net/
// @version      2024-11-11
// @description  try to take over the world!
// @author       You
// @match        https://hvl.instructure.com/courses/29406/assignments/80710
// @icon         https://www.google.com/s2/favicons?sz=64&domain=tampermonkey.net
// @grant        none
// ==/UserScript==
class StateController {
    /**
     * A map of the statefunctions
     * @type {Map<String,Function>}
     */
    #statesMap = new Map();

    /**
     * The current state
     * @type {String}
     */
    #state;

    /**
     * constructor
     */
    constructor() {
        this._parseStates();
    }

    /**
     * Private postconstruct method
     * @return {StateController}
     */
    _parseStates(){
        if (!this.constructor.STATES)
            throw new Error(this.constructor.name + " doesnt have a static declaration of states");
        for (let key in this.constructor.STATES){
            let f = this.constructor.STATES[key];
            this.#statesMap.set(key,f);
        }
        return this;
    }

    /**
     * Sets the state and triggers the corresponding statefunction
     * @param {Function} state
     * @return {StateController}
     */
    setState(state) {
        this.#statesMap.get(state.name).call(this);
        this.#state = state.name;
        return this;
    }

    /**
     * Returns the state
     * @return {String}
     */
    getState(){
        return this.#state;
    }
}
class ElementController extends StateController{
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
/**
 * Represents an UIElement - HTML code which can can be attached and detached from the DOM
 */
class UIElement {
    /**
     * the HTMLElement to which the UIElement this is fastened when attached
     * @type{HTMLElement}
     */
    #attachmentAnchorElement;

    /**
     * the HTMLElement to which the UIElement this is fastened when detached
     * @type{HTMLElement}
     */
    #detachmentAnchorElement;

    /**
     * The root element of the UIElement
     * @type{HTMLElement}
     */
    #rootElement;

    /**
     * A map of the UIElements inputelements
     * @type{Map<String,HTMLInputElement>}
     */
    #inputElements;

    /**
     * A map of elements to which other UIElements are attached
     * @type {Map<String,HTMLElement>}
     */
    #anchorElements;

    /**
     * The node which contains the UIElement
     * @type {ElementNode}
     */
    #node;

    /**
     * Constructor must receive a HTMLString and a parent Node
     * @param htmlString
     * @param node
     */
    constructor(htmlString,node) {
        this.#node = node;
        let doc = new DOMParser().parseFromString(htmlString,"text/html");
        this.#detachmentAnchorElement = doc.body;
        this.#rootElement = this.#detachmentAnchorElement.firstElementChild;
        this.#rootElement.id = this.constructor.name;

        let inputElements = doc.querySelectorAll('[data-input]');
        if (inputElements.length>0){
            this.#inputElements=new Map();
            inputElements.forEach(inputElement => {
                this.#inputElements.set(inputElement.getAttribute('data-input'),inputElement)
                inputElement.removeAttribute('data-input');
            });
        }
//TODO: Denne kan bli problematisk hvis det plasseres inputelementer i et UIElement med anchors
        let anchorElements = doc.querySelectorAll('[data-anchor]');
        if (anchorElements.length>0){
            this.#anchorElements = new Map();
            anchorElements.forEach(anchorElement=>{
                this.#anchorElements.set(anchorElement.getAttribute('data-anchor'),anchorElement);
                anchorElement.id = "anchor: " + anchorElement.getAttribute('data-anchor')
                anchorElement.removeAttribute('data-anchor');
            })
        }
    }

    /**
     * Attaches the UIElement to the DOM
     */
    attach(){
        if (this.#rootElement.ownerDocument!==document)
            this.#attachmentAnchorElement.appendChild(this.#rootElement);
    }

    /**
     * Detaches the UIElement from the DOM
     */
    detach(){
        if (this.#rootElement.ownerDocument===document)
            this.#detachmentAnchorElement.appendChild(this.#rootElement);
    }

    /**
     * Fixes this UIElement to another UIElement, or to document@body
     * @param {Function} UIElementClassDefinition
     * @return {UIElement}
     */
    fixTo(UIElementClassDefinition = undefined){
        if (UIElementClassDefinition===undefined)
            this.#attachmentAnchorElement = document.getElementById("EzAnchor");
        else {
            if (!(UIElementClassDefinition instanceof Function))
                throw new Error(this.constructor.name+".fixTo() has wrong parameter type (needs to be a function) https://vg.no")
            let node = this.getNode();
            let UIElementInstance = undefined;
            while (UIElementInstance===undefined){
                if (node.getUIElement(UIElementClassDefinition))
                    UIElementInstance = node.getUIElement(UIElementClassDefinition)
                else{
                    node = node.getParentNode();
                    if (node===undefined)
                        break;
                }
            }
            if (UIElementInstance===undefined)
                throw new Error("No such element at this element or upstream");
            this.#attachmentAnchorElement=UIElementInstance._getAnchorElement(this.constructor.name);
        }
    }

    /**
     * Returns an inputelement of the UIElement, identified in HTMLString in the classdefinition
     * @param {string} name
     * @return {HTMLInputElement}
     */
    getInputElement(name){
        return this.#inputElements.get(name);
    }

    /**
     * private postconstruct method
     * @param {string} name
     * @return {HTMLElement}
     */
    _getAnchorElement(name){
        return this.#anchorElements.get(name);
    }

    /**
     * private postconstruct method
     */
    _infuseSearchPath(searchPathObject){
        this.#rootElement.setAttribute("data-searchObject", searchPathObject);
    }

    /**
     * Gives access to the Node which contains the UIElement
     * @return {ElementNode}
     */
    getNode(){
       return this.#node;
    }
}
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
window.find = find;
class BasicSolution extends EzUI {

    /**
     * Definerer EasyChatklassen sine UIElementer (html kode)
     * @return {EasyChat}
     */
    defineUIElements(){
        //this.addUIElement(new Program_Anchors(this))
          //  .fixTo();
        super.defineUIElements();
        this.fetchBruker();
        return this;
    }

    /**
     * Definerer EasyChatklassen tilstander
     */
    static STATES = {
        INIT: function(){
            //this.getUIElement(Program_Anchors).attach();
        }
    };

    /**
     *Henter en bruker fra server
    */
    fetchBruker(){
        this.getApi().getDefaultbruker()
            .then(defaultBruker=>{
                if (defaultBruker.rolle === "student"){
                    let bruker = new Student(defaultBruker,this)
                                    .defineUIElements()
                                    .setState(Student.STATES.COLLAPSED);
                                this.velgBruker(bruker);
                }
                else if (defaultBruker.rolle === "underviser"){
                    let bruker = new Underviser(defaultBruker,this)
                                    .defineUIElements()
                                    .setState(Underviser.STATES.COLLAPSED);
                                this.velgBruker(bruker);
                }

            })
            .catch(error =>alert(error))
    }

    /**
     * @param {Bruker} bruker
     */
    velgBruker(bruker){
        if (this.getFavourite()!==undefined)
            this.removeChild(this.getFavourite())
        super.setFavourite(bruker);
    }

    /**
     * @param {EasyChatAPI | EasyChatMockAPI} api
     */
    constructor(api) {
        super(api);
    }
}

class API{

    //Til BrukerController
        regUser(credentials) {
            return this.fetchObject("bruker/registrer", "Bruker eksisterer fra før", credentials);
        }

        getDefaultbruker(){
        return new Promise((resolve, reject) => {
                try {
                    //const user = { id: 1, username: "Brukernavn! Du er jo en student", rolle: "student" };
                    const user = { id: 1, username: "Brukernavn! MockAPI'en sier at du er en lærer", rolle: "underviser" };
                    if (user) {
                        resolve(user);
                    } else {
                        throw new Error("User data not found");
                    }
                } catch (error) {
                    reject(error);
                }
            });
        }

        loginUser(credentials = undefined) {
            return this.fetchObject("bruker/login", "Feil brukernavn/passord",credentials)
        }

        logoutUser(){
            return this.fetchObject("bruker/logout","feil med logout");
        }

        deleteUser(credentials){
            return this.fetchObject("bruker/delete", "feil med delete",credentials)
        }

        //Til ChatRomController

        createChatRom(newRom){
            return this.fetchObject("chatterom/lag","en feil, kanskje navn er brukt?",newRom);
        }
        joinChatRom(romTema){
            return this.fetchObject("chatterom/join", "noe skar seg",romTema)
        }

        getChatterom(bruker){
            return this.fetchObject("chatterom/get","en feil", bruker)
                .then(body=>body)
        }

        //Til meldingcontroller
        postMelding(DTO){
            return this.fetchObject("melding/post", "noe trøbbel", DTO);
        }

        getMeldinger(chatrom){
            return this.fetchObject("melding/get","feil",chatrom)
                .then(body=>body)
        }
        getNewMeldinger(DTO){
            return this.fetchObject("melding/getlatest","feil med getnew", DTO)
        }

        deleteMelding(melding){
            return this.fetchObject("melding/delete","neh",melding);
        }

    fetchObject(endpoint, rejectreason, body= null) {
        return new Promise((resolve, reject) => {
            fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(body)
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(response.status.toString());
                    }
                    return response.json();
                })
                .then(body => {
                    resolve(body);
                })
                .catch(error => {
                    switch (error.message) {
                        case "401":
                            reject(error.message + ": " + rejectreason);
                            break;
                        default:
                            reject(error.message + ": Annen feil");
                    }
                });
        });
    }
}
class Student extends ElementNode {

    /**
     * Definerer Brukerklassen sine UIElementer (html kode)
     * @return {Bruker}
     */
    defineUIElements() {
        this.addUIElement(new CollapsedState(this))
            .fixTo();
        this.addUIElement(new ExpandedState(this))
            .fixTo();
        this.addUIElement(new StudentUI(this))
            .fixTo();
        super.defineUIElements();
        //this.fetchChatterom();
        return this;
    }

    /**
     * Definerer Brukerklassens tilstander
     */
    static STATES = {
        INIT: function(){
        },
        COLLAPSED: function(){
            this.getUIElement(CollapsedState).attach();
            this.getUIElement(ExpandedState).detach();
            this.getUIElement(StudentUI).detach();
        },
        EXPANDED: function(){
            this.getUIElement(CollapsedState).detach();
            this.getUIElement(ExpandedState).attach();
            this.getUIElement(StudentUI).attach();
        }
    };

    /**
     *Henter chatterom fra server
     */
    fetchChatterom() {
        let brukerid = {id:this.getData().id};
        program.getApi()
            .getChatterom(this.getData())
            .then(crPojos=>{
                crPojos.forEach(pojo=>{
                    let chatterom = new Chatterom(pojo, this)
                        .defineUIElements()
                    if (chatterom.getParentNode().getFavourite()===undefined)
                        this.velgChatterom(chatterom)
                });
            })
            .catch(error=>{
                console.log(error);
            })
    }
    /**
     *
     * @param newFavourite
     */
    velgChatterom(newFavourite) {
        let oldFavourite = this.getFavourite();
        if (oldFavourite!==undefined)
            oldFavourite.setState(Chatterom.STATES.UNSELECTED);
        if (newFavourite!==undefined)
            newFavourite.setState(Chatterom.STATES.SELECTED);
        super.setFavourite(newFavourite);
    }
}
class Underviser extends ElementNode {

    /**
     * Definerer Underviserklassen sine UIElementer (html kode)
     * @return {Bruker}
     */
    defineUIElements() {
        this.addUIElement(new CollapsedState(this))
            .fixTo();
        this.addUIElement(new ExpandedState(this))
            .fixTo();
        this.addUIElement(new TeacherUI(this))
            .fixTo();
        super.defineUIElements();
        return this;
    }

    /**
     * Definerer Underviserklassen tilstander
     */
    static STATES = {
        INIT: function(){
        },
        COLLAPSED: function(){
            this.getUIElement(CollapsedState).attach();
            this.getUIElement(ExpandedState).detach();
            this.getUIElement(TeacherUI).detach();
        },
        EXPANDED: function(){
            this.getUIElement(CollapsedState).detach();
            this.getUIElement(ExpandedState).attach();
            this.getUIElement(TeacherUI).attach();
        }
    };

    /**
     *Henter chatterom fra server
     */
    fetchChatterom() {
        let brukerid = {id:this.getData().id};
        program.getApi()
            .getChatterom(this.getData())
            .then(crPojos=>{
                crPojos.forEach(pojo=>{
                    let chatterom = new Chatterom(pojo, this)
                        .defineUIElements()
                    if (chatterom.getParentNode().getFavourite()===undefined)
                        this.velgChatterom(chatterom)
                });
            })
            .catch(error=>{
                console.log(error);
            })
    }
    /**
     *
     * @param newFavourite
     */
    velgChatterom(newFavourite) {
        let oldFavourite = this.getFavourite();
        if (oldFavourite!==undefined)
            oldFavourite.setState(Chatterom.STATES.UNSELECTED);
        if (newFavourite!==undefined)
            newFavourite.setState(Chatterom.STATES.SELECTED);
        super.setFavourite(newFavourite);
    }
}
class TeacherUI extends UIElement{
    /**
     *
     * @param {ElementNode} nexus
     */
    constructor(nexus) {
        let htmlString
            =`
<fieldset class="IkkeInnlogget sentrerHorisontalt">
    <h3>Badass Teacher UI</h3>
    <p>Kjøre på med lister over grupper og hvem som er i dem - drag & drop funksjonalitet?</p>
    <ul>
        <li>Gruppe 1, 4/[8-12] studenter </li>
        <li>Gruppe 2, 4/[8-12] studenter </li>
        <li>Gruppe 3, 4/[8-12] studenter </li>
        <li>Gruppe 3, 4/[8-12] studenter </li>
        <li>Gruppe 3, 4/[8-12] studenter </li>
        <li>Gruppe 1, 4/[8-12] studenter </li>
        <li>Gruppe 2, 4/[8-12] studenter </li>
        <li>Gruppe 3, 4/[8-12] studenter </li>
        <li>Gruppe 3, 4/[8-12] studenter </li>
        <li>Gruppe 3, 4/[8-12] studenter </li>
        <li>Gruppe 1, 4/[8-12] studenter </li>
        <li>Gruppe 2, 4/[8-12] studenter </li>
        <li>Gruppe 3, 4/[8-12] studenter </li>
        <li>Gruppe 3, 4/[8-12] studenter </li>
        <li>Gruppe 3, 4/[8-12] studenter </li>
        <li>...</li>
        <li>25 studenter ikke i gruppe. 10 av dem har bedt å bli plassert i gruppe</li>
    </ul>

    <!--
    <input data-input="username" type="text" placeholder="asdfasdf"
        onkeydown="
            let regform = find(this);
            switch (event.key){
                case 'Enter':
                    regform.getInputElement('regButton').click();
                    break;
                case 'Escape':
                    regform.getNode().setState(Bruker.STATES.LOGGED_OUT);
                    break;
            }"
    />

    <input data-input="password" type="password" placeholder="Passord"
        onkeydown="
            let regform = find(this);
            switch (event.key){
                case 'Enter':
                    regform.getInputElement('regButton').click();
                    break;
                case 'Escape':
                    regform.getNode().setState(Bruker.STATES.LOGGED_OUT);
                    break;
            }"
    />
    -->
    <br>
    <input data-input="regButton" type="button" value ="Magisk algoritmeknapp som organiserer 'rest' studenter i grupper"
        onclick='find(this).regnewUser()'
        onkeydown="if (event.key === 'Escape') find(this).getNode().setState(Bruker.STATES.LOGGED_OUT)"
    ">
    <br>
</fieldset>
            `;
        super(htmlString, nexus);
    }
    /**
     * @returns {Bruker}
     */
    regnewUser() {
        let credentials = {
            brukernavn: this.getInputElement("username").value,
            passord: this.getInputElement("password").value
        };

        program.getApi().regUser(credentials)
            .then(() => {
                return program.getApi().loginUser(credentials);
            })
            .then(data => {
                let bruker = new Bruker(data, program)
                    .defineUIElements()
                    .setState(Bruker.STATES.LOGGED_IN);
                program.velgBruker(bruker);
            })
            .catch(error =>alert(error))
        this.getInputElement("username").value = "";
        this.getInputElement("password").value = "";
    }

}
class StudentUI extends UIElement {

    /**
     *
     * @param {ElementNode} nexus
     */
    constructor(nexus) {

        let htmlString
            =`
<fieldset class="IkkeInnlogget sentrerHorisontalt">
    <h3>Badass Student UI</h3>
    <input data-input="username" type="text" placeholder="Brukernavn"
        onkeydown="
            let loginform = find(this);
            switch (event.key){
                case 'Enter':
                    loginform.getInputElement('loginButton').click();
                    break;
                case 'Escape':
                    loginform.getNode().setState(Bruker.STATES.LOGGED_OUT);
                    break;
            }"
    />
    <input data-input="password" type="password" placeholder="Passord"
        onkeydown="
            let loginform = find(this);
            switch (event.key){
                case 'Enter':
                    loginform.getInputElement('loginButton').click();
                    break;
                case 'Escape':
                    loginform.getNode().setState(Bruker.STATES.LOGGED_OUT);
                    break;
            }"
    />
    <br>
    <input data-input="loginButton" type="button" value ="få SKYNET til å fikse en gruppe til deg"
        onclick='find(this).login()'
        onkeydown="if (event.key === 'Escape') find(this).getNode().setState(Bruker.STATES.LOGGED_OUT)"
    ">
    <br>
</fieldset>
            `;
        super(htmlString, nexus);
    }
    /**
     * @returns {Bruker}
     */
    login() {
        let credentials ={
            brukernavn:this.getInputElement("username").value,
            passord:this.getInputElement("password").value
        };
        program.getApi().loginUser(credentials)
            .then(data=>{
                let bruker= new Bruker(data,program)
                    .defineUIElements()
                    .setState(Bruker.STATES.LOGGED_IN);
                program.velgBruker(bruker);
            })
            .catch(error => alert(error))
        this.getInputElement("username").value = "";
        this.getInputElement("password").value = "";
    }
}
class ExpandedState extends UIElement
{
    /**
     *
     * @param {ElementNode} nexus
     */
    constructor(nexus) {
        let jsonElement = nexus.getData();
        let htmlString
            = `
<fieldset>
    <legend>Hei ${jsonElement.username}</legend>
    <input data-input="utvidButton" type="button" value="Kollaps" onclick="find(this).minimer()">
</fieldset>
            `;
        super(htmlString,nexus);
    }
    minimer() {
        this.getNode().setState(Student.STATES.COLLAPSED);
    }
}
class CollapsedState extends UIElement{
    /**
     *
     * @param {ElementNode} nexus
     */
    constructor(nexus) {
        let jsonElement = nexus.getData();
        let htmlString
            = `
<fieldset>
    <legend>Hei ${jsonElement.username}</legend>
    <input data-input="utvidButton" type="button" value="Utvid" onclick="find(this).utvid()">
</fieldset>
            `;
        super(htmlString,nexus);
    }
    utvid() {
        this.getNode().setState(Student.STATES.EXPANDED);
    }
}

//let program = "asd";
//window.program=program

window.addEventListener("load", function () {
    setTimeout(function () {
        const spanElements = document.querySelectorAll('span[data-testid="title"]');
        let targetSpan = null;
        for (let span of spanElements) {
            if (span.textContent.trim() === "Samarbeidsavtale") {
                targetSpan = span;
                break;
            }
        }

        if (targetSpan) {
            const ezAnchor = document.createElement("div");
            ezAnchor.id = "EzAnchor";
            targetSpan.insertAdjacentElement("afterend", ezAnchor);
        } else {
            console.error('No span with the content "Samarbeidsavtale" and data-testid="title" found!');
        }
        window.program = new BasicSolution(new API())
        program.defineUIElements();
    }, 2000);
});
