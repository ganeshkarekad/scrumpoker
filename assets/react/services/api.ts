// API service functions for TanStack Query
// Replace these with your actual API endpoints

export interface Vote {
    id: number;
    label: string;
}

export interface Participant {
    id: string;
    username: string;
    isCreator: boolean;
}

export interface RoomData {
    roomKey: string;
    participants: Participant[];
    status: string;
    currentStory?: string;
    createdBy: string;
}

// API functions
export const api = {
    // Fetch all votes
    fetchVotes: async (): Promise<Vote[]> => {
        const response = await fetch('/api/vote/list');
        if (!response.ok) {
            throw new Error('Failed to fetch votes');
        }
        return response.json();
    },

    // Fetch room data with participants
    fetchRoom: async (roomKey: string): Promise<RoomData> => {
        const response = await fetch(`/api/room/${roomKey}/participants`);
        if (!response.ok) {
            throw new Error('Failed to fetch room participants');
        }
        return response.json();
    },

    // Submit a vote
    submitVote: async (roomKey: string, userId: string, voteId: number): Promise<any> => {
        console.log('Submitting vote:', { roomKey, userId, voteId });
        const response = await fetch('/api/vote/add', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                roomKey,
                userId,
                voteId,
            }),
        });

        console.log('Response status:', response.status);
        console.log('Response URL:', response.url);

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({ error: 'Unknown error' }));
            console.error('API Error:', errorData);
            throw new Error(errorData.error || 'Failed to submit vote');
        }

        return response.json();
    },

    // Reset all votes in a room
    resetVotes: async (roomKey: string): Promise<any> => {
        console.log('Resetting votes for room:', roomKey);
        const response = await fetch('/api/vote/reset', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                roomKey,
            }),
        });

        console.log('Reset response status:', response.status);

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({ error: 'Unknown error' }));
            console.error('Reset API Error:', errorData);
            throw new Error(errorData.error || 'Failed to reset votes');
        }

        return response.json();
    }
};

// Query keys for consistent cache management
export const queryKeys = {
    votes: () => ['votes'] as const,
    room: (roomKey: string) => ['room', roomKey] as const,
} as const;
