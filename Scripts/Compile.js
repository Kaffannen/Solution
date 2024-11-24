const args = process.argv.slice(2).reduce((acc, arg, index, array) => {
    if (arg.startsWith('--')) {
        acc[arg.slice(2)] = array[index + 1];
    }
    return acc;
}, {});

Object.entries(args).forEach(([key, value]) => {
    console.log(`key: ${key}, value: ${value}`);
});


const fs = require('fs');
const { get } = require('http');
const path = require('path');

const mainsFolderPath = args.mainsFolderPath
const javascriptRootFolderPath = args.javascriptRootFolderPath
const outputFolderPath = args.outputFolderPath
fs.mkdirSync(outputFolderPath, { recursive: true });

//const mainsFolderPath = path.join(__dirname, '../Javascript/Mains');
//const javascriptRootFolderPath = path.join(__dirname, '../Javascript');
//const outputFolderPath = path.join(__dirname, '../CompiledBundles');
//fs.mkdirSync(outputFolderPath, { recursive: true });

function traverseFolders(folder) {
    let result = [];
    const files = fs.readdirSync(folder);

    files.forEach(file => {
        const filepath = path.join(folder, file);
        const stat = fs.statSync(filepath);

        if (stat.isDirectory()) {
            result = result.concat(traverseFolders(filepath));
        } else {
            let content = fs.readFileSync(filepath, 'utf8');
            const classDeclarationRegex = /class\s[a-zA-Z_$][a-zA-Z0-9_$]*\s/g;
            const matches = getMatchesFromContent(content, classDeclarationRegex)
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
            thisclass.requires = [...new Set(thisclass.requires)];
        });
        return classes;
    }
    function getMatchesFromContent(content, regex) {
        const matches = content.match(regex);
        return [...new Set(matches || [])];
    }
    return discoverDependencies(result);
}

const allFileObjects = traverseFolders(javascriptRootFolderPath);
console.log("allFileObjects:", JSON.stringify(allFileObjects, null, 2));
const mainFileObjects = allFileObjects.filter(fileObject => fileObject.path.includes(mainsFolderPath));
console.log("mainFileObjects:", JSON.stringify(mainFileObjects, null, 2));

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
        console.log("dependencies of", fileObject.provides, ":", JSON.stringify(fileObject.requires, null, 2));
        console.log("dependencies of", fileObject.provides, ":", JSON.stringify(dependencies, null, 2));
        dependencies.forEach(dependency => {
            arr = arr.concat(createLine(dependency, fileObjects));
        });
        return arr;
    }
}

const lines = createLines(mainFileObjects, allFileObjects);
console.log("lines:", JSON.stringify(lines, null, 2));

function pruneAndReverseLines(lines) {
    return lines.map(line => line.reverse()).map(line => [...new Set(line)]);
}

const prunedLines = pruneAndReverseLines(lines);
console.log("prunedLines:", JSON.stringify(prunedLines, null, 2));

function createBundles(prunedLines) {
    const bundles = [];
    prunedLines.forEach(line => {
        const firstFileName = path.basename(line[line.length-1].path, '.js');
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



