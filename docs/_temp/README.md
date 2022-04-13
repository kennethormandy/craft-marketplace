Prism syntax highlighting for languages that reference `Prism` in the global scope within a hook appear to be breaking.

So far, I was able to reproduce this for:

- Twig
- Liquid

The files in this folder comment out the issues, to still preserve some syntax highlighting for Twig, in the meantime.
