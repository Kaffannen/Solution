class StudentUI extends UIElement {

    constructor(nexus) {

        let htmlString
            =`
<fieldset class="fieldset-reset">
    <p>HeiIII</p>
    <div data-anchor=${StudentGroupUIE.name}></div>
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