// ==UserScript==
// @name         TeamUp TM import
// @namespace    http://tampermonkey.net/
// @version      2024-11-14
// @description  try to take over the world!
// @author       You
// @match        https://hvl.instructure.com/courses/29406/assignments/80710
// @icon         https://www.google.com/s2/favicons?sz=64&domain=github.com
// @grant        none
// ==/UserScript==

window.addEventListener("load", function () {
    setTimeout(function () {
        const spanElements = document.querySelectorAll('span[data-testid="title"]');
        let targetSpan = null;
        for (let span of spanElements) {
            if (span.textContent.trim() === "Samarbeidsavtale") {
                targetSpan = span;
                break;
            }
        }

        if (targetSpan) {
            const ezAnchor = document.createElement("div");
            ezAnchor.id = "EzAnchor";
            targetSpan.insertAdjacentElement("afterend", ezAnchor);
        } else {
            console.error('No span with the content "Samarbeidsavtale" and data-testid="title" found!');
        }
        const script = document.createElement('script');
        script.src = 'https://kaffannen.github.io/Solution/Javascript/TeamUpBundle.js';
        script.type = 'module';
        document.head.appendChild(script);
    }, 2000);
});