class BasicSolution extends EzUI {

    async defineUIElements(){
        super.defineUIElements();
        await this.fetchBruker();
        return this;
    }

    static STATES = {
        INIT: function(){
        }
    };

    fetchBruker(){
        return program.getApi().onLoadInfo()
            .then(loadInfo=>{
                if (loadInfo.course.enrollments[0].role === "StudentEnrollment"){
                    let bruker = new Student(loadInfo,this)
                                    .defineUIElements()
                                    .setState(Student.STATES.COLLAPSED);
                                this.velgBruker(bruker);
                }
                else {
                    let bruker = new Underviser(loadInfo,this)
                                    .defineUIElements()
                                    .setState(Underviser.STATES.COLLAPSED);
                                this.velgBruker(bruker);
                }

            })
            .catch(error =>alert(error))
    }

    velgBruker(bruker){
        if (this.getFavourite()!==undefined)
            this.removeChild(this.getFavourite())
        super.setFavourite(bruker);
    }

    constructor(api) {
        super(api);
    }
}

