let program;

import BasicSolution from "https://kaffannen.github.io/Solution/Javascript/TeamUp/Program/BasicSolution.js";
import API from "https://kaffannen.github.io/Solution/Javascript/TeamUp/Api/API.js";



document.addEventListener("DOMContentLoaded", async function () {
    window.program = new BasicSolution(new API())
    program = window.program;
    await program.defineUIElements();
    program.getFavourite().setState(Student.STATES.EXPANDED);
});