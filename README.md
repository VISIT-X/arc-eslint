# arc-eslint

ES-lint linter for arcanist &amp; phabricator

### Install & configuration

1. Install the package via npm:

```
npm install arc-eslint eslint
```

#### Sample .arcconfig

```json
{
    "project_id": "YourProjectName",
    "load" : [
        "./node_modules/arc-eslint"
    ]
}
```

#### Sample .arclint

```json
{
    "linters": {
        "js-files": {
            "type": "eslint",
            "include": "(^src/app/.*\\.js(x)$)",
            "exclude": "(^build/.*\\.js$)",
            "bin": "./node_modules/.bin/eslint"
        }
    }
}
```

#### Custom config for arcanist (other then IDE, for instance)

```json
{
    "linters": {
        "js-files": {
            "type": "eslint",
            "include": "(^src/app/.*\\.js(x)$)",
            "exclude": "(^build/.*\\.js$)",
            "bin": "./node_modules/.bin/eslint",
            "eslint.config": "./.eslintrc.strict.json"
        }
    }
}
```
