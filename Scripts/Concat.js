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

function concatenateFiles(folder) {
    let content = '';
    const files = fs.readdirSync(folder);

    files.forEach(file => {
        const filepath = path.join(folder, file);
        const stat = fs.statSync(filepath);

        if (stat.isDirectory()) {
            content += concatenateFiles(filepath);
        } else {
            content += getMatchesFromFile(filepath, /class\s[a-zA-Z_$][a-zA-Z0-9_$]*\s/g)
                .map(match => match.trim())  // Optional: Trim any excess whitespace
                .join(', ') + '  : ' + filepath + '\n';

        }
    });
    return content;
}

const concatenatedContent = concatenateFiles(folderpath);

// Create the test file
fs.writeFileSync(path.join(dist, 'concatTest.txt'), concatenatedContent);

console.log('Test file created!');
