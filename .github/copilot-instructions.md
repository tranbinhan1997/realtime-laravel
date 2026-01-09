## Quick Context

This repository is a small Laravel app paired with a standalone Node/socket.io server for realtime features. The Node server (socket-server) handles live chats, rooms, and emits events; Laravel provides the primary web app, HTTP API and optional broadcasting hooks.

## How to run (developer commands)

- **Laravel app** (from `realtime-app`):
  - `composer install`
  - copy `.env` and set app DB keys, then `php artisan key:generate`
  - run migrations: `php artisan migrate`
  - serve: `php artisan serve --host=127.0.0.1 --port=8000`
- **Assets** (in `realtime-app`): `npm install` then `npm run dev` (or `npm run production`)
- **Socket server** (in `socket-server`): `npm install` then `node server.js` (listens on port 3000)

## Big-picture architecture

- Components: Laravel backend (HTTP, views, API) + Node `socket.io` process (`socket-server/server.js`).
- Communication patterns:
  - Clients connect to `socket.io` directly (Node). Handshake expects `handshake.auth.user_id` (see `socket-server/server.js`).
  - Rooms: user rooms are `user_{id}`; group rooms are `group_{group_id}`.
  - Node exposes an HTTP POST `/post` that emits `new_post` to all sockets.
  - Laravel's `BroadcastServiceProvider` and `routes/channels.php` are present but `resources/js/bootstrap.js` has Echo/Pusher commented out — the repo currently uses the standalone socket server rather than Laravel Echo.

## Project-specific conventions and patterns

- Socket message names and mapping (examples from `socket-server/server.js`):
  - client emits `private_message` => server emits `receive_private` to `user_{to}`
  - client emits `join_group` and `group_message` => server emits `receive_group` to `group_{group_id}`
  - server logs connections using `handshake.auth.user_id`.
- Folder/file hotspots to inspect:
  - [routes/web.php](routes/web.php) — web routes and API endpoints
  - [socket-server/server.js](socket-server/server.js) — socket logic, room naming, message events
  - [resources/js/bootstrap.js](resources/js/bootstrap.js) — client-side bootstrapping; Echo is commented
  - [app/Providers/BroadcastServiceProvider.php](app/Providers/BroadcastServiceProvider.php) and [config/broadcasting.php](config/broadcasting.php) — Laravel broadcasting setup
  - [routes/channels.php](routes/channels.php) — channel auth callbacks

## Integration & external dependencies

- Node dependencies: `socket.io`, `express`, `cors` (see `socket-server/package.json`).
- Laravel may be configured to use Pusher/Ably/Redis (see `config/broadcasting.php`), but default `BROADCAST_DRIVER` is `null` in this repo — confirm `.env` when enabling Echo/Pusher.

## Typical agent tasks & examples

- To add a realtime event flow change, update `socket-server/server.js` for socket semantics and update any client JS under `resources/js` that connects to socket.io.
- If you need Laravel-authorized channels, edit `routes/channels.php` and ensure `Broadcast::routes()` is enabled in `app/Providers/BroadcastServiceProvider.php`.
- To test end-to-end locally: run `php artisan serve` (Laravel) and `node socket-server/server.js` (Node), open the frontend, and use browser console to connect a socket.io client with `auth: { user_id: <id> }`.

## Where to look first when debugging

- Socket connectivity and room issues: [socket-server/server.js](socket-server/server.js).
- Laravel-side event definitions: `app/Events` (if present) and `routes/channels.php` for authorization.
- Client-side listeners and bootstrap: [resources/js/bootstrap.js](resources/js/bootstrap.js) and `resources/js/app.js`.

---
If anything here is unclear or you'd like this expanded (for example: example client connect code, recommended .env settings, or test instructions), tell me which part to expand.
