import ElementNode from "https://kaffannen.github.io/Solution/Javascript/EzUI/DeveloperClasses/ElementNode.js";
import StudentGroup from "https://kaffannen.github.io/Solution/Javascript/TeamUp/Group/UIElementer/StudentGroup.js";
import StudentUI from "https://kaffannen.github.io/Solution/Javascript/TeamUp/Bruker/UIElementer/StudentUI.js";
import GroupMember from "https://kaffannen.github.io/Solution/Javascript/TeamUp/GroupMember/GroupMember.js";

export default class Group extends ElementNode {

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
}