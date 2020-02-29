module.exports = {
  root: true,
  env: {
    node: true,
  },
  extends: [
    'prettier',
    'plugin:@typescript-eslint/recommended',
    'prettier/@typescript-eslint',
    'plugin:prettier/recommended',
    'plugin:vue/recommended',
    '@vue/prettier',
    '@vue/typescript',
  ],
  parserOptions: {
    parser: '@typescript-eslint/parser',
    ecmaVersion: 2018, // Allows for the parsing of modern ECMAScript features
    sourceType: 'module', // Allows for the use of imports
  },
  rules: {
    // Place to specify ESLint rules. Can be used to overwrite rules specified from the extended configs
    // e.g. "@typescript-eslint/explicit-function-return-type": "off",
    'vue/no-duplicate-attributes': 'off',
    'no-console': process.env.NODE_ENV === 'production' ? 'error' : 'off',
    'no-debugger': process.env.NODE_ENV === 'production' ? 'error' : 'off',
  },
  overrides:       {
      "files": [
          "**/tests/*.{j,t}s?(x)",
          "**/tests/**/*.spec.{j,t}s?(x)"
      ],
      "env": {
          "jest": true
      }
  },
}
