const fs = require('fs');
const { get } = require('http');
const path = require('path');

const mainsFolderPath = path.join(__dirname, '../Javascript/Mains');
const javascriptRootFolderPath = path.join(__dirname, '../Javascript');
const outputFolderPath = path.join(__dirname, '../dist');

function traverseFolders(folder) {
    let result = [];
    const files = fs.readdirSync(folder);

    files.forEach(file => {
        const filepath = path.join(folder, file);
        const stat = fs.statSync(filepath);

        if (stat.isDirectory()) {
            result = result.concat(traverseFolders(filepath));
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
        return classes;
    }
    return discoverDependencies(result);
}

const allFileObjects = traverseFolders(javascriptRootFolderPath);
const mainFileObjects = traverseFolders(mainsFolderPath);

function createLines(mainFileObjects, allFileObjects) {
    let lines = [];
    mainFileObjects.forEach(mainFileObject => {
        lines.push(createLine(mainFileObject, allFileObjects));
    });
    return lines;

    function createLine(fileObject, fileObjects) {
        let arr = [];
        arr.push(fileObject);
        let dependencies = fileObjects.filter(f => fileObject.requires.includes(f.provides));
        dependencies.forEach(dependency => {
            arr = arr.concat(createLine(dependency, fileObjects));
        });
        return arr;
    }
}

const lines = createLines(mainFileObjects, allFileObjects);

function pruneLines(lines) {
    const reversed = lines.map(line => line.reverse());
    function removeDuplicates(arr) {
        return [...new Set(arr)];
    }
    const prunedLines = lines.map(line => removeDuplicates(line));
    return prunedLines.map(line => line.reverse());
}

const prunedLines = pruneLines(lines);

function createBundles(prunedLines) {
    const bundles = [];
    prunedLines.forEach(line => {
        const firstFileName = path.basename(line[0].path, '.js');
        const bundleFileName = `${firstFileName}_bundle.js`;
        const bundle = line.map(fileObject => fs.readFileSync
            (fileObject.path, 'utf8')).join('\n');
        bundles.push({ bundleFileName, bundle });
    });
    return bundles;
}

const bundles = createBundles(prunedLines);

bundles.forEach(bundle => {
    fs.writeFileSync(path.join(outputFolderPath, bundle.bundleFileName), bundle.bundle);
});



