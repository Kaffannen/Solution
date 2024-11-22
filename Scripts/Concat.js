const fs = require('fs');
const path = require('path');

// Define the directory where the test file will be created
const distDir = path.join(__dirname, '../dist');

// Ensure the 'dist' directory exists
fs.mkdirSync(distDir, { recursive: true });

// Create the test file
fs.writeFileSync(path.join(distDir, 'test.txt'), 'This is a test file created by the concat.js script.');

console.log('Test file created!');
