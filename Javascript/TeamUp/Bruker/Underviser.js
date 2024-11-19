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
            /**
            https://hvl.instructure.com/api/v1/courses/29406/assignments/80710/users/82310/group_members
            https://hvl.instructure.com/api/v1/courses/25563/assignments/75844/users/15686/group_members
            gir tilgang til alle gruppemedlemmer i en gruppe - for alle jeg har ID på.
            https://hvl.instructure.com/api/v1/courses/25563/users?page=1&per_page=1000&enrollment_type=teacher gir alle teachers
            https://hvl.instructure.com/api/v1/courses/25563/users?page=1&per_page=1000&enrollment_type=student gir alle students
            **/
            return program.getApi().fetchGroups(this.getData().assignment.assignment_group_id)
                .then(userGroups => {
                    userGroups.studentgroups.forEach(groupInfo=>{
                        let group = new TeacherGroup(groupInfo,this)
                            .defineUIElements()
                            .setState(TeacherGroup.STATES.INIT);
                    });
                })
                .catch(error =>console.error(error))
        }
}
