import pluginVue from "eslint-plugin-vue";

export default [
  ...pluginVue.configs["flat/essential"],
  {
    rules: {
      // Warn on unused variables and imports
      "no-unused-vars": [
        "warn",
        { argsIgnorePattern: "^_", varsIgnorePattern: "^_" },
      ],
      // Warn on unused components in templates
      "vue/no-unused-components": "warn",
      // Allow single-word component names (common for page components)
      "vue/multi-word-component-names": "off",
    },
  },
];
