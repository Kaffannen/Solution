let program;
import BasicSolution from "https://kaffannen.github.io/Solution/Javascript/TeamUp/Program/BasicSolution.js";
import API from "https://kaffannen.github.io/Solution/Javascript/TeamUp/Api/API.js";
import Student from "https://kaffannen.github.io/Solution/Javascript/TeamUp/Bruker/Student.js"
/**
import StateController from "https://kaffannen.github.io/Solution/Javascript/EzUI/InternalSupers/StateController.js"
import ElementController from  "https://kaffannen.github.io/Solution/Javascript/EzUI/InternalSupers/ElementController.js"
import UIElement from "https://kaffannen.github.io/Solution/Javascript/EzUI/DeveloperClasses/UIElement.js"
import ElementNode from "https://kaffannen.github.io/Solution/Javascript/EzUI/DeveloperClasses/ElementNode.js"
import EzUI from "https://kaffannen.github.io/Solution/Javascript/EzUI/DeveloperClasses/EzUI.js"
import CanvasAPIMock from "https://kaffannen.github.io/Solution/Javascript/TeamUp/Api/CanvasAPIMock.js"

import Underviser from "https://kaffannen.github.io/Solution/Javascript/TeamUp/Bruker/Underviser.js"
import TeacherUI from "https://kaffannen.github.io/Solution/Javascript/TeamUp/Bruker/UIElementer/TeacherUI.js"
import StudentUI from "https://kaffannen.github.io/Solution/Javascript/TeamUp/Bruker/UIElementer/StudentUI.js"
import CollapsedState from "https://kaffannen.github.io/Solution/Javascript/TeamUp/Bruker/UIElementer/CollapsedState.js"
import ExpandedState from "https://kaffannen.github.io/Solution/Javascript/TeamUp/Bruker/UIElementer/ExpandedState.js"
import StudentGroup from "https://kaffannen.github.io/Solution/Javascript/TeamUp/Group/UIElementer/StudentGroup.js"
import Group from "https://kaffannen.github.io/Solution/Javascript/TeamUp/Group/Group.js"
import GroupMember from "https://kaffannen.github.io/Solution/Javascript/TeamUp/GroupMember/GroupMember.js"
import AssignmentGroupMember from  "https://kaffannen.github.io/Solution/Javascript/TeamUp/GroupMember/UIElements/AssignmentGroupMember.js"
**/
//<link rel="stylesheet" href="css/Style.css">

document.addEventListener("DOMContentLoaded", async function () {
    window.program = new BasicSolution(new API())
    program = window.program;
    await program.defineUIElements();
    program.getFavourite().setState(Student.STATES.EXPANDED);
});