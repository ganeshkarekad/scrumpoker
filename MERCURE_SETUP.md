# Mercure Real-time Setup Guide

This document explains how to set up and use the Mercure-based real-time functionality in the Scrum Poker application.

## Overview

The application now uses **Mercure** instead of polling for real-time updates. Mercure is a protocol built on Server-Sent Events (SSE) that provides efficient real-time communication between the server and clients.

## Setup Instructions

### 1. Start the Mercure Hub

Before using the application, you need to start the Mercure hub:

```bash
# Make sure you're in the project root directory
cd /path/to/scrumpoker

# Start the Mercure hub (runs on http://127.0.0.1:3000)
./start-mercure.sh
```

The Mercure hub will start and display logs showing when clients connect and disconnect.

### 2. Start the Symfony Server

In a separate terminal, start the Symfony development server:

```bash
# Start Symfony server (usually runs on https://127.0.0.1:8000)
symfony server:start
```

### 3. Build Assets

Make sure the frontend assets are built:

```bash
npm run build
# or for development with watch mode
npm run dev
```

## How It Works

### Real-time Events

The system broadcasts the following events in real-time:

1. **Vote Updates** - When a user casts or changes their vote
2. **Visibility Toggle** - When someone clicks Show/Hide votes
3. **Vote Reset** - When all votes are reset
4. **Participant Updates** - When users join or leave rooms
5. **Room Updates** - General room state changes

### Frontend Integration

The React frontend automatically:
- Connects to Mercure when entering a room
- Subscribes to room-specific updates
- Updates the UI in real-time when events are received
- Handles connection failures with automatic reconnection
- Shows connection status (Live/Connecting/Offline)

### Backend Integration

The Symfony backend:
- Publishes Mercure updates when API endpoints are called
- Uses the `MercurePublisher` service to send structured messages
- Includes vote data, room state, and participant information in updates

## API Endpoints

All existing API endpoints now publish Mercure updates:

- `POST /api/vote/add` - Publishes vote updates
- `POST /api/vote/toggle-visibility` - Publishes visibility changes
- `POST /api/vote/reset` - Publishes reset events
- `GET /api/room/{roomKey}/participants` - Returns current state (no polling needed)

## Configuration

### Environment Variables

The Mercure configuration is in `.env`:

```env
MERCURE_URL=http://127.0.0.1:3000/.well-known/mercure
MERCURE_PUBLIC_URL=http://127.0.0.1:3000/.well-known/mercure
MERCURE_JWT_SECRET="!ChangeThisMercureHubJWTSecretKey!"
```

### Mercure Hub Configuration

The hub configuration is in `Caddyfile.dev`:
- Runs on port 3000
- Allows anonymous subscriptions for development
- Enables CORS for local development
- Uses HTTP (not HTTPS) for simplicity

## Troubleshooting

### Common Issues

1. **Connection Failed**: Make sure the Mercure hub is running on port 3000
2. **CORS Errors**: Check that the Caddyfile.dev has proper CORS settings
3. **No Real-time Updates**: Verify that both Symfony and Mercure are running

### Debugging

Check the browser console for Mercure connection logs:
- "Connecting to Mercure: ..." - Connection attempt
- "Mercure connection opened" - Successful connection
- "Received Mercure message: ..." - Incoming real-time updates

Check the Mercure hub logs for subscriber information:
- "New subscriber" - When clients connect
- Published messages appear in the logs

## Performance Benefits

Compared to the previous polling approach:

- **Reduced Server Load**: No more requests every second
- **Lower Latency**: Instant updates instead of up to 1-second delay
- **Better Scalability**: Mercure handles many concurrent connections efficiently
- **Bandwidth Savings**: Only sends data when changes occur

## Production Considerations

For production deployment:

1. Use HTTPS for the Mercure hub
2. Configure proper JWT secrets
3. Set up proper CORS origins (not "*")
4. Consider using a reverse proxy for the Mercure hub
5. Monitor Mercure hub performance and connections

## Files Modified

### Backend
- `src/Service/MercurePublisher.php` - Mercure publishing service
- `src/Controller/Api/VoteController.php` - Added Mercure publishing
- `config/packages/mercure.yaml` - Mercure configuration
- `.env` - Mercure environment variables

### Frontend
- `assets/react/services/mercure.ts` - Mercure client service
- `assets/react/hooks/useMercure.ts` - React hook for Mercure
- `assets/react/controllers/Dashboard.tsx` - Integrated Mercure instead of polling

### Configuration
- `Caddyfile.dev` - Mercure hub configuration
- `start-mercure.sh` - Script to start Mercure hub
- `mercure` - Mercure hub binary

The system now provides true real-time synchronization with better performance and user experience!
