const fs = require('fs');
const path = require('path');

// Define the directory where the test file will be created
const dist = path.join(__dirname, '../dist');

// Ensure the 'dist' directory exists
fs.mkdirSync(dist, { recursive: true });


const folderpath = path.join(__dirname, '../Javascript');

function concatenateFiles(folder) {
    let content = '';
    const files = fs.readdirSync(folder);

    files.forEach(file => {
        const filepath = path.join(folder, file);
        const stat = fs.statSync(filepath);

        if (stat.isDirectory()) {
            content += concatenateFiles(filepath);
        } else {
            content += fs.readFileSync(filepath, 'utf8');
        }
    });

    return content;
}

const concatenatedContent = concatenateFiles(folderpath);

// Create the test file
fs.writeFileSync(path.join(dist, 'concatTest.txt'), concatenatedContent);

console.log('Test file created!');
