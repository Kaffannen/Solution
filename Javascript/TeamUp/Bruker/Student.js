//import ElementNode from "https://kaffannen.github.io/Solution/Javascript/EzUI/DeveloperClasses/ElementNode.js";
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
        return program.getApi().fetchGroup(this.#data.assignment.assignment_group_id)
            .then(groupInfo=>{
                let group = new Group(groupInfo,this)
                    .defineUIElements()
                    .setState(Group.STATES.INIT);
                this.setFavourite(group);
                })
            .catch(error =>alert(error))
    }
}