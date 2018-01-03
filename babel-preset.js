const BABEL_ENV = process.env.BABEL_ENV || process.env.NODE_ENV || '';

module.exports = {
    presets: BABEL_ENV === 'test' ? ['env'] : [
        ['env', {
            modules: BABEL_ENV === 'cjs' ? 'commonjs' : false,
            targets: {
                browsers: [
                    '> 1%',
                    'last 2 versions',
                    'ios >= 7',
                    'ie >= 9',
                ],
            },
            useBuiltIns: BABEL_ENV === 'ejs',
        }],
    ],
    plugins: [
        ['transform-object-rest-spread', {
            useBuiltIns: BABEL_ENV === 'ejs',
        }],
        ['transform-runtime', {
            helpers: BABEL_ENV === 'cjs' || BABEL_ENV === 'ejs',
            polyfill: false,
            regenerator: BABEL_ENV === 'cjs' || BABEL_ENV === 'ejs',
            moduleName: 'babel-runtime',
        }],
    ],
};
