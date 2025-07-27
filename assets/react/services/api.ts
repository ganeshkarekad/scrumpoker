// API service functions for TanStack Query
// Replace these with your actual API endpoints

export interface Vote {
    id: number;
    label: string;
}

export interface ParticipantVote {
    id: number;
    label: string;
    createdAt: string;
    updatedAt: string;
}

export interface Participant {
    id: string;
    username: string;
    isCreator: boolean;
    vote: ParticipantVote | null;
}

export interface RoomData {
    roomKey: string;
    participants: Participant[];
    status: string;
    currentStory?: string;
    createdBy: string;
    votesVisible: boolean;
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

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({ error: 'Unknown error' }));
            console.error('API Error:', errorData);
            throw new Error(errorData.error || 'Failed to submit vote');
        }

        return response.json();
    },

    // Toggle vote visibility in a room
    toggleVoteVisibility: async (roomKey: string): Promise<any> => {
        const response = await fetch('/api/vote/toggle-visibility', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                roomKey,
            }),
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({ error: 'Unknown error' }));
            console.error('Toggle visibility API Error:', errorData);
            throw new Error(errorData.error || 'Failed to toggle vote visibility');
        }

        return response.json();
    },

    // Reset all votes in a room
    resetVotes: async (roomKey: string): Promise<any> => {
        const response = await fetch('/api/vote/reset', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                roomKey,
            }),
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({ error: 'Unknown error' }));
            console.error('Reset API Error:', errorData);
            throw new Error(errorData.error || 'Failed to reset votes');
        }

        return response.json();
    },

    // Leave a room
    leaveRoom: async (roomKey: string, userId: string): Promise<any> => {
        const response = await fetch(`/api/room/${roomKey}/leave`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                userId,
            }),
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({ error: 'Unknown error' }));
            console.error('Leave room API Error:', errorData);
            throw new Error(errorData.error || 'Failed to leave room');
        }

        return response.json();
    }
};

// Query keys for consistent cache management
export const queryKeys = {
    votes: () => ['votes'] as const,
    room: (roomKey: string) => ['room', roomKey] as const,
} as const;
