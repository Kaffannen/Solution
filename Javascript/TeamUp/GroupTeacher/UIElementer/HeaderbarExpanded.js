class HeaderbarExpanded extends UIElement {
    constructor(nexus) {
        let jsonElement = nexus.getData();
        let htmlString
            =`
<p> 
        'Gruppenavn | #medlemmer '
        <input type = "button" data-input="" value = "kollaps" onclick = "program.find(this).collapse()">
</p>
            `;
        super(htmlString, nexus);
    }
    collapse(){
        this.getNode().setState(TeacherGroup.STATES.COLLAPSED);
    }
}

