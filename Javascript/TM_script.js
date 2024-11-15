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

window.addEventListener("load", function () {
    setTimeout(function () {
        // Select all span elements with data-testid="title"
        const spanElements = document.querySelectorAll('span[data-testid="title"]');
        let targetSpan = null;

        // Loop through the span elements and find the one with data-testid="title"
        for (let span of spanElements) {
            targetSpan = span;
            break; // Break after finding the first match
        }

        if (targetSpan) {
            const ezAnchor = document.createElement("div");
            ezAnchor.id = "EzAnchor";
            targetSpan.insertAdjacentElement("afterend", ezAnchor);
        } else {
            console.error('No span with data-testid="title" found!');
        }

        // Load the external script
        const script = document.createElement('script');
        script.src = 'https://kaffannen.github.io/Solution/Javascript/TeamUpBundle.js';
        script.type = 'module';
        document.head.appendChild(script);
    }, 2000);
});
