import { BasicSolution } from "https://kaffannen.github.io/Solution/Javascript/TeamUp/Program/BasicSolution.js";

let program;

document.addEventListener("DOMContentLoaded", async function () {
    program = new BasicSolution(new API())
    await program.defineUIElements();
    program.getFavourite().setState(Student.STATES.EXPANDED);
});