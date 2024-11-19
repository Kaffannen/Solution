class TeacherGroup extends ElementNode {

    defineUIElements() {
        this.addUIElement(new TeacherGroupUIE(this))
            .fixTo(this.getParentNode() instanceof Student ? StudentUI : TeacherUI);
        super.defineUIElements();
        this.fetchGroupMembers();
        return this;
    }

    static STATES = {
        INIT: function(){
            this.getUIElement(TeacherGroupUIE).attach();
        }
    };
    fetchGroupMembers(){
        this.getData().members.forEach(memberInfo => {
                                let member = new GroupMember(memberInfo,this)
                                .defineUIElements()
                                .setState(GroupMember.STATES.INIT);
                            });
    }
}
