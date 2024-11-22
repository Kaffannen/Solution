const fs = require('fs');
const { get } = require('http');
const path = require('path');

// Define the directory where the test file will be created
const dist = path.join(__dirname, '../dist');

// Ensure the 'dist' directory exists
fs.mkdirSync(dist, { recursive: true });


const folderpath = path.join(__dirname, '../Javascript');



function getMatchesFromFile(filepath, regex) {
    const content = fs.readFileSync(filepath, 'utf8');
    const matches = content.match(regex);
    return matches || [];
}

function traverseAndBuildJson(folder) {
    let result = [];
    const files = fs.readdirSync(folder);

    files.forEach(file => {
        const filepath = path.join(folder, file);
        const stat = fs.statSync(filepath);

        if (stat.isDirectory()) {
            result = result.concat(traverseAndBuildJson(filepath));
        } else {
            const matches = getMatchesFromFile(filepath, /class\s[a-zA-Z_$][a-zA-Z0-9_$]*\s/g)
                .map(match => match.trim());
            if (matches.length !== 1) {
                console.log(`File ${filepath} contains ${matches.length} class declarations. Expected exactly one.`);
            }
            result.push({
                path: filepath,
                provides: matches.length>0 ? matches[0].split(' ')[1] : "",
                requires: []
            });
        }
    });

    return result;
}
let classes = traverseAndBuildJson(folderpath);

function discoverDependencies(classes) {
    classes.forEach((thisclass) => {
        let thisclassContent = fs.readFileSync(thisclass.path, 'utf8');
        classes.forEach((otherclass) => {
            if (thisclass.path !== otherclass.path && otherclass.provides && thisclassContent.includes(otherclass.provides)) {
                thisclass.requires.push(otherclass.provides);
            }
            else
                console.log(`\n ${otherclass.provides} not in:  ${thisclassContent} \n`);
        }); 
    });
}
discoverDependencies(classes);


fs.writeFileSync(path.join(dist, 'classes.txt'), JSON.stringify(classes, null, 2));


console.log('Test file created!');
