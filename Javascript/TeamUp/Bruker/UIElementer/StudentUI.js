class StudentUI extends UIElement {

    /**
     *
     * @param {ElementNode} nexus
     */
    constructor(nexus) {

        let htmlString
            =`
<fieldset class="IkkeInnlogget sentrerHorisontalt">
    <h3>Badass Student UI</h3>
    <input data-input="username" type="text" placeholder="Brukernavn" 
        onkeydown="
            let loginform = find(this);
            switch (event.key){
                case 'Enter':
                    loginform.getInputElement('loginButton').click();
                    break;
                case 'Escape':
                    loginform.getNode().setState(Bruker.STATES.LOGGED_OUT);
                    break;
            }"
    />
    <input data-input="password" type="password" placeholder="Passord" 
        onkeydown="
            let loginform = find(this);
            switch (event.key){
                case 'Enter':
                    loginform.getInputElement('loginButton').click();
                    break;
                case 'Escape':
                    loginform.getNode().setState(Bruker.STATES.LOGGED_OUT);
                    break; 
            }"
    />
    <br>
    <input data-input="loginButton" type="button" value ="få SKYNET til å fikse en gruppe til deg"
        onclick='find(this).login()'
        onkeydown="if (event.key === 'Escape') find(this).getNode().setState(Bruker.STATES.LOGGED_OUT)"
    ">
    <br>
</fieldset>
            `;
        super(htmlString, nexus);
    }
    /**
     * @returns {Bruker}
     */
    login() {
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