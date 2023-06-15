import { fileURLToPath } from "url";
const __dirname = fileURLToPath( new URL( `.`, import.meta.url ) );

import archiver from "archiver";
import { argv } from "process";
import { createWriteStream } from "fs";
import { glob } from "glob";
import { readFile, stat } from "fs/promises";
import { relative } from "path";

const findFiles = pattern => glob( pattern, {
    "cwd": __dirname,
    "root": `.`,
} );

const files = new Set( await findFiles( `/**` ) );

await Promise.all( ( await readFile( `${ __dirname }/.distignore`, `utf8` ) ).split( `\n` ).map( async line => {
    const pattern = line.trim();
    if ( !pattern ) {
        return;
    }
    await Promise.all( ( await findFiles( pattern ) ).map( async filename => {
        files.delete( filename );
        if ( ( await stat( filename ) ).isDirectory() ) {
            ( await findFiles( pattern.replace( /\/?$/, `/**` ) ) ).forEach( subFilename => {
                files.delete( subFilename );
            } );
        }
    } ) );
} ) );

const [ , , version ] = argv;

const zipFile = createWriteStream( `twicpics.${ version ? `${ version }.` : `` }${ Date.now() }.zip` );
const archive = archiver( `zip`, {
    "zlib": {
        "level": 9,
    },
} );
archive.pipe( zipFile );

for ( const file of files ) {
    // eslint-disable-next-line no-await-in-loop
    if ( !( await stat( file ) ).isDirectory() ) {
        archive.file( file, {
            "name": relative( __dirname, file ),
        } );
    }
}

archive.finalize();
