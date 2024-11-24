const fs = require('fs');
const { get } = require('http');
const path = require('path');
const { JSDOM } = require('jsdom');

const javascriptRootFolderPath = path.join(__dirname, '../Javascript');
/*
const dev = {
    mainsPath: path.join(__dirname, '../Javascript/Mains/DevMains'),
    outputPath: path.join(__dirname, '../Compiled/Dev'),
    outputType: 'HTML'
};
const test = {
    mainsPath: path.join(__dirname, '../Javascript/Mains/TestMains'),
    outputPath: path.join(__dirname, '../Compiled/Test'),
    outputType: 'Javascript'
};
const prod = {
    mainsPath: path.join(__dirname, '../Javascript/Mains/ProdMains'),
    outputPath: path.join(__dirname, '../Compiled/Prod'),
    outputType: 'Javascript'
};

console.log(`\nAnalyzing files of folder: ${javascriptRootFolderPath}\n`);
const allFileObjects = traverseFolders(javascriptRootFolderPath);
*/

let filelist = flattenFolder(javascriptRootFolderPath)
    .map(file => (
        { 
            path: file,
            //content: fs.readFileSync(file, 'utf8'),
            provides: getClassDefinitionsFromFile(fs.readFileSync(file, 'utf8')),
            requires: []
         }))
filelist.forEach(file => discoverDependencies(file, filelist));


function discoverDependencies(thisFile, allFiles) {
    let content = fs.readFileSync(thisFile.path, 'utf8');
    for (let otherFile of allFiles) {
        if (thisFile.path === otherFile.path) continue;
        otherFile.provides.forEach(classDef => {
            const regex = new RegExp(`\\bnew\\s+${classDef}\\b|\\b${classDef}\\.name\\b|\\bextends\\s+${classDef}\\b`, 'g');
            if (regex.test(content)) {
                thisFile.requires.push(otherFile.provides);
            }
        });
    }
}
    
console.log("filelist:", JSON.stringify(filelist, null, 2));

function flattenFolder(folder) {
    let result = [];
    const files = fs.readdirSync(folder);

    files.forEach(file => {
        const filepath = path.join(folder, file);
        const stat = fs.statSync(filepath);

        if (stat.isDirectory()) {
            result = result.concat(flattenFolder(filepath));
        } else {
            result.push(filepath);
        }
    });

    return result;
}

function getClassDefinitionsFromFile(content) {
    regex = /class\s+([a-zA-Z_$][a-zA-Z0-9_$]*)/g;
    let allMatches = [];
    let match;
    while ((match = regex.exec(content)) !== null) {
        allMatches.push(match[0]);
    }
    return Array.from(new Set(allMatches)).map(match => match.split(' ')[1]);
}

/*
try {
    compile(dev, allFileObjects);
    compile(test, allFileObjects);
    compile(prod, allFileObjects);
} catch (error) {
    console.log("error:", error);
}
*/

function compile({ mainsPath, outputPath, outputType }, classObjectList) {
    const mainFileObjects = classObjectList.filter(fileObject => fileObject.path.includes(mainsPath));
    console.log("mainFileObjects:", JSON.stringify(mainFileObjects, null, 2));
    console.log("classObjectList:", JSON.stringify(classObjectList, null, 2));
    const lines = createLines(mainFileObjects, classObjectList);
    console.log("lines:", JSON.stringify(lines, null, 2));
    let outputs;
    if (outputType === 'HTML')
        outputs = createHTMLOutputs(lines);
    else if (outputType === 'Javascript')
        outputs = createJavaScriptOutputs(lines);
    outputs.forEach(output => {
        fs.mkdirSync(outputPath, { recursive: true });
        fs.writeFileSync(path.join(outputPath, output.outputFileName), output.content);
        console.log(`File ${output.outputFileName} created in ${outputPath} with content:\n${output.content}\n`);
    });
}

/*
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
            const classDeclarationRegex = /class\s+([a-zA-Z_$][a-zA-Z0-9_$]*)/g;
            const matches = getMatchesFromContent(content, classDeclarationRegex)
                .map(match => match.trim());
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
        const matches = [];
        let match;
        while ((match = regex.exec(content)) !== null) {
            matches.push(match[0]);
        }
        return [...new Set(matches)];
    }
    const classObjects = discoverDependencies(result);
    classObjects.forEach((classObject, index) => {
        console.log(
            `\t${index}: ${classObject.path}\n\t\tprovides: ${classObject.provides}\n\t\trequires: ${classObject.requires.join(', ')}\n`
        );
    });
    return classObjects;
}*/

function createLines(mainFileObjects, allFileObjects) {
    let lines = [];
    mainFileObjects.forEach(mainFileObject => {
        lines.push(createLine(mainFileObject, allFileObjects));
    });
    return lines.map(line => line.reverse()).map(line => [...new Set(line)]);

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

function createJavaScriptOutputs(prunedLines) {
    console.log("prunedLines:", JSON.stringify(prunedLines, null, 2));
    const outputs = [];
    prunedLines.forEach(line => {
        const firstFileName = path.basename(line[line.length - 1].path, '.js');
        const outputFileName = `${firstFileName}_Bundle.js`;
        const content = line.map(fileObject => fs.readFileSync
            (fileObject.path, 'utf8')).join('\n');
        outputs.push({ outputFileName, content });
    });
    console.log("outputs:", JSON.stringify(outputs, null, 2));
    return outputs;
}

function createHTMLOutputs(prunedLines) {
    console.log("prunedLines:", JSON.stringify(prunedLines, null, 2));
    const outputs = [];

    prunedLines.forEach(line => {
        const firstFileName = path.basename(line[line.length - 1].path, '.js');
        const outputFileName = `${firstFileName}.html`;

        const dom = new JSDOM(`<!DOCTYPE html><html><head></head><body></body></html>`);
        const document = dom.window.document;
        const titleElement = document.createElement('title');
        titleElement.textContent = firstFileName;
        const metaElement = document.createElement('meta');
        metaElement.setAttribute('charset', 'utf-8');
        document.head.appendChild(metaElement);
        document.head.appendChild(titleElement);
        document.body.id = 'EzAnchor';

        line.forEach(fileObject => {
            const scriptElement = document.createElement('script');
            const relativePath = fileObject.path.replace('/home/runner/work/Solution/Solution/Javascript', '../../Javascript');
            scriptElement.src = relativePath;
            document.head.appendChild(scriptElement);
        });

        const htmlString = dom.serialize();
        const prettyHtmlString = htmlString.replace(/></g, '>\n<');

        const content = line.map(fileObject => fs.readFileSync
            (fileObject.path, 'utf8')).join('\n');
        outputs.push({ outputFileName, content: prettyHtmlString });
    });
    console.log("outputs:", JSON.stringify(outputs, null, 2));
    return outputs;
}



