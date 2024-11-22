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
                provides: matches.length > 0 ? matches[0].split(' ')[1] : "",
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
            if (thisclass.path !== otherclass.path && otherclass.provides) {
                const regex = new RegExp(`\\bnew\\s+${otherclass.provides}\\b|\\b${otherclass.provides}\\.name\\b|\\bextends\\s+${otherclass.provides}\\b`, 'g');
                if (regex.test(thisclassContent)) {
                    thisclass.requires.push(otherclass.provides);
                }
            }
        });
    });
}
discoverDependencies(classes);
fs.writeFileSync(path.join(dist, 'classes.txt'), JSON.stringify(classes, null, 2));

function concatClasses(classes) {
    let str = ""
    workdone = true;
    while (workdone) {
        workdone = false;
        classes.forEach((thisclass) => {
            if (thisclass.requires.length === 0 && thisclass.provides) {
                let thisclassContent = fs.readFileSync(thisclass.path, 'utf8');
                str += thisclassContent + '\n';
                console.log(`Concatenated ${thisclass.provides}`);
                classes = classes.filter(c => c.path !== thisclass.path);
                classes.forEach((otherclass) => {
                    otherclass.requires = otherclass.requires.filter(r => r !== thisclass.provides);
                });
                workdone = true;
            }
        });
    }
    return str;
}
let concatedClasses = concatClasses(classes);
fs.writeFileSync(path.join(dist, 'concat.js'), concatedClasses);

const concatContent = fs.readFileSync(path.join(dist, 'concat.js'), 'utf8');

const mainsFolderPath = path.join(__dirname, '../Javascript/Mains');
const mainFiles = fs.readdirSync(mainsFolderPath);

mainFiles.forEach(file => {
    console.log(`Processing ${file}`);
    const mainFilePath = path.join(mainsFolderPath, file);
    const mainFileContent = fs.readFileSync(mainFilePath, 'utf8');
    const newFileName = file.replace('.js', '_Bundle.js');
    const newFilePath = path.join(dist, newFileName);
    const newFileContent = concatedClasses + '\n' + mainFileContent;
    fs.writeFileSync(newFilePath, newFileContent);
    console.log(`Created bundle file: ${newFileName}`);
});

console.log('Test file created!');
