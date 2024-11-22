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

class API{
    #canvasApi;
    #msgBroker;
    #persistence;

setCanvasApi(canvasApi){
    this.#canvasApi = canvasApi;
    return this;
}
setMsgBroker(msgBroker){
    this.#msgBroker = msgBroker;
    return this;
}
setPersistence(persistence){
    this.#persistence = persistence;
    return this;
}

    onLoadInfo(){
        let courseId = 29406
        let assignmentId = 80710
        return Promise.all([
            this.#canvasApi.getUserInfo(),
            this.#canvasApi.getCourseInfo(courseId),
            this.#canvasApi.getAssignmentInfo(assignmentId)])
        .then(([user, course, assignment]) => {
            return {
            id: user.id,
            user: user,
            course: course,
            assignment: assignment
        }
    })
    }
    fetchGroup(assignmentGroupId){
        return this.#canvasApi.getAssignmentGroup(assignmentGroupId)
    }
    fetchGroupMembers(selfId){
        return this.#canvasApi.getGroupMembers(selfId)
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
    fetchGroups(assignmentId){
        return this.#canvasApi.fetchGroups()
    }
}

class CanvasAPI {

constructor(roleOverride = "none"){
    if (roleOverride === "student")
        this.roleOverride = "StudentEnrollment"
    else if (roleOverride === "teacher")
        this.roleOverride = "TeacherEnrollment"
    else
        this.roleOverride = "none"
}

getCourseId(){
    return window.location.pathname.split('/').filter(Boolean)[1];
}

getAssignmentId(){
    return window.location.pathname.split('/').filter(Boolean)[3];
}

getUserInfo(){
    return fetch('https://hvl.instructure.com/api/v1/users/self')
                .then(response => response.json())
}

getCourseInfo(){
    return fetch(`https://hvl.instructure.com/api/v1/courses/${this.getCourseId()}`)
                .then(response => response.json())
                .then(courseInfo => {
                    if (courseInfo.enrollments && courseInfo.enrollments.length > 0 && this.roleOverride !== "none") {
                                    courseInfo.enrollments[0].role = this.roleOverride;
                                }
                    return courseInfo
                })

}
getAssignmentInfo(){
    return fetch(`https://hvl.instructure.com/api/v1/courses/${this.getCourseId()}/assignments/${this.getAssignmentId()}`)
                .then(response => response.json());
}

getGroupMembers(selfId){
    return fetch(`https://hvl.instructure.com/api/v1/courses/${this.getCourseId()}/assignments/${this.getAssignmentId()}/users/${selfId}/group_members`)
                .then(response => response.json());
}
getAssignmentGroup(assignmentGroupId){
    return fetch(`https://hvl.instructure.com/api/v1/courses/${this.getCourseId()}/assignment_groups/${assignmentGroupId}`)
                .then(response => response.json());
}
async fetchGroups(assignmentId) {
    let course = 25563;
    let assignment = 75844;

    // Fetch all users (students, teachers, assistants)
    const [studentsResponse, teachersResponse, assistantsResponse] = await Promise.all([
        fetch(`https://hvl.instructure.com/api/v1/courses/${this.getCourseId()}/users?page=1&per_page=200&enrollment_type=student`),
        fetch(`https://hvl.instructure.com/api/v1/courses/${this.getCourseId()}/users?page=1&per_page=200&enrollment_type=teacher`),
        fetch(`https://hvl.instructure.com/api/v1/courses/${this.getCourseId()}/users?page=1&per_page=200&enrollment_type=ta`)
    ]);

    // Convert response to JSON
    const students = await studentsResponse.json();
    const teachers = await teachersResponse.json();
    const assistants = await assistantsResponse.json();

    const studentgroups = [];

    // Process students
    for (let i = 0; i < students.length; i++) {
        // Fetch the group members for the current student
        const groupResponse = await fetch(`https://hvl.instructure.com/api/v1/courses/${this.getCourseId()}/assignments/${this.getAssignmentId()}/users/${students[i].id}/group_members`);
        let group = await groupResponse.json();

        // Remove the students that are part of this group
        group.forEach(student => {
            const index = students.findIndex(s => s.id === Number(student.id));
            if (index !== -1) {
                students.splice(index, 1);
            }
        });

        // Add the group to the studentgroups array
        group = {
            id: group[0].id,
            members : group
        }
        studentgroups.push(group);
    }
    return {
            studentgroups,
            teachers,
            assistants
            };
    }
}

class CollapsedState extends UIElement{

    constructor(nexus) {
        let jsonElement = nexus.getData();
        let htmlString
            = `

<div>
<br>
<input data-input="utvidButton" type="button" value="TeamUp - Trykk for å utvide" onclick="program.find(this).utvid()">
</div>
            `;
        super(htmlString,nexus);
    }
    utvid() {
        this.getNode().setState(Student.STATES.EXPANDED);
    }
}
class ExpandedState extends UIElement
{
    constructor(nexus) {
        let jsonElement = nexus.getData();
        let htmlString
            = `
<div>
<br>
<input data-input="minimerButton" type="button" value="TeamUp - Trykk for å lukke" onclick="program.find(this).minimer()">
</div>
            `;
        super(htmlString,nexus);
    }
    minimer() {
        this.getNode().setState(Student.STATES.COLLAPSED);
    }
}
class AssignmentGroupMember extends UIElement {

    constructor(nexus) {
        let jsonElement = nexus.getData();
        let htmlString
            =`
<fieldset class="fieldset-reset">
    <p>${jsonElement.name}</p>
</fieldset>
            `;
        super(htmlString, nexus);
    }
}
class StudentGroupUIE extends UIElement {
    constructor(nexus) {
        let jsonElement = nexus.getData();
        let htmlString
            =`
<fieldset class="fieldset-reset">
    <p> 'Gruppenavn | Ingen gruppe funnet '</p>
    <p> 'Antall medlemmer / [minimum - maximum medlemmer for oblig] '</p>
    <div data-anchor=${AssignmentGroupMember.name}></div>
    <input data-input="" type="button" value ="Inviter en person / gruppe"
                        onclick='program.find(this).mergeRequest()'
                    ">
    <input data-input="" type="button" value ="Si til faglærer at gruppen ønsker å bli tilordnet medlemmer"
            onclick='program.find(this).signalDisposition(open)'
        ">
    <input data-input="" type="button" value ="Forlat gruppe"
                onclick='program.find(this).studentAction()'
            ">

</fieldset>
            `;
        super(htmlString, nexus);
    }

    studentAction() {
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
class HeaderbarCollapsed extends UIElement {
    constructor(nexus) {
        let jsonElement = nexus.getData();
        let htmlString
            =`
<input type = "button" data-input="" value = "utvid" onclick = "program.find(this).expand()">
            `;
        super(htmlString, nexus);
    }
    expand(){
        this.getNode().setState(TeacherGroup.STATES.EXPANDED);
    }
}


class HeaderbarExpanded extends UIElement {
    constructor(nexus) {
        let jsonElement = nexus.getData();
        let htmlString
            =`
<input type = "button" data-input="" value = "kollaps" onclick = "program.find(this).collapse()">
            `;
        super(htmlString, nexus);
    }
    collapse(){
        this.getNode().setState(TeacherGroup.STATES.COLLAPSED);
    }
}


class TeacherGroupUIE extends UIElement {
    constructor(nexus) {
        let jsonElement = nexus.getData();
        let htmlString
            =`
<fieldset class="fieldset-reset">
    <p>
        'Gruppenavn | medlemmer: ${jsonElement.members.length} | (Oblig levert Y/N | Oblig godkjent Y/N)'
        <span data-anchor=${HeaderbarCollapsed.name}></div>
        <span data-anchor=${HeaderbarExpanded.name}></div>
    </p>
    <div data-anchor=${AssignmentGroupMember.name}></div>
</fieldset>
            `;
        super(htmlString, nexus);
    }

    studentAction() {
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

class StudentUI extends UIElement {

    constructor(nexus) {

        let htmlString
            =`
<fieldset class="fieldset-reset">
    <div data-anchor=${StudentGroupUIE.name}></div>
</fieldset>
            `;
        super(htmlString, nexus);
    }

    studentAction() {
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
    closeTeamUp() {
        this.getNode().setState(Student.STATES.COLLAPSED);
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
<fieldset class="fieldset-reset">
    <p> #Antall studenter i kurset</p>
    <p>Drag & drop funksjonalitet?</p>
    <div data-anchor=${TeacherGroupUIE.name}></div>
    <p>stor gruppe med studenter 'i enmannsgruppe' (rent logisk)</p>
    <input data-input="regButton" type="button" value ="Magisk algoritmeknapp som organiserer 'rest' studenter i grupper"
        onclick='find(this).doAction()'
        onkeydown="if (event.key === 'Escape') find(this).getNode().setState(Bruker.STATES.LOGGED_OUT)"
    ">
    <br>
</fieldset>
            `;
        super(htmlString, nexus);
    }

    doAction() {
        fetch('https://hvl.instructure.com/api/v1/users/self/')
            .then(response => response.json())
            .then(data => {
                console.log(data);
            })
            .catch(error => alert(error));
    }

    startUpShit(){
        const promises = [];
        promises.push(fetch('https://api.example.com/data1').then(res => res.json()));
        promises.push(fetch('https://api.example.com/data2').then(res => res.json()));
        /**
        try {
            return await Promise.all(promises);
        } catch (error) {
            console.error("An error occurred:", error);
            throw error;
        }
        */
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

class GroupMember extends ElementNode {

    defineUIElements() {
        this.addUIElement(new AssignmentGroupMember(this))
            .fixTo(this.getParentNode() instanceof StudentGroup ? StudentGroupUIE : TeacherGroupUIE);
        super.defineUIElements();
        return this;
    }

    static STATES = {
        INIT: function(){
        },
        ATTACHED: function(){
            this.getUIElement(AssignmentGroupMember).attach();
        },
        DETACHED: function(){
            this.getUIElement(AssignmentGroupMember).detach();
        }
    };
}
class StudentGroup extends ElementNode {

    defineUIElements() {
        this.addUIElement(new StudentGroupUIE(this))
            .fixTo(this.getParentNode() instanceof Student ? StudentUI : TeacherUI);
        super.defineUIElements();
        this.fetchGroupMembers();
        return this;
    }

    static STATES = {
        INIT: function(){
            this.getUIElement(StudentGroupUIE).attach();
        }
    };
    fetchGroupMembers(){
        program.getApi().fetchGroupMembers(this.getParentNode().getData().id)
            .then(groupMembersInfo => {
                groupMembersInfo.forEach(memberInfo => {
                        let member = new GroupMember(memberInfo,this)
                        .defineUIElements()
                        .setState(GroupMember.STATES.ATTACHED);
                    });
            })
            .catch(error => {
                console.log("Error fetching group members: " + error);
            });
    }
}

class TeacherGroup extends ElementNode {

    defineUIElements() {
        this.addUIElement(new TeacherGroupUIE(this))
            .fixTo(TeacherUI);
        this.addUIElement(new HeaderbarCollapsed(this))
            .fixTo(TeacherGroupUIE);
        this.addUIElement(new HeaderbarExpanded(this))
            .fixTo(TeacherGroupUIE);
        super.defineUIElements();
        this.fetchGroupMembers();
        return this;
    }

    static STATES = {
        INIT: function(){
            this.getUIElement(TeacherGroupUIE).attach();
        },
        COLLAPSED: function(){
            this._getChildren().forEach(node => node.setState(GroupMember.STATES.DETACHED));
            this.getUIElement(HeaderbarCollapsed).attach();
            this.getUIElement(HeaderbarExpanded).detach();
        },
        EXPANDED: function(){
            this._getChildren().forEach(node => node.setState(GroupMember.STATES.ATTACHED));
            this.getUIElement(HeaderbarCollapsed).detach();
            this.getUIElement(HeaderbarExpanded).attach();
    }};
    fetchGroupMembers(){
        this.getData().members.forEach(memberInfo => {
                                let member = new GroupMember(memberInfo,this)
                                .defineUIElements()
                                .setState(GroupMember.STATES.INIT);
                            });
    }
}

class Student extends ElementNode {

    defineUIElements() {
        this.addUIElement(new CollapsedState(this))
            .fixTo();
        this.addUIElement(new ExpandedState(this))
            .fixTo();
        this.addUIElement(new StudentUI(this))
            .fixTo();
        super.defineUIElements();
        this.fetchGroup();
        return this;
    }

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
    }
    fetchGroup(){
        return program.getApi().fetchGroup(this.getData().assignment.assignment_group_id)
            .then(groupInfo=>{
                let group = new StudentGroup(groupInfo,this)
                    .defineUIElements()
                    .setState(StudentGroup.STATES.INIT);
                this.setFavourite(group);
                })
            .catch(error =>console.error(error))
    }
}
class Underviser extends ElementNode {

    defineUIElements() {
        this.addUIElement(new CollapsedState(this))
            .fixTo();
        this.addUIElement(new ExpandedState(this))
            .fixTo();
        this.addUIElement(new TeacherUI(this))
            .fixTo();
        super.defineUIElements();
        this.fetchGroups();
        return this;
    }

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
    fetchGroups(){
            /**
            https://hvl.instructure.com/api/v1/courses/29406/assignments/80710/users/82310/group_members
            https://hvl.instructure.com/api/v1/courses/25563/assignments/75844/users/15686/group_members
            gir tilgang til alle gruppemedlemmer i en gruppe - for alle jeg har ID på.
            https://hvl.instructure.com/api/v1/courses/25563/users?page=1&per_page=1000&enrollment_type=teacher gir alle teachers
            https://hvl.instructure.com/api/v1/courses/25563/users?page=1&per_page=1000&enrollment_type=student gir alle students
            **/
            return program.getApi().fetchGroups(this.getData().assignment.assignment_group_id)
                .then(userGroups => {
                    userGroups.studentgroups.forEach(groupInfo=>{
                        let group = new TeacherGroup(groupInfo,this)
                            .defineUIElements()
                            .setState(TeacherGroup.STATES.COLLAPSED);
                    });
                })
                .catch(error =>console.error(error))
        }
}

class BasicSolution extends EzUI {
    async defineUIElements(){
        super.defineUIElements();
        await this.fetchBruker();
        return this;
    }

    static STATES = {
        INIT: function(){
        }
    };

    fetchBruker(){
        return program.getApi().onLoadInfo()
            .then(loadInfo=>{
                if (loadInfo.course.enrollments[0].role === "StudentEnrollment"){
                    let bruker = new Student(loadInfo,this)
                                    .defineUIElements()
                                    .setState(Student.STATES.COLLAPSED);
                                this.velgBruker(bruker);
                }
                else  {
                    let bruker = new Underviser(loadInfo,this)
                                    .defineUIElements()
                                    .setState(Underviser.STATES.COLLAPSED);
                                this.velgBruker(bruker);
                }

            })
            .catch(error =>console.log(error))
    }

    velgBruker(bruker){
        if (this.getFavourite()!==undefined)
            this.removeChild(this.getFavourite())
        super.setFavourite(bruker);
    }

    constructor(api) {
        super(api);
    }
}


