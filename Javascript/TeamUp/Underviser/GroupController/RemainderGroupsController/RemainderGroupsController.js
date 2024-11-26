class RemainderGroupsController extends ElementNode {

    defineUIElements() {
        this.addUIElement(new RemainderGroupControllerUIE(this))
            .fixTo();
        super.defineUIElements();
        this.createGroupControllers();
        return this;
    }

    static STATES = {
        INIT: function () {
        },
        COLLAPSED: function () {
            this.getUIElement(CollapsedState).attach();
            this.getUIElement(ExpandedState).detach();
            this.getUIElement(TeacherUI).detach();
        },
        EXPANDED: function () {
            this.getUIElement(CollapsedState).detach();
            this.getUIElement(ExpandedState).attach();
            this.getUIElement(TeacherUI).attach();
        }
    };
    createGroupController(){
        let controller = new GroupController(this)
            .defineUIElements()
            .setState(GroupController.STATES.COLLAPSED);
    }
}
