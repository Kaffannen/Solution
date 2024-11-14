import UIElement from "https://kaffannen.github.io/Solution/Javascript/EzUI/DeveloperClasses/UIElement.js";

export default class AssignmentGroupMember extends UIElement {

    constructor(nexus) {
        let jsonElement = nexus.getData();
        let htmlString
            =`
<fieldset class="IkkeInnlogget sentrerHorisontalt">
    <p>${jsonElement.name}</p>
</fieldset>
            `;
        super(htmlString, nexus);
    }
}