import React, { useState } from 'react';
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

    // State to control vote visibility
    const [showVotes, setShowVotes] = useState<boolean>(false);

    const queryClient = useQueryClient();

    // Use TanStack Query to fetch votes
    const { data: votes, isLoading: votesLoading, error: votesError } = useQuery({
        queryKey: queryKeys.votes(),
        queryFn: () => api.fetchVotes(),
    });

    // Use TanStack Query to fetch room data
    const { data: roomData, isLoading: roomLoading, error: roomError } = useQuery({
        queryKey: queryKeys.room(roomKey),
        queryFn: () => api.fetchRoom(roomKey),
        enabled: !!roomKey,
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

    // Use TanStack Query mutation for resetting votes
    const resetVotesMutation = useMutation({
        mutationFn: () => api.resetVotes(roomKey),
        onSuccess: () => {
            // Clear selected vote and invalidate room data
            setSelectedVote(null);
            setShowVotes(false);
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

    const handleShowHideClick = () => {
        setShowVotes(!showVotes);
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
        // If votes are hidden, show "Hidden" for all participants
        if (!showVotes) {
            return (
                <span className="badge bg-warning">
                    Hidden
                </span>
            );
        }

        // If this is the current user and they have selected a vote, show it
        if (participant.id === currentUserId && selectedVote) {
            return (
                <span className="badge bg-primary">
                    {selectedVote.label}
                </span>
            );
        }

        // TODO: Show actual votes from other participants when available
        // For now, show "No Vote" for other participants
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
                            className={`btn btn-lg ${showVotes ? 'btn-warning' : 'btn-outline-warning'}`}
                            onClick={handleShowHideClick}
                            disabled={submitVoteMutation.isPending || resetVotesMutation.isPending}
                        >
                            {showVotes ? 'Hide Votes' : 'Show Votes'}
                        </button>
                        <button
                            className="btn btn-danger btn-lg"
                            onClick={handleResetClick}
                            disabled={submitVoteMutation.isPending || resetVotesMutation.isPending}
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
            {(submitVoteMutation.isError || resetVotesMutation.isError) && (
                <div className="row mt-2">
                    <div className="col-12">
                        <div className="alert alert-danger">
                            {submitVoteMutation.isError && (
                                <>Error submitting vote: {submitVoteMutation.error instanceof Error ? submitVoteMutation.error.message : 'Unknown error'}</>
                            )}
                            {resetVotesMutation.isError && (
                                <>Error resetting votes: {resetVotesMutation.error instanceof Error ? resetVotesMutation.error.message : 'Unknown error'}</>
                            )}
                        </div>
                    </div>
                </div>
            )}

            {(submitVoteMutation.isSuccess || resetVotesMutation.isSuccess) && (
                <div className="row mt-2">
                    <div className="col-12">
                        <div className="alert alert-success">
                            {submitVoteMutation.isSuccess && 'Vote submitted successfully!'}
                            {resetVotesMutation.isSuccess && 'All votes reset successfully!'}
                        </div>
                    </div>
                </div>
            )}

            {/* Participants Table Section */}
            <div className="row mt-4">
                <div className="col-12">
                    <div className="card">
                        <div className="card-header">
                            <h5 className="mb-0">Participants ({roomData?.participants?.length || 0})</h5>
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
