import { useEffect, useState, useCallback, useRef } from 'react';
import { mercureClient, MercureMessage, MercureEventHandler } from '../services/mercure';

export interface UseMercureOptions {
    roomKey: string;
    onMessage?: MercureEventHandler;
    onVoteUpdate?: (data: any) => void;
    onVisibilityToggle?: (data: any) => void;
    onVoteReset?: (data: any) => void;
    onParticipantUpdate?: (data: any) => void;
    onRoomUpdate?: (data: any) => void;
}

export interface MercureConnectionState {
    isConnected: boolean;
    connectionStatus: 'connected' | 'connecting' | 'disconnected' | 'error';
    lastMessage: MercureMessage | null;
    error: string | null;
}

export function useMercure(options: UseMercureOptions): MercureConnectionState {
    const { roomKey, onMessage, onVoteUpdate, onVisibilityToggle, onVoteReset, onParticipantUpdate, onRoomUpdate } = options;

    const [connectionState, setConnectionState] = useState<MercureConnectionState>({
        isConnected: false,
        connectionStatus: 'disconnected',
        lastMessage: null,
        error: null,
    });

    // Use refs to store the latest handlers without causing re-renders
    const handlersRef = useRef({
        onMessage,
        onVoteUpdate,
        onVisibilityToggle,
        onVoteReset,
        onParticipantUpdate,
        onRoomUpdate,
    });

    // Update refs when handlers change
    useEffect(() => {
        handlersRef.current = {
            onMessage,
            onVoteUpdate,
            onVisibilityToggle,
            onVoteReset,
            onParticipantUpdate,
            onRoomUpdate,
        };
    }, [onMessage, onVoteUpdate, onVisibilityToggle, onVoteReset, onParticipantUpdate, onRoomUpdate]);

    // Update connection status
    const updateConnectionStatus = useCallback(() => {
        const status = mercureClient.getConnectionStatus();
        const isConnected = mercureClient.isConnectedToMercure();

        setConnectionState(prev => ({
            ...prev,
            isConnected,
            connectionStatus: status,
            error: status === 'error' ? 'Connection error' : null,
        }));
    }, []);

    // Handle incoming messages
    const handleMessage = useCallback((message: MercureMessage) => {
        console.log('Received message in hook:', message);

        setConnectionState(prev => ({
            ...prev,
            lastMessage: message,
            error: null,
        }));

        const handlers = handlersRef.current;

        // Call general message handler
        if (handlers.onMessage) {
            handlers.onMessage(message);
        }

        // Call specific handlers based on message type
        switch (message.type) {
            case 'vote_update':
                if (handlers.onVoteUpdate) {
                    handlers.onVoteUpdate(message.data);
                }
                break;
            case 'visibility_toggle':
                if (handlers.onVisibilityToggle) {
                    handlers.onVisibilityToggle(message.data);
                }
                break;
            case 'vote_reset':
                if (handlers.onVoteReset) {
                    handlers.onVoteReset(message.data);
                }
                break;
            case 'participant_update':
                if (handlers.onParticipantUpdate) {
                    handlers.onParticipantUpdate(message.data);
                }
                break;
            case 'room_update':
                if (handlers.onRoomUpdate) {
                    handlers.onRoomUpdate(message.data);
                }
                break;
        }
    }, []); // Empty dependency array since we use refs

    useEffect(() => {
        if (!roomKey) return;

        console.log('Setting up Mercure subscription for room:', roomKey);

        // Subscribe to Mercure updates
        const unsubscribe = mercureClient.subscribe(roomKey, handleMessage);

        // Set up periodic status updates
        const statusInterval = setInterval(updateConnectionStatus, 1000);

        // Initial status update
        updateConnectionStatus();

        // Cleanup function
        return () => {
            console.log('Cleaning up Mercure subscription for room:', roomKey);
            unsubscribe();
            clearInterval(statusInterval);
        };
    }, [roomKey]); // Remove handleMessage and updateConnectionStatus from dependencies

    // Cleanup on unmount
    useEffect(() => {
        return () => {
            // Note: We don't disconnect here because other components might be using Mercure
            // The MercureClient handles cleanup when no more handlers are registered
        };
    }, []);

    return connectionState;
}
