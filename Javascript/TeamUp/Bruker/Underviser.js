class Underviser extends ElementNode {

    /**
     * Definerer Underviserklassen sine UIElementer (html kode)
     * @return {Bruker}
     */
    defineUIElements() {
        this.addUIElement(new CollapsedState(this))
            .fixTo();
        this.addUIElement(new ExpandedState(this))
            .fixTo();
        this.addUIElement(new TeacherUI(this))
            .fixTo();
        super.defineUIElements();
        return this;
    }

    /**
     * Definerer Underviserklassen tilstander
     */
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

    /**
     *Henter chatterom fra server
     */
    fetchChatterom() {
        let brukerid = {id:this.getData().id};
        program.getApi()
            .getChatterom(this.getData())
            .then(crPojos=>{
                crPojos.forEach(pojo=>{
                    let chatterom = new Chatterom(pojo, this)
                        .defineUIElements()
                    if (chatterom.getParentNode().getFavourite()===undefined)
                        this.velgChatterom(chatterom)
                });
            })
            .catch(error=>{
                console.log(error);
            })
    }
    /**
     *
     * @param newFavourite
     */
    velgChatterom(newFavourite) {
        let oldFavourite = this.getFavourite();
        if (oldFavourite!==undefined)
            oldFavourite.setState(Chatterom.STATES.UNSELECTED);
        if (newFavourite!==undefined)
            newFavourite.setState(Chatterom.STATES.SELECTED);
        super.setFavourite(newFavourite);
    }
}