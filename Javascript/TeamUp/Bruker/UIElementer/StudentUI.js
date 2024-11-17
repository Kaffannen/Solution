class StudentUI extends UIElement {

    constructor(nexus) {

        let htmlString
            =`
<fieldset class="IkkeInnlogget sentrerHorisontalt">
    <h3>Student UI</h3>
    <div data-anchor=${StudentGroup.name}>studentanchor</div>
    <br>
    <input data-input="loginButton" type="button" value ="få SKYNET til å fikse en gruppe til deg"
        onclick='program.find(this).studentAction()'
    ">
    <input data-input="loginButton" type="button" value ="Lukk"
            onclick='program.find(this).closeTeamUp()'
        ">
    <br>
</fieldset>
            `;
        super(htmlString, nexus);
    }

    studentAction() {
        let credentials ={
            brukernavn:this.getInputElement("username").value,
            passord:this.getInputElement("password").value
        };
        program.getApi().loginUser(credentials)
            .then(data=>{
                let bruker= new Bruker(data,program)
                    .defineUIElements()
                    .setState(Bruker.STATES.LOGGED_IN);
                program.velgBruker(bruker);
            })
            .catch(error => alert(error))
        this.getInputElement("username").value = "";
        this.getInputElement("password").value = "";
    }
    closeTeamUp() {
        this.getNode().setState(Student.STATES.COLLAPSED);
    }
}