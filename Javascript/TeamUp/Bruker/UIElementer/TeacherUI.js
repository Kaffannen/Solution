class TeacherUI extends UIElement{
    /**
     *
     * @param {ElementNode} nexus
     */
    constructor(nexus) {
        let htmlString
            =`
<fieldset class="IkkeInnlogget sentrerHorisontalt">
    <h3>Badass Teacher UI</h3>
    <p>Kjøre på med lister over grupper og hvem som er i dem - drag & drop funksjonalitet?</p>
    <ul>
        <li>Gruppe 1, 4/[8-12] studenter </li>
        <li>Gruppe 2, 4/[8-12] studenter </li>
        <li>Gruppe 3, 4/[8-12] studenter </li>
        <li>Gruppe 3, 4/[8-12] studenter </li>
        <li>Gruppe 3, 4/[8-12] studenter </li>
        <li>Gruppe 1, 4/[8-12] studenter </li>
        <li>Gruppe 2, 4/[8-12] studenter </li>
        <li>Gruppe 3, 4/[8-12] studenter </li>
        <li>Gruppe 3, 4/[8-12] studenter </li>
        <li>Gruppe 3, 4/[8-12] studenter </li>
        <li>Gruppe 1, 4/[8-12] studenter </li>
        <li>Gruppe 2, 4/[8-12] studenter </li>
        <li>Gruppe 3, 4/[8-12] studenter </li>
        <li>Gruppe 3, 4/[8-12] studenter </li>
        <li>Gruppe 3, 4/[8-12] studenter </li>
        <li>...</li>
        <li>25 studenter ikke i gruppe. 10 av dem har bedt å bli plassert i gruppe</li>
    </ul>

    <!--
    <input data-input="username" type="text" placeholder="asdfasdf"
        onkeydown="
            let regform = find(this);
            switch (event.key){
                case 'Enter':
                    regform.getInputElement('regButton').click();
                    break;
                case 'Escape':
                    regform.getNode().setState(Bruker.STATES.LOGGED_OUT);
                    break;
            }"
    />

    <input data-input="password" type="password" placeholder="Passord" 
        onkeydown="
            let regform = find(this);
            switch (event.key){
                case 'Enter':
                    regform.getInputElement('regButton').click();
                    break;
                case 'Escape':
                    regform.getNode().setState(Bruker.STATES.LOGGED_OUT);
                    break;
            }"
    />
    -->
    <br>
    <input data-input="regButton" type="button" value ="Magisk algoritmeknapp som organiserer 'rest' studenter i grupper"
        onclick='find(this).regnewUser()'
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
    regnewUser() {
        let credentials = {
            brukernavn: this.getInputElement("username").value,
            passord: this.getInputElement("password").value
        };

        program.getApi().regUser(credentials)
            .then(() => {
                return program.getApi().loginUser(credentials);
            })
            .then(data => {
                let bruker = new Bruker(data, program)
                    .defineUIElements()
                    .setState(Bruker.STATES.LOGGED_IN);
                program.velgBruker(bruker);
            })
            .catch(error =>alert(error))
        this.getInputElement("username").value = "";
        this.getInputElement("password").value = "";
    }

}