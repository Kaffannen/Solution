import ElementNode from "https://kaffannen.github.io/Solution/Javascript/EzUI/DeveloperClasses/ElementNode.js";
import CollapsedState from "https://kaffannen.github.io/Solution/Javascript/TeamUp/Bruker/UIElementer/CollapsedState.js";
import ExpandedState from "https://kaffannen.github.io/Solution/Javascript/TeamUp/Bruker/UIElementer/ExpandedState.js";
import TeacherUI from "https://kaffannen.github.io/Solution/Javascript/TeamUp/Bruker/UIElementer/TeacherUI.js";

export default class Underviser extends ElementNode {

    defineUIElements() {
        this.addUIElement(new CollapsedState(this))
            .fixTo();
        this.addUIElement(new ExpandedState(this))
            .fixTo();
        this.addUIElement(new TeacherUI(this))
            .fixTo();
        super.defineUIElements();
        return this;
    }

    static STATES = {
        INIT: function(){
        },
        COLLAPSED: function(){
            this.getUIElement(CollapsedState).attach();
            this.getUIElement(ExpandedState).detach();
            this.getUIElement(TeacherUI).detach();
        },
        EXPANDED: function(){
            this.getUIElement(CollapsedState).detach();
            this.getUIElement(ExpandedState).attach();
            this.getUIElement(TeacherUI).attach();
        }
    };
}