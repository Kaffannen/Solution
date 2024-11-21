class HeaderbarExpanded extends UIElement {
    constructor(nexus) {
        let jsonElement = nexus.getData();
        let htmlString
            =`
<p> 
        'Gruppenavn | #medlemmer '
        <input type = "button" data-input="" value = "kollaps" onclick = "program.find(this).getNode().setState(TeacherGroup.STATES.COLLAPSED)">
</p>
            `;
        super(htmlString, nexus);
    }
}

