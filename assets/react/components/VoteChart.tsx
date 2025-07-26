import React, { useMemo } from 'react';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    BarElement,
    Title,
    Tooltip,
    Legend,
    ArcElement,
} from 'chart.js';
import { Bar, Doughnut } from 'react-chartjs-2';
import { Participant } from '../services/api';

// Register Chart.js components
ChartJS.register(
    CategoryScale,
    LinearScale,
    BarElement,
    Title,
    Tooltip,
    Legend,
    ArcElement
);

interface VoteChartProps {
    participants: Participant[];
    isVisible?: boolean;
}

interface VoteBreakdown {
    label: string;
    count: number;
    percentage: number;
}

export const VoteChart: React.FC<VoteChartProps> = ({ participants, isVisible = true }) => {
    const voteBreakdown = useMemo(() => {
        if (!participants || participants.length === 0) {
            return [];
        }

        // Count votes by label
        const voteCounts: { [key: string]: number } = {};
        let totalVotes = 0;

        participants.forEach(participant => {
            if (participant.vote) {
                const label = participant.vote.label;
                voteCounts[label] = (voteCounts[label] || 0) + 1;
                totalVotes++;
            }
        });

        // Convert to breakdown array with percentages
        const breakdown: VoteBreakdown[] = Object.entries(voteCounts)
            .map(([label, count]) => ({
                label,
                count,
                percentage: totalVotes > 0 ? Math.round((count / totalVotes) * 100) : 0,
            }))
            .sort((a, b) => {
                // Sort by numeric value if possible, otherwise alphabetically
                const aNum = parseFloat(a.label);
                const bNum = parseFloat(b.label);
                if (!isNaN(aNum) && !isNaN(bNum)) {
                    return aNum - bNum;
                }
                return a.label.localeCompare(b.label);
            });

        return breakdown;
    }, [participants]);

    const hasVotes = voteBreakdown.length > 0;
    const totalParticipants = participants.length;
    const votedParticipants = voteBreakdown.reduce((sum, item) => sum + item.count, 0);
    const notVotedCount = totalParticipants - votedParticipants;

    // Chart colors - matching the homepage gradient theme
    const colors = [
        'rgba(102, 126, 234, 0.8)',  // Primary gradient start
        'rgba(118, 75, 162, 0.8)',   // Primary gradient end
        'rgba(240, 147, 251, 0.8)',  // Secondary gradient start
        'rgba(245, 87, 108, 0.8)',   // Secondary gradient end
        'rgba(79, 172, 254, 0.8)',   // Accent gradient start
        'rgba(0, 242, 254, 0.8)',    // Accent gradient end
        'rgba(16, 185, 129, 0.8)',   // Success
        'rgba(245, 158, 11, 0.8)',   // Warning
        'rgba(244, 63, 94, 0.8)',    // Danger
        'rgba(100, 116, 139, 0.8)',  // Secondary
    ];

    const borderColors = colors.map(color => color.replace('0.8', '1'));

    // Bar chart data
    const barData = {
        labels: voteBreakdown.map(item => item.label),
        datasets: [
            {
                label: 'Votes',
                data: voteBreakdown.map(item => item.count),
                backgroundColor: colors.slice(0, voteBreakdown.length),
                borderColor: borderColors.slice(0, voteBreakdown.length),
                borderWidth: 2,
                borderRadius: 8,
                borderSkipped: false,
            },
        ],
    };

    // Doughnut chart data (including "Not Voted" if applicable)
    const doughnutLabels = [...voteBreakdown.map(item => item.label)];
    const doughnutData = [...voteBreakdown.map(item => item.count)];
    const doughnutColors = [...colors.slice(0, voteBreakdown.length)];
    const doughnutBorderColors = [...borderColors.slice(0, voteBreakdown.length)];

    if (notVotedCount > 0) {
        doughnutLabels.push('Not Voted');
        doughnutData.push(notVotedCount);
        doughnutColors.push('rgba(156, 163, 175, 0.8)');
        doughnutBorderColors.push('rgba(156, 163, 175, 1)');
    }

    const doughnutChartData = {
        labels: doughnutLabels,
        datasets: [
            {
                data: doughnutData,
                backgroundColor: doughnutColors,
                borderColor: doughnutBorderColors,
                borderWidth: 2,
            },
        ],
    };

    const chartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom' as const,
                labels: {
                    padding: 20,
                    usePointStyle: true,
                    font: {
                        size: 12,
                        family: 'Inter, system-ui, sans-serif',
                    },
                },
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                titleColor: 'white',
                bodyColor: 'white',
                borderColor: 'rgba(255, 255, 255, 0.2)',
                borderWidth: 1,
                cornerRadius: 8,
                displayColors: true,
                callbacks: {
                    label: function(context: any) {
                        const label = context.label || '';
                        const value = context.parsed.y || context.raw || 0;
                        const percentage = totalParticipants > 0 ? Math.round((value / totalParticipants) * 100) : 0;
                        return `${label}: ${value} votes (${percentage}%)`;
                    },
                },
            },
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1,
                    font: {
                        family: 'Inter, system-ui, sans-serif',
                    },
                },
                grid: {
                    color: 'rgba(0, 0, 0, 0.1)',
                },
            },
            x: {
                ticks: {
                    font: {
                        family: 'Inter, system-ui, sans-serif',
                    },
                },
                grid: {
                    display: false,
                },
            },
        },
    };

    const doughnutOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom' as const,
                labels: {
                    padding: 20,
                    usePointStyle: true,
                    font: {
                        size: 12,
                        family: 'Inter, system-ui, sans-serif',
                    },
                },
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                titleColor: 'white',
                bodyColor: 'white',
                borderColor: 'rgba(255, 255, 255, 0.2)',
                borderWidth: 1,
                cornerRadius: 8,
                displayColors: true,
                callbacks: {
                    label: function(context: any) {
                        const label = context.label || '';
                        const value = context.parsed;
                        const percentage = Math.round((value / totalParticipants) * 100);
                        return `${label}: ${value} votes (${percentage}%)`;
                    },
                },
            },
        },
    };

    if (!isVisible || !hasVotes) {
        return null;
    }

    return (
        <div className="vote-chart-section">
            <div className="text-center mb-4">
                <h4 className="fw-bold mb-2">📊 Vote Distribution</h4>
                <p className="text-muted mb-0">
                    {votedParticipants} of {totalParticipants} participants have voted
                </p>
            </div>

            <div className="row g-4">
                {/* Bar Chart */}
                <div className="col-lg-6">
                    <div className="chart-container">
                        <h6 className="chart-title">Vote Breakdown</h6>
                        <div className="chart-wrapper" style={{ height: '300px' }}>
                            <Bar data={barData} options={chartOptions} />
                        </div>
                    </div>
                </div>

                {/* Doughnut Chart */}
                <div className="col-lg-6">
                    <div className="chart-container">
                        <h6 className="chart-title">Vote Distribution</h6>
                        <div className="chart-wrapper" style={{ height: '300px' }}>
                            <Doughnut data={doughnutChartData} options={doughnutOptions} />
                        </div>
                    </div>
                </div>
            </div>

            {/* Vote Summary */}
            <div className="vote-summary mt-4">
                <div className="row g-3">
                    {voteBreakdown.map((item, index) => (
                        <div key={item.label} className="col-auto">
                            <div className="vote-summary-item">
                                <div
                                    className="vote-color-indicator"
                                    style={{ backgroundColor: colors[index % colors.length] }}
                                ></div>
                                <span className="vote-label fw-semibold">{item.label}</span>
                                <span className="vote-count badge bg-light text-dark ms-2">
                                    {item.count} ({item.percentage}%)
                                </span>
                            </div>
                        </div>
                    ))}
                    {notVotedCount > 0 && (
                        <div className="col-auto">
                            <div className="vote-summary-item">
                                <div
                                    className="vote-color-indicator"
                                    style={{ backgroundColor: 'rgba(156, 163, 175, 0.8)' }}
                                ></div>
                                <span className="vote-label fw-semibold">Not Voted</span>
                                <span className="vote-count badge bg-light text-dark ms-2">
                                    {notVotedCount} ({Math.round((notVotedCount / totalParticipants) * 100)}%)
                                </span>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
};
