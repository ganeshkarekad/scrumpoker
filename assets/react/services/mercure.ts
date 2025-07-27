// Mercure client service for real-time updates
export interface MercureMessage {
    type: 'room_update' | 'vote_update' | 'visibility_toggle' | 'vote_reset' | 'participant_update';
    roomKey: string;
    data: any;
    timestamp: number;
}

export type MercureEventHandler = (message: MercureMessage) => void;

export class MercureClient {
    private eventSource: EventSource | null = null;
    private handlers: Map<string, Set<MercureEventHandler>> = new Map();
    private reconnectAttempts = 0;
    private maxReconnectAttempts = 5;
    private reconnectDelay = 1000; // Start with 1 second
    private isConnected = false;

    constructor(private mercureUrl: string) {
        if (!mercureUrl) {
            throw new Error('Mercure URL is required');
        }
    }

    /**
     * Subscribe to updates for a specific room
     */
    subscribe(roomKey: string, handler: MercureEventHandler): () => void {
        const topic = `room/${roomKey}`;

        if (!this.handlers.has(topic)) {
            this.handlers.set(topic, new Set());
        }

        this.handlers.get(topic)!.add(handler);

        // Connect if not already connected
        if (!this.eventSource) {
            this.connect(roomKey);
        }

        // Return unsubscribe function
        return () => {
            const topicHandlers = this.handlers.get(topic);
            if (topicHandlers) {
                topicHandlers.delete(handler);
                if (topicHandlers.size === 0) {
                    this.handlers.delete(topic);
                    // Disconnect if no more handlers
                    if (this.handlers.size === 0) {
                        this.disconnect();
                    }
                }
            }
        };
    }

    /**
     * Connect to Mercure hub
     */
    private connect(roomKey: string): void {
        try {
            const url = new URL(this.mercureUrl);
            url.searchParams.append('topic', `room/${roomKey}`);

            this.eventSource = new EventSource(url.toString());

            this.eventSource.onopen = () => {
                this.isConnected = true;
                this.reconnectAttempts = 0;
                this.reconnectDelay = 1000;
            };

            this.eventSource.onmessage = (event) => {
                try {
                    const message: MercureMessage = JSON.parse(event.data);
                    this.handleMessage(message);
                } catch (error) {
                    console.error('Error parsing Mercure message:', error);
                }
            };

            this.eventSource.onerror = (error) => {
                console.error('Mercure connection error:', error);
                this.isConnected = false;
                this.handleConnectionError(roomKey);
            };

        } catch (error) {
            console.error('Error creating Mercure connection:', error);
            this.handleConnectionError(roomKey);
        }
    }

    /**
     * Handle incoming messages and route to appropriate handlers
     */
    private handleMessage(message: MercureMessage): void {
        const topic = `room/${message.roomKey}`;
        const handlers = this.handlers.get(topic);

        if (handlers) {
            handlers.forEach(handler => {
                try {
                    handler(message);
                } catch (error) {
                    console.error('Error in Mercure message handler:', error);
                }
            });
        }
    }

    /**
     * Handle connection errors with exponential backoff
     */
    private handleConnectionError(roomKey: string): void {
        if (this.eventSource) {
            this.eventSource.close();
            this.eventSource = null;
        }

        if (this.reconnectAttempts < this.maxReconnectAttempts) {
            this.reconnectAttempts++;
            const delay = this.reconnectDelay * Math.pow(2, this.reconnectAttempts - 1);

            setTimeout(() => {
                if (this.handlers.size > 0) { // Only reconnect if we still have handlers
                    this.connect(roomKey);
                }
            }, delay);
        } else {
            console.error('Max reconnection attempts reached. Mercure connection failed.');
        }
    }

    /**
     * Disconnect from Mercure hub
     */
    disconnect(): void {
        if (this.eventSource) {
            this.eventSource.close();
            this.eventSource = null;
            this.isConnected = false;
        }
        this.handlers.clear();
        this.reconnectAttempts = 0;
    }

    /**
     * Check if connected to Mercure
     */
    isConnectedToMercure(): boolean {
        return this.isConnected && this.eventSource?.readyState === EventSource.OPEN;
    }

    /**
     * Get connection status
     */
    getConnectionStatus(): 'connected' | 'connecting' | 'disconnected' | 'error' {
        if (!this.eventSource) return 'disconnected';

        switch (this.eventSource.readyState) {
            case EventSource.CONNECTING:
                return 'connecting';
            case EventSource.OPEN:
                return 'connected';
            case EventSource.CLOSED:
                return 'disconnected';
            default:
                return 'error';
        }
    }
}

/**
 * Create a new Mercure client instance with the provided URL
 */
export function createMercureClient(mercureUrl: string): MercureClient {
    return new MercureClient(mercureUrl);
}
