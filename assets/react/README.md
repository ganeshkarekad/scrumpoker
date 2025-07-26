# TanStack Query Integration

This directory contains the TanStack Query integration for the Scrum Poker application.

## Structure

```
assets/react/
├── controllers/
│   └── Dashboard.tsx          # Main dashboard component with TanStack Query
├── providers/
│   └── QueryProvider.tsx      # QueryClient provider setup
├── services/
│   └── api.ts                 # API service functions and types
└── README.md                  # This file
```

## Features Implemented

### 1. QueryClient Setup (`providers/QueryProvider.tsx`)
- Configured QueryClient with sensible defaults
- 5-minute stale time for cached data
- 10-minute garbage collection time
- Retry logic and window focus refetch disabled
- Wraps components with QueryClientProvider

### 2. API Service Layer (`services/api.ts`)
- Centralized API functions for room operations
- TypeScript interfaces for type safety
- Consistent query keys for cache management
- Simulated API calls (replace with real endpoints)

### 3. Dashboard Component (`controllers/Dashboard.tsx`)
- Uses `useQuery` for fetching room data
- Uses `useMutation` for updating room status
- Proper loading and error states
- Cache invalidation after mutations
- Bootstrap 5 styling

## Usage Examples

### Fetching Data with useQuery
```tsx
const { data, isLoading, error } = useQuery({
    queryKey: queryKeys.room(roomKey),
    queryFn: () => roomApi.fetchRoom(roomKey),
    enabled: !!roomKey,
});
```

### Mutations with useMutation
```tsx
const updateStatusMutation = useMutation({
    mutationFn: roomApi.updateStatus,
    onSuccess: () => {
        queryClient.invalidateQueries({ queryKey: queryKeys.room(roomKey) });
    },
});
```

## Key Benefits

1. **Automatic Caching**: Data is cached and reused across components
2. **Background Updates**: Stale data is refetched in the background
3. **Optimistic Updates**: UI can be updated before server confirmation
4. **Error Handling**: Built-in error states and retry logic
5. **Loading States**: Automatic loading indicators
6. **Cache Invalidation**: Smart cache management after mutations

## API Endpoints

### Room Participants
- **Endpoint**: `GET /api/room/{roomKey}/participants`
- **Description**: Fetches all participants for a specific room
- **Response**: Returns room data with participants array
- **Usage**: Used by Dashboard component to display participants table

### Vote List
- **Endpoint**: `GET /api/vote/list`
- **Description**: Fetches all available votes in proper Scrum Poker sequence
- **Response**: Returns array of vote objects with id and label
- **Usage**: Used by Dashboard component to generate vote buttons

### Vote Submission
- **Endpoint**: `POST /api/vote/add`
- **Description**: Submits or updates a user's vote for a room
- **Request Body**: `{ "roomKey": string, "userId": string, "voteId": number }`
- **Response**: Returns vote confirmation with timestamps
- **Usage**: Used when user clicks on vote buttons

## Next Steps

1. ✅ ~~Replace simulated API calls with real Symfony API endpoints~~ (Completed for room participants)
2. Add more query hooks for different data types (votes, user votes, etc.)
3. Implement optimistic updates for better UX
4. Add React Query DevTools for development
5. Consider adding infinite queries for paginated data

## TanStack Query Documentation

For more information, visit: https://tanstack.com/query/latest
