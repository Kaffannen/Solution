class ExpandedState extends UIElement
{
    constructor(nexus) {
        let jsonElement = nexus.getData();
        let htmlString
            = `
<fieldset>
    <legend>Hei ${jsonElement.user.name}</legend>
    <input data-input="utvidButton" type="button" value="Kollaps" onclick="program.find(this).minimer()">
</fieldset>
            `;
        super(htmlString,nexus);
    }
    minimer() {
        this.getNode().setState(Student.STATES.COLLAPSED);
    }
}