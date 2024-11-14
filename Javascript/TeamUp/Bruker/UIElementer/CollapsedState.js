export default class CollapsedState extends UIElement{

    constructor(nexus) {
        let jsonElement = nexus.getData();
        let htmlString
            = `
<fieldset> 
    <legend>Hei ${jsonElement.user.name}</legend>
    <input data-input="utvidButton" type="button" value="Utvid" onclick="find(this).utvid()">
</fieldset>
            `;
        super(htmlString,nexus);
    }
    utvid() {
        this.getNode().setState(Student.STATES.EXPANDED);
    }
}