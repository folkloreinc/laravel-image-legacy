import path from "path";
import babel from "@rollup/plugin-babel";
import resolve from "@rollup/plugin-node-resolve";
import commonjs from "@rollup/plugin-commonjs";
import replace from "@rollup/plugin-replace";

export default {
  input: "js/src/index.js",
  output: [
    {
      file: "js/lib/index.js",
      format: "cjs",
    },
    {
      file: "js/es/index.js",
    },
  ],
  plugins: [
    resolve({
      extensions: [".mjs", ".js", ".jsx", ".json", ".node"],
      jail: path.join(process.cwd(), "js/src"),
    }),
    commonjs(),
    babel({
      extensions: [".mjs", ".js", ".jsx", ".json", ".node"],
      exclude: "node_modules/**",
      // rootMode: 'upward',
      babelHelpers: "runtime",
      presets: [
        [
          require("@babel/preset-env"),
          {
            modules: false,
            useBuiltIns: false,
          },
        ],
        [
          require("@babel/preset-react"),
          {
            useBuiltIns: true,
          },
        ],
      ],
      plugins: [
        [
          require.resolve("@babel/plugin-transform-runtime"),
          {
            version: require("@babel/helpers/package.json").version,
            helpers: true,
          },
        ],
      ],
    }),
    replace({
      values: {
        "process.env.NODE_ENV": JSON.stringify(process.env.NODE_ENV),
      },
      preventAssignment: true,
    }),
  ],
};
