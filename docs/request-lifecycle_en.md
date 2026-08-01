# Request lifecycle

On a high level, the request lifecycle is as follows:

1. Web server forwards the HTTP request to the front controller in public/.
2. The application boots:
  - Loads configuration files, builds the DI container.
  - Executes bootstrap logic.
  - Registers global middleware from configuration.
3. Routing:
  - The router matches the incoming request to a defined route.
  - Any route‑level middleware is applied.
4. Controller/action:
  - The matched action executes, interacts with services, and returns a
  response.
5. View rendering (if applicable):
  - The view engine composes the page using templates and layouts.
6. Response emission:
  - A PSR‑7 response is emitted back to the client.
Logging/events:
  - Relevant events may be dispatched and logs written as configured.