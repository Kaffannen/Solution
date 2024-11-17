class ExpandedState extends UIElement
{
    constructor(nexus) {
        let jsonElement = nexus.getData();
        let htmlString
            = `
<div>
<br>
<input data-input="minimerButton" type="button" value="TeamUp - Trykk for å lukke" onclick="program.find(this).minimer()">
</div>
            `;
        super(htmlString,nexus);
    }
    minimer() {
        this.getNode().setState(Student.STATES.COLLAPSED);
    }
}