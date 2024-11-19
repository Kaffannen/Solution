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
                        .setState(GroupMember.STATES.INIT);
                    });
            })
            .catch(error => {
                console.log("Error fetching group members: " + error);
            });
    }
}
