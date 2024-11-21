class HeaderbarCollapsed extends UIElement {
    constructor(nexus) {
        let jsonElement = nexus.getData();
        let htmlString
            =`
<p> 
        'Gruppenavn | #medlemmer '
        <input type = "button" data-input="" value = "utvid" onclick = "program.find(this).expand()">
</p>
            `;
        super(htmlString, nexus);
    }
    expand(){
        this.getNode().setState(TeacherGroup.STATES.EXPANDED);
    }
}

