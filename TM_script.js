// ==UserScript==
// @name         TeamUp TM import
// @namespace    http://tampermonkey.net/
// @version      2024-11-14
// @description  try to take over the world!
// @author       You
// @match        https://hvl.instructure.com/courses/*/assignments/*
// @icon         https://www.google.com/s2/favicons?sz=64&domain=github.com
// @grant        none
// ==/UserScript==

/*
    VELG EN OVERRIDE (kommenter bort de andre alternativene med '//')

    Gå inn på en valgfri oppgave til et valgfritt kurs, og se hva som dukker opp!

*/

//const override = "student"
const override = "underviser"
//const override = "ingen"


/* */


























window.addEventListener("load", function () {
    setTimeout(function () {
        const spanElements = document.querySelectorAll('span[data-testid="title"]');
        let targetSpan = null;

        for (let span of spanElements) {
            targetSpan = span;
            break;
        }

    if (targetSpan) {
            const ezAnchor = document.createElement("div");
            ezAnchor.id = "EzAnchor";
            targetSpan.insertAdjacentElement("afterend", ezAnchor);
        } else {
            console.error('No span with data-testid="title" found!');
        }

        const script = document.createElement('script');
        let scriptUrl;
        if (override === "underviser")
            scriptUrl = 'https://kaffannen.github.io/Solution/Compiles/TMTeacherProtoMain_Bundle.js';
        else if (override === "student")
            scriptUrl = 'https://kaffannen.github.io/Solution/Compiles/TMStudentProtoMain_Bundle.js';
        else
            scriptUrl = 'https://kaffannen.github.io/Solution/Compiles/TMMain_Bundle.js';

        script.src = scriptUrl;
        script.type = 'module';
        document.head.appendChild(script);
    }, 2000);
});
