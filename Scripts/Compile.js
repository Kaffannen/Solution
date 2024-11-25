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

function createFileObjects(javascriptRootFolderPath) {
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
                const regex = new RegExp(`\\bnew\\s+${classDef}\\b|\\b${classDef}\\.\\b|\\bextends\\s+${classDef}\\b`, 'g');
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
const allFileObjects = createFileObjects(javascriptRootFolderPath);
//console.log("allFileObjects:", JSON.stringify(allFileObjects, null, 2));

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
        let fileSet = createFileSet(new Set().add(fileObject), fileObjects);
        console.log(`fileset belonging to ${fileObject.path}: has ${fileSet.size} files.`);
        //console.log("fileset entries:", JSON.stringify(Array.from(fileSet), null, 2));
        let arr = sortTopologically(fileSet);
        console.log(`line belonging to ${fileObject.path}: has ${arr.length} files.`);
        console.log("line entries:", JSON.stringify(arr, null, 2));
        return arr;

        function createFileSet(fileset, fileObjects) {
            let workdone = true;
            while (workdone) {
                workdone = false;
                fileset.forEach(file => {
                    file.requires.forEach(req => {
                        let requiredFile = fileObjects.find(f => f.provides.includes(req));
                        if (requiredFile) {
                            if (!fileset.has(requiredFile)) {
                                fileset.add(requiredFile);
                                workdone = true;
                            }
                        }
                        else
                            console.log(`File ${file.path} requires ${req} but no file provides it.`);
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
                fileArray = fileArray.filter(file => !noDependencies.includes(file));
                arr = arr.concat(noDependencies);
                workdone = noDependencies.length > 0;
            }
            return arr;
        }
        return arr;
    }
}

    /*
        function createLine(fileObject, fileObjects) {
            let arr = [];
            arr.push(fileObject);
            fileObjects.forEach(otherFile => {
                if (fileObject.path === otherFile.path) return;
                const intersection = fileObject.requires.filter(req => otherFile.provides.includes(req));
                if (intersection.length > 0) {
                    fileObject.requires = fileObject.requires.filter(req => !intersection.includes(req));
                    arr = arr.concat(createLine(otherFile, fileObjects));
                }
            });
            return arr;
        }
    */

    /*
    function createLine(fileObject, fileObjects) {
        let arr = [];
        arr.push(fileObject);
        let dependencies = fileObjects.filter(f => fileObject.requires.includes(f.provides));
        dependencies.forEach(dependency => {
            arr = arr.concat(createLine(dependency, fileObjects));
        });
        return arr;
    }
    */


function createJavaScriptOutputs(prunedLines) {
    const outputs = [];
    prunedLines.forEach(line => {
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
    return outputs;
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