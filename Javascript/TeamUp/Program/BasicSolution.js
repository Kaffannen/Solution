class BasicSolution extends EzUI {

    /**
     * Definerer EasyChatklassen sine UIElementer (html kode)
     * @return {EasyChat}
     */
    defineUIElements(){
        //this.addUIElement(new Program_Anchors(this))
          //  .fixTo();
        super.defineUIElements();
        this.fetchBruker();
        return this;
    }

    /**
     * Definerer EasyChatklassen tilstander
     */
    static STATES = {
        INIT: function(){
            //this.getUIElement(Program_Anchors).attach();
        }
    };

    /**
     *Henter en bruker fra server
    */
    fetchBruker(){
        this.getApi().getDefaultbruker()
            .then(defaultBruker=>{
                if (defaultBruker.rolle === "student"){
                    let bruker = new Student(defaultBruker,this)
                                    .defineUIElements()
                                    .setState(Student.STATES.COLLAPSED);
                                this.velgBruker(bruker);
                }
                else if (defaultBruker.rolle === "underviser"){
                    let bruker = new Underviser(defaultBruker,this)
                                    .defineUIElements()
                                    .setState(Underviser.STATES.COLLAPSED);
                                this.velgBruker(bruker);
                }

            })
            .catch(error =>alert(error))
    }

    /**
     * @param {Bruker} bruker
     */
    velgBruker(bruker){
        if (this.getFavourite()!==undefined)
            this.removeChild(this.getFavourite())
        super.setFavourite(bruker);
    }

    /**
     * @param {EasyChatAPI | EasyChatMockAPI} api
     */
    constructor(api) {
        super(api);
    }
}

