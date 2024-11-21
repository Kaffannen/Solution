class HeaderbarExpanded extends UIElement {
    constructor(nexus) {
        let jsonElement = nexus.getData();
        let htmlString
            =`
<input type = "button" data-input="" value = "kollaps" onclick = "program.find(this).collapse()">
            `;
        super(htmlString, nexus);
    }
    collapse(){
        this.getNode().setState(TeacherGroup.STATES.COLLAPSED);
    }
}

