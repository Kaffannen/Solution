class TeacherGroupUIE extends UIElement {
    constructor(nexus) {
        let jsonElement = nexus.getData();
        let htmlString
            =`
<fieldset class="fieldset-reset">
    <div data-anchor=${HeaderbarCollapsed.name}></div>
    <div data-anchor=${HeaderbarExpanded.name}></div>
    <div data-anchor=${AssignmentGroupMember.name}></div>
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
}