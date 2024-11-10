class CollapsedState extends UIElement{
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
    <input data-input="utvidButton" type="button" value="Utvid" onclick="find(this).utvid()">
</fieldset>
            `;
        super(htmlString,nexus);
    }
    utvid() {
        this.getNode().setState(Student.STATES.EXPANDED);
    }
}