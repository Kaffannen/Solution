class HeaderbarCollapsed extends UIElement {
    constructor(nexus) {
        let jsonElement = nexus.getData();
        let htmlString
            =`
<input type = "button" data-input="" value = "utvid" onclick = "program.find(this).expand()">
            `;
        super(htmlString, nexus);
    }
    expand(){
        this.getNode().setState(TeacherGroup.STATES.EXPANDED);
    }
}

