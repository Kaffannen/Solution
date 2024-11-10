class Student extends ElementNode {

    /**
     * Definerer Brukerklassen sine UIElementer (html kode)
     * @return {Bruker}
     */
    defineUIElements() {
        this.addUIElement(new CollapsedState(this))
            .fixTo();
        this.addUIElement(new ExpandedState(this))
            .fixTo();
        this.addUIElement(new StudentUI(this))
            .fixTo();
        super.defineUIElements();
        //this.fetchChatterom();
        return this;
    }

    /**
     * Definerer Brukerklassens tilstander
     */
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