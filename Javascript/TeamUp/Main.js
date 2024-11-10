/**
 * @type {EasyChat}
 */
let program;

document.addEventListener("DOMContentLoaded", function () {
    // program = new EasyChat(new EasyChatMockAPI())
    program = new BasicSolution(new API())
    program.defineUIElements();
});