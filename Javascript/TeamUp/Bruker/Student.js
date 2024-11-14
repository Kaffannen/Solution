export default class Student extends ElementNode {

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
        return program.getApi().fetchGroup()
            .then(groupInfo=>{
                let group = new Group(groupInfo,this)
                    .defineUIElements()
                    .setState(Group.STATES.INIT);
                this.setFavourite(group);
                })
            .catch(error =>alert(error))
    }
}