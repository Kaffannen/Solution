const fs = require('fs');
const { get } = require('http');
const path = require('path');
const { JSDOM } = require('jsdom');
const { create } = require('domain');

const javascriptRootFolderPath = path.join(__dirname, '../Javascript');

/**/
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
const allFileObjects = createFileObjects(javascriptRootFolderPath);
console.log(`${allFileObjects.size} files: `, JSON.stringify(allFileObjects, null, 2));
console.log("***********************\n\n");


try {
    compile(dev, allFileObjects);
    compile(test, allFileObjects);
    compile(prod, allFileObjects);
} catch (error) {
    console.log("error:", error);
}

function createFileObjects(javascriptRootFolderPath) {
    let filelist = flattenFolder(javascriptRootFolderPath)
        .map(file => (
            {
                path: file,
                provides: getClassDefinitionsFromFile(fs.readFileSync(file, 'utf8')),
                requires: []
            }))
    filelist.forEach(file => discoverDependencies(file, filelist));

    function discoverDependencies(thisFile, allFiles) {
        let content = fs.readFileSync(thisFile.path, 'utf8');
        for (let otherFile of allFiles) {
            if (thisFile.path === otherFile.path) continue;
            otherFile.provides.forEach(classDef => {
                const regex = new RegExp(`\\bnew\\s+${classDef}\\b|\\bextends\\s+${classDef}\\b`, 'g');
                if (regex.test(content)) {
                    thisFile.requires.push(classDef);
                }
            });
        }
    }
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
    return filelist;
}





function compile({ mainsPath, outputPath, outputType }, classObjectList) {
    const mainFileObjects = classObjectList.filter(fileObject => fileObject.path.includes(mainsPath));
    const lines = createLines(mainFileObjects, classObjectList);
    let outputs;
    if (outputType === 'HTML')
        outputs = createHTMLOutputs(lines);
    else if (outputType === 'Javascript')
        outputs = createJavaScriptOutputs(lines);
    outputs.forEach(output => {
        fs.mkdirSync(outputPath, { recursive: true });
        fs.writeFileSync(path.join(outputPath, output.outputFileName), output.content);
    });
}

function createLines(mainFileObjects, allFileObjects) {
    let lines = [];
    mainFileObjects.forEach(mainFileObject => {
        let line = createLine(mainFileObject, allFileObjects);
        lines.push(line);
    });
    return lines.map(line => line.reverse()).map(line => [...new Set(line)]);

    function createLine(fileObject, fileObjects) {
        fileObjects = createFileObjects(javascriptRootFolderPath)
        //console.log(`before createFileSet() fileObjects size: ${fileObjects.length}`);
        let fileSet = createFileSet(new Set().add(fileObject), fileObjects);
        //console.log(`fileset belonging to ${fileObject.path}: has ${fileSet.size} files.`);
        //console.log("fileset entries:", JSON.stringify(Array.from(fileSet), null, 2));
        let arr = sortTopologically(fileSet);
        //console.log(`line belonging to ${fileObject.path}: has ${arr.length} files.`);
        //console.log("line entries:", JSON.stringify(arr, null, 2));
        return arr;

        function createFileSet(fileset, fileObjects) {
            console.log(`Creating fileset for: ${path.basename(Array.from(fileset)[0].path)} Fileset initial size is ${fileset.size}`);
            let workdone = true;
            while (workdone) {
                workdone = false;
                fileset.forEach(file => {
                    console.log(`Checking file ${path.basename(file.path)} for requirements.`);
                    file.requires.forEach(req => {
                        console.log(`File ${path.basename(file.path)} requires ${req}.`);
                        let requiredFile = fileObjects.find(f => f.provides.includes(req));
                        if (requiredFile) {
                            console.log(`file ${path.basename(requiredFile.path)} provides it.`);
                            if (!fileset.has(requiredFile)) {
                                console.log(`File is ${path.basename(requiredFile.path)}, adding to set.`);
                                fileset.add(requiredFile);
                                workdone = true;
                            }
                            else{
                                console.log(`File ${path.basename(requiredFile.path)} already in fileset, skipping.`);
                            }
                        }
                        else
                            console.log(`File ${path.basename(file.path)} requires ${req} but no file provides it.`);
                    });
                });
            }
            return fileset;
        }

        function sortTopologically(fileset) {
            let fileArray = Array.from(fileset);
            let workdone = true;
            let arr = []
            while (workdone) {
                workdone = false;
                let noDependencies = fileArray.filter(file => file.requires.length === 0);
                if (noDependencies.length === 0) break;
                file = noDependencies[0];
                fileArray = fileArray.filter(f => f !== file);
                arr.push(file);
                file.provides.forEach(req => {
                    fileArray.forEach(f => {
                        f.requires = f.requires.filter(r => r !== req);
                    });
                });
                workdone = true;
            }
            console.log(`${arr.length} files sorted topologically:`, JSON.stringify(arr, null, 2));
            console.log(`${fileArray.length} files remaining:`, JSON.stringify(fileArray, null, 2));
            
            return arr;
        }
        return arr;
    }
}

function createJavaScriptOutputs(prunedLines) {
    const outputs = [];
    prunedLines.forEach(line => {
        line = line.reverse();
        const firstFileName = path.basename(line[line.length - 1].path, '.js');
        const outputFileName = `${firstFileName}_Bundle.js`;
        const content = line.map(fileObject => fs.readFileSync
            (fileObject.path, 'utf8')).join('\n');
        outputs.push({ outputFileName, content });
    });
    return outputs;
}

function createHTMLOutputs(prunedLines) {
    const outputs = [];

    prunedLines.forEach(line => {
        line = line.reverse();
        //console.log("line:", JSON.stringify(line, null, 2));
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
        //console.log(prettyHtmlString);

        const content = line.map(fileObject => fs.readFileSync
            (fileObject.path, 'utf8')).join('\n');
        outputs.push({ outputFileName, content: prettyHtmlString });
    });
    return outputs;
}