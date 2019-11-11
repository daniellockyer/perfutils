# perfutils

This repo is a POC of a tool I've wanted to make for a long time.

I often work on WordPress sites with terrible response times because they're using slow plugins. I want to know which specific plugins are to blame, but existing tools are unmaintained or non-functional. It'd also be useful for plugin developers to be able to performance profile their code and optimize it.

The idea I tried to implement was the following:

1. Install WordPress plugin 
2. Go to a slow page and click the Profile button in the top bar.
3. The page reloads, enables Xdebug and POSTs the data to my processing server.
4. The server collapses the stack and returns a flamegraph.
5. The flamegraph is inserted into the page for ease of use.
