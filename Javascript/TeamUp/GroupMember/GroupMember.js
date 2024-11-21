//import AssignmentGroupMember from "https://kaffannen.github.io/Solution/Javascript/TeamUp/GroupMember/UIElements/AssignmentGroupMember.js";
//import ElementNode from "https://kaffannen.github.io/Solution/Javascript/EzUI/DeveloperClasses/ElementNode.js";
//import StudentGroup from "https://kaffannen.github.io/Solution/Javascript/TeamUp/Group/UIElementer/StudentGroup.js";

//export default class GroupMember extends ElementNode {
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