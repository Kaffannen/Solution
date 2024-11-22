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