let program;

document.addEventListener("DOMContentLoaded", async function () {
    program = new BasicSolution(new API())
    await program.defineUIElements();
    program.getFavourite().setState(Student.STATES.EXPANDED);
});