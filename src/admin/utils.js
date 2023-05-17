const rPath = /\S/;
const rURL = /^\s*https?:\/\/\S+/;

export const checkPath = path => rPath.test( path );
export const checkURL = url => rURL.test( url );

export const domain = document.location.host;
export const domainWithProtocol = `${ document.location.protocol }//${ domain }`;
