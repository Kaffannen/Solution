class Underviser extends ElementNode {

    defineUIElements() {
        this.addUIElement(new CollapsedState(this))
            .fixTo();
        this.addUIElement(new ExpandedState(this))
            .fixTo();
        this.addUIElement(new TeacherUI(this))
            .fixTo();
        super.defineUIElements();
        this.fetchGroups();
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
    fetchGroups(){
            return program.getApi().fetchGroups(this.getData().assignment.assignment_group_id)
                .then(userGroups => {
                    userGroups.studentgroups.forEach(groupInfo=>{
                        let group = new TeacherGroup(groupInfo,this)
                            .defineUIElements()
                            .setState(TeacherGroup.STATES.COLLAPSED);
                    });
                })
                .catch(error =>console.error(error))
        }
}
