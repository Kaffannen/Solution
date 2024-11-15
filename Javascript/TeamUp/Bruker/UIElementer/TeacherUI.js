//import UIElement from "https://kaffannen.github.io/Solution/Javascript/EzUI/DeveloperClasses/UIElement.js";
//export default class TeacherUI extends UIElement{
class TeacherUI extends UIElement{
    /**
     *
     * @param {ElementNode} nexus
     */
    constructor(nexus) {
        let htmlString
            =`
<fieldset class="IkkeInnlogget sentrerHorisontalt">
    <h3>Badass Teacher UI</h3>
    <p>Kjøre på med lister over grupper og hvem som er i dem - drag & drop funksjonalitet?</p>
    <ul>
        <li>Gruppe 1, 4/[8-12] studenter </li>
        <li>Gruppe 2, 4/[8-12] studenter </li>
        <li>Gruppe 3, 4/[8-12] studenter </li>
        <li>Gruppe 3, 4/[8-12] studenter </li>
        <li>Gruppe 3, 4/[8-12] studenter </li>
        <li>Gruppe 1, 4/[8-12] studenter </li>
        <li>Gruppe 2, 4/[8-12] studenter </li>
        <li>Gruppe 3, 4/[8-12] studenter </li>
        <li>Gruppe 3, 4/[8-12] studenter </li>
        <li>Gruppe 3, 4/[8-12] studenter </li>
        <li>Gruppe 1, 4/[8-12] studenter </li>
        <li>Gruppe 2, 4/[8-12] studenter </li>
        <li>Gruppe 3, 4/[8-12] studenter </li>
        <li>Gruppe 3, 4/[8-12] studenter </li>
        <li>Gruppe 3, 4/[8-12] studenter </li>
        <li>...</li>
        <li>25 studenter ikke i gruppe. 10 av dem har bedt å bli plassert i gruppe</li>
    </ul>
    <br>
    <input data-input="regButton" type="button" value ="Magisk algoritmeknapp som organiserer 'rest' studenter i grupper"
        onclick='find(this).doAction()'
        onkeydown="if (event.key === 'Escape') find(this).getNode().setState(Bruker.STATES.LOGGED_OUT)"
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

    startUpShit(){
        const promises = [];
        promises.push(fetch('https://api.example.com/data1').then(res => res.json()));
        promises.push(fetch('https://api.example.com/data2').then(res => res.json()));
        /**
        try {
            return await Promise.all(promises);
        } catch (error) {
            console.error("An error occurred:", error);
            throw error;
        }
        */
    }

}