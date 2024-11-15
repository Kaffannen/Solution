//export default class StateController {
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
}//import StateController from "https://kaffannen.github.io/Solution/Javascript/EzUI/InternalSupers/StateController.js";
//import EzUI from "https://kaffannen.github.io/Solution/Javascript/EzUI/DeveloperClasses/EzUI.js";

//export default class ElementController extends StateController{
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
}//import ElementController from "https://kaffannen.github.io/Solution/Javascript/EzUI/InternalSupers/ElementController.js";

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
}//import ElementNode from "https://kaffannen.github.io/Solution/Javascript/EzUI/DeveloperClasses/ElementNode.js";

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
}/**
 * Represents an UIElement - HTML code which can can be attached and detached from the DOM
 */
//export default class UIElement {
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
}//export default class CanvasAPIMock {
class CanvasAPIMock {

static getUserInfo(){
    return new Promise((resolve, reject) => {
    const user = {
                                        //GET https://hvl.instructure.com/api/v1/users/self
                                         "id": 81736,
                                         "name": "Christian Hagen",
                                         "created_at": "2022-07-30T08:15:49+02:00",
                                         "sortable_name": "Hagen, Christian",
                                         "short_name": "Christian Hagen",
                                         "avatar_url": "https://hvl.instructure.com/images/messages/avatar-50.png",
                                         "last_name": "Hagen",
                                         "first_name": "Christian",
                                         "locale": "nb",
                                         "effective_locale": "nb",
                                         "permissions": {
                                             "can_update_name": false,
                                             "can_update_avatar": true,
                                             "limit_parent_app_web_access": false
                                         }
                                     }
     resolve(user)
    })
}

static getCourseInfo(){
    return new Promise((resolve, reject) => {
        const course = { //GET https://hvl.instructure.com/api/v1/courses/29406
                                                "id": 29406,
                                                "name": "ING303-1 24H Systemtenking og innovasjon for ingeniører",
                                                "account_id": 15739,
                                                "uuid": "8wan4Q35WCFevkPd2bsA0xVNHXhP8HI5S6w4NSZC",
                                                "start_at": null,
                                                "grading_standard_id": null,
                                                "is_public": false,
                                                "created_at": "2024-05-01T06:20:36Z",
                                                "course_code": "ING303-1 24H",
                                                "default_view": "modules",
                                                "root_account_id": 1,
                                                "enrollment_term_id": 330,
                                                "license": "private",
                                                "grade_passback_setting": null,
                                                "end_at": null,
                                                "public_syllabus": false,
                                                "public_syllabus_to_auth": false,
                                                "storage_quota_mb": 524,
                                                "is_public_to_auth_users": false,
                                                "homeroom_course": false,
                                                "course_color": null,
                                                "friendly_name": null,
                                                "apply_assignment_group_weights": false,
                                                "calendar": {
                                                    "ics": "https://hvl.instructure.com/feeds/calendars/course_8wan4Q35WCFevkPd2bsA0xVNHXhP8HI5S6w4NSZC.ics"
                                                },
                                                "time_zone": "Europe/Copenhagen",
                                                "blueprint": false,
                                                "template": false,
                                                "enrollments": [
                                                    {
                                                        "type": "student",
                                                        "role": "StudentEnrollment",
                                                        "role_id": 3,
                                                        "user_id": 81736,
                                                        "enrollment_state": "active",
                                                        "limit_privileges_to_course_section": false
                                                    },
                                                    {
                                                        "type": "student",
                                                        "role": "StudentEnrollment",
                                                        "role_id": 3,
                                                        "user_id": 81736,
                                                        "enrollment_state": "active",
                                                        "limit_privileges_to_course_section": false
                                                    }
                                                ],
                                                "hide_final_grades": false,
                                                "workflow_state": "available",
                                                "restrict_enrollments_to_course_dates": false
                                            }
        resolve(course)
    })
}
static getAssignmentInfo(){
    return new Promise((resolve, reject) => {
                            const assignment = { //https://hvl.instructure.com/api/v1/courses/29406/assignments/80710
                                                   "id": 80710,
                                                   "description": "\u003cp\u003eAlle gruppene skal levera utfylt og signert samarbeidsavtale seinast 12. september. Bruk \u003ca class=\"instructure_file_link instructure_scribd_file inline_disabled\" title=\"Lenke\" href=\"https://hvl.instructure.com/courses/29406/files/2895778?wrap=1\" target=\"_blank\" data-canvas-previewable=\"true\" data-api-endpoint=\"https://hvl.instructure.com/api/v1/courses/29406/files/2895778\" data-api-returntype=\"File\"\u003emal for samarbeidsavtale\u003c/a\u003e\u0026nbsp;som utgangspunkt for å bli einige om innhaldet i avtalen internt i gruppa. Å levera signert samarbeidsavtale er eit arbeidskrav.\u003c/p\u003e",
                                                   "due_at": "2024-09-12T21:30:00Z",
                                                   "unlock_at": "2024-08-19T22:00:00Z",
                                                   "lock_at": null,
                                                   "points_possible": 0.0,
                                                   "grading_type": "pass_fail",
                                                   "assignment_group_id": 32409,
                                                   "grading_standard_id": null,
                                                   "created_at": "2024-08-12T13:23:12Z",
                                                   "updated_at": "2024-10-01T06:31:23Z",
                                                   "peer_reviews": false,
                                                   "automatic_peer_reviews": false,
                                                   "position": 1,
                                                   "grade_group_students_individually": false,
                                                   "anonymous_peer_reviews": false,
                                                   "group_category_id": 17240,
                                                   "post_to_sis": false,
                                                   "moderated_grading": false,
                                                   "omit_from_final_grade": false,
                                                   "intra_group_peer_reviews": false,
                                                   "anonymous_instructor_annotations": false,
                                                   "anonymous_grading": false,
                                                   "graders_anonymous_to_graders": false,
                                                   "grader_count": 0,
                                                   "grader_comments_visible_to_graders": true,
                                                   "final_grader_id": null,
                                                   "grader_names_visible_to_final_grader": true,
                                                   "allowed_attempts": -1,
                                                   "annotatable_attachment_id": null,
                                                   "hide_in_gradebook": false,
                                                   "secure_params": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJsdGlfYXNzaWdubWVudF9pZCI6ImY3YzljMGQyLTY5MGQtNDM4OS04OWZiLTM4NzYxZjk1YjNiZSIsImx0aV9hc3NpZ25tZW50X2Rlc2NyaXB0aW9uIjoiXHUwMDNjcFx1MDAzZUFsbGUgZ3J1cHBlbmUgc2thbCBsZXZlcmEgdXRmeWx0IG9nIHNpZ25lcnQgc2FtYXJiZWlkc2F2dGFsZSBzZWluYXN0IDEyLiBzZXB0ZW1iZXIuIEJydWsgXHUwMDNjYSBjbGFzcz1cImluc3RydWN0dXJlX2ZpbGVfbGluayBpbnN0cnVjdHVyZV9zY3JpYmRfZmlsZSBpbmxpbmVfZGlzYWJsZWRcIiB0aXRsZT1cIkxlbmtlXCIgaHJlZj1cIi9jb3Vyc2VzLzI5NDA2L2ZpbGVzLzI4OTU3Nzg_d3JhcD0xXCIgdGFyZ2V0PVwiX2JsYW5rXCIgZGF0YS1jYW52YXMtcHJldmlld2FibGU9XCJ0cnVlXCJcdTAwM2VtYWwgZm9yIHNhbWFyYmVpZHNhdnRhbGVcdTAwM2MvYVx1MDAzZVx1MDAyNm5ic3A7c29tIHV0Z2FuZ3NwdW5rdCBmb3Igw6UgYmxpIGVpbmlnZSBvbSBpbm5oYWxkZXQgaSBhdnRhbGVuIGludGVybnQgaSBncnVwcGEuIMOFIGxldmVyYSBzaWduZXJ0IHNhbWFyYmVpZHNhdnRhbGUgZXIgZWl0IGFyYmVpZHNrcmF2Llx1MDAzYy9wXHUwMDNlIn0.NUmLE_CIFLmYqO89dnDgp1HPJRJNhsSCOqmUbaoFvSo",
                                                   "lti_context_id": "f7c9c0d2-690d-4389-89fb-38761f95b3be",
                                                   "course_id": 29406,
                                                   "name": "Samarbeidsavtale",
                                                   "submission_types": [
                                                       "online_upload"
                                                   ],
                                                   "has_submitted_submissions": true,
                                                   "due_date_required": false,
                                                   "max_name_length": 255,
                                                   "in_closed_grading_period": false,
                                                   "graded_submissions_exist": true,
                                                   "is_quiz_assignment": false,
                                                   "can_duplicate": true,
                                                   "original_course_id": null,
                                                   "original_assignment_id": null,
                                                   "original_lti_resource_link_id": null,
                                                   "original_assignment_name": null,
                                                   "original_quiz_id": null,
                                                   "workflow_state": "published",
                                                   "important_dates": false,
                                                   "muted": true,
                                                   "html_url": "https://hvl.instructure.com/courses/29406/assignments/80710",
                                                   "published": true,
                                                   "only_visible_to_overrides": false,
                                                   "visible_to_everyone": true,
                                                   "locked_for_user": false,
                                                   "submissions_download_url": "https://hvl.instructure.com/courses/29406/assignments/80710/submissions?zip=1",
                                                   "post_manually": false,
                                                   "anonymize_students": false,
                                                   "require_lockdown_browser": false,
                                                   "restrict_quantitative_data": false
                                               }
        resolve(assignment)
})
}
static getGroupMembers(){
    return new Promise((resolve, reject) => {
                            //https://hvl.instructure.com/api/v1/courses/29406/assignments/80710/users/81736/group_members
                            const group = [
                                            {
                                              "id": "15686",
                                              "name": "Ole Kristoffer Høivaag Jensen"
                                            },
                                            {
                                              "id": "66800",
                                              "name": "Jonas Grundvåg Rong"
                                            },
                                            {
                                              "id": "69850",
                                              "name": "Simen Hærnes Ihlen"
                                            },
                                            {
                                              "id": "80861",
                                              "name": "Martin Sollesnes Kummeneje"
                                            },
                                            {
                                              "id": "81736",
                                              "name": "Christian Hagen"
                                            },
                                            {
                                              "id": "82310",
                                              "name": "Vlad Nicusor Lipovanu"
                                            }
                                          ]
        resolve(group)
})
}
static getAssignmentGroup(){
    return new Promise((resolve, reject) => {
    //https://hvl.instructure.com/api/v1/courses/29406/assignment_groups/32409

        const group = {
                        "id": 32409,
                        "name": "Oppgåver",
                        "position": 2,
                        "group_weight": 0,
                        "sis_source_id": null,
                        "integration_data": {

                        },
                        "rules": {

                        }
                      }
        resolve(group)
    })
}
}//export default class CanvasAPIMock {
class MsgBrokerMock {

static getUserInfo(){
    return new Promise((resolve, reject) => {
    const user = {
                                        //GET https://hvl.instructure.com/api/v1/users/self
                                         "id": 81736,
                                         "name": "Christian Hagen",
                                         "created_at": "2022-07-30T08:15:49+02:00",
                                         "sortable_name": "Hagen, Christian",
                                         "short_name": "Christian Hagen",
                                         "avatar_url": "https://hvl.instructure.com/images/messages/avatar-50.png",
                                         "last_name": "Hagen",
                                         "first_name": "Christian",
                                         "locale": "nb",
                                         "effective_locale": "nb",
                                         "permissions": {
                                             "can_update_name": false,
                                             "can_update_avatar": true,
                                             "limit_parent_app_web_access": false
                                         }
                                     }
     resolve(user)
    })
}

static getCourseInfo(){
    return new Promise((resolve, reject) => {
        const course = { //GET https://hvl.instructure.com/api/v1/courses/29406
                                                "id": 29406,
                                                "name": "ING303-1 24H Systemtenking og innovasjon for ingeniører",
                                                "account_id": 15739,
                                                "uuid": "8wan4Q35WCFevkPd2bsA0xVNHXhP8HI5S6w4NSZC",
                                                "start_at": null,
                                                "grading_standard_id": null,
                                                "is_public": false,
                                                "created_at": "2024-05-01T06:20:36Z",
                                                "course_code": "ING303-1 24H",
                                                "default_view": "modules",
                                                "root_account_id": 1,
                                                "enrollment_term_id": 330,
                                                "license": "private",
                                                "grade_passback_setting": null,
                                                "end_at": null,
                                                "public_syllabus": false,
                                                "public_syllabus_to_auth": false,
                                                "storage_quota_mb": 524,
                                                "is_public_to_auth_users": false,
                                                "homeroom_course": false,
                                                "course_color": null,
                                                "friendly_name": null,
                                                "apply_assignment_group_weights": false,
                                                "calendar": {
                                                    "ics": "https://hvl.instructure.com/feeds/calendars/course_8wan4Q35WCFevkPd2bsA0xVNHXhP8HI5S6w4NSZC.ics"
                                                },
                                                "time_zone": "Europe/Copenhagen",
                                                "blueprint": false,
                                                "template": false,
                                                "enrollments": [
                                                    {
                                                        "type": "student",
                                                        "role": "StudentEnrollment",
                                                        "role_id": 3,
                                                        "user_id": 81736,
                                                        "enrollment_state": "active",
                                                        "limit_privileges_to_course_section": false
                                                    },
                                                    {
                                                        "type": "student",
                                                        "role": "StudentEnrollment",
                                                        "role_id": 3,
                                                        "user_id": 81736,
                                                        "enrollment_state": "active",
                                                        "limit_privileges_to_course_section": false
                                                    }
                                                ],
                                                "hide_final_grades": false,
                                                "workflow_state": "available",
                                                "restrict_enrollments_to_course_dates": false
                                            }
        resolve(course)
    })
}
static getAssignmentInfo(){
    return new Promise((resolve, reject) => {
                            const assignment = { //https://hvl.instructure.com/api/v1/courses/29406/assignments/80710
                                                   "id": 80710,
                                                   "description": "\u003cp\u003eAlle gruppene skal levera utfylt og signert samarbeidsavtale seinast 12. september. Bruk \u003ca class=\"instructure_file_link instructure_scribd_file inline_disabled\" title=\"Lenke\" href=\"https://hvl.instructure.com/courses/29406/files/2895778?wrap=1\" target=\"_blank\" data-canvas-previewable=\"true\" data-api-endpoint=\"https://hvl.instructure.com/api/v1/courses/29406/files/2895778\" data-api-returntype=\"File\"\u003emal for samarbeidsavtale\u003c/a\u003e\u0026nbsp;som utgangspunkt for å bli einige om innhaldet i avtalen internt i gruppa. Å levera signert samarbeidsavtale er eit arbeidskrav.\u003c/p\u003e",
                                                   "due_at": "2024-09-12T21:30:00Z",
                                                   "unlock_at": "2024-08-19T22:00:00Z",
                                                   "lock_at": null,
                                                   "points_possible": 0.0,
                                                   "grading_type": "pass_fail",
                                                   "assignment_group_id": 32409,
                                                   "grading_standard_id": null,
                                                   "created_at": "2024-08-12T13:23:12Z",
                                                   "updated_at": "2024-10-01T06:31:23Z",
                                                   "peer_reviews": false,
                                                   "automatic_peer_reviews": false,
                                                   "position": 1,
                                                   "grade_group_students_individually": false,
                                                   "anonymous_peer_reviews": false,
                                                   "group_category_id": 17240,
                                                   "post_to_sis": false,
                                                   "moderated_grading": false,
                                                   "omit_from_final_grade": false,
                                                   "intra_group_peer_reviews": false,
                                                   "anonymous_instructor_annotations": false,
                                                   "anonymous_grading": false,
                                                   "graders_anonymous_to_graders": false,
                                                   "grader_count": 0,
                                                   "grader_comments_visible_to_graders": true,
                                                   "final_grader_id": null,
                                                   "grader_names_visible_to_final_grader": true,
                                                   "allowed_attempts": -1,
                                                   "annotatable_attachment_id": null,
                                                   "hide_in_gradebook": false,
                                                   "secure_params": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJsdGlfYXNzaWdubWVudF9pZCI6ImY3YzljMGQyLTY5MGQtNDM4OS04OWZiLTM4NzYxZjk1YjNiZSIsImx0aV9hc3NpZ25tZW50X2Rlc2NyaXB0aW9uIjoiXHUwMDNjcFx1MDAzZUFsbGUgZ3J1cHBlbmUgc2thbCBsZXZlcmEgdXRmeWx0IG9nIHNpZ25lcnQgc2FtYXJiZWlkc2F2dGFsZSBzZWluYXN0IDEyLiBzZXB0ZW1iZXIuIEJydWsgXHUwMDNjYSBjbGFzcz1cImluc3RydWN0dXJlX2ZpbGVfbGluayBpbnN0cnVjdHVyZV9zY3JpYmRfZmlsZSBpbmxpbmVfZGlzYWJsZWRcIiB0aXRsZT1cIkxlbmtlXCIgaHJlZj1cIi9jb3Vyc2VzLzI5NDA2L2ZpbGVzLzI4OTU3Nzg_d3JhcD0xXCIgdGFyZ2V0PVwiX2JsYW5rXCIgZGF0YS1jYW52YXMtcHJldmlld2FibGU9XCJ0cnVlXCJcdTAwM2VtYWwgZm9yIHNhbWFyYmVpZHNhdnRhbGVcdTAwM2MvYVx1MDAzZVx1MDAyNm5ic3A7c29tIHV0Z2FuZ3NwdW5rdCBmb3Igw6UgYmxpIGVpbmlnZSBvbSBpbm5oYWxkZXQgaSBhdnRhbGVuIGludGVybnQgaSBncnVwcGEuIMOFIGxldmVyYSBzaWduZXJ0IHNhbWFyYmVpZHNhdnRhbGUgZXIgZWl0IGFyYmVpZHNrcmF2Llx1MDAzYy9wXHUwMDNlIn0.NUmLE_CIFLmYqO89dnDgp1HPJRJNhsSCOqmUbaoFvSo",
                                                   "lti_context_id": "f7c9c0d2-690d-4389-89fb-38761f95b3be",
                                                   "course_id": 29406,
                                                   "name": "Samarbeidsavtale",
                                                   "submission_types": [
                                                       "online_upload"
                                                   ],
                                                   "has_submitted_submissions": true,
                                                   "due_date_required": false,
                                                   "max_name_length": 255,
                                                   "in_closed_grading_period": false,
                                                   "graded_submissions_exist": true,
                                                   "is_quiz_assignment": false,
                                                   "can_duplicate": true,
                                                   "original_course_id": null,
                                                   "original_assignment_id": null,
                                                   "original_lti_resource_link_id": null,
                                                   "original_assignment_name": null,
                                                   "original_quiz_id": null,
                                                   "workflow_state": "published",
                                                   "important_dates": false,
                                                   "muted": true,
                                                   "html_url": "https://hvl.instructure.com/courses/29406/assignments/80710",
                                                   "published": true,
                                                   "only_visible_to_overrides": false,
                                                   "visible_to_everyone": true,
                                                   "locked_for_user": false,
                                                   "submissions_download_url": "https://hvl.instructure.com/courses/29406/assignments/80710/submissions?zip=1",
                                                   "post_manually": false,
                                                   "anonymize_students": false,
                                                   "require_lockdown_browser": false,
                                                   "restrict_quantitative_data": false
                                               }
        resolve(assignment)
})
}
static getGroupMembers(){
    return new Promise((resolve, reject) => {
                            //https://hvl.instructure.com/api/v1/courses/29406/assignments/80710/users/81736/group_members
                            const group = [
                                            {
                                              "id": "15686",
                                              "name": "Ole Kristoffer Høivaag Jensen"
                                            },
                                            {
                                              "id": "66800",
                                              "name": "Jonas Grundvåg Rong"
                                            },
                                            {
                                              "id": "69850",
                                              "name": "Simen Hærnes Ihlen"
                                            },
                                            {
                                              "id": "80861",
                                              "name": "Martin Sollesnes Kummeneje"
                                            },
                                            {
                                              "id": "81736",
                                              "name": "Christian Hagen"
                                            },
                                            {
                                              "id": "82310",
                                              "name": "Vlad Nicusor Lipovanu"
                                            }
                                          ]
        resolve(group)
})
}
static getAssignmentGroup(){
    return new Promise((resolve, reject) => {
    //https://hvl.instructure.com/api/v1/courses/29406/assignment_groups/32409

        const group = {
                        "id": 32409,
                        "name": "Oppgåver",
                        "position": 2,
                        "group_weight": 0,
                        "sis_source_id": null,
                        "integration_data": {

                        },
                        "rules": {

                        }
                      }
        resolve(group)
    })
}
}//export default class CanvasAPIMock {
class PersistenceMock {

static getUserInfo(){
    return new Promise((resolve, reject) => {
    const user = {
                                        //GET https://hvl.instructure.com/api/v1/users/self
                                         "id": 81736,
                                         "name": "Christian Hagen",
                                         "created_at": "2022-07-30T08:15:49+02:00",
                                         "sortable_name": "Hagen, Christian",
                                         "short_name": "Christian Hagen",
                                         "avatar_url": "https://hvl.instructure.com/images/messages/avatar-50.png",
                                         "last_name": "Hagen",
                                         "first_name": "Christian",
                                         "locale": "nb",
                                         "effective_locale": "nb",
                                         "permissions": {
                                             "can_update_name": false,
                                             "can_update_avatar": true,
                                             "limit_parent_app_web_access": false
                                         }
                                     }
     resolve(user)
    })
}

static getCourseInfo(){
    return new Promise((resolve, reject) => {
        const course = { //GET https://hvl.instructure.com/api/v1/courses/29406
                                                "id": 29406,
                                                "name": "ING303-1 24H Systemtenking og innovasjon for ingeniører",
                                                "account_id": 15739,
                                                "uuid": "8wan4Q35WCFevkPd2bsA0xVNHXhP8HI5S6w4NSZC",
                                                "start_at": null,
                                                "grading_standard_id": null,
                                                "is_public": false,
                                                "created_at": "2024-05-01T06:20:36Z",
                                                "course_code": "ING303-1 24H",
                                                "default_view": "modules",
                                                "root_account_id": 1,
                                                "enrollment_term_id": 330,
                                                "license": "private",
                                                "grade_passback_setting": null,
                                                "end_at": null,
                                                "public_syllabus": false,
                                                "public_syllabus_to_auth": false,
                                                "storage_quota_mb": 524,
                                                "is_public_to_auth_users": false,
                                                "homeroom_course": false,
                                                "course_color": null,
                                                "friendly_name": null,
                                                "apply_assignment_group_weights": false,
                                                "calendar": {
                                                    "ics": "https://hvl.instructure.com/feeds/calendars/course_8wan4Q35WCFevkPd2bsA0xVNHXhP8HI5S6w4NSZC.ics"
                                                },
                                                "time_zone": "Europe/Copenhagen",
                                                "blueprint": false,
                                                "template": false,
                                                "enrollments": [
                                                    {
                                                        "type": "student",
                                                        "role": "StudentEnrollment",
                                                        "role_id": 3,
                                                        "user_id": 81736,
                                                        "enrollment_state": "active",
                                                        "limit_privileges_to_course_section": false
                                                    },
                                                    {
                                                        "type": "student",
                                                        "role": "StudentEnrollment",
                                                        "role_id": 3,
                                                        "user_id": 81736,
                                                        "enrollment_state": "active",
                                                        "limit_privileges_to_course_section": false
                                                    }
                                                ],
                                                "hide_final_grades": false,
                                                "workflow_state": "available",
                                                "restrict_enrollments_to_course_dates": false
                                            }
        resolve(course)
    })
}
static getAssignmentInfo(){
    return new Promise((resolve, reject) => {
                            const assignment = { //https://hvl.instructure.com/api/v1/courses/29406/assignments/80710
                                                   "id": 80710,
                                                   "description": "\u003cp\u003eAlle gruppene skal levera utfylt og signert samarbeidsavtale seinast 12. september. Bruk \u003ca class=\"instructure_file_link instructure_scribd_file inline_disabled\" title=\"Lenke\" href=\"https://hvl.instructure.com/courses/29406/files/2895778?wrap=1\" target=\"_blank\" data-canvas-previewable=\"true\" data-api-endpoint=\"https://hvl.instructure.com/api/v1/courses/29406/files/2895778\" data-api-returntype=\"File\"\u003emal for samarbeidsavtale\u003c/a\u003e\u0026nbsp;som utgangspunkt for å bli einige om innhaldet i avtalen internt i gruppa. Å levera signert samarbeidsavtale er eit arbeidskrav.\u003c/p\u003e",
                                                   "due_at": "2024-09-12T21:30:00Z",
                                                   "unlock_at": "2024-08-19T22:00:00Z",
                                                   "lock_at": null,
                                                   "points_possible": 0.0,
                                                   "grading_type": "pass_fail",
                                                   "assignment_group_id": 32409,
                                                   "grading_standard_id": null,
                                                   "created_at": "2024-08-12T13:23:12Z",
                                                   "updated_at": "2024-10-01T06:31:23Z",
                                                   "peer_reviews": false,
                                                   "automatic_peer_reviews": false,
                                                   "position": 1,
                                                   "grade_group_students_individually": false,
                                                   "anonymous_peer_reviews": false,
                                                   "group_category_id": 17240,
                                                   "post_to_sis": false,
                                                   "moderated_grading": false,
                                                   "omit_from_final_grade": false,
                                                   "intra_group_peer_reviews": false,
                                                   "anonymous_instructor_annotations": false,
                                                   "anonymous_grading": false,
                                                   "graders_anonymous_to_graders": false,
                                                   "grader_count": 0,
                                                   "grader_comments_visible_to_graders": true,
                                                   "final_grader_id": null,
                                                   "grader_names_visible_to_final_grader": true,
                                                   "allowed_attempts": -1,
                                                   "annotatable_attachment_id": null,
                                                   "hide_in_gradebook": false,
                                                   "secure_params": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJsdGlfYXNzaWdubWVudF9pZCI6ImY3YzljMGQyLTY5MGQtNDM4OS04OWZiLTM4NzYxZjk1YjNiZSIsImx0aV9hc3NpZ25tZW50X2Rlc2NyaXB0aW9uIjoiXHUwMDNjcFx1MDAzZUFsbGUgZ3J1cHBlbmUgc2thbCBsZXZlcmEgdXRmeWx0IG9nIHNpZ25lcnQgc2FtYXJiZWlkc2F2dGFsZSBzZWluYXN0IDEyLiBzZXB0ZW1iZXIuIEJydWsgXHUwMDNjYSBjbGFzcz1cImluc3RydWN0dXJlX2ZpbGVfbGluayBpbnN0cnVjdHVyZV9zY3JpYmRfZmlsZSBpbmxpbmVfZGlzYWJsZWRcIiB0aXRsZT1cIkxlbmtlXCIgaHJlZj1cIi9jb3Vyc2VzLzI5NDA2L2ZpbGVzLzI4OTU3Nzg_d3JhcD0xXCIgdGFyZ2V0PVwiX2JsYW5rXCIgZGF0YS1jYW52YXMtcHJldmlld2FibGU9XCJ0cnVlXCJcdTAwM2VtYWwgZm9yIHNhbWFyYmVpZHNhdnRhbGVcdTAwM2MvYVx1MDAzZVx1MDAyNm5ic3A7c29tIHV0Z2FuZ3NwdW5rdCBmb3Igw6UgYmxpIGVpbmlnZSBvbSBpbm5oYWxkZXQgaSBhdnRhbGVuIGludGVybnQgaSBncnVwcGEuIMOFIGxldmVyYSBzaWduZXJ0IHNhbWFyYmVpZHNhdnRhbGUgZXIgZWl0IGFyYmVpZHNrcmF2Llx1MDAzYy9wXHUwMDNlIn0.NUmLE_CIFLmYqO89dnDgp1HPJRJNhsSCOqmUbaoFvSo",
                                                   "lti_context_id": "f7c9c0d2-690d-4389-89fb-38761f95b3be",
                                                   "course_id": 29406,
                                                   "name": "Samarbeidsavtale",
                                                   "submission_types": [
                                                       "online_upload"
                                                   ],
                                                   "has_submitted_submissions": true,
                                                   "due_date_required": false,
                                                   "max_name_length": 255,
                                                   "in_closed_grading_period": false,
                                                   "graded_submissions_exist": true,
                                                   "is_quiz_assignment": false,
                                                   "can_duplicate": true,
                                                   "original_course_id": null,
                                                   "original_assignment_id": null,
                                                   "original_lti_resource_link_id": null,
                                                   "original_assignment_name": null,
                                                   "original_quiz_id": null,
                                                   "workflow_state": "published",
                                                   "important_dates": false,
                                                   "muted": true,
                                                   "html_url": "https://hvl.instructure.com/courses/29406/assignments/80710",
                                                   "published": true,
                                                   "only_visible_to_overrides": false,
                                                   "visible_to_everyone": true,
                                                   "locked_for_user": false,
                                                   "submissions_download_url": "https://hvl.instructure.com/courses/29406/assignments/80710/submissions?zip=1",
                                                   "post_manually": false,
                                                   "anonymize_students": false,
                                                   "require_lockdown_browser": false,
                                                   "restrict_quantitative_data": false
                                               }
        resolve(assignment)
})
}
static getGroupMembers(){
    return new Promise((resolve, reject) => {
                            //https://hvl.instructure.com/api/v1/courses/29406/assignments/80710/users/81736/group_members
                            const group = [
                                            {
                                              "id": "15686",
                                              "name": "Ole Kristoffer Høivaag Jensen"
                                            },
                                            {
                                              "id": "66800",
                                              "name": "Jonas Grundvåg Rong"
                                            },
                                            {
                                              "id": "69850",
                                              "name": "Simen Hærnes Ihlen"
                                            },
                                            {
                                              "id": "80861",
                                              "name": "Martin Sollesnes Kummeneje"
                                            },
                                            {
                                              "id": "81736",
                                              "name": "Christian Hagen"
                                            },
                                            {
                                              "id": "82310",
                                              "name": "Vlad Nicusor Lipovanu"
                                            }
                                          ]
        resolve(group)
})
}
static getAssignmentGroup(){
    return new Promise((resolve, reject) => {
    //https://hvl.instructure.com/api/v1/courses/29406/assignment_groups/32409

        const group = {
                        "id": 32409,
                        "name": "Oppgåver",
                        "position": 2,
                        "group_weight": 0,
                        "sis_source_id": null,
                        "integration_data": {

                        },
                        "rules": {

                        }
                      }
        resolve(group)
    })
}
}//import CanvasAPIMock from "https://kaffannen.github.io/Solution/Javascript/TeamUp/Api/CanvasAPIMock.js";

//export default class API{
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
    fetchGroup(){
        return this.#canvasApi.getAssignmentGroup()
    }
    fetchGroupMembers(){
        return this.#canvasApi.getGroupMembers()
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




class CanvasMsgBrokerMock {

}
class CanvasDBMock {

}

//import UIElement from "https://kaffannen.github.io/Solution/Javascript/EzUI/DeveloperClasses/UIElement.js";

//export default class AssignmentGroupMember extends UIElement {
class AssignmentGroupMember extends UIElement {

    constructor(nexus) {
        let jsonElement = nexus.getData();
        let htmlString
            =`
<fieldset class="IkkeInnlogget sentrerHorisontalt">
    <p>${jsonElement.name}</p>
</fieldset>
            `;
        super(htmlString, nexus);
    }
}//import AssignmentGroupMember from "https://kaffannen.github.io/Solution/Javascript/TeamUp/GroupMember/UIElements/AssignmentGroupMember.js";
//import ElementNode from "https://kaffannen.github.io/Solution/Javascript/EzUI/DeveloperClasses/ElementNode.js";
//import StudentGroup from "https://kaffannen.github.io/Solution/Javascript/TeamUp/Group/UIElementer/StudentGroup.js";

//export default class GroupMember extends ElementNode {
class GroupMember extends ElementNode {

    defineUIElements() {
        this.addUIElement(new AssignmentGroupMember(this))
            .fixTo(StudentGroup);
        super.defineUIElements();
        return this;
    }

    static STATES = {
        INIT: function(){
            this.getUIElement(AssignmentGroupMember).attach();
        }
    };
}//import UIElement from "https://kaffannen.github.io/Solution/Javascript/EzUI/DeveloperClasses/UIElement.js";
//import AssignmentGroupMember from "https://kaffannen.github.io/Solution/Javascript/TeamUp/GroupMember/UIElements/AssignmentGroupMember.js";
//export default class StudentGroup extends UIElement {
class StudentGroup extends UIElement {
    constructor(nexus) {

        let htmlString
            =`
<fieldset class="IkkeInnlogget sentrerHorisontalt">
    <h3>Badass StudentGroup</h3>
    <div data-anchor=${AssignmentGroupMember.name}></div>
    <br>
    <br>
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
}//import ElementNode from "https://kaffannen.github.io/Solution/Javascript/EzUI/DeveloperClasses/ElementNode.js";
//import StudentGroup from "https://kaffannen.github.io/Solution/Javascript/TeamUp/Group/UIElementer/StudentGroup.js";
//import StudentUI from "https://kaffannen.github.io/Solution/Javascript/TeamUp/Bruker/UIElementer/StudentUI.js";
//import GroupMember from "https://kaffannen.github.io/Solution/Javascript/TeamUp/GroupMember/GroupMember.js";

//export default class Group extends ElementNode {
class Group extends ElementNode {

    defineUIElements() {
        this.addUIElement(new StudentGroup(this))
            .fixTo(StudentUI);
        super.defineUIElements();
        this.fetchGroupMembers();
        return this;
    }

    static STATES = {
        INIT: function(){
            this.getUIElement(StudentGroup).attach();
        }
    };
    fetchGroupMembers(){
        program.getApi().fetchGroupMembers()
            .then(groupMembersInfo => {
                groupMembersInfo.forEach(memberInfo => {
                        let member = new GroupMember(memberInfo,this)
                        .defineUIElements()
                        .setState(GroupMember.STATES.INIT);
                    });
            })
            .catch(error => {
                console.log("Error fetching group members: " + error);
            });
    }
}//import UIElement from "https://kaffannen.github.io/Solution/Javascript/EzUI/DeveloperClasses/UIElement.js";
//import Student from "https://kaffannen.github.io/Solution/Javascript/TeamUp/Bruker/Student.js";
//export default class CollapsedState extends UIElement{
class CollapsedState extends UIElement{

    constructor(nexus) {
        let jsonElement = nexus.getData();
        let htmlString
            = `
<fieldset> 
    <legend>Hei ${jsonElement.user.name}</legend>
    <input data-input="utvidButton" type="button" value="Utvid" onclick="program.find(this).utvid()">
</fieldset>
            `;
        super(htmlString,nexus);
    }
    utvid() {
        this.getNode().setState(Student.STATES.EXPANDED);
    }
}//import UIElement from "https://kaffannen.github.io/Solution/Javascript/EzUI/DeveloperClasses/UIElement.js";
//import Student from "https://kaffannen.github.io/Solution/Javascript/TeamUp/Bruker/Student.js";
//export default class ExpandedState extends UIElement
class ExpandedState extends UIElement
{
    constructor(nexus) {
        let jsonElement = nexus.getData();
        let htmlString
            = `
<fieldset>
    <legend>Hei ${jsonElement.user.name}</legend>
    <input data-input="utvidButton" type="button" value="Kollaps" onclick="program.find(this).minimer()">
</fieldset>
            `;
        super(htmlString,nexus);
    }
    minimer() {
        this.getNode().setState(Student.STATES.COLLAPSED);
    }
}//import StudentGroup from "https://kaffannen.github.io/Solution/Javascript/TeamUp/Group/UIElementer/StudentGroup.js";
//import UIElement from "https://kaffannen.github.io/Solution/Javascript/EzUI/DeveloperClasses/UIElement.js";
//export default class StudentUI extends UIElement {
class StudentUI extends UIElement {

    constructor(nexus) {

        let htmlString
            =`
<fieldset class="IkkeInnlogget sentrerHorisontalt">
    <h3>Total boss of a Badass Student UI</h3>
    <div data-anchor=${StudentGroup.name}>studentanchor</div>
    <br>
    <input data-input="loginButton" type="button" value ="få SKYNET til å fikse en gruppe til deg"
        onclick='find(this).studentAction()'
    ">
    <br>
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
}//import UIElement from "https://kaffannen.github.io/Solution/Javascript/EzUI/DeveloperClasses/UIElement.js";
//export default class TeacherUI extends UIElement{
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
    <br>
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

}//import ElementNode from "https://kaffannen.github.io/Solution/Javascript/EzUI/DeveloperClasses/ElementNode.js";
//import CollapsedState from "https://kaffannen.github.io/Solution/Javascript/TeamUp/Bruker/UIElementer/CollapsedState.js";
//import ExpandedState from "https://kaffannen.github.io/Solution/Javascript/TeamUp/Bruker/UIElementer/ExpandedState.js";
//import StudentUI from "https://kaffannen.github.io/Solution/Javascript/TeamUp/Bruker/UIElementer/StudentUI.js";
//import Group from "https://kaffannen.github.io/Solution/Javascript/TeamUp/Group/Group.js";

//export default class Student extends ElementNode {
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
        return program.getApi().fetchGroup()
            .then(groupInfo=>{
                let group = new Group(groupInfo,this)
                    .defineUIElements()
                    .setState(Group.STATES.INIT);
                this.setFavourite(group);
                })
            .catch(error =>alert(error))
    }
}//import ElementNode from "https://kaffannen.github.io/Solution/Javascript/EzUI/DeveloperClasses/ElementNode.js";
//import CollapsedState from "https://kaffannen.github.io/Solution/Javascript/TeamUp/Bruker/UIElementer/CollapsedState.js";
//import ExpandedState from "https://kaffannen.github.io/Solution/Javascript/TeamUp/Bruker/UIElementer/ExpandedState.js";
//import TeacherUI from "https://kaffannen.github.io/Solution/Javascript/TeamUp/Bruker/UIElementer/TeacherUI.js";

//export default class Underviser extends ElementNode {
class Underviser extends ElementNode {

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
}//import EzUI from "https://kaffannen.github.io/Solution/Javascript/EzUI/DeveloperClasses/EzUI.js";
//import Student from "https://kaffannen.github.io/Solution/Javascript/TeamUp/Bruker/Student.js";
//import Underviser from "https://kaffannen.github.io/Solution/Javascript/TeamUp/Bruker/Underviser.js";

//export default class BasicSolution extends EzUI {
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
                else {
                    let bruker = new Underviser(loadInfo,this)
                                    .defineUIElements()
                                    .setState(Underviser.STATES.COLLAPSED);
                                this.velgBruker(bruker);
                }

            })
            .catch(error =>alert(error))
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

let api = new API()
        .setCanvasApi(CanvasAPI)
        .setMsgBroker(MsgBrokerMock)
        .setPersistence(PersistenceMock)

window.program = new BasicSolution(api)
let program = window.program;
await program.defineUIElements();


