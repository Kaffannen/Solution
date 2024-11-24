const fs = require('fs');
const { get } = require('http');
const path = require('path');
const { JSDOM } = require('jsdom');

const javascriptRootFolderPath = path.join(__dirname, '../Javascript');

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

try {
    compile(dev, allFileObjects);
    compile(test, allFileObjects);
    compile(prod, allFileObjects);
} catch (error) {
    console.log("error:", error);
}

function compile({ mainsPath, outputPath, outputType }, classObjectList) {
    const mainFileObjects = classObjectList.filter(fileObject => fileObject.path.includes(mainsPath));
    const lines = createLines(mainFileObjects, classObjectList);
    let outputs;
    if (outputType === 'HTML')
        outputs = createHTMLOutputs(lines);
    else if (outputType === 'Javascript')
        outputs = createJavaScriptBundle(lines);
    outputs.forEach(output => {
        fs.mkdirSync(outputPath, { recursive: true });
        fs.writeFileSync(path.join(outputPath, output.outputFileName), output.content);
        console.log(`File ${output.outputFileName} created in ${outputPath} with content:\n${output.content}\n`);
    });
}

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
    const classObjects = discoverDependencies(result);
    classObjects.forEach((classObject, index) => {
        console.log(
            `\t${index}: ${classObject.path}\n\t\tprovides: ${classObject.provides}\n\t\trequires: ${classObject.requires.join(', ')}\n`
        );
    });
    return classObjects;
}

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

function createJavaScriptBundle(prunedLines) {
    const outputs = [];
    prunedLines.forEach(line => {
        const firstFileName = path.basename(line[line.length - 1].path, '.js');
        const outputFileName = `${firstFileName}_bundle.js`;
        const content = line.map(fileObject => fs.readFileSync
            (fileObject.path, 'utf8')).join('\n');
        outputs.push({ outputFileName, content });
    });
    return outputs;
}

function createHTMLOutputs(prunedLines) {
    const outputs = [];
    
    const dom = new JSDOM(`<!DOCTYPE html><html><head></head><body></body></html>`);
    const document = dom.window.document;
    const scriptElement = document.createElement('script');
    scriptElement.src = 'https://example.com/made-up-url.js';
    document.head.appendChild(scriptElement);
    const htmlString = dom.serialize();
    console.log(htmlString);


    prunedLines.forEach(line => {
        const firstFileName = path.basename(line[line.length - 1].path, '.js');
        const outputFileName = `${firstFileName}_bundle.js`;
        const content = line.map(fileObject => fs.readFileSync
            (fileObject.path, 'utf8')).join('\n');
        outputs.push({ outputFileName, htmlString });
    });
    console.log("outputs:", JSON.stringify(outputs, null, 2));
    return outputs;
}



