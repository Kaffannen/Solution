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
}