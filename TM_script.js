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
    1: Marker all tekst på denne siden (CTRL + A) 
    2: Åpne opp Tampermonkey og trykk på "Create a new script..."
    3: Marker all tekst i det nye scriptet (CTRL + A) og lim inn denne koden 
    4: Trykk på "File" -> "Save" (CTRL + S)
    5: Velg en 'override', som vist under
    6: Naviger til en oppgave i et valgfritt kurs på Canvas 
    
    //TODO: opplyse om dev mode!

    Gå inn på en valgfri oppgave til et valgfritt kurs, og se hva som dukker opp!

*/

/* VELG EN OVERRIDE (kommenter bort de andre alternativene med '//') */

    //const override = "student"
    const override = "underviser"
    //const override = "ingen"





























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
