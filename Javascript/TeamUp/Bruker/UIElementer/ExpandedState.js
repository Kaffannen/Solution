class ExpandedState extends UIElement
{
    /**
     *
     * @param {ElementNode} nexus
     */
    constructor(nexus) {
        let jsonElement = nexus.getData();
        let htmlString
            = `
<fieldset>
    <legend>Hei ${jsonElement.username}</legend>
    <input data-input="utvidButton" type="button" value="Kollaps" onclick="find(this).minimer()">
</fieldset>
            `;
        super(htmlString,nexus);
    }
    minimer() {
        this.getNode().setState(Student.STATES.COLLAPSED);
    }
}