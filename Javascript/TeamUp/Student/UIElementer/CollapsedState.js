class CollapsedState extends UIElement{

    constructor(nexus) {
        let jsonElement = nexus.getData();
        let htmlString
            = `

<div>
<br>
<input data-input="utvidButton" type="button" value="TeamUp - Trykk for å utvide" onclick="program.find(this).utvid()">
</div>
            `;
        super(htmlString,nexus);
    }
    utvid() {
        this.getNode().setState(Student.STATES.EXPANDED);
    }
}