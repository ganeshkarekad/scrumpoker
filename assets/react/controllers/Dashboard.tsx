import React, { useState, useEffect } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { QueryProvider } from '../providers/QueryProvider';
import { api, queryKeys, Vote, Participant } from '../services/api';

interface DashboardProps {
    roomKey: string;
    currentUserId: string;
}

function DashboardContent({ roomKey, currentUserId }: DashboardProps) {
    console.log(roomKey);
    console.log(currentUserId);

    // State to store the selected vote
    const [selectedVote, setSelectedVote] = useState<Vote | null>(null);

    const queryClient = useQueryClient();

    // Use TanStack Query to fetch votes with polling
    const { data: votes, isLoading: votesLoading, error: votesError } = useQuery({
        queryKey: queryKeys.votes(),
        queryFn: () => api.fetchVotes(),
        refetchInterval: 5000, // Poll every 5 seconds for votes (less frequent since they change rarely)
        refetchIntervalInBackground: true,
    });

    // Use TanStack Query to fetch room data with polling
    const { data: roomData, isLoading: roomLoading, error: roomError } = useQuery({
        queryKey: queryKeys.room(roomKey),
        queryFn: () => api.fetchRoom(roomKey),
        enabled: !!roomKey,
        refetchInterval: roomError ? false : 1000, // Poll every 1 second, stop on error
        refetchIntervalInBackground: true, // Continue polling when tab is not active
        retry: 3, // Retry failed requests up to 3 times
        retryDelay: (attemptIndex) => Math.min(1000 * 2 ** attemptIndex, 30000), // Exponential backoff
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
                <span key="you" className="badge bg-primary ms-2">
                    You
                </span>
            );
        }

        if (participant.isCreator) {
            labels.push(
                <span key="host" className="badge bg-success ms-2">
                    Host
                </span>
            );
        }

        return labels;
    };

    const renderParticipantVote = (participant: Participant) => {
        // If votes are hidden (based on room state), show "Hidden" for all participants
        if (!roomData?.votesVisible) {
            return (
                <span className="badge bg-warning">
                    Hidden
                </span>
            );
        }

        // If participant has voted, show their vote
        if (participant.vote) {
            return (
                <span className={`badge ${participant.id === currentUserId ? 'bg-primary' : 'bg-success'}`}>
                    {participant.vote.label}
                </span>
            );
        }

        // If no vote, show "No Vote"
        return (
            <span className="badge bg-secondary">
                No Vote
            </span>
        );
    };

    if (votesLoading || roomLoading) {
        return (
            <div className="container-fluid">
                <div className="d-flex align-items-center justify-content-center p-4">
                    <div className="spinner-border spinner-border-sm me-2" role="status">
                        <span className="visually-hidden">Loading...</span>
                    </div>
                    Loading...
                </div>
            </div>
        );
    }

    if (votesError || roomError) {
        return (
            <div className="container-fluid">
                <div className="alert alert-danger">
                    <p>Error loading data: {(votesError || roomError) instanceof Error ? (votesError || roomError)?.message : 'Unknown error'}</p>
                </div>
            </div>
        );
    }

    return (
        <div className="container-fluid">
            {/* Vote Buttons Section */}
            <div className="row">
                <div className="col-12">
                    <div className="d-flex flex-wrap gap-2 justify-content-center p-3">
                        {votes?.map((vote) => (
                            <button
                                key={vote.id}
                                className={`btn btn-lg ${
                                    selectedVote?.id === vote.id
                                        ? 'btn-primary'
                                        : 'btn-outline-primary'
                                }`}
                                onClick={() => handleVoteClick(vote)}
                                disabled={submitVoteMutation.isPending}
                                style={{ minWidth: '60px', minHeight: '60px' }}
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
            </div>

            {/* Control Buttons */}
            <div className="row mt-3">
                <div className="col-12">
                    <div className="d-flex justify-content-center gap-3">
                        <button
                            className={`btn btn-lg ${roomData?.votesVisible ? 'btn-warning' : 'btn-outline-warning'}`}
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
                                roomData?.votesVisible ? 'Hide Votes' : 'Show Votes'
                            )}
                        </button>
                        <button
                            className="btn btn-danger btn-lg"
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
                                'Reset Votes'
                            )}
                        </button>
                    </div>
                </div>
            </div>

            {/* Error/Success Messages */}
            {(submitVoteMutation.isError || resetVotesMutation.isError || toggleVisibilityMutation.isError) && (
                <div className="row mt-2">
                    <div className="col-12">
                        <div className="alert alert-danger">
                            {submitVoteMutation.isError && (
                                <>Error submitting vote: {submitVoteMutation.error instanceof Error ? submitVoteMutation.error.message : 'Unknown error'}</>
                            )}
                            {resetVotesMutation.isError && (
                                <>Error resetting votes: {resetVotesMutation.error instanceof Error ? resetVotesMutation.error.message : 'Unknown error'}</>
                            )}
                            {toggleVisibilityMutation.isError && (
                                <>Error toggling vote visibility: {toggleVisibilityMutation.error instanceof Error ? toggleVisibilityMutation.error.message : 'Unknown error'}</>
                            )}
                        </div>
                    </div>
                </div>
            )}

            {(submitVoteMutation.isSuccess || resetVotesMutation.isSuccess || toggleVisibilityMutation.isSuccess) && (
                <div className="row mt-2">
                    <div className="col-12">
                        <div className="alert alert-success">
                            {submitVoteMutation.isSuccess && 'Vote submitted successfully!'}
                            {resetVotesMutation.isSuccess && 'All votes reset successfully!'}
                            {toggleVisibilityMutation.isSuccess && 'Vote visibility toggled successfully!'}
                        </div>
                    </div>
                </div>
            )}

            {/* Participants Table Section */}
            <div className="row mt-4">
                <div className="col-12">
                    <div className="card">
                        <div className="card-header d-flex justify-content-between align-items-center">
                            <h5 className="mb-0">Participants ({roomData?.participants?.length || 0})</h5>
                            <div className="d-flex align-items-center">
                                <span className="badge bg-success me-2">
                                    <i className="fas fa-circle me-1" style={{ fontSize: '8px' }}></i>
                                    Live
                                </span>
                                {roomLoading && (
                                    <div className="spinner-border spinner-border-sm text-primary" role="status">
                                        <span className="visually-hidden">Updating...</span>
                                    </div>
                                )}
                            </div>
                        </div>
                        <div className="card-body p-0">
                            <div className="table-responsive">
                                <table className="table table-hover mb-0">
                                    <thead className="table-light">
                                        <tr>
                                            <th scope="col">#</th>
                                            <th scope="col">Username</th>
                                            <th scope="col">Vote</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {roomData?.participants?.map((participant, index) => (
                                            <tr key={participant.id}>
                                                <th scope="row">{index + 1}</th>
                                                <td>
                                                    {participant.username}
                                                    {renderParticipantLabels(participant)}
                                                </td>
                                                <td>
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
