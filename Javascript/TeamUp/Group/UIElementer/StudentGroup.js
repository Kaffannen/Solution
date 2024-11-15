//import UIElement from "https://kaffannen.github.io/Solution/Javascript/EzUI/DeveloperClasses/UIElement.js";
//import AssignmentGroupMember from "https://kaffannen.github.io/Solution/Javascript/TeamUp/GroupMember/UIElements/AssignmentGroupMember.js";
//export default class StudentGroup extends UIElement {
class StudentGroup extends UIElement {
    constructor(nexus) {

        let htmlString
            =`
<fieldset class="IkkeInnlogget sentrerHorisontalt">
    <h3>Badass StudentGroup</h3>
    <div data-anchor=${AssignmentGroupMember.name}></div>
    <br>
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
}