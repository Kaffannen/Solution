import UIElement from "https://kaffannen.github.io/Solution/Javascript/EzUI/DeveloperClasses/UIElement.js";
import Student from "https://kaffannen.github.io/Solution/Javascript/TeamUp/Bruker/Student.js";
export default class CollapsedState extends UIElement{

    constructor(nexus) {
        let jsonElement = nexus.getData();
        let htmlString
            = `
<fieldset> 
    <legend>Hei ${jsonElement.user.name}</legend>
    <input data-input="utvidButton" type="button" value="Utvid" onclick="program.find(this).utvid()">
</fieldset>
            `;
        super(htmlString,nexus);
    }
    utvid() {
        this.getNode().setState(Student.STATES.EXPANDED);
    }
}