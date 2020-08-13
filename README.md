# DocBuilder

DocBuilder uses a simple json syntax to allow for aggregation of docs from multiple repositories into a single directory structure. In order to do this, DocBuilder will expect its single command to be run referencing a json manifest of the respositories from which to retrieve docs, where the docs can be found within that repository, and what the desired sub-directory destination for that repo's documentation is.

Here's a simple example:

```
{
  "repositories": {
    "acquia_contenthub": {
      "repo": "git@github.com:acquia/acquia_contenthub.git",
      "source": "docs/source",
      "destination": "docs/docs/lift/contenthub",
      "entrypoint": "index.rst"
    }
  },
  "destination": "/Users/kris/foobar"
}
```
