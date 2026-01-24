import watch from 'node-watch';
import { copyFile, existsSync, readFile, unlink } from 'node:fs';
import engine from 'php-parser';

const srcFolder = process.argv[2] || 'src';
// TODO pass this const as argument.
const destinationFolder = process.argv[3] || '/var/www/marius.click/html/contest/api';
const phpParser = new engine({
    // some options :
    parser: {
        extractDoc: true,
        php8: true,
    },
    ast: {
        withPositions: true,
    },
});

console.log('');
console.log('\x1b[34mWatching %s\x1b[0m. Go to code ;)', srcFolder);
console.log('');
console.log('');

watch(srcFolder, { recursive: true }, function(evt, sourceFileName) {
    console.log('');
    console.log('[%s] \x1b[32m%s\x1b[0m.', evt, sourceFileName);

    const destinationFileName = sourceFileName.replace(srcFolder, destinationFolder);

    switch (evt) {
        case 'remove':
            if (!existsSync(destinationFileName)) {
                console.log('[Nothing to do]\x1b[32m%s\x1b[0m doesn’t exists.', destinationFileName);
                return;
            }

            unlink(destinationFileName, (err) => {
                if (err) {
                    console.log("\x1b[31mUnable to delete %s\x1b[0m: ", destinationFileName, String(err));
                }
            }); 
            break;
        default:
            // The destination file will be created or overwritten by default.
            copyFile(sourceFileName, destinationFileName, (err) => {
                if (err) {
                    console.log("\x1b[31mUnable to copy %s\x1b[0m: ", destinationFileName, String(err));
                    return;
                }

                console.log('[copy] \x1b[32m%s\x1b[0m.', destinationFileName);
            });

            readFile(sourceFileName, (error, data) => {
                if (error) {
                    console.log("\x1b[31mUnable to load %s\x1b[0m: ", sourceFileName, String(error));
                    return;
                }

                try {
                    phpParser.parseCode(data);
                } catch (e) {
                    console.log("\x1b[31mError %s\x1b[0m: %s, column ", sourceFileName, String(e), e.columnNumber);
                }
            });

            break;
    }
});

setInterval(() => {
    console.log("still connected…");
}, 1000 * 60 * 2);

