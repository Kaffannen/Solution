class StudentGroupUIE extends UIElement {
    constructor(nexus) {
        let jsonElement = nexus.getData();
        let htmlString
            =`
<fieldset class="fieldset-reset">
    <p> 'Gruppenavn | Ingen gruppe funnet '</p>
    <p> 'Antall medlemmer / [minimum - maximum medlemmer for oblig] '</p>
    <div data-anchor=${AssignmentGroupMember.name}></div>
    <input data-input="" type="button" value ="Inviter en person / gruppe"
                        onclick='program.find(this).mergeRequest()'
                    ">
    <input data-input="" type="button" value ="Si til faglærer at gruppen ønsker å bli tilordnet medlemmer"
            onclick='program.find(this).signalDisposition(open)'
        ">
    <input data-input="" type="button" value ="Forlat gruppe"
                onclick='program.find(this).studentAction()'
            ">

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