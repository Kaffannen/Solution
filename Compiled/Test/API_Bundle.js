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

class PersistenceMock {


}
class MsgBrokerMock {


}
class CanvasAPITeacherMock {

  getUserInfo() {
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

  getCourseInfo() {
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
            "role": this.#rolle,
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
  getAssignmentInfo() {
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
  getGroupMembers() {
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
  getAssignmentGroup() {
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
  async fetchGroups() {
    return {
      "studentgroups": [
        {
          "id": "69281",
          "members": [
            {
              "id": "69281",
              "name": "Bjørnar Øvretveit Helgeland"
            },
            {
              "id": "79577",
              "name": "Storm Anders Sangolt"
            },
            {
              "id": "81078",
              "name": "Eren Akat"
            }
          ]
        },
        {
          "id": "80331",
          "members": [
            {
              "id": "80331",
              "name": "Justin Roland Schüttpelz"
            },
            {
              "id": "80992",
              "name": "Ole Martin Lystadmoen Amundsen"
            },
            {
              "id": "82546",
              "name": "Sondre Aasarmoen"
            }
          ]
        },
        {
          "id": "15686",
          "members": [
            {
              "id": "15686",
              "name": "Ole Kristoffer Høivaag Jensen"
            },
            {
              "id": "16967",
              "name": "Inger Lise Paulsen"
            },
            {
              "id": "81760",
              "name": "Elisabeth Orrebakken Vatnøy"
            },
            {
              "id": "82310",
              "name": "Vlad Nicusor Lipovanu"
            },
            {
              "id": "82715",
              "name": "Hannah Synnøve Hofseth"
            },
            {
              "id": "82814",
              "name": "Fermari John Angcos"
            }
          ]
        },
        {
          "id": "67355",
          "members": [
            {
              "id": "67355",
              "name": "Deni Zaudinovitsj Makhmadiyev"
            },
            {
              "id": "80459",
              "name": "Sidar Baran"
            },
            {
              "id": "83643",
              "name": "Emir Bulduk"
            }
          ]
        },
        {
          "id": "20931",
          "members": [
            {
              "id": "20931",
              "name": "Ulrik Hiis Bergh"
            },
            {
              "id": "67533",
              "name": "Niklas Aronsen Stokke"
            },
            {
              "id": "79636",
              "name": "Håvard Buner Færøvik"
            },
            {
              "id": "79969",
              "name": "Didrik Smidt Andersen"
            },
            {
              "id": "80535",
              "name": "August Håkonssøn Langfeldt"
            }
          ]
        },
        {
          "id": "68735",
          "members": [
            {
              "id": "68735",
              "name": "Einar Gangsøy Eide"
            },
            {
              "id": "80875",
              "name": "Sindri Olafur S Szarvas"
            },
            {
              "id": "81064",
              "name": "Eivind Bjørnli"
            }
          ]
        },
        {
          "id": "8801",
          "members": [
            {
              "id": "8801",
              "name": "Siri Norstrand Eielsen"
            },
            {
              "id": "66630",
              "name": "Ketil Jacobsen"
            },
            {
              "id": "84202",
              "name": "Tina Hansen Bolstad"
            }
          ]
        },
        {
          "id": "36543",
          "members": [
            {
              "id": "36543",
              "name": "Fredrik Nicolai Crook"
            },
            {
              "id": "46133",
              "name": "Gørild Mostraum Krabbedal"
            },
            {
              "id": "81189",
              "name": "Sara Hønsi"
            },
            {
              "id": "82966",
              "name": "Celine Habbestad Seland"
            }
          ]
        },
        {
          "id": "46345",
          "members": [
            {
              "id": "46345",
              "name": "Jan-Petter Dåvøy"
            }
          ]
        },
        {
          "id": "79615",
          "members": [
            {
              "id": "79615",
              "name": "Ibrahim Mohamad Elrahmoun"
            },
            {
              "id": "80009",
              "name": "Moussa Mohamad Elrahmoun"
            },
            {
              "id": "81893",
              "name": "Felix Berget"
            },
            {
              "id": "83099",
              "name": "Olav Osnes Devik"
            }
          ]
        },
        {
          "id": "83704",
          "members": [
            {
              "id": "83704",
              "name": "Tamer Kyed Gharz al Deen"
            }
          ]
        },
        {
          "id": "32974",
          "members": [
            {
              "id": "32974",
              "name": "Markus Nedreberg Gjerde"
            },
            {
              "id": "80430",
              "name": "Gard Molegoda"
            },
            {
              "id": "80687",
              "name": "Rahiima Abdilahi Mahamuud"
            },
            {
              "id": "81736",
              "name": "Christian Hagen"
            }
          ]
        },
        {
          "id": "80930",
          "members": [
            {
              "id": "80930",
              "name": "Shawn Grifon Helgesen"
            }
          ]
        },
        {
          "id": "66836",
          "members": [
            {
              "id": "66836",
              "name": "André Hetlevik"
            }
          ]
        },
        {
          "id": "69113",
          "members": [
            {
              "id": "69113",
              "name": "Isabel Nickelsen"
            },
            {
              "id": "79340",
              "name": "Dat Thanh Huynh"
            },
            {
              "id": "79682",
              "name": "Marius Alvestad Horn"
            },
            {
              "id": "80266",
              "name": "Adam Yasaev"
            }
          ]
        },
        {
          "id": "70900",
          "members": [
            {
              "id": "70900",
              "name": "Andréas Øihaugen"
            },
            {
              "id": "72909",
              "name": "Oskar Emil Jacobsen"
            },
            {
              "id": "73434",
              "name": "Simon Midthjell Bjørnsen"
            }
          ]
        },
        {
          "id": "40165",
          "members": [
            {
              "id": "40165",
              "name": "Eivind Løseth"
            },
            {
              "id": "45919",
              "name": "Muaz Mahmoud Alfarhan"
            },
            {
              "id": "55726",
              "name": "Håvard Nordheim Karlsen"
            },
            {
              "id": "81349",
              "name": "Ahmad Jahanmehmani"
            }
          ]
        },
        {
          "id": "11296",
          "members": [
            {
              "id": "11296",
              "name": "Ana Eleah Patricia Igesund Kleppe"
            }
          ]
        },
        {
          "id": "69916",
          "members": [
            {
              "id": "69916",
              "name": "Azzat Ammar Kouzi"
            }
          ]
        },
        {
          "id": "80861",
          "members": [
            {
              "id": "80861",
              "name": "Martin Sollesnes Kummeneje"
            }
          ]
        },
        {
          "id": "72835",
          "members": [
            {
              "id": "72835",
              "name": "Martin Lenes"
            },
            {
              "id": "72836",
              "name": "Andre Kristopher Ripman"
            },
            {
              "id": "79945",
              "name": "Leo Alexander Gilje"
            },
            {
              "id": "80946",
              "name": "Oliver Kvebek"
            }
          ]
        },
        {
          "id": "35526",
          "members": [
            {
              "id": "35526",
              "name": "Petter Tesdal"
            },
            {
              "id": "67835",
              "name": "Kristoffer Fjeldstad Madsen"
            }
          ]
        },
        {
          "id": "14539",
          "members": [
            {
              "id": "14539",
              "name": "Even Bakke"
            },
            {
              "id": "21890",
              "name": "Bjørn Renslo Instefjord"
            },
            {
              "id": "76188",
              "name": "Kamil Tomasz Matyjaszczyk"
            },
            {
              "id": "79986",
              "name": "Richard Persson"
            }
          ]
        },
        {
          "id": "69428",
          "members": [
            {
              "id": "69428",
              "name": "Mathias Hafstad Hetle"
            },
            {
              "id": "70991",
              "name": "Ferdinand August Sørlie"
            },
            {
              "id": "71466",
              "name": "Kristoffer Astrup Nedrebø"
            }
          ]
        },
        {
          "id": "69262",
          "members": [
            {
              "id": "69262",
              "name": "Seyit Siyar Yüce"
            },
            {
              "id": "70416",
              "name": "Kedirdin Keyser"
            },
            {
              "id": "72718",
              "name": "Saciid Ahmed Noor"
            },
            {
              "id": "81086",
              "name": "Eyüp Yayan"
            }
          ]
        },
        {
          "id": "67226",
          "members": [
            {
              "id": "67226",
              "name": "Kristina Gryting Olsen"
            }
          ]
        },
        {
          "id": "80629",
          "members": [
            {
              "id": "80629",
              "name": "Johan Severin Reitan"
            },
            {
              "id": "81060",
              "name": "Maxwell Andric Xavier Wogsland"
            }
          ]
        },
        {
          "id": "47849",
          "members": [
            {
              "id": "47849",
              "name": "Andreas Helleberg Rovik"
            },
            {
              "id": "69672",
              "name": "Mikal Bø"
            },
            {
              "id": "70587",
              "name": "Iver Sande"
            }
          ]
        },
        {
          "id": "73195",
          "members": [
            {
              "id": "73195",
              "name": "Rana Kelani"
            },
            {
              "id": "79876",
              "name": "Eirik Magnus Stenberg"
            },
            {
              "id": "83984",
              "name": "Jonas Stokkeland"
            }
          ]
        }
      ],
      "teachers": [
        {
          "id": 21705,
          "name": "Per Helge Litzheim Frøiland",
          "created_at": "2018-08-07T12:20:30+02:00",
          "sortable_name": "Frøiland, Per Helge Litzheim",
          "short_name": "Per Helge Litzheim Frøiland"
        },
        {
          "id": 4610,
          "name": "Richard Kjepso",
          "created_at": "2018-03-15T13:24:41+01:00",
          "sortable_name": "Kjepso, Richard",
          "short_name": "Richard Kjepso"
        },
        {
          "id": 1552,
          "name": "Remy Andre Monsen",
          "created_at": "2017-12-16T00:21:33+01:00",
          "sortable_name": "Monsen, Remy Andre",
          "short_name": "Remy Andre Monsen"
        },
        {
          "id": 73877,
          "name": "Ngoc Thanh Nguyen",
          "created_at": "2021-08-20T14:15:14+02:00",
          "sortable_name": "Nguyen, Ngoc Thanh",
          "short_name": "Ngoc Thanh Nguyen"
        }
      ],
      "assistants": [
        {
          "id": 70371,
          "name": "Martin Berg Alstad",
          "created_at": "2021-07-29T03:43:45+02:00",
          "sortable_name": "Alstad, Martin Berg",
          "short_name": "Martin Berg Alstad"
        },
        {
          "id": 46345,
          "name": "Jan-Petter Dåvøy",
          "created_at": "2020-07-29T08:14:37+02:00",
          "sortable_name": "Dåvøy, Jan-Petter",
          "short_name": "Jan-Petter Dåvøy"
        }
      ]
    }

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
        return Promise.all([
            this.#canvasApi.getUserInfo(),
            this.#canvasApi.getCourseInfo(),
            this.#canvasApi.getAssignmentInfo()])
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



