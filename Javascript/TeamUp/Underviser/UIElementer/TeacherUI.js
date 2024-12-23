class TeacherUI extends UIElement{
    /**
     *
     * @param {ElementNode} nexus
     */
    constructor(nexus) {
        let htmlString
            =`
<fieldset class="fieldset-reset">
    <p>#Antall studenter i kurset</p>
    <p>#Antall studenter som er i grupper som oppfyller min/max krav</p>
    <p>#Antall studenter som IKKE er grupper som oppfyller min/max krav</p>

    <div data-anchor=${GroupControllerUIE.name}></div>
    <p>stor gruppe med studenter 'i enmannsgruppe' (rent logisk)</p>
    <input data-input="regButton" type="button" value ="Magisk algoritmeknapp som organiserer 'rest' studenter i grupper"
        onclick='find(this).doAction()'
        onkeydown="if (event.key === 'Escape') find(this).getNode().doAction(Bruker.STATES.LOGGED_OUT)"
    ">
    <br>
</fieldset>
            `;
        super(htmlString, nexus);
    }

    doAction() {
        fetch('https://hvl.instructure.com/api/v1/users/self/')
            .then(response => response.json())
            .then(data => {
                console.log(data);
            })
            .catch(error => alert(error));
    }
}