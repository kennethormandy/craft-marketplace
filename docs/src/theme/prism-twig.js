/**
 * Adds Prism syntax highlighting customizations.
 *
 * This is using the Sept 2021 version of Twig highlighting config:
 * https://github.com/PrismJS/prism/blob/e2630d890e9ced30a79cdf9ef272601ceeaedccf/components/prism-twig.js
 * …which seemed to get a strange change in Dec 2021 by the same author, which
 * made things worse (at least in the way I am trying to use it for Craft):
 * https://github.com/PrismJS/prism/blob/e03a7c24783d8a9e82869628755f4010e704c23c/components/prism-twig.js
 * @see https://github.com/facebook/docusaurus/issues/8065#issuecomment-1398336240
 */
;(function (Prism) {
  Prism.languages.twig = {
    comment: /\{#[\s\S]*?#\}/,
    tag: {
      pattern: /\{\{[\s\S]*?\}\}|\{%[\s\S]*?%\}/,
      inside: {
        ld: {
          pattern: /^(?:\{\{-?|\{%-?\s*\w+)/,
          inside: {
            punctuation: /^(?:\{\{|\{%)-?/,
            keyword: /\w+/,
          },
        },
        rd: {
          pattern: /-?(?:%\}|\}\})$/,
          inside: {
            punctuation: /.+/,
          },
        },
        string: {
          pattern: /("|')(?:\\.|(?!\1)[^\\\r\n])*\1/,
          inside: {
            punctuation: /^['"]|['"]$/,
          },
        },
        keyword: /\b(?:even|if|odd)\b/,
        boolean: /\b(?:false|null|true)\b/,
        number: /\b0x[\dA-Fa-f]+|(?:\b\d+(?:\.\d*)?|\B\.\d+)(?:[Ee][-+]?\d+)?/,
        operator: [
          {
            pattern: /(\s)(?:and|b-and|b-or|b-xor|ends with|in|is|matches|not|or|same as|starts with)(?=\s)/,
            lookbehind: true,
          },
          /[=<>]=?|!=|\*\*?|\/\/?|\?:?|[-+~%|]/,
        ],
        property: /\b[a-zA-Z_]\w*\b/,
        punctuation: /[()\[\]{}:.,]/,
      },
    },

    // The rest can be parsed as HTML
    other: {
      // We want non-blank matches
      pattern: /\S(?:[\s\S]*\S)?/,
      inside: Prism.languages.markup,
    },
  }
})(Prism)
