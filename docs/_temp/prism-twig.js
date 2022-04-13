Prism.languages.twig = {
	'comment': /^\{#[\s\S]*?#\}$/,

	'tag-name': {
		pattern: /(^\{%-?\s*)\w+/,
		lookbehind: true,
		alias: 'keyword'
	},
	'delimiter': {
		pattern: /^\{[{%]-?|-?[%}]\}$/,
		alias: 'punctuation'
	},

	'string': {
		pattern: /("|')(?:\\.|(?!\1)[^\\\r\n])*\1/,
		inside: {
			'punctuation': /^['"]|['"]$/
		}
	},
	'keyword': /\b(?:even|if|odd)\b/,
	'boolean': /\b(?:false|null|true)\b/,
	'number': /\b0x[\dA-Fa-f]+|(?:\b\d+(?:\.\d*)?|\B\.\d+)(?:[Ee][-+]?\d+)?/,
	'operator': [
		{
			pattern: /(\s)(?:and|b-and|b-or|b-xor|ends with|in|is|matches|not|or|same as|starts with)(?=\s)/,
			lookbehind: true
		},
		/[=<>]=?|!=|\*\*?|\/\/?|\?:?|[-+~%|]/
	],
	'punctuation': /[()\[\]{}:.,]/,
};

// This fixed the issue 
Prism.hooks.add('before-tokenize', function (env) {
	if (env.language !== 'twig') {
		return;
	}

	// Prism is undefined in this hook in Docusaurus
	console.log('Prism', typeof Prism)

	// Added conditional
	if (
		typeof Prism !== 'undefined' &&
		typeof Prism.languages !== 'undefined' &&
		typeof Prism.languages['markup-templating'] !== 'undefined'
	) {
		var pattern = /\{(?:#[\s\S]*?#|%[\s\S]*?%|\{[\s\S]*?\})\}/g;
		Prism.languages['markup-templating'].buildPlaceholders(env, 'twig', pattern);
	}
});

Prism.hooks.add('after-tokenize', function (env) {
		// Added conditional
	if (
		typeof Prism !== 'undefined' &&
		typeof Prism.languages !== 'undefined' &&
		typeof Prism.languages['markup-templating'] !== 'undefined'
	) {
		Prism.languages['markup-templating'].tokenizePlaceholders(env, 'twig');
	}
});
