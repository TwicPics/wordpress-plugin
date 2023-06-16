import { fileURLToPath } from "url";
const __dirname = fileURLToPath( new URL( `.`, import.meta.url ) );

import archiver from "archiver";
import { createWriteStream } from "fs";
import dateFormat from "dateformat";
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

const versionHolders = new Map( await Promise.all( [ `twicpics.php`, `readme.txt` ].map( async filename => [
    filename,
    await readFile( `${ __dirname }/${ filename }`, `utf8` ),
] ) ) );

const version =
    /\bVersion\s*:\s*(?<version>\S+)/
        .exec( versionHolders.get( `twicpics.php` ) )
        ?.groups?.version;

const actualVersion = `${ version ? `${ version }.` : `` }${ dateFormat( Date.now(), `yymmddHHMM` ) }`;

const zipFile = createWriteStream( `twicpics.${ actualVersion }.zip` );
const archive = archiver( `zip`, {
    "zlib": {
        "level": 9,
    },
} );
archive.pipe( zipFile );

for ( const filename of files ) {
    // eslint-disable-next-line no-await-in-loop
    if ( !( await stat( filename ) ).isDirectory() ) {
        const relFilename = relative( __dirname, filename );
        if ( versionHolders.has( relFilename ) ) {
            archive.append( versionHolders.get( relFilename ).replaceAll( version, actualVersion ), {
                "name": relFilename,
            } );
        } else {
            archive.file( filename, {
                "name": relFilename,
            } );
        }
    }
}

archive.finalize();
