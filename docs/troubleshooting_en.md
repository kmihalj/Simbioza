# Troubleshooting

- Blank page or 500 error:
    - Check the logs under data/ (ensure directories are writable).
    - Verify your environment file exists and is correctly configured.
- Routes not found:
    - Ensure your web server points to public/ as the document root and that URL rewriting is enabled to route requests
      to the front controller.
    - Confirm the route definition matches the expected method/path.
- CSRF issues:
    - Ensure forms include the expected CSRF token or adjust exclusions in application settings if appropriate.
