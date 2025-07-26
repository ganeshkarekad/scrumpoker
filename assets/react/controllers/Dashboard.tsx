import React, { useState, useEffect, useCallback } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { QueryProvider } from '../providers/QueryProvider';
import { api, queryKeys, Vote, Participant } from '../services/api';
import { useMercure } from '../hooks/useMercure';

interface DashboardProps {
    roomKey: string;
    currentUserId: string;
}

function DashboardContent({ roomKey, currentUserId }: DashboardProps) {
    console.log('Dashboard render - roomKey:', roomKey, 'currentUserId:', currentUserId);

    // State to store the selected vote
    const [selectedVote, setSelectedVote] = useState<Vote | null>(null);

    const queryClient = useQueryClient();

    // Use TanStack Query to fetch votes (no polling, just initial load)
    const { data: votes, isLoading: votesLoading, error: votesError } = useQuery({
        queryKey: queryKeys.votes(),
        queryFn: () => api.fetchVotes(),
    });

    // Use TanStack Query to fetch room data (no polling, Mercure will handle updates)
    const { data: roomData, isLoading: roomLoading, error: roomError } = useQuery({
        queryKey: queryKeys.room(roomKey),
        queryFn: () => api.fetchRoom(roomKey),
        enabled: !!roomKey,
    });

    // Create stable handler functions using useCallback
    const handleVoteUpdate = useCallback((data: any) => {
        console.log('Vote update received:', data);
        // Invalidate room data to refetch with new vote
        queryClient.invalidateQueries({ queryKey: queryKeys.room(roomKey) });
    }, [queryClient, roomKey]);

    const handleVisibilityToggle = useCallback((data: any) => {
        console.log('Visibility toggle received:', data);
        // Invalidate room data to refetch with new visibility state
        queryClient.invalidateQueries({ queryKey: queryKeys.room(roomKey) });
    }, [queryClient, roomKey]);

    const handleVoteReset = useCallback((data: any) => {
        console.log('Vote reset received:', data);
        // Clear selected vote and invalidate room data
        setSelectedVote(null);
        queryClient.invalidateQueries({ queryKey: queryKeys.room(roomKey) });
    }, [queryClient, roomKey]);

    const handleParticipantUpdate = useCallback((data: any) => {
        console.log('Participant update received:', data);
        // Invalidate room data to refetch with new participants
        queryClient.invalidateQueries({ queryKey: queryKeys.room(roomKey) });
    }, [queryClient, roomKey]);

    const handleRoomUpdate = useCallback((data: any) => {
        console.log('Room update received:', data);
        // Invalidate room data to refetch
        queryClient.invalidateQueries({ queryKey: queryKeys.room(roomKey) });
    }, [queryClient, roomKey]);

    // Use Mercure for real-time updates
    const mercureState = useMercure({
        roomKey,
        onVoteUpdate: handleVoteUpdate,
        onVisibilityToggle: handleVisibilityToggle,
        onVoteReset: handleVoteReset,
        onParticipantUpdate: handleParticipantUpdate,
        onRoomUpdate: handleRoomUpdate,
    });

    // Use TanStack Query mutation for submitting votes
    const submitVoteMutation = useMutation({
        mutationFn: (vote: Vote) => api.submitVote(roomKey, currentUserId, vote.id),
        onSuccess: () => {
            // Invalidate and refetch room data after successful vote submission
            queryClient.invalidateQueries({ queryKey: queryKeys.room(roomKey) });
        },
        onError: (error) => {
            console.error('Failed to submit vote:', error);
        },
    });

    // Use TanStack Query mutation for toggling vote visibility
    const toggleVisibilityMutation = useMutation({
        mutationFn: () => api.toggleVoteVisibility(roomKey),
        onSuccess: () => {
            // Invalidate and refetch room data after successful toggle
            queryClient.invalidateQueries({ queryKey: queryKeys.room(roomKey) });
        },
        onError: (error) => {
            console.error('Failed to toggle vote visibility:', error);
        },
    });

    // Use TanStack Query mutation for resetting votes
    const resetVotesMutation = useMutation({
        mutationFn: () => api.resetVotes(roomKey),
        onSuccess: () => {
            // Clear selected vote and invalidate room data
            setSelectedVote(null);
            queryClient.invalidateQueries({ queryKey: queryKeys.room(roomKey) });
        },
        onError: (error) => {
            console.error('Failed to reset votes:', error);
        },
    });

    const handleVoteClick = (vote: Vote) => {
        setSelectedVote(vote);
        console.log('Vote selected:', vote);

        // Submit the vote to the API
        submitVoteMutation.mutate(vote);
    };

    // Sync selected vote with current user's actual vote from room data
    useEffect(() => {
        if (roomData?.participants) {
            const currentUser = roomData.participants.find(p => p.id === currentUserId);
            if (currentUser?.vote) {
                setSelectedVote({
                    id: currentUser.vote.id,
                    label: currentUser.vote.label
                });
            } else {
                setSelectedVote(null);
            }
        }
    }, [roomData, currentUserId]);

    const handleShowHideClick = () => {
        toggleVisibilityMutation.mutate();
    };

    const handleResetClick = () => {
        if (window.confirm('Are you sure you want to reset all votes? This action cannot be undone.')) {
            resetVotesMutation.mutate();
        }
    };

    const renderParticipantLabels = (participant: Participant) => {
        const labels = [];

        if (participant.id === currentUserId) {
            labels.push(
                <span key="you" className="status-badge badge bg-primary">
                    👤 You
                </span>
            );
        }

        if (participant.isCreator) {
            labels.push(
                <span key="host" className="status-badge badge bg-success">
                    👑 Host
                </span>
            );
        }

        return labels;
    };

    const renderParticipantVote = (participant: Participant) => {
        // If votes are hidden (based on room state), show "Hidden" for all participants
        if (!roomData?.votesVisible) {
            return (
                <span className="status-badge badge bg-warning">
                    🙈 Hidden
                </span>
            );
        }

        // If participant has voted, show their vote
        if (participant.vote) {
            return (
                <span className={`status-badge badge ${participant.id === currentUserId ? 'bg-primary' : 'bg-success'}`}>
                    ✅ {participant.vote.label}
                </span>
            );
        }

        // If no vote, show "No Vote"
        return (
            <span className="status-badge badge bg-secondary">
                ⏳ No Vote
            </span>
        );
    };

    if (votesLoading || roomLoading) {
        return (
            <div className="dashboard-container">
                <div className="container-fluid py-4">
                    <div className="loading-container">
                        <div className="d-flex align-items-center justify-content-center">
                            <div className="spinner-border spinner-border-sm me-3" role="status">
                                <span className="visually-hidden">Loading...</span>
                            </div>
                            <span className="fs-5 fw-medium">Loading your poker session...</span>
                        </div>
                    </div>
                </div>
            </div>
        );
    }

    if (votesError || roomError) {
        return (
            <div className="dashboard-container">
                <div className="container-fluid py-4">
                    <div className="alert alert-danger">
                        <h5 className="alert-heading">
                            ⚠️ Oops! Something went wrong
                        </h5>
                        <p className="mb-0">Error loading data: {(votesError || roomError) instanceof Error ? (votesError || roomError)?.message : 'Unknown error'}</p>
                    </div>
                </div>
            </div>
        );
    }

    return (
        <div className="dashboard-container">
            <div className="container-fluid py-4">
                {/* Vote Buttons Section */}
                <div className="vote-buttons-section">
                    <div className="text-center mb-4">
                        <h3 className="fw-bold mb-2">Choose Your Estimate</h3>
                        <p className="text-muted mb-0">Select a card to cast your vote</p>
                    </div>
                    <div className="d-flex flex-wrap gap-3 justify-content-center">
                        {votes?.map((vote) => (
                            <button
                                key={vote.id}
                                className={`vote-button ${
                                    selectedVote?.id === vote.id ? 'selected' : ''
                                }`}
                                onClick={() => handleVoteClick(vote)}
                                disabled={submitVoteMutation.isPending}
                            >
                                {submitVoteMutation.isPending && selectedVote?.id === vote.id ? (
                                    <div className="spinner-border spinner-border-sm" role="status">
                                        <span className="visually-hidden">Submitting...</span>
                                    </div>
                                ) : (
                                    vote.label
                                )}
                            </button>
                        ))}
                    </div>
                </div>

                {/* Control Buttons */}
                <div className="control-buttons-section">
                    <div className="text-center mb-3">
                        <h5 className="fw-semibold mb-1">Session Controls</h5>
                        <p className="text-muted small mb-0">Manage vote visibility and reset the session</p>
                    </div>
                    <div className="d-flex justify-content-center gap-3 flex-wrap">
                        <button
                            className={`control-button btn btn-lg ${roomData?.votesVisible ? 'btn-warning' : 'btn-outline-warning'}`}
                            onClick={handleShowHideClick}
                            disabled={submitVoteMutation.isPending || resetVotesMutation.isPending || toggleVisibilityMutation.isPending}
                        >
                            {toggleVisibilityMutation.isPending ? (
                                <>
                                    <div className="spinner-border spinner-border-sm me-2" role="status">
                                        <span className="visually-hidden">Toggling...</span>
                                    </div>
                                    Toggling...
                                </>
                            ) : (
                                <>
                                    {roomData?.votesVisible ? '🙈 Hide Votes' : '👁️ Show Votes'}
                                </>
                            )}
                        </button>
                        <button
                            className="control-button btn btn-danger btn-lg"
                            onClick={handleResetClick}
                            disabled={submitVoteMutation.isPending || resetVotesMutation.isPending || toggleVisibilityMutation.isPending}
                        >
                            {resetVotesMutation.isPending ? (
                                <>
                                    <div className="spinner-border spinner-border-sm me-2" role="status">
                                        <span className="visually-hidden">Resetting...</span>
                                    </div>
                                    Resetting...
                                </>
                            ) : (
                                <>
                                    🔄 Reset Votes
                                </>
                            )}
                        </button>
                    </div>
                </div>

                {/* Error/Success Messages */}
                {(submitVoteMutation.isError || resetVotesMutation.isError || toggleVisibilityMutation.isError) && (
                    <div className="alert alert-danger">
                        <h6 className="alert-heading">
                            ❌ Action Failed
                        </h6>
                        {submitVoteMutation.isError && (
                            <p className="mb-0">Error submitting vote: {submitVoteMutation.error instanceof Error ? submitVoteMutation.error.message : 'Unknown error'}</p>
                        )}
                        {resetVotesMutation.isError && (
                            <p className="mb-0">Error resetting votes: {resetVotesMutation.error instanceof Error ? resetVotesMutation.error.message : 'Unknown error'}</p>
                        )}
                        {toggleVisibilityMutation.isError && (
                            <p className="mb-0">Error toggling vote visibility: {toggleVisibilityMutation.error instanceof Error ? toggleVisibilityMutation.error.message : 'Unknown error'}</p>
                        )}
                    </div>
                )}

                {(submitVoteMutation.isSuccess || resetVotesMutation.isSuccess || toggleVisibilityMutation.isSuccess) && (
                    <div className="alert alert-success">
                        <h6 className="alert-heading">
                            ✅ Success!
                        </h6>
                        {submitVoteMutation.isSuccess && <p className="mb-0">Vote submitted successfully!</p>}
                        {resetVotesMutation.isSuccess && <p className="mb-0">All votes reset successfully!</p>}
                        {toggleVisibilityMutation.isSuccess && <p className="mb-0">Vote visibility toggled successfully!</p>}
                    </div>
                )}

                {/* Participants Table Section */}
                <div className="participants-section">
                    <div className="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h5 className="mb-1">
                                👥 Participants ({roomData?.participants?.length || 0})
                            </h5>
                            <small className="opacity-75">Real-time participant status and votes</small>
                        </div>
                        <div className="d-flex align-items-center">
                            <span className={`status-badge badge me-2 ${
                                mercureState.isConnected ? 'bg-success' :
                                mercureState.connectionStatus === 'connecting' ? 'bg-warning' : 'bg-danger'
                            }`}>
                                🟢 {mercureState.isConnected ? 'Live' :
                                 mercureState.connectionStatus === 'connecting' ? 'Connecting' : 'Offline'}
                            </span>
                            {(roomLoading || mercureState.connectionStatus === 'connecting') && (
                                <div className="spinner-border spinner-border-sm text-white" role="status">
                                    <span className="visually-hidden">
                                        {mercureState.connectionStatus === 'connecting' ? 'Connecting...' : 'Updating...'}
                                    </span>
                                </div>
                            )}
                        </div>
                    </div>
                    <div className="card-body p-0">
                        <div className="table-responsive">
                            <table className="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th scope="col" className="text-center" style={{ width: '60px' }}>#</th>
                                        <th scope="col">
                                            👤 Participant
                                        </th>
                                        <th scope="col" className="text-center">
                                            🗳️ Vote Status
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {roomData?.participants?.map((participant, index) => (
                                        <tr key={participant.id}>
                                            <td className="text-center fw-semibold text-muted">{index + 1}</td>
                                            <td>
                                                <div className="d-flex align-items-center">
                                                    <div className="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3">
                                                        {participant.username.charAt(0).toUpperCase()}
                                                    </div>
                                                    <div>
                                                        <div className="fw-semibold">{participant.username}</div>
                                                        <div className="d-flex gap-1 mt-1">
                                                            {renderParticipantLabels(participant)}
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="text-center">
                                                {renderParticipantVote(participant)}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

// Main Dashboard component wrapped with QueryProvider
export default function Dashboard(props: DashboardProps) {
    return (
        <QueryProvider>
            <DashboardContent {...props} />
        </QueryProvider>
    );
}
