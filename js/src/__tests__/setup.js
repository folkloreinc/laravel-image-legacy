import { JSDOM } from 'jsdom';

// JSDOM
const { window } = new JSDOM('<!doctype html><html><body></body></html>');

function copyProps(src, target) {
    const props = Object.getOwnPropertyNames(src)
        .filter(prop => typeof target[prop] === 'undefined')
        .map(prop => Object.getOwnPropertyDescriptor(src, prop));
    Object.defineProperties(target, props);
}

global.window = window;
global.document = window.document;
global.navigator = {
    userAgent: 'node.js',
};
copyProps(window, global);
global.window.URL.createObjectURL = () => {};
global.window.Worker = () => {};
global.window.Worker.prototype.postMessage = () => {};
