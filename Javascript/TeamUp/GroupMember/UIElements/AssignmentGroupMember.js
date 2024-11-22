class AssignmentGroupMember extends UIElement {

    constructor(nexus) {
        let jsonElement = nexus.getData();
        let htmlString
            =`
<fieldset class="fieldset-reset">
    <p>${jsonElement.name}</p>
</fieldset>
            `;
        super(htmlString, nexus);
    }
}