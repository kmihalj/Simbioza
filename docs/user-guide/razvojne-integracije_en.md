# For developers

Simbioza and HeartPhrame modules can integrate with other applications. Choose the example that matches where your form or data lives.

## HTML editor in your own form

To let a user edit formatted content in a text field, embed the HTML editor component. The user chooses **Edit content**, edits text in a dialog, applies the change and then saves your form. Your application checks permissions, sanitizes HTML and persists it in its own field.

Open [HTML editor in a form field](html-editor-u-obrascu_en.md) for a working example, actual screenshots and the distinction between applying text and saving the form. You do not need to create a Wiki workspace or a Simbioza document.

## API for external applications

Reading or changing data over HTTP requires the **API** module, an active key with the appropriate scope and a user with access to the content. A key does not extend its owner's permissions. Keep keys on the server, never in JavaScript delivered to a browser.

- [API: contract, authentication and routes](../api-v1-contract_en.md)
- [Surveys: cURL examples](ankete-api_en.md)
- [Surveys: separate PHP examples](ankete-api-php_en.md)

Examples use sample addresses and data. Replace them with your installation's values; keep secrets out of documentation and public repositories.

## Development and maintenance

Check each module's dependencies and its route and service registration before integrating it. Use public module APIs rather than internal database tables. HeartPhrame Framework is an external dependency; this project does not maintain its source code.
