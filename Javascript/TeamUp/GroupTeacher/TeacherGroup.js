class TeacherGroup extends ElementNode {

    defineUIElements() {
        this.addUIElement(new TeacherGroupUIE(this))
            .fixTo(this.getParentNode() instanceof Student ? StudentUI : TeacherUI);
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
